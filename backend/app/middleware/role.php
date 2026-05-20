<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

function require_role(string $role): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== strtolower($role)) {
        http_response_code(403);
        send_json(error_response('Forbidden: Insufficient permissions', [], 403));
        exit;
    }
}
