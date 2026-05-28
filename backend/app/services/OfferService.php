<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/NotificationRepository.php';

/**
 * OfferService
 *
 * Business rules for offer submission, negotiation, and acceptance.
 * Enforces the state machine transitions triggered by offer lifecycle events.
 */
final class OfferService
{
    private OfferRepository        $offers;
    private RequestRepository      $requests;
    private StatusHistoryRepository $statusHistory;
    private AuditLogRepository     $audit;
    private ?NotificationRepository $notifications;

    public function __construct(
        OfferRepository        $offers,
        RequestRepository      $requests,
        StatusHistoryRepository $statusHistory,
        AuditLogRepository     $audit,
        ?NotificationRepository $notifications = null
    ) {
        $this->offers        = $offers;
        $this->requests      = $requests;
        $this->statusHistory = $statusHistory;
        $this->audit         = $audit;
        $this->notifications = $notifications;
    }

    // ------------------------------------------------------------------
    // Provider: Submit an offer
    // ------------------------------------------------------------------

    public function submitOffer(string $providerId, array $data): array
    {
        $requestId = $data['request_id'] ?? '';
        $request   = $this->requests->findById($requestId);

        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        if (!in_array($request['STATUS'], ['Requested', 'Negotiating'], true)) {
            return ['success' => false, 'message' => 'This request is no longer open for offers.', 'http_code' => 422];
        }

        if ($this->offers->existsByProviderAndRequest($providerId, $requestId)) {
            return ['success' => false, 'message' => 'You have already submitted an offer for this request.', 'http_code' => 409];
        }

        $price   = (float) ($data['price'] ?? 0);
        $message = trim((string) ($data['message'] ?? ''));

        if ($price <= 0) {
            return ['success' => false, 'message' => 'Offer price must be greater than zero.', 'http_code' => 422];
        }

        try {
            $offerId = $this->offers->transaction(function () use ($requestId, $providerId, $price, $message, $request): string {
                $offerId = $this->offers->create($requestId, $providerId, $price, $message);

                if ($request['STATUS'] === 'Requested') {
                    $this->requests->updateStatus($requestId, 'Negotiating');
                    $this->statusHistory->record($requestId, 'Requested', 'Negotiating', $providerId, 'First offer received');
                }

                $this->audit->log($providerId, 'offer_submitted', 'offer', $offerId, "Request: $requestId, Price: $price");

                return $offerId;
            });

            return ['success' => true, 'offer_id' => $offerId];
        } catch (\Throwable $e) {
            if ($this->isDuplicateConstraint($e)) {
                return ['success' => false, 'message' => 'You have already submitted an offer for this request.', 'http_code' => 409];
            }

            error_log('[OfferService] submitOffer failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit offer. Please try again.', 'http_code' => 500];
        }
    }

    // ------------------------------------------------------------------
    // Customer: Accept an offer
    // ------------------------------------------------------------------

