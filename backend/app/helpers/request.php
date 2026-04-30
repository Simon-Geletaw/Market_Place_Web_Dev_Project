<?php

declare(strict_types=1);

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : rtrim($path, '/');
}

function request_query(): array
{
    return $_GET;
}

function request_json_body(): array
{
    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new InvalidArgumentException('Invalid JSON body.');
    }

    return $decoded;
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
