<?php

declare(strict_types=1);

final class OfferController
{
    public function submit(): array
    {
        return success_response('Offer submit action ready.');
    }

    public function accept(): array
    {
        return success_response('Offer accept action ready.', [
            'params' => route_params(),
        ]);
    }

    public function reject(): array
    {
        return success_response('Offer reject action ready.', [
            'params' => route_params(),
        ]);
    }

    public function counter(): array
    {
        return success_response('Offer counter action ready.', [
            'params' => route_params(),
        ]);
    }
}
