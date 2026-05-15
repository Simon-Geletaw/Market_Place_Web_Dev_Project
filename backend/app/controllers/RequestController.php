<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/RequestService.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/DatabaseConnector.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../validators/request_validator.php';

/**
 * RequestController
 *
 * Handles service request HTTP endpoints for customers and providers.
 */
final class RequestController
{
    private RequestService $requestService;

    public function __construct()
    {
        $db    = (new DatabaseConnector())->getConnection();
        $this->requestService = new RequestService(
            new RequestRepository($db),
            new CategoryRepository($db),
            new StatusHistoryRepository($db),
            new AuditLogRepository($db),
            new OfferRepository($db)
        );
    }

    private function currentUserId(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user_id'] ?? '';
    }

    private function currentUserRole(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return strtolower($_SESSION['role'] ?? '');
    }

    // GET /api/requests  (customer: my requests)
    public function myRequests(): array
    {
        $customerId = $this->currentUserId();
        $status     = $_GET['status'] ?? '';
        $sort       = $_GET['sort']   ?? 'desc';

        $data = $this->requestService->getMyRequests($customerId, $status, $sort);
        return success_response('Requests retrieved.', $data);
    }

    // GET /api/requests/{id}
    public function show(): array
    {
        $requestId  = route_param('id', '');
        $viewerId   = $this->currentUserId();
        $viewerRole = $this->currentUserRole();

        $result = $this->requestService->getRequestDetail($requestId, $viewerId, $viewerRole);
        $code   = $result['http_code'] ?? 200;

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response('Request detail retrieved.', $result['data']);
    }

    // POST /api/requests  (customer: create)
    public function create(): array
    {
        $input      = request_json_body();
        $customerId = $this->currentUserId();

        $errors = validate_request_create($input);
        if (!empty($errors)) {
            return error_response('Validation failed.', $errors, 422);
        }

        $result = $this->requestService->createRequest($customerId, $input);
        $code   = $result['http_code'] ?? ($result['success'] ? 201 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response('Request created successfully.', ['request_id' => $result['request_id']], 201);
    }

    // POST /api/requests/{id}/complete  (provider)
    public function markCompleted(): array
    {
        $requestId  = route_param('id', '');
        $providerId = $this->currentUserId();
        $input      = request_json_body();
        $photoPath  = $input['completion_photo'] ?? null;

        $result = $this->requestService->markCompleted($requestId, $providerId, $photoPath);
        $code   = $result['http_code'] ?? ($result['success'] ? 200 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response($result['message']);
    }

    // GET /api/requests/{id}/status-history (any auth)
    public function statusHistory(): array
    {
        $requestId = route_param('id', '');
        // Simple implementation — return from RequestService detail.
        return success_response('Status history not yet implemented.', []);
    }

    // GET /api/marketplace  (public browse)
    public function browse(): array
    {
        $filters = [];
        if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
        if (!empty($_GET['location']))    $filters['location']    = $_GET['location'];

        $data = $this->requestService->browseOpenRequests($filters);
        return success_response('Open requests retrieved.', $data);
    }

    // GET /api/provider/stats (dashboard counts)
    public function stats(): array
    {
        $customerId = $this->currentUserId();
        $counts     = $this->requestService->getStats($customerId);
        return success_response('Stats retrieved.', $counts);
    }

    public function providerAssignedJobs(): array
    {
        return success_response('Assigned jobs retrieved.', $this->requestService->getProviderJobs($this->currentUserId(), 'Assigned'));
    }

    public function providerCompletedJobs(): array
    {
        return success_response('Completed jobs retrieved.', $this->requestService->getProviderJobs($this->currentUserId(), 'Completed'));
    }
}
