<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../repositories/MetricsRepository.php';

/**
 * AdminService
 *
 * Business logic for admin operations: provider verification, category management,
 * platform metrics, and audit log access.
 */
final class AdminService
{
    private UserRepository     $users;
    private AuditLogRepository $audit;
    private CategoryRepository $categories;
    private ?MetricsRepository $metrics;

    public function __construct(
        UserRepository     $users,
        AuditLogRepository $audit,
        CategoryRepository $categories,
        ?MetricsRepository $metrics = null
    ) {
        $this->users      = $users;
        $this->audit      = $audit;
        $this->categories = $categories;
        $this->metrics    = $metrics;
    }

    // ------------------------------------------------------------------
    // Provider verification
    // ------------------------------------------------------------------

    public function verifyProvider(string $providerId, string $adminId): array
    {
        $provider = $this->users->findById($providerId);
        if (!$provider) {
            return ['success' => false, 'message' => 'Provider not found.', 'http_code' => 404];
        }
        if ($provider['ROLE'] !== 'Provider') {
            return ['success' => false, 'message' => 'User is not a provider.', 'http_code' => 422];
        }
        if ((bool) $provider['IS_VERIFIED']) {
            return ['success' => false, 'message' => 'Provider is already verified.', 'http_code' => 409];
        }

        $this->users->verifyProvider($providerId);
        $this->audit->log($adminId, 'provider_verified', 'user', $providerId, "Admin: $adminId");

        return ['success' => true, 'message' => 'Provider verified successfully.'];
    }

    public function listProviders(): array
    {
        $rows = $this->users->findAllProviders();
        return array_map(function (array $row): array {
            return [
                'id'             => $row['USER_ID'],
                'name'           => $row['NAME'],
                'email'          => $row['EMAIL'],
                'phone'          => $row['PHONE']          ?? null,
                'location'       => $row['LOCATION']       ?? null,
                'rating_average' => (float) ($row['RATING_AVERAGE'] ?? 0.0),
                'total_reviews'  => (int)   ($row['TOTAL_REVIEWS']  ?? 0),
                'is_verified'    => (bool)   $row['IS_VERIFIED'],
                'created_at'     => $row['CREATED_AT'],
            ];
        }, $rows);
    }

    // ------------------------------------------------------------------
    // Audit logs
    // ------------------------------------------------------------------

    public function getAuditLogs(int $page = 1, int $perPage = 50): array
    {
        $rows  = $this->audit->findAll($page, $perPage);
        $total = $this->audit->countAll();

        $normalized = array_map(function (array $row): array {
            return [
                'id'          => $row['AUDIT_ID'],
                'user_id'     => $row['USER_ID']     ?? null,
                'user_name'   => $row['USER_NAME']   ?? 'System',
                'user_role'   => $row['USER_ROLE']   ?? null,
                'action'      => $row['ACTION'],
                'entity_type' => $row['ENTITY_TYPE'] ?? null,
                'entity_id'   => $row['ENTITY_ID']   ?? null,
                'details'     => $row['DETAILS']     ?? '',
                'ip_address'  => $row['IP_ADDRESS']  ?? null,
                'created_at'  => $row['CREATED_AT'],
            ];
        }, $rows);

        return ['logs' => $normalized, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    // ------------------------------------------------------------------
    // Category management
    // ------------------------------------------------------------------

    public function listCategories(): array
    {
        $rows = $this->categories->findAllActive();
        return array_map(fn(array $r) => [
            'id'          => $r['CATEGORY_ID'],
            'name'        => $r['NAME'],
            'description' => $r['DESCRIPTION'] ?? '',
            'icon'        => $r['ICON']        ?? '',
        ], $rows);
    }

    public function createCategory(string $adminId, array $data): array
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'message' => 'Category name is required.', 'http_code' => 422];
        }
        if ($this->categories->findByName($name)) {
            return ['success' => false, 'message' => 'A category with this name already exists.', 'http_code' => 409];
        }

        $categoryId = $this->categories->create($name, $data['description'] ?? '', $data['icon'] ?? '');
        $this->audit->log($adminId, 'category_created', 'service_category', $categoryId, "Name: $name");

        return ['success' => true, 'category_id' => $categoryId];
    }

    public function updateCategory(string $adminId, string $categoryId, array $data): array
    {
        if (!$this->categories->findById($categoryId)) {
            return ['success' => false, 'message' => 'Category not found.', 'http_code' => 404];
        }

        $fields = [];
        if (isset($data['name']))        $fields['NAME']        = trim($data['name']);
        if (isset($data['description'])) $fields['DESCRIPTION'] = trim($data['description']);
        if (isset($data['icon']))        $fields['ICON']        = trim($data['icon']);
        if (isset($data['is_active']))   $fields['IS_ACTIVE']   = (bool) $data['is_active'];

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update.', 'http_code' => 422];
        }

        $this->categories->update($categoryId, $fields);
        $this->audit->log($adminId, 'category_updated', 'service_category', $categoryId);

        return ['success' => true, 'message' => 'Category updated.'];
    }

    public function metrics(): array
    {
        if ($this->metrics === null) {
            return [];
        }

        return array_merge($this->metrics->totals(), [
            'users_by_role' => $this->metrics->usersByRole(),
            'requests_by_status' => $this->metrics->requestsByStatus(),
            'requests_by_category' => $this->metrics->requestsByCategory(),
        ]);
    }
}
