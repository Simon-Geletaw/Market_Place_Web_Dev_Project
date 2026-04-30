<?php

declare(strict_types=1);

return [
    'GET /ok' => [StatusController::class, 'ok'],
    'GET /api/health' => [StatusController::class, 'ok'],
    'POST /api/validate-demo' => [PayloadController::class, 'validateDemo'],
];
