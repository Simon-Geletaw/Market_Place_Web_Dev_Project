<?php

declare(strict_types=1);

// ------------------------------------------------------------------
// CORS — Allow the frontend origin to use session cookies
// ------------------------------------------------------------------
$allowedOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true) || $origin === '') {
    // Same-host requests or explicit allowed origins
    header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}

header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ------------------------------------------------------------------
// Error logging
// ------------------------------------------------------------------
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

// ------------------------------------------------------------------
// Session configuration (must be before any output)
// ------------------------------------------------------------------
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// ini_set('session.cookie_secure', '1'); // Enable on HTTPS

// ------------------------------------------------------------------
// Autoload helpers (must be first — everything else depends on these)
// ------------------------------------------------------------------
require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/request.php';
require_once __DIR__ . '/../app/helpers/router.php';

// ------------------------------------------------------------------
// Middleware
// ------------------------------------------------------------------
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/middleware/role.php';
require_once __DIR__ . '/../app/middleware/validation.php';

// ------------------------------------------------------------------
// Validators
// ------------------------------------------------------------------
require_once __DIR__ . '/../app/validators/auth_validator.php';
require_once __DIR__ . '/../app/validators/request_validator.php';
require_once __DIR__ . '/../app/validators/offer_validator.php';
require_once __DIR__ . '/../app/validators/review_validator.php';

// ------------------------------------------------------------------
// Repositories (load before controllers — controllers require them)
// ------------------------------------------------------------------
require_once __DIR__ . '/../app/repositories/DatabaseConnector.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';
require_once __DIR__ . '/../app/repositories/CategoryRepository.php';
require_once __DIR__ . '/../app/repositories/RequestRepository.php';
require_once __DIR__ . '/../app/repositories/OfferRepository.php';
require_once __DIR__ . '/../app/repositories/ReviewRepository.php';
require_once __DIR__ . '/../app/repositories/AuditLogRepository.php';
require_once __DIR__ . '/../app/repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../app/repositories/NotificationRepository.php';
require_once __DIR__ . '/../app/repositories/PasswordResetRepository.php';
require_once __DIR__ . '/../app/repositories/MetricsRepository.php';

// ------------------------------------------------------------------
// Services
// ------------------------------------------------------------------
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/RequestService.php';
require_once __DIR__ . '/../app/services/OfferService.php';
require_once __DIR__ . '/../app/services/ReviewService.php';
require_once __DIR__ . '/../app/services/AdminService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

// ------------------------------------------------------------------
// Controllers
// ------------------------------------------------------------------
require_once __DIR__ . '/../app/controllers/StatusController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/RequestController.php';
require_once __DIR__ . '/../app/controllers/OfferController.php';
require_once __DIR__ . '/../app/controllers/ReviewController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/NotificationController.php';
require_once __DIR__ . '/../app/controllers/PublicStatsController.php';

// ------------------------------------------------------------------
// Route registry
// ------------------------------------------------------------------
$routes = require __DIR__ . '/../app/routes/api.php';

// ------------------------------------------------------------------
// Dispatch
// ------------------------------------------------------------------
try {
    $method = request_method();
    $path   = request_path();

    $route = match_route($method, $path, $routes);

    if ($route === null) {
        send_json(error_response('Route not found: ' . $method . ' ' . $path, [], 404));
        exit;
    }

    // Store route params globally so controllers can read them.
    set_route_params($route['params'] ?? []);

    // Run middleware stack in order.
    foreach ($route['middleware'] as $middleware) {
        if ($middleware === 'auth') {
            require_authentication();
            continue;
        }
        if (str_starts_with($middleware, 'role:')) {
            require_role(substr($middleware, 5));
            continue;
        }
        if (str_starts_with($middleware, 'validate:')) {
            $validator = substr($middleware, 9);
            $errors = run_named_validator($validator, request_json_body(), route_params());
            if ($errors !== []) {
                send_json(validation_error_response($errors));
                exit;
            }
        }
    }

    // Instantiate controller (no constructor args — each controller creates its own deps).
    $controller = new $route['controller']();
    $action     = $route['action'];
    $result     = $controller->$action();

    if (is_array($result)) {
        send_json($result);
    }

} catch (InvalidArgumentException $e) {
    send_json(error_response($e->getMessage(), [], 400));
} catch (Throwable $e) {
    error_log('[500] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    send_json(error_response('Internal server error.', [], 500));
}
