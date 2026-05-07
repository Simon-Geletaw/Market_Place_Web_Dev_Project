<?php

declare(strict_types=1);

/**
 * AuditLogRepository
 *
 * Writes security and business action events to AUDIT_LOGS.
 * Never throws — audit failures must not break workflows.
 */
final class AuditLogRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Insert an audit log row. Silently ignores failures.
     */
    public function log(
        ?string $userId,
        string  $action,
        string  $entityType = '',
        string  $entityId   = '',
        string  $details    = ''
    ): void {
        try {
            $uuid = $this->generateUuid();
            $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $sql  = 'INSERT INTO AUDIT_LOGS (AUDIT_ID, USER_ID, ACTION, ENTITY_TYPE, ENTITY_ID, DETAILS, IP_ADDRESS)
                     VALUES (:id, :user_id, :action, :entity_type, :entity_id, :details, :ip)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id'          => $uuid,
                ':user_id'     => $userId,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId ?: null,
                ':details'     => $details,
                ':ip'          => $ip,
            ]);
        } catch (\Throwable $e) {
            error_log('[AuditLog] Failed to write log: ' . $e->getMessage());
        }
    }

    /**
     * Paginated audit log retrieval for admin screen.
     */
    public function findAll(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $sql    = 'SELECT al.*, u.NAME AS USER_NAME, u.ROLE AS USER_ROLE
                   FROM AUDIT_LOGS al
                   LEFT JOIN USERS u ON al.USER_ID = u.USER_ID
                   ORDER BY al.CREATED_AT DESC
                   LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM AUDIT_LOGS')->fetchColumn();
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