    public function acceptOffer(string $offerId, string $customerId, array $data = []): array
    {
        $offer = $this->offers->findById($offerId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Offer not found.', 'http_code' => 404];
        }

        $request = $this->requests->findById($offer['REQUEST_ID']);
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        // Ownership: only the customer who owns the request can accept.
        if ($request['CUSTOMER_ID'] !== $customerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        if ($request['STATUS'] !== 'Negotiating') {
            return ['success' => false, 'message' => 'Offer can only be accepted while request is Negotiating.', 'http_code' => 422];
        }

        if ($offer['STATUS'] !== 'Pending' && $offer['STATUS'] !== 'Countered') {
            return ['success' => false, 'message' => 'This offer is no longer available to accept.', 'http_code' => 422];
        }

        $location = trim((string) ($data['location'] ?? ''));
        $preferredDate = trim((string) ($data['preferred_date'] ?? $data['date'] ?? ''));

        if ($location === '' || !$this->isValidDate($preferredDate)) {
            return ['success' => false, 'message' => 'Location and valid date are required.', 'http_code' => 422];
        }

        try {
            $this->offers->transaction(function () use ($offer, $offerId, $customerId, $location, $preferredDate): void {
                $freshRequest = $this->requests->findById($offer['REQUEST_ID']);
                if (!$freshRequest || $freshRequest['STATUS'] !== 'Negotiating') {
                    throw new RuntimeException('Request is no longer negotiable.');
                }

                $this->offers->updateStatus($offerId, 'Accepted');
                $this->requests->assignOfferWithSchedule($offer['REQUEST_ID'], $offerId, $location, $preferredDate);
                $this->offers->rejectOthers($offer['REQUEST_ID'], $offerId);

                $this->statusHistory->record($offer['REQUEST_ID'], 'Negotiating', 'Assigned', $customerId, "Offer $offerId accepted");
                $this->audit->log($customerId, 'offer_accepted', 'offer', $offerId, "Request: {$offer['REQUEST_ID']}");

                $this->notify(
                    $offer['PROVIDER_ID'],
                    "Your offer was accepted. Scheduled for $preferredDate at $location.",
                    'offer_accepted',
                    "../request-detail-provider.html?id={$offer['REQUEST_ID']}",
                    'request',
                    $offer['REQUEST_ID']
                );
            });

            return ['success' => true, 'message' => 'Offer accepted. Request is now Assigned.'];
        } catch (\Throwable $e) {
            error_log('[OfferService] acceptOffer failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to accept offer.', 'http_code' => 500];
        }
    }

    // ------------------------------------------------------------------
    // Customer: Reject an offer
    // ------------------------------------------------------------------

    public function rejectOffer(string $offerId, string $customerId): array
    {
        $offer = $this->offers->findById($offerId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Offer not found.', 'http_code' => 404];
        }

        $request = $this->requests->findById($offer['REQUEST_ID']);
        if (!$request || $request['CUSTOMER_ID'] !== $customerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        if (!in_array($offer['STATUS'], ['Pending', 'Countered'], true)) {
            return ['success' => false, 'message' => 'This offer cannot be rejected.', 'http_code' => 422];
        }

        $this->offers->updateStatus($offerId, 'Rejected');
        $this->audit->log($customerId, 'offer_rejected', 'offer', $offerId);

        $this->notify(
            $offer['PROVIDER_ID'],
            'Your offer was declined.',
            'offer_rejected',
            "../request-detail-provider.html?id={$offer['REQUEST_ID']}",
            'request',
            $offer['REQUEST_ID']
        );

        return ['success' => true, 'message' => 'Offer rejected.'];
    }

    // ------------------------------------------------------------------
    // Customer: Counter an offer
    // ------------------------------------------------------------------

    public function counterOffer(string $offerId, string $customerId, array $data): array
    {
        $offer = $this->offers->findById($offerId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Offer not found.', 'http_code' => 404];
        }

        $request = $this->requests->findById($offer['REQUEST_ID']);
        if (!$request || $request['CUSTOMER_ID'] !== $customerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        if ($offer['STATUS'] !== 'Pending') {
            return ['success' => false, 'message' => 'Only Pending offers can be countered.', 'http_code' => 422];
        }

        $counterPrice = (float) ($data['counter_price'] ?? 0);
        if ($counterPrice <= 0) {
            return ['success' => false, 'message' => 'Counter price must be greater than zero.', 'http_code' => 422];
        }

        $this->offers->storeCounter($offerId, $counterPrice, $data['counter_message'] ?? '');
        $this->audit->log($customerId, 'offer_countered', 'offer', $offerId, "Counter: $counterPrice");

        $this->notify(
            $offer['PROVIDER_ID'],
            'You received a counter-offer of ETB ' . number_format($counterPrice, 2) . '.',
            'offer_countered',
            "../request-detail-provider.html?id={$offer['REQUEST_ID']}",
            'request',
            $offer['REQUEST_ID']
        );

        return ['success' => true, 'message' => 'Counter-offer sent.'];
    }

    // ------------------------------------------------------------------
    // Provider: Accept a customer's counter-offer
    // ------------------------------------------------------------------

    public function providerAcceptCounter(string $offerId, string $providerId): array
    {
        $offer = $this->offers->findById($offerId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Offer not found.', 'http_code' => 404];
        }

        if ($offer['PROVIDER_ID'] !== $providerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        if ($offer['STATUS'] !== 'Countered') {
            return ['success' => false, 'message' => 'This offer has no counter to accept.', 'http_code' => 422];
        }

        $counterPrice = (float) ($offer['COUNTER_PRICE'] ?? 0);
        if ($counterPrice <= 0) {
            return ['success' => false, 'message' => 'Invalid counter price.', 'http_code' => 422];
        }

        $this->offers->acceptCounter($offerId);
        $this->audit->log($providerId, 'counter_accepted', 'offer', $offerId, "Accepted counter: $counterPrice");

        // Notify the customer that the provider agreed to their price
        $request = $this->requests->findById($offer['REQUEST_ID']);
        if ($request) {
            $this->notify(
                $request['CUSTOMER_ID'],
                'The provider accepted your counter-offer of ETB ' . number_format($counterPrice, 2) . '. You can now accept to finalize.',
                'counter_accepted',
                "../request-detail.html?id={$offer['REQUEST_ID']}",
                'request',
                $offer['REQUEST_ID']
            );
        }

        return ['success' => true, 'message' => 'Counter-offer accepted. The customer can now finalize.'];
    }

    // ------------------------------------------------------------------
    // Provider: Revise offer price (respond to counter with a new price)
    // ------------------------------------------------------------------

    public function providerReviseOffer(string $offerId, string $providerId, array $data): array
    {
        $offer = $this->offers->findById($offerId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Offer not found.', 'http_code' => 404];
        }

        if ($offer['PROVIDER_ID'] !== $providerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        if ($offer['STATUS'] !== 'Countered') {
            return ['success' => false, 'message' => 'You can only revise after a counter-offer.', 'http_code' => 422];
        }

        $newPrice = (float) ($data['price'] ?? 0);
        if ($newPrice <= 0) {
            return ['success' => false, 'message' => 'Price must be greater than zero.', 'http_code' => 422];
        }

        $this->offers->revisePrice($offerId, $newPrice);
        $this->audit->log($providerId, 'offer_revised', 'offer', $offerId, "New price: $newPrice");

        $request = $this->requests->findById($offer['REQUEST_ID']);
        if ($request) {
            $this->notify(
                $request['CUSTOMER_ID'],
                'The provider proposed a new price of ETB ' . number_format($newPrice, 2) . '.',
                'offer_revised',
                "../request-detail.html?id={$offer['REQUEST_ID']}",
                'request',
                $offer['REQUEST_ID']
            );
        }

        return ['success' => true, 'message' => 'Offer revised with new price.'];
    }

    // ------------------------------------------------------------------
    // List offers
    // ------------------------------------------------------------------

    public function getOffersForRequest(string $requestId, string $customerId): array
    {
        $request = $this->requests->findById($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        if ($request['CUSTOMER_ID'] !== $customerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        $rows = $this->offers->findByRequest($requestId);
        return ['success' => true, 'data' => array_map([$this, 'normalizeOffer'], $rows)];
    }

    public function getMyOffers(string $providerId, string $status = ''): array
    {
        $rows = $this->offers->findByProvider($providerId, $status);
        return array_map([$this, 'normalizeOffer'], $rows);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function normalizeOffer(array $row): array
    {
        return [
            'id'               => $row['OFFER_ID'],
            'request_id'       => $row['REQUEST_ID'],
            'provider_id'      => $row['PROVIDER_ID'],
            'provider_name'    => $row['PROVIDER_NAME']    ?? null,
            'rating_average'   => (float) ($row['RATING_AVERAGE'] ?? 0.0),
            'provider_rating'  => (float) ($row['RATING_AVERAGE'] ?? 0.0),
            'is_verified'      => (bool) ($row['IS_VERIFIED']     ?? false),
            'price'            => (float) $row['PRICE'],
            'message'          => $row['MESSAGE']          ?? '',
            'status'           => $row['STATUS'],
            'counter_price'    => isset($row['COUNTER_PRICE'])   ? (float) $row['COUNTER_PRICE']   : null,
            'counter_message'  => $row['COUNTER_MESSAGE']  ?? null,
            'request_description' => $row['REQUEST_DESCRIPTION'] ?? null,
            'request_location'    => $row['REQUEST_LOCATION']    ?? null,
            'request_status'      => $row['REQUEST_STATUS']      ?? null,
            'category_name'       => $row['CATEGORY_NAME']       ?? null,
            'customer_name'       => $row['CUSTOMER_NAME']       ?? null,
            'provider_completed_jobs' => isset($row['COMPLETED_JOBS']) ? (int) $row['COMPLETED_JOBS'] : 0,
            'created_at'       => $row['CREATED_AT'],
        ];
    }

    private function notify(
        string $userId,
        string $message,
        string $type,
        ?string $link,
        ?string $entityType,
        ?string $entityId
    ): void {
        if ($this->notifications === null || $userId === '') {
            return;
        }

        try {
            $this->notifications->create($userId, $message, $type, $link, $entityType, $entityId);
        } catch (\Throwable $exception) {
            error_log('[OfferService] Notification failed: ' . $exception->getMessage());
        }
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function isDuplicateConstraint(\Throwable $exception): bool
    {
        if (!$exception instanceof \PDOException) {
            return false;
        }

        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        return $sqlState === '23000';
    }
}
