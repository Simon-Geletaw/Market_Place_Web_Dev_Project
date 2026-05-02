<?php

declare(strict_types=1);

final class ReviewController
{
    public function submit(): array
    {
        return success_response('Review submit action ready.');
    }

    public function providerReviews(): array
    {
        return success_response('Provider reviews route ready.', [
            'params' => route_params(),
        ]);
    }
}
