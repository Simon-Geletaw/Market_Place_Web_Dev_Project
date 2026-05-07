<?php

declare(strict_types=1);

/**
 * OfferRepository
 *
 * Owns all SQL for the OFFERS table.
 * Enforces the unique (REQUEST_ID, PROVIDER_ID) constraint at the DB level.
 */
final class OfferRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ------------------------------------------------------------------
    // Write operations
    // ------------------------------------------------------------------

    public function create(string $requestId, string $providerId, float $price, string $message): string
    {
        $uuid = $this->generateUuid();
        $sql  = 'INSERT INTO OFFERS (OFFER_ID, REQUEST_ID, PROVIDER_ID, PRICE, MESSAGE, STATUS)
                 VALUES (:id, :request_id, :provider_id, :price, :message, :status)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'          => $uuid,
            ':request_id'  => $requestId,
            ':provider_id' => $providerId,
            ':price'       => $price,
            ':message'     => trim($message),
            ':status'      => 'Pending',
        ]);
        return $uuid;
    }

    public function updateStatus(string $offerId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE OFFERS SET STATUS = :status WHERE OFFER_ID = :id');
        $stmt->execute([':status' => $status, ':id' => $offerId]);
        return $stmt->rowCount() > 0;
    }

    public function storeCounter(string $offerId, float $counterPrice, string $counterMessage): bool
    {
        $sql  = 'UPDATE OFFERS SET STATUS = :status, COUNTER_PRICE = :cprice, COUNTER_MESSAGE = :cmsg
                 WHERE OFFER_ID = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => 'Countered', ':cprice' => $counterPrice, ':cmsg' => trim($counterMessage), ':id' => $offerId]);
        return $stmt->rowCount() > 0;
    }

    /** Reject all other pending/countered offers after one is accepted. */
    public function rejectOthers(string $requestId, string $acceptedOfferId): void
    {
        $sql  = 'UPDATE OFFERS SET STATUS = :status
                 WHERE REQUEST_ID = :rid AND OFFER_ID != :aid AND STATUS IN (:s1, :s2)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => 'Rejected', ':rid' => $requestId, ':aid' => $acceptedOfferId, ':s1' => 'Pending', ':s2' => 'Countered']);
    }

    // ------------------------------------------------------------------
    // Read operations
    // ------------------------------------------------------------------

    public function findById(string $offerId): ?array
    {
        $sql  = 'SELECT o.*, u.NAME AS PROVIDER_NAME, u.RATING_AVERAGE, u.IS_VERIFIED
                 FROM OFFERS o JOIN USERS u ON o.PROVIDER_ID = u.USER_ID
                 WHERE o.OFFER_ID = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $offerId]);
        return $stmt->fetch() ?: null;
    }

    public function findByRequest(string $requestId): array
    {
        $sql  = 'SELECT o.*, u.NAME AS PROVIDER_NAME, u.RATING_AVERAGE, u.IS_VERIFIED
                 FROM OFFERS o JOIN USERS u ON o.PROVIDER_ID = u.USER_ID
                 WHERE o.REQUEST_ID = :id ORDER BY o.CREATED_AT ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetchAll();
    }

    public function findByProvider(string $providerId, string $status = ''): array
    {
        $sql    = 'SELECT o.*, sr.DESCRIPTION AS REQUEST_DESCRIPTION, sr.LOCATION AS REQUEST_LOCATION,
                          sr.STATUS AS REQUEST_STATUS, sc.NAME AS CATEGORY_NAME, u.NAME AS CUSTOMER_NAME
                   FROM OFFERS o
                   JOIN SERVICE_REQUESTS   sr ON o.REQUEST_ID   = sr.REQUEST_ID
                   JOIN SERVICE_CATEGORIES sc ON sr.CATEGORY_ID = sc.CATEGORY_ID
                   JOIN USERS              u  ON sr.CUSTOMER_ID = u.USER_ID
                   WHERE o.PROVIDER_ID = :pid';
        $params = [':pid' => $providerId];
        if ($status !== '') {
            $sql .= ' AND o.STATUS = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY o.CREATED_AT DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function existsByProviderAndRequest(string $providerId, string $requestId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM OFFERS WHERE PROVIDER_ID = :pid AND REQUEST_ID = :rid LIMIT 1');
        $stmt->execute([':pid' => $providerId, ':rid' => $requestId]);
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
