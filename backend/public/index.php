<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/helpers/request.php';
require_once __DIR__ . '/../app/middleware/validation.php';
require_once __DIR__ . '/../app/controllers/StatusController.php';
require_once __DIR__ . '/../app/controllers/PayloadController.php';

$routes = require __DIR__ . '/../app/routes/api.php'; //load this files exactly one's

try {
    $key = route_key(); //form the api request in detail like like get api/health

    if (!array_key_exists($key, $routes)) {
        send_json(error_response('Not Found', ['route' => $key], 404));
        exit;
    }

    [$className, $methodName] = $routes[$key];
    $controller = new $className();

    send_json($controller->$methodName());
    exit;
} catch (InvalidArgumentException $exception) {
    send_json(error_response($exception->getMessage(), [], 400));
    exit;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    send_json(error_response('Internal Server Error', [], 500));
    exit;
}
