<?php

declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/request.php';
require_once __DIR__ . '/../app/helpers/router.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/middleware/role.php';
require_once __DIR__ . '/../app/middleware/validation.php';
require_once __DIR__ . '/../app/validators/auth_validator.php';
require_once __DIR__ . '/../app/controllers/StatusController.php';
require_once __DIR__ . '/../app/controllers/PayloadController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/RequestController.php';
require_once __DIR__ . '/../app/controllers/OfferController.php';
require_once __DIR__ . '/../app/controllers/ReviewController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/repositories/DatabaseConnector.php';

$routes = require __DIR__ . '/../app/routes/api.php';

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

try {
    $method = request_method();
    $path = request_path();
    $key = $method . ' ' . $path;
    error_log("Requested route key: $key");

    $route = match_route($method, $path, $routes);

    if ($route === null) {
        send_json(error_response('Not Found', ['route' => $key], 404));
        exit;
    }

    set_route_params($route['params'] ?? []);

    foreach ($route['middleware'] ?? [] as $middleware) {
        if ($middleware === 'auth') {
            require_authentication();
            continue;
        }

        if (str_starts_with($middleware, 'role:')) {
            require_role(substr($middleware, 5));
        }
    }

    $className = $route['controller'];
    $methodName = $route['action'];

    $reflection = new ReflectionClass($className);
    $constructor = $reflection->getConstructor();

    if ($constructor && $constructor->getNumberOfParameters() > 0) {
        $db = (new DatabaseConnector())->getConnection();
        $controller = $reflection->newInstance($db);
    } else {
        $controller = $reflection->newInstance();
    }

    $result = $controller->$methodName();
    if (is_array($result)) {
        send_json($result);
    }

    exit;
} catch (InvalidArgumentException $exception) {
    send_json(error_response($exception->getMessage(), [], 400));
    exit;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    send_json(error_response('Internal Server Error', [], 500));
    exit;
}