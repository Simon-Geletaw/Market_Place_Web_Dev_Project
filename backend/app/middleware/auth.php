<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

function require_authentication(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        send_json(error_response('Unauthorized: Authentication required', [], 401));
        exit;
    }
}
