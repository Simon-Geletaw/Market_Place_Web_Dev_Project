<?php

declare(strict_types=1);

/**
 * UserRole enum — maps to MySQL ENUM('Customer','Provider','Admin').
 */
enum UserRole: string
{
    case Customer = 'Customer';
    case Provider = 'Provider';
    case Admin    = 'Admin';
}

/**
 * UserRepository
 *
 * Owns all SQL that reads/writes the USERS table.
 * Column names are UPPER_CASE matching the MySQL schema.
 * Returns plain associative arrays — no business logic here.
 */
final class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Insert a new user row and return the generated UUID string.
     */
    public function createUser(
        string   $email,
        string   $passwordHash,
        UserRole $role,
        string   $name,
        string   $location,
        string   $phone
    ): string {
        // Generate a proper RFC-4122 v4 UUID.
        $uuid = $this->generateUuid();

        $sql = 'INSERT INTO USERS (USER_ID, EMAIL, PASSWORD_HASH, ROLE, NAME, LOCATION, PHONE)
                VALUES (:uuid, :email, :password_hash, :role, :name, :location, :phone)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uuid'          => $uuid,
            ':email'         => strtolower(trim($email)),
            ':password_hash' => $passwordHash,
            ':role'          => $role->value,
            ':name'          => trim($name),
            ':location'      => trim($location),
            ':phone'         => trim($phone),
        ]);

        if ($stmt->rowCount() < 1) {
            throw new RuntimeException('Failed to insert user row.');
        }

        return $uuid;
    }

    /**
     * Find a user by email (case-insensitive). Returns null if not found.
     */
    public function findByEmail(string $email): ?array
    {
        $sql  = 'SELECT * FROM USERS WHERE EMAIL = :email LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find a user by phone. Returns null if not found.
     */
    public function findByPhone(string $phone): ?array
    {
        $sql  = 'SELECT * FROM USERS WHERE PHONE = :phone LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':phone' => trim($phone)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find a user by UUID. Returns null if not found.
     */
    public function findById(string $userId): ?array
    {
        $sql  = 'SELECT * FROM USERS WHERE USER_ID = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Set is_verified = TRUE for a provider.
     */
    public function verifyProvider(string $providerId): bool
    {
        $sql  = 'UPDATE USERS SET IS_VERIFIED = TRUE WHERE USER_ID = :id AND ROLE = :role';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $providerId, ':role' => UserRole::Provider->value]);
        return $stmt->rowCount() > 0;
    }

    /**
     * List all providers (for admin verification screen).
     */
    public function findAllProviders(): array
    {
        $sql  = 'SELECT USER_ID, NAME, EMAIL, PHONE, LOCATION, RATING_AVERAGE, TOTAL_REVIEWS, IS_VERIFIED, CREATED_AT
                 FROM USERS WHERE ROLE = :role ORDER BY CREATED_AT DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => UserRole::Provider->value]);
        return $stmt->fetchAll();
    }

    /**
     * Update user profile fields.
     */
    public function updateProfile(string $userId, array $fields): bool
    {
        $allowed = ['NAME', 'PHONE', 'LOCATION'];
        $sets    = [];
        $params  = [':id' => $userId];

        foreach ($allowed as $col) {
            if (isset($fields[$col])) {
                $sets[]           = "$col = :$col";
                $params[":$col"] = $fields[$col];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql  = 'UPDATE USERS SET ' . implode(', ', $sets) . ' WHERE USER_ID = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
    public function updatePassword(string $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE USERS SET PASSWORD_HASH = :hash WHERE USER_ID = :id');
        $stmt->execute([':hash' => $passwordHash, ':id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update a provider's rating aggregate after a new review.
     */
    public function refreshProviderRating(string $providerId): void
    {
        $sql = 'UPDATE USERS u
                SET RATING_AVERAGE = (
                        SELECT COALESCE(AVG(r.RATING), 0) FROM REVIEWS r WHERE r.PROVIDER_ID = :id
                    ),
                    TOTAL_REVIEWS = (
                        SELECT COUNT(*) FROM REVIEWS r WHERE r.PROVIDER_ID = :id2
                    )
                WHERE u.USER_ID = :id3';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $providerId, ':id2' => $providerId, ':id3' => $providerId]);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
