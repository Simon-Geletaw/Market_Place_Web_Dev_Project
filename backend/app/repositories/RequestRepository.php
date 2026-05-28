<?php

declare(strict_types=1);

/**
 * RequestRepository
 *
 * Owns all SQL for the SERVICE_REQUESTS table and related joins.
 * Column names are UPPER_CASE per the MySQL schema.
 *
 * NOTE: There is no TITLE column in the schema. The description IS the title.
 * The frontend sends {title, description, category(name), location, preferred_date, budget}.
 * We store description only; title is treated as a short alias for the first 60 chars of description
 * for display purposes — the category name is the primary label.
 */
final class RequestRepository
{
    private PDO $db;
    private ?array $columns = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function transaction(callable $callback): mixed
    {
        $alreadyInTransaction = $this->db->inTransaction();

        if (!$alreadyInTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $result = $callback();

            if (!$alreadyInTransaction) {
                $this->db->commit();
            }

            return $result;
        } catch (Throwable $exception) {
            if (!$alreadyInTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    // ------------------------------------------------------------------
    // Write operations
    // ------------------------------------------------------------------

    /**
     * Create a new service request and return its UUID.
     *
     * @param string $customerId  Customer USER_ID
     * @param string $categoryId  SERVICE_CATEGORIES.CATEGORY_ID (already resolved)
     * @param array  $data        Validated input fields
     */
    public function create(string $customerId, string $categoryId, array $data): string
    {
        $uuid = $this->generateUuid();

        $fields = ['REQUEST_ID', 'CUSTOMER_ID', 'CATEGORY_ID', 'DESCRIPTION', 'LOCATION', 'PREFERRED_DATE', 'STATUS'];
        $values = [':id', ':customer_id', ':category_id', ':description', ':location', ':preferred_date', ':status'];
        $params = [
            ':id'             => $uuid,
            ':customer_id'    => $customerId,
            ':category_id'    => $categoryId,
            ':description'    => trim((string) ($data['description'] ?? $data['title'] ?? '')),
            ':location'       => trim($data['location'] ?? ''),
            ':preferred_date' => !empty($data['preferred_date']) ? $data['preferred_date'] : null,
            ':status'         => 'Requested',
        ];

        $columns = $this->columns();
        if (in_array('TITLE', $columns, true)) {
            $fields[] = 'TITLE';
            $values[] = ':title';
            $params[':title'] = trim((string) ($data['title'] ?? substr($params[':description'], 0, 80)));
        }
        if (in_array('BUDGET', $columns, true)) {
            $fields[] = 'BUDGET';
            $values[] = ':budget';
            $params[':budget'] = isset($data['budget']) && $data['budget'] !== '' ? (float) $data['budget'] : null;
        }

        $sql = 'INSERT INTO SERVICE_REQUESTS (' . implode(', ', $fields) . ')
                VALUES (' . implode(', ', $values) . ')';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $uuid;
    }

    /**
     * Update the status of a request (called by service layer after validation).
     */
    public function updateStatus(string $requestId, string $newStatus): bool
    {
        $sql  = 'UPDATE SERVICE_REQUESTS SET STATUS = :status WHERE REQUEST_ID = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $newStatus, ':id' => $requestId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Link accepted offer to the request, set location/date, and move to Assigned.
     */
    public function assignOfferWithSchedule(string $requestId, string $offerId, string $location, string $preferredDate): bool
    {
        $sql  = 'UPDATE SERVICE_REQUESTS
                 SET STATUS = :status, ACCEPTED_OFFER_ID = :offer_id, LOCATION = :location, PREFERRED_DATE = :date
                 WHERE REQUEST_ID = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status' => 'Assigned',
            ':offer_id' => $offerId,
            ':location' => trim($location),
            ':date' => $preferredDate,
            ':id' => $requestId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Store a completion photo path and move to Completed.
     */
    public function markCompleted(string $requestId, ?string $photoPath): bool
    {
        $sql  = 'UPDATE SERVICE_REQUESTS
                 SET STATUS = :status, COMPLETION_PHOTO = :photo
                 WHERE REQUEST_ID = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => 'Completed', ':photo' => $photoPath, ':id' => $requestId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Move to Reviewed (after a review is created).
     */
    public function markReviewed(string $requestId): bool
    {
        return $this->updateStatus($requestId, 'Reviewed');
    }

    /**
     * Delete a pending request — only succeeds if STATUS = 'Requested'
     * AND the row belongs to the given customer.
     * Returns true when a row was actually deleted, false otherwise.
     */
    public function deleteIfRequested(string $requestId, string $customerId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM SERVICE_REQUESTS
             WHERE REQUEST_ID = :id
               AND CUSTOMER_ID = :cid
               AND STATUS = 'Requested'"
        );
        $stmt->execute([':id' => $requestId, ':cid' => $customerId]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------------
    // Read operations
    // ------------------------------------------------------------------

    /**
     * Find a single request by ID. Returns null if not found.
     */
    public function findById(string $requestId): ?array
    {
        $sql = 'SELECT sr.*,
                       sc.NAME AS CATEGORY_NAME,
                       u.NAME  AS CUSTOMER_NAME,
                       u.PHONE AS CUSTOMER_PHONE,
                       ao.PRICE AS ACCEPTED_PRICE,
                       (SELECT COUNT(*) FROM OFFERS o WHERE o.REQUEST_ID = sr.REQUEST_ID) AS OFFER_COUNT
                FROM SERVICE_REQUESTS sr
                JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID  = sc.CATEGORY_ID
                JOIN USERS             u  ON sr.CUSTOMER_ID   = u.USER_ID
                LEFT JOIN OFFERS       ao ON sr.ACCEPTED_OFFER_ID = ao.OFFER_ID
                WHERE sr.REQUEST_ID = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * List all requests belonging to a customer, with optional status filter and sort.
     */
    public function findByCustomer(string $customerId, string $status = '', string $sort = 'desc'): array
    {
        $sortDir = strtoupper($sort) === 'ASC' ? 'ASC' : 'DESC';

        $sql = 'SELECT sr.*,
                       sc.NAME AS CATEGORY_NAME,
                       (SELECT COUNT(*) FROM OFFERS o WHERE o.REQUEST_ID = sr.REQUEST_ID) AS OFFER_COUNT
                FROM SERVICE_REQUESTS sr
                JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID = sc.CATEGORY_ID
                WHERE sr.CUSTOMER_ID = :customer_id';

        $params = [':customer_id' => $customerId];

        if ($status !== '') {
            $sql          .= ' AND sr.STATUS = :status';
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY sr.CREATED_AT $sortDir";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Browse open requests for the marketplace (status = Requested or Negotiating).
     * Providers use this to find work.
     */
    public function findOpenRequests(array $filters = []): array
    {
        $sql = 'SELECT sr.*,
                       sc.NAME AS CATEGORY_NAME,
                       u.NAME  AS CUSTOMER_NAME,
                       (SELECT COUNT(*) FROM OFFERS o WHERE o.REQUEST_ID = sr.REQUEST_ID) AS OFFER_COUNT
                FROM SERVICE_REQUESTS sr
                JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID = sc.CATEGORY_ID
                JOIN USERS             u  ON sr.CUSTOMER_ID  = u.USER_ID
                WHERE sr.STATUS IN (:s1, :s2)';

        $params = [':s1' => 'Requested', ':s2' => 'Negotiating'];

        if (!empty($filters['category_id'])) {
            $sql             .= ' AND sr.CATEGORY_ID = :cat';
            $params[':cat']   = $filters['category_id'];
        }

        if (!empty($filters['location'])) {
            $sql              .= ' AND sr.LOCATION = :loc';
            $params[':loc']    = $filters['location'];
        }

        $sql .= ' ORDER BY sr.CREATED_AT DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get all requests assigned to a specific provider (via accepted offer).
     */
    public function findAssignedByProvider(string $providerId, string $status = 'Assigned'): array
    {
        $sql = 'SELECT sr.*,
                       sc.NAME AS CATEGORY_NAME,
                       u.NAME  AS CUSTOMER_NAME,
                       u.PHONE AS CUSTOMER_PHONE,
                       o.PRICE AS ACCEPTED_PRICE
                FROM SERVICE_REQUESTS sr
                JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID      = sc.CATEGORY_ID
                JOIN USERS             u  ON sr.CUSTOMER_ID        = u.USER_ID
                JOIN OFFERS            o  ON sr.ACCEPTED_OFFER_ID  = o.OFFER_ID
                WHERE o.PROVIDER_ID = :provider_id';

        $params = [':provider_id' => $providerId];

        if ($status === 'Completed') {
            $sql .= ' AND sr.STATUS IN (:completed, :reviewed)';
            $params[':completed'] = 'Completed';
            $params[':reviewed'] = 'Reviewed';
        } else {
            $sql .= ' AND sr.STATUS = :status';
            $params[':status'] = $status;
        }

        $sql .= '
                ORDER BY sr.UPDATED_AT DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Admin: all requests with pagination support.
     */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $sql    = 'SELECT sr.*, sc.NAME AS CATEGORY_NAME, u.NAME AS CUSTOMER_NAME
                   FROM SERVICE_REQUESTS sr
                   JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID = sc.CATEGORY_ID
                   JOIN USERS             u  ON sr.CUSTOMER_ID  = u.USER_ID
                   ORDER BY sr.CREATED_AT DESC
                   LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Dashboard stats for a customer: count per status.
     */
    public function countByStatus(string $customerId): array
    {
        $sql  = 'SELECT STATUS, COUNT(*) AS CNT
                 FROM SERVICE_REQUESTS
                 WHERE CUSTOMER_ID = :id
                 GROUP BY STATUS';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $customerId]);
        $rows = $stmt->fetchAll();

        $counts = [
            'Requested'   => 0,
            'Negotiating' => 0,
            'Assigned'    => 0,
            'Completed'   => 0,
            'Reviewed'    => 0,
        ];
        foreach ($rows as $row) {
            $counts[$row['STATUS']] = (int) $row['CNT'];
        }
        return $counts;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function columns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $rows = $this->db->query('SHOW COLUMNS FROM SERVICE_REQUESTS')->fetchAll();
        $this->columns = array_map(static fn(array $row): string => strtoupper((string) ($row['FIELD'] ?? $row['Field'] ?? '')), $rows);

        return $this->columns;
    }
}
