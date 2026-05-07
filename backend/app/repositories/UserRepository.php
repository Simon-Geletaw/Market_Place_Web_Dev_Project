<?php

declare(strict_types=1);

enum UserRole: string
{
    case Customer = 'Customer';
    case Provider = 'Provider';
    case Admin = 'Admin';
}

final class UserRepository
{
<<<<<<< Updated upstream
    public function findByEmail(string $email): ?array
    {
        return null;
    }
=======
    private PDO $DBConnection;

    public function __construct(PDO $DBConnection)
    {
        $this->DBConnection = $DBConnection;
    }

    public function createUser(string $email, string $passwordHash, UserRole $Role, string $Name, string $location, string $phone): string
    {
        if ($this->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            throw new Exception('Email already exists');
        }

        if ($this->findByPhone($phone)) {
            http_response_code(409);
            echo json_encode(['error' => 'phone already exists']);
            throw new Exception('phone already exists');
        }

        $userId = bin2hex(random_bytes(16)); // Simple UUID version 4 equivalent or just random
        // Note: schema uses CHAR(36), so actual UUID format preferred:
        $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $sql = "INSERT INTO USERS (USER_ID, EMAIL, PASSWORD_HASH, ROLE, LOCATION, NAME, PHONE) VALUES (:user_id, :email, :password_hash, :ROLE, :location, :Name, :PHONE)";
        $stmt = $this->DBConnection->prepare($sql);
        
        $roleValue = $Role->value;
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':ROLE', $roleValue);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':Name', $Name);
        $stmt->bindParam(':PHONE', $phone);
        
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $userId;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user']);
            throw new Exception('Failed to create user');
        }
    }

    public function findByEmail(string $email): bool
    {
        try {
            $sql = "SELECT EMAIL FROM USERS WHERE EMAIL = :email";
            $stmt = $this->DBConnection->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to find user by email');
        }
    }

    public function findByPhone(string $phone): bool
    {
        try {
            $sql = "SELECT PHONE FROM USERS WHERE PHONE = :phone";
            $stmt = $this->DBConnection->prepare($sql);
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to find user by phone');
        }
    }

    public function getUserWithPasswordByEmail(string $email): ?array
    {
        try {
            $sql = "SELECT USER_ID, EMAIL, PASSWORD_HASH, ROLE, NAME FROM USERS WHERE EMAIL = :email";
            $stmt = $this->DBConnection->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to get user by email');
        }
    }

>>>>>>> Stashed changes
}
