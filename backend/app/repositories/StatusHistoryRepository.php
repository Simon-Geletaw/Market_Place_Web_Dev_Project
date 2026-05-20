<?php

declare(strict_types=1);

/**
 * StatusHistoryRepository
 *
 * Records every status transition of SERVICE_REQUESTS for timeline analytics.
 */
final class StatusHistoryRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Insert a status change record.
     *
     * @param string      $requestId  The request that changed state
     * @param string|null $oldStatus  Previous status (null for initial creation)
     * @param string      $newStatus  New status value
     * @param string      $changedBy  USER_ID of the actor
     * @param string      $notes      Optional human-readable note
     */
    public function record(
        string  $requestId,
        ?string $oldStatus,
        string  $newStatus,
        string  $changedBy,
        string  $notes = ''
    ): void {
        $uuid = $this->generateUuid();
        $sql  = 'INSERT INTO STATUS_HISTORY
                     (STATUS_HISTORY_ID, REQUEST_ID, OLD_STATUS, NEW_STATUS, CHANGED_BY, NOTES)
                 VALUES (:id, :request_id, :old_status, :new_status, :changed_by, :notes)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'          => $uuid,
            ':request_id'  => $requestId,
            ':old_status'  => $oldStatus,
            ':new_status'  => $newStatus,
            ':changed_by'  => $changedBy,
            ':notes'       => $notes,
        ]);
    }

    public function findByRequest(string $requestId): array
    {
        $sql  = 'SELECT sh.*, u.NAME AS CHANGED_BY_NAME
                 FROM STATUS_HISTORY sh
                 LEFT JOIN USERS u ON sh.CHANGED_BY = u.USER_ID
                 WHERE sh.REQUEST_ID = :id
                 ORDER BY sh.CHANGED_AT ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $requestId]);
        return $stmt->fetchAll();
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
