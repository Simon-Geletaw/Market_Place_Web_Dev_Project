<?php

declare(strict_types=1);

final class NotificationRepository
{
    private PDO $db;
    private ?array $columns = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(
        string $userId,
        string $message,
        string $type = 'general',
        ?string $link = null,
        ?string $entityType = null,
        ?string $entityId = null
    ): string {
        $uuid = $this->generateUuid();
        $columns = $this->columns();

        $fields = ['NOTIFICATION_ID', 'USER_ID', 'MESSAGE'];
        $values = [':id', ':user_id', ':message'];
        $params = [
            ':id' => $uuid,
            ':user_id' => $userId,
            ':message' => $message,
        ];

        $optional = [
            'TYPE' => [':type', $type],
            'LINK' => [':link', $link],
            'ENTITY_TYPE' => [':entity_type', $entityType],
            'ENTITY_ID' => [':entity_id', $entityId],
        ];

        foreach ($optional as $column => [$placeholder, $value]) {
            if (in_array($column, $columns, true)) {
                $fields[] = $column;
                $values[] = $placeholder;
                $params[$placeholder] = $value;
            }
        }

        $sql = 'INSERT INTO NOTIFICATIONS (' . implode(', ', $fields) . ')
                VALUES (' . implode(', ', $values) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $uuid;
    }

    public function findByUser(string $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM NOTIFICATIONS WHERE USER_ID = :user_id ORDER BY CREATED_AT DESC LIMIT :limit');
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markRead(string $notificationId, string $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE NOTIFICATIONS SET IS_READ = TRUE WHERE NOTIFICATION_ID = :id AND USER_ID = :user_id');
        $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(string $userId): int
    {
        $stmt = $this->db->prepare('UPDATE NOTIFICATIONS SET IS_READ = TRUE WHERE USER_ID = :user_id AND IS_READ = FALSE');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->rowCount();
    }

    private function columns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $rows = $this->db->query('SHOW COLUMNS FROM NOTIFICATIONS')->fetchAll();
        $this->columns = array_map(static fn(array $row): string => strtoupper((string) $row['Field']), $rows);

        return $this->columns;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
