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

/**
 * DashboardController
 *
 * Provides summary data for role-based dashboards.
 * Thin — delegates to RequestService.
 */
final class DashboardController
{
    private RequestService $requestService;

    public function __construct()
    {
        $db = (new DatabaseConnector())->getConnection();
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

    // GET /api/customer/dashboard
    public function customer(): array
    {
        $customerId = $this->currentUserId();
        $stats      = $this->requestService->getStats($customerId);
        $recent     = $this->requestService->getMyRequests($customerId, '', 'desc');
        $recent     = array_slice($recent, 0, 5); // Last 5 for dashboard preview

        return success_response('Customer dashboard data.', [
            'stats'          => $stats,
            'recent_requests' => $recent,
        ]);
    }

    // GET /api/provider/dashboard
    public function provider(): array
    {
        $providerId = $this->currentUserId();
        $assigned   = $this->requestService->getProviderJobs($providerId, 'Assigned');
        $completed  = $this->requestService->getProviderJobs($providerId, 'Completed');

        return success_response('Provider dashboard data.', [
            'assigned_count'  => count($assigned),
            'completed_count' => count($completed),
            'recent_assigned' => array_slice($assigned, 0, 5),
        ]);
    }
}
