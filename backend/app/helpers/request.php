<?php

declare(strict_types=1);

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDir = rtrim(dirname($scriptName), '/');

    // If SCRIPT_NAME is /public/index.php, baseDir would be /public
    // Try to strip it from the path if present
    if ($baseDir !== '' && $baseDir !== '/' && str_starts_with($path, $baseDir)) {
        $path = substr($path, strlen($baseDir));
    }

    // Also handle case where REQUEST_URI includes the full path structure
    // e.g., /Market_Place_Web_Dev_Project/backend/public/api/auth/register
    // should become /api/auth/register
    $basePatterns = [
        '/backend/public',
        '/public',
    ];

    foreach ($basePatterns as $pattern) {
        if (str_contains($path, $pattern . '/') || str_ends_with($path, $pattern)) {
            $pos = strpos($path, $pattern);
            if ($pos !== false) {
                $path = substr($path, $pos + strlen($pattern));
                break;
            }
        }
    }

    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : rtrim($path, '/');
}

function request_query(): array
{
    return $_GET;
}

function request_json_body(): array
{
    if (array_key_exists('json_body', $GLOBALS)) {
        return $GLOBALS['json_body'];
    }

    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    if ($contentType !== '' && !str_contains($contentType, 'application/json')) {
        $GLOBALS['json_body'] = [];
        return $GLOBALS['json_body'];
    }

    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        $GLOBALS['json_body'] = [];
        return $GLOBALS['json_body'];
    }

    $decoded = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new InvalidArgumentException('Invalid JSON body.');
    }

    $GLOBALS['json_body'] = $decoded;
    return $GLOBALS['json_body'];
}

function request_input(): array
{
    return [
        'query' => request_query(),
        'body' => request_json_body(),
    ];
}

function route_key(): string
{
    return request_method() . ' ' . request_path();
}

function set_route_params(array $params): void
{
    $GLOBALS['route_params'] = $params;
}

function route_params(): array
{
    return $GLOBALS['route_params'] ?? [];
}

function route_param(string $name, mixed $default = null): mixed
{
    $params = route_params();

    return $params[$name] ?? $default;
}

function current_user_id(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return (string) ($_SESSION['user_id'] ?? '');
}

function current_user_role(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return strtolower((string) ($_SESSION['role'] ?? ''));
}
