<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/ReviewService.php';
require_once __DIR__ . '/../repositories/ReviewRepository.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/DatabaseConnector.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../validators/review_validator.php';

/**
 * ReviewController
 *
 * Handles review submission (customer) and review browsing (provider/public).
 */
final class ReviewController
{
    private ReviewService $reviewService;

    public function __construct()
    {
        $db = (new DatabaseConnector())->getConnection();
        $this->reviewService = new ReviewService(
            new ReviewRepository($db),
            new RequestRepository($db),
            new OfferRepository($db),
            new UserRepository($db),
            new AuditLogRepository($db)
        );
    }

    private function currentUserId(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user_id'] ?? '';
    }

    // POST /api/requests/{id}/review  (customer)
    public function submitForRequest(): array
    {
        $requestId  = route_param('id', '');
        $customerId = $this->currentUserId();
        $input      = request_json_body();

        // Inject request_id from route into the payload.
        $input['request_id'] = $requestId;

        $errors = validate_review_submit($input);
        if (!empty($errors)) {
            return error_response('Validation failed.', $errors, 422);
        }

        $result = $this->reviewService->submitReview($customerId, $input);
        $code   = $result['http_code'] ?? ($result['success'] ? 201 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response('Review submitted successfully.', ['review_id' => $result['review_id']], 201);
    }

    // GET /api/reviews/provider/{id}  (public)
    public function providerReviews(): array
    {
        $providerId = route_param('id', '');
        $reviews    = $this->reviewService->getProviderReviews($providerId);
        return success_response('Provider reviews retrieved.', $reviews);
    }

    // GET /api/reviews/my  (customer: reviews I have given)
    public function myReviews(): array
    {
        $customerId = $this->currentUserId();
        $reviews    = $this->reviewService->getCustomerReviews($customerId);
        return success_response('Your reviews retrieved.', $reviews);
    }
}
