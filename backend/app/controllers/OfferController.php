<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/OfferService.php';
require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/DatabaseConnector.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../validators/offer_validator.php';

/**
 * OfferController
 *
 * Handles offer HTTP endpoints for providers (submit, list) and customers (accept, reject, counter).
 */
final class OfferController
{
    private OfferService $offerService;

    public function __construct()
    {
        $db = (new DatabaseConnector())->getConnection();
        $this->offerService = new OfferService(
            new OfferRepository($db),
            new RequestRepository($db),
            new StatusHistoryRepository($db),
            new AuditLogRepository($db)
        );
    }

    private function currentUserId(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user_id'] ?? '';
    }

    // POST /api/requests/{id}/offers  (provider submits offer)
    public function submit(): array
    {
        $requestId  = route_param('id', '');
        $providerId = $this->currentUserId();
        $input      = request_json_body();

        // Inject the request_id from the route into input so the service can find it.
        $input['request_id'] = $requestId;

        $errors = validate_offer_submit($input);
        if (!empty($errors)) {
            return error_response('Validation failed.', $errors, 422);
        }

        $result = $this->offerService->submitOffer($providerId, $input);
        $code   = $result['http_code'] ?? ($result['success'] ? 201 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response('Offer submitted.', ['offer_id' => $result['offer_id']], 201);
    }

    // GET /api/requests/{id}/offers  (customer views offers on their request)
    public function listForRequest(): array
    {
        $requestId  = route_param('id', '');
        $customerId = $this->currentUserId();

        $offers = $this->offerService->getOffersForRequest($requestId, $customerId);
        return success_response('Offers retrieved.', $offers);
    }

    // GET /api/offers  (provider views their own offers)
    public function myOffers(): array
    {
        $providerId = $this->currentUserId();
        $status     = $_GET['status'] ?? '';

        $offers = $this->offerService->getMyOffers($providerId, $status);
        return success_response('Your offers retrieved.', $offers);
    }

    // PATCH /api/offers/{id}/accept  (customer)
    public function accept(): array
    {
        $offerId    = route_param('id', '');
        $customerId = $this->currentUserId();

        $result = $this->offerService->acceptOffer($offerId, $customerId);
        $code   = $result['http_code'] ?? ($result['success'] ? 200 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response($result['message']);
    }

    // PATCH /api/offers/{id}/reject  (customer)
    public function reject(): array
    {
        $offerId    = route_param('id', '');
        $customerId = $this->currentUserId();

        $result = $this->offerService->rejectOffer($offerId, $customerId);
        $code   = $result['http_code'] ?? ($result['success'] ? 200 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response($result['message']);
    }

    // PATCH /api/offers/{id}/counter  (customer)
    public function counter(): array
    {
        $offerId    = route_param('id', '');
        $customerId = $this->currentUserId();
        $input      = request_json_body();

        $errors = validate_offer_counter($input);
        if (!empty($errors)) {
            return error_response('Validation failed.', $errors, 422);
        }

        $result = $this->offerService->counterOffer($offerId, $customerId, $input);
        $code   = $result['http_code'] ?? ($result['success'] ? 200 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response($result['message']);
    }
}
