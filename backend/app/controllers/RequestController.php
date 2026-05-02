<?php

declare(strict_types=1);

final class RequestController
{
    public function browse(): array
    {
        return success_response('Request browse route ready.');
    }

    public function create(): array
    {
        return success_response('Request create action ready.');
    }

    public function show(): array
    {
        return success_response('Request detail route ready.', [
            'params' => route_params(),
        ]);
    }

    public function markCompleted(): array
    {
        return success_response('Request completion action ready.', [
            'params' => route_params(),
        ]);
    }
}
