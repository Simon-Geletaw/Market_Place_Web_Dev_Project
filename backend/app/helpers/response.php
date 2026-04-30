<?php

declare(strict_types=1);

function json_response(bool $success, string $message, array $data = [], int $statusCode = 200): array
{
    return [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'status_code' => $statusCode,
    ];
}

function send_json(array $response): void
{
    $statusCode = (int) ($response['status_code'] ?? 200);

    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

function success_response(string $message, array $data = [], int $statusCode = 200): array
{
    return json_response(true, $message, $data, $statusCode);
}

function error_response(string $message, array $data = [], int $statusCode = 400): array
{
    return json_response(false, $message, $data, $statusCode);
}
