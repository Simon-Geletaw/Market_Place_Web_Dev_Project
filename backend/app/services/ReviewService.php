<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/ReviewRepository.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';

/**
 * ReviewService
 *
 * Business rules for submitting reviews after job completion.
 * Enforces: only completed requests can be reviewed, only once, only by the customer.
 */
final class ReviewService
{
    private ReviewRepository  $reviews;
    private RequestRepository $requests;
    private OfferRepository   $offers;
    private UserRepository    $users;
    private AuditLogRepository $audit;
    private ?StatusHistoryRepository $statusHistory;

    public function __construct(
        ReviewRepository  $reviews,
        RequestRepository $requests,
        OfferRepository   $offers,
        UserRepository    $users,
        AuditLogRepository $audit,
        ?StatusHistoryRepository $statusHistory = null
    ) {
        $this->reviews  = $reviews;
        $this->requests = $requests;
        $this->offers   = $offers;
        $this->users    = $users;
        $this->audit    = $audit;
        $this->statusHistory = $statusHistory;
    }

    /**
     * Submit a review for a completed request.
     *
     * @return array{success: bool, review_id?: string, message?: string, http_code?: int}
     */
    public function submitReview(string $customerId, array $data): array
    {
        $requestId = $data['request_id'] ?? '';
        $rating    = (int) ($data['rating'] ?? 0);
        $comment   = trim((string) ($data['comment'] ?? ''));

        // Load the request and validate state.
        $request = $this->requests->findById($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        // Only the owning customer may review.
        if ($request['CUSTOMER_ID'] !== $customerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        // Only Completed requests can be reviewed.
        if ($request['STATUS'] !== 'Completed') {
            return ['success' => false, 'message' => 'You can only review a completed job.', 'http_code' => 422];
        }

        // Prevent duplicate reviews (unique constraint on REQUEST_ID in REVIEWS).
        if ($this->reviews->existsForRequest($requestId)) {
            return ['success' => false, 'message' => 'You have already reviewed this job.', 'http_code' => 409];
        }

        // Validate rating range.
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5.', 'http_code' => 422];
        }

        // Resolve the assigned provider from the accepted offer.
        $acceptedOfferId = $request['ACCEPTED_OFFER_ID'] ?? null;
        if (!$acceptedOfferId) {
            return ['success' => false, 'message' => 'No accepted offer found for this request.', 'http_code' => 422];
        }

        $offer = $this->offers->findById($acceptedOfferId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Offer record not found.', 'http_code' => 500];
        }

        $providerId = $offer['PROVIDER_ID'];

        try {
            $reviewId = $this->reviews->transaction(function () use ($requestId, $customerId, $providerId, $rating, $comment): string {
                $reviewId = $this->reviews->create($requestId, $customerId, $providerId, $rating, $comment);
                $this->requests->markReviewed($requestId);

                if ($this->statusHistory !== null) {
                    $this->statusHistory->record($requestId, 'Completed', 'Reviewed', $customerId, 'Customer submitted review');
                }

                $this->users->refreshProviderRating($providerId);
                $this->audit->log($customerId, 'review_submitted', 'review', $reviewId, "Rating: $rating, Provider: $providerId");

                return $reviewId;
            });

            return ['success' => true, 'review_id' => $reviewId];
        } catch (\Throwable $e) {
            error_log('[ReviewService] submitReview failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit review.', 'http_code' => 500];
        }
    }

    /**
     * Get all reviews for a provider (public-facing).
     */
    public function getProviderReviews(string $providerId): array
    {
        $rows = $this->reviews->findByProvider($providerId);
        return array_map([$this, 'normalizeReview'], $rows);
    }

    /**
     * Get all reviews a customer has left.
     */
    public function getCustomerReviews(string $customerId): array
    {
        $rows = $this->reviews->findByCustomer($customerId);
        return array_map([$this, 'normalizeReview'], $rows);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function normalizeReview(array $row): array
    {
        return [
            'id'                  => $row['REVIEW_ID'],
            'request_id'          => $row['REQUEST_ID'],
            'customer_id'         => $row['CUSTOMER_ID'],
            'provider_id'         => $row['PROVIDER_ID'],
            'rating'              => (int) $row['RATING'],
            'comment'             => $row['COMMENT']              ?? '',
            'customer_name'       => $row['CUSTOMER_NAME']        ?? null,
            'provider_name'       => $row['PROVIDER_NAME']        ?? null,
            'request_description' => $row['REQUEST_DESCRIPTION']  ?? null,
            'request_title'       => $row['REQUEST_TITLE'] ?? $row['REQUEST_DESCRIPTION'] ?? null,
            'category_name'       => $row['CATEGORY_NAME']        ?? null,
            'created_at'          => $row['CREATED_AT'],
        ];
    }
}
