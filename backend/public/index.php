<?php

declare(strict_types=1);

// Basic Front Controller
require_once __DIR__ . '/../app/helpers/response.php';
require_once __DIR__ . '/../app/controllers/StatusController.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/ok', PHP_URL_PATH);
$uri = '/' . ltrim($uri, '/');
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Simple Router
if ($uri === '/ok') {
    $controller = new StatusController();
    $response = $controller->ok();

    if (PHP_SAPI === 'cli') {
        echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
        exit;
    }

    header('Content-Type: application/json');
    http_response_code($response['status_code'] ?? 200);
    echo json_encode($response);
    exit;
}

// Fallback for other routes
header('Content-Type: application/json');
http_response_code(404);
echo json_encode(json_response(false, 'Not Found', [], 404));
