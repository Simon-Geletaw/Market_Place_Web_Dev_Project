<?php

declare(strict_types=1);

/**
 * ReviewRepository
 *
 * Owns all SQL for the REVIEWS table.
 * The unique constraint on REQUEST_ID ensures one review per job.
 */
final class ReviewRepository
{
    private PDO $db;

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

    public function create(string $requestId, string $customerId, string $providerId, int $rating, string $comment): string
    {
        $uuid = $this->generateUuid();
        $sql  = 'INSERT INTO REVIEWS (REVIEW_ID, REQUEST_ID, CUSTOMER_ID, PROVIDER_ID, RATING, COMMENT)
                 VALUES (:id, :request_id, :customer_id, :provider_id, :rating, :comment)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'          => $uuid,
            ':request_id'  => $requestId,
            ':customer_id' => $customerId,
            ':provider_id' => $providerId,
            ':rating'      => $rating,
            ':comment'     => trim($comment),
        ]);
        return $uuid;
    }

    public function findByRequest(string $requestId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM REVIEWS WHERE REQUEST_ID = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetch() ?: null;
    }

    public function findByProvider(string $providerId): array
    {
        $sql  = 'SELECT r.*, u.NAME AS CUSTOMER_NAME, sr.DESCRIPTION AS REQUEST_DESCRIPTION,
                        sr.DESCRIPTION AS REQUEST_TITLE,
                        sc.NAME AS CATEGORY_NAME
                 FROM REVIEWS r
                 JOIN USERS              u  ON r.CUSTOMER_ID  = u.USER_ID
                 JOIN SERVICE_REQUESTS   sr ON r.REQUEST_ID   = sr.REQUEST_ID
                 JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID = sc.CATEGORY_ID
                 WHERE r.PROVIDER_ID = :pid ORDER BY r.CREATED_AT DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $providerId]);
        return $stmt->fetchAll();
    }

    public function findByCustomer(string $customerId): array
    {
        $sql  = 'SELECT r.*, u.NAME AS PROVIDER_NAME, sr.DESCRIPTION AS REQUEST_DESCRIPTION,
                        sr.DESCRIPTION AS REQUEST_TITLE,
                        sc.NAME AS CATEGORY_NAME
                 FROM REVIEWS r
                 JOIN USERS              u  ON r.PROVIDER_ID  = u.USER_ID
                 JOIN SERVICE_REQUESTS   sr ON r.REQUEST_ID   = sr.REQUEST_ID
                 JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID = sc.CATEGORY_ID
                 WHERE r.CUSTOMER_ID = :cid ORDER BY r.CREATED_AT DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetchAll();
    }

    public function existsForRequest(string $requestId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM REVIEWS WHERE REQUEST_ID = :id LIMIT 1');
        $stmt->execute([':id' => $requestId]);
        return (bool) $stmt->fetch();
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
