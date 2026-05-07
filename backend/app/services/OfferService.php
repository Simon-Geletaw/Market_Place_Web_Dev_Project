<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';

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

    public function __construct(
        OfferRepository        $offers,
        RequestRepository      $requests,
        StatusHistoryRepository $statusHistory,
        AuditLogRepository     $audit
    ) {
        $this->offers        = $offers;
        $this->requests      = $requests;
        $this->statusHistory = $statusHistory;
        $this->audit         = $audit;
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
            $offerId = $this->offers->create($requestId, $providerId, $price, $message);

            // Move request to Negotiating if it is still in Requested state.
            if ($request['STATUS'] === 'Requested') {
                $this->requests->updateStatus($requestId, 'Negotiating');
                $this->statusHistory->record($requestId, 'Requested', 'Negotiating', $providerId, 'First offer received');
            }

            $this->audit->log($providerId, 'offer_submitted', 'offer', $offerId, "Request: $requestId, Price: $price");

            return ['success' => true, 'offer_id' => $offerId];
        } catch (\Throwable $e) {
            error_log('[OfferService] submitOffer failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit offer. Please try again.', 'http_code' => 500];
        }
    }

    // ------------------------------------------------------------------
    // Customer: Accept an offer
    // ------------------------------------------------------------------

    public function acceptOffer(string $offerId, string $customerId): array
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

        try {
            // Atomically: accept this offer, assign the request, reject all others.
            $this->offers->updateStatus($offerId, 'Accepted');
            $this->requests->assignOffer($offer['REQUEST_ID'], $offerId);
            $this->offers->rejectOthers($offer['REQUEST_ID'], $offerId);

            $this->statusHistory->record($offer['REQUEST_ID'], 'Negotiating', 'Assigned', $customerId, "Offer $offerId accepted");
            $this->audit->log($customerId, 'offer_accepted', 'offer', $offerId, "Request: {$offer['REQUEST_ID']}");

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

        return ['success' => true, 'message' => 'Counter-offer sent.'];
    }

    // ------------------------------------------------------------------
    // List offers
    // ------------------------------------------------------------------

    public function getOffersForRequest(string $requestId, string $customerId): array
    {
        $request = $this->requests->findById($requestId);
        if (!$request || $request['CUSTOMER_ID'] !== $customerId) {
            return [];
        }
        $rows = $this->offers->findByRequest($requestId);
        return array_map([$this, 'normalizeOffer'], $rows);
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
            'created_at'       => $row['CREATED_AT'],
        ];
    }
}
