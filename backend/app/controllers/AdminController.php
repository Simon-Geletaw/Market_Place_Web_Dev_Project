<?php

declare(strict_types=1);

final class AdminController
{
    public function dashboard(): array
    {
        return success_response('Admin dashboard route ready.');
    }

    public function categories(): array
    {
        return success_response('Admin categories route ready.');
    }

    public function createCategory(): array
    {
        return success_response('Admin category create action ready.');
    }

    public function updateCategory(): array
    {
        return success_response('Admin category update action ready.', [
            'params' => route_params(),
        ]);
    }

    public function auditLogs(): array
    {
        return success_response('Admin audit logs route ready.');
    }

    public function verifyProvider(): array
    {
        return success_response('Admin provider verification action ready.', [
            'params' => route_params(),
        ]);
    }
}
