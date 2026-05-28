<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/OfferRepository.php';

final class RequestService
{
    private RequestRepository $requests;
    private CategoryRepository $categories;
    private StatusHistoryRepository $statusHistory;
    private AuditLogRepository $audit;
    private ?OfferRepository $offers;

    public function __construct(
        RequestRepository $requests,
        CategoryRepository $categories,
        StatusHistoryRepository $statusHistory,
        AuditLogRepository $audit,
        ?OfferRepository $offers = null
    ) {
        $this->requests = $requests;
        $this->categories = $categories;
        $this->statusHistory = $statusHistory;
        $this->audit = $audit;
        $this->offers = $offers;
    }

    public function createRequest(string $customerId, array $data): array
    {
        $category = null;

        if (!empty($data['category_id'])) {
            $category = $this->categories->findById((string) $data['category_id']);
        }

        if (!$category && !empty($data['category'])) {
            $category = $this->categories->findByName((string) $data['category']);
        }

        if (!$category) {
            return ['success' => false, 'message' => 'Invalid category selected.', 'http_code' => 422];
        }

        try {
            $requestId = $this->requests->transaction(function () use ($customerId, $category, $data): string {
                $requestId = $this->requests->create($customerId, $category['CATEGORY_ID'], $data);
                $this->statusHistory->record($requestId, null, 'Requested', $customerId, 'Request created');
                $this->audit->log($customerId, 'request_created', 'service_request', $requestId);

                return $requestId;
            });

            return ['success' => true, 'request_id' => $requestId];
        } catch (Throwable $exception) {
            error_log('[RequestService] createRequest failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Could not create request. Please try again.', 'http_code' => 500];
        }
    }

    public function getMyRequests(string $customerId, string $status = '', string $sort = 'desc'): array
    {
        return array_map([$this, 'normalizeRequest'], $this->requests->findByCustomer($customerId, $status, $sort));
    }

    public function getRequestDetail(string $requestId, string $viewerId, string $viewerRole): array
    {
        $row = $this->requests->findById($requestId);

        if (!$row) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        $role = strtolower($viewerRole);

        if ($role === 'customer' && $row['CUSTOMER_ID'] !== $viewerId) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        if ($role === 'provider'
            && !in_array($row['STATUS'], ['Requested', 'Negotiating'], true)
            && !$this->isAssignedProvider($row, $viewerId)
        ) {
            return ['success' => false, 'message' => 'Access denied.', 'http_code' => 403];
        }

        return ['success' => true, 'data' => $this->normalizeRequest($row)];
    }

    public function markCompleted(string $requestId, string $providerId, ?string $photoPath): array
    {
        $row = $this->requests->findById($requestId);

        if (!$row) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        if ($row['STATUS'] !== 'Assigned') {
            return ['success' => false, 'message' => 'Only Assigned requests can be marked complete.', 'http_code' => 422];
        }

        if (!$this->isAssignedProvider($row, $providerId)) {
            return ['success' => false, 'message' => 'You are not the assigned provider for this request.', 'http_code' => 403];
        }

        try {
            $this->requests->transaction(function () use ($requestId, $providerId, $photoPath): void {
                $this->requests->markCompleted($requestId, $photoPath);
                $this->statusHistory->record($requestId, 'Assigned', 'Completed', $providerId, 'Provider marked job complete');
                $this->audit->log($providerId, 'request_completed', 'service_request', $requestId);
            });

            return ['success' => true, 'message' => 'Request marked as completed.'];
        } catch (Throwable $exception) {
            error_log('[RequestService] markCompleted failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Could not complete request. Please try again.', 'http_code' => 500];
        }
    }

    public function browseOpenRequests(array $filters = []): array
    {
        return array_map([$this, 'normalizeRequest'], $this->requests->findOpenRequests($filters));
    }

    public function getStats(string $customerId): array
    {
        return $this->requests->countByStatus($customerId);
    }

    public function getProviderJobs(string $providerId, string $status): array
    {
        return array_map([$this, 'normalizeRequest'], $this->requests->findAssignedByProvider($providerId, $status));
    }

    public function cancelRequest(string $requestId, string $customerId): array
    {
        $row = $this->requests->findById($requestId);

        if (!$row) {
            return ['success' => false, 'message' => 'Request not found.', 'http_code' => 404];
        }

        if ($row['CUSTOMER_ID'] !== $customerId) {
            return ['success' => false, 'message' => 'You do not own this request.', 'http_code' => 403];
        }

        if ($row['STATUS'] !== 'Requested') {
            return [
                'success'   => false,
                'message'   => 'Only requests with status "Requested" can be cancelled.',
                'http_code' => 422,
            ];
        }

        try {
            $deleted = $this->requests->deleteIfRequested($requestId, $customerId);

            if (!$deleted) {
                return ['success' => false, 'message' => 'Could not cancel request.', 'http_code' => 500];
            }

            $this->audit->log($customerId, 'request_cancelled', 'service_request', $requestId);

            return ['success' => true, 'message' => 'Request cancelled successfully.'];
        } catch (Throwable $e) {
            error_log('[RequestService] cancelRequest failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not cancel request. Please try again.', 'http_code' => 500];
        }
    }

    private function isAssignedProvider(array $request, string $providerId): bool
    {
        if (empty($request['REQUEST_ID']) || $this->offers === null) {
            return false;
        }

        return $this->offers->findAcceptedByRequestAndProvider($request['REQUEST_ID'], $providerId) !== null;
    }

    private function normalizeRequest(array $row): array
    {
        $description = (string) ($row['DESCRIPTION'] ?? '');

        return [
            'id' => $row['REQUEST_ID'],
            'customer_id' => $row['CUSTOMER_ID'],
            'category_id' => $row['CATEGORY_ID'],
            'title' => $row['TITLE'] ?? substr($description, 0, 80),
            'category_name' => $row['CATEGORY_NAME'] ?? '',
            'description' => $description,
            'budget' => isset($row['BUDGET']) ? (float) $row['BUDGET'] : null,
            'location' => $row['LOCATION'],
            'preferred_date' => $row['PREFERRED_DATE'] ?? null,
            'status' => $row['STATUS'],
            'accepted_offer_id' => $row['ACCEPTED_OFFER_ID'] ?? null,
            'completion_photo' => $row['COMPLETION_PHOTO'] ?? null,
            'offer_count' => (int) ($row['OFFER_COUNT'] ?? 0),
            'customer_name' => $row['CUSTOMER_NAME'] ?? null,
            'customer_phone' => $row['CUSTOMER_PHONE'] ?? null,
            'accepted_price' => isset($row['ACCEPTED_PRICE']) ? (float) $row['ACCEPTED_PRICE'] : null,
            'created_at' => $row['CREATED_AT'],
            'updated_at' => $row['UPDATED_AT'] ?? null,
        ];
    }
}
