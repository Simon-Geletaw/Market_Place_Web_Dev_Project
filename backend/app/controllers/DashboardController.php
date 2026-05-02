<?php

declare(strict_types=1);

final class DashboardController
{
    public function customer(): array
    {
        return success_response('Customer dashboard route ready.');
    }

    public function provider(): array
    {
        return success_response('Provider dashboard route ready.');
    }
}
