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
