<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../services/RequestService.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../repositories/RequestRepository.php';
require_once __DIR__ . '/../repositories/StatusHistoryRepository.php';
require_once __DIR__ . '/../repositories/OfferRepository.php';
require_once __DIR__ . '/../repositories/MetricsRepository.php';
require_once __DIR__ . '/../repositories/DatabaseConnector.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';

/**
 * AdminController
 *
 * Admin-only routes: provider verification, audit logs, category management, metrics.
 */
final class AdminController
{
    private AdminService   $adminService;
    private RequestService $requestService;

    public function __construct()
    {
        $db = (new DatabaseConnector())->getConnection();

        $this->adminService = new AdminService(
            new UserRepository($db),
            new AuditLogRepository($db),
            new CategoryRepository($db),
            new MetricsRepository($db)
        );

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

    // GET /api/admin/dashboard
    public function dashboard(): array
    {
        $providers = $this->adminService->listProviders();
        $unverified = count(array_filter($providers, fn($p) => !$p['is_verified']));

        return success_response('Admin dashboard data.', [
            'total_providers'     => count($providers),
            'unverified_providers' => $unverified,
        ]);
    }

    public function metrics(): array
    {
        return success_response('Admin metrics retrieved.', $this->adminService->metrics());
    }

    // GET /api/admin/audit-logs
    public function auditLogs(): array
    {
        $page    = (int) ($_GET['page']     ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 50);
        $data    = $this->adminService->getAuditLogs($page, $perPage);
        return success_response('Audit logs retrieved.', $data);
    }

    // PATCH /api/admin/providers/{id}/verify
    public function verifyProvider(): array
    {
        $providerId = route_param('id', '');
        $adminId    = $this->currentUserId();

        $result = $this->adminService->verifyProvider($providerId, $adminId);
        $code   = $result['http_code'] ?? ($result['success'] ? 200 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response($result['message']);
    }

    // GET /api/admin/categories
    public function categories(): array
    {
        $data = $this->adminService->listCategories();
        return success_response('Categories retrieved.', $data);
    }

    // POST /api/admin/categories
    public function createCategory(): array
    {
        $input   = request_json_body();
        $adminId = $this->currentUserId();

        $result = $this->adminService->createCategory($adminId, $input);
        $code   = $result['http_code'] ?? ($result['success'] ? 201 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response('Category created.', ['category_id' => $result['category_id']], 201);
    }

    // PATCH /api/admin/categories/{id}
    public function updateCategory(): array
    {
        $categoryId = route_param('id', '');
        $adminId    = $this->currentUserId();
        $input      = request_json_body();

        $result = $this->adminService->updateCategory($adminId, $categoryId, $input);
        $code   = $result['http_code'] ?? ($result['success'] ? 200 : 400);

        if (!$result['success']) {
            return error_response($result['message'], [], $code);
        }

        return success_response($result['message']);
    }

    // GET /api/admin/providers  (list for verification screen)
    public function providers(): array
    {
        $data = $this->adminService->listProviders();
        return success_response('Providers retrieved.', $data);
    }

    // GET /api/admin/users
    public function users(): array
    {
        $data = $this->adminService->listUsers();
        return success_response('Users retrieved.', $data);
    }
}
