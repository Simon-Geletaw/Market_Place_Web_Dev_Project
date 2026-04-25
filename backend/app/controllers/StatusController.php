<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

final class StatusController
{
    public function ok(): array
    {
        return json_response(true, 'OK');
    }
}
