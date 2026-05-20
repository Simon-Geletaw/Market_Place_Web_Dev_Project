<?php

declare(strict_types=1);

final class PasswordResetRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(string $userId, string $tokenHash, string $expiresAt): string
    {
        $uuid = $this->generateUuid();
        $sql = 'INSERT INTO PASSWORD_RESETS (RESET_ID, USER_ID, TOKEN_HASH, EXPIRES_AT)
                VALUES (:id, :user_id, :token_hash, :expires_at)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $uuid,
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

        return $uuid;
    }

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $sql = 'SELECT * FROM PASSWORD_RESETS
                WHERE TOKEN_HASH = :token_hash
                  AND USED_AT IS NULL
                  AND EXPIRES_AT > NOW()
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token_hash' => $tokenHash]);

        return $stmt->fetch() ?: null;
    }

    public function markUsed(string $resetId): void
    {
        $stmt = $this->db->prepare('UPDATE PASSWORD_RESETS SET USED_AT = NOW() WHERE RESET_ID = :id');
        $stmt->execute([':id' => $resetId]);
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
