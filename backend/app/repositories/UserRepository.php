<?php


declare(strict_types=1);

enum UserRole: string
{
    case Customer = 'Customer';
    case Provider = 'Provider';
}

final class UserRepository
{
    private  PDO $DBConnection;
    public function __construct(PDO $DBConnection)
    {
        $this->DBConnection = $DBConnection;
    }

    public function createUser(string $email, string $passwordHash, UserRole $Role, string $Name, string $location, string $phone): int
    {
        $sql = "INSERT INTO users (EMAIL, PASSWORD_HASH, ROLE, LOCATION, NAME, PHONE) VALUES (:email, :password_hash, :ROLE, :location, :Name, :PHONE)";
        $stmt = $this->DBConnection->prepare($sql);
        
        $roleValue = $Role->value;
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':ROLE', $roleValue);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':Name', $Name);
        $stmt->bindParam(':PHONE', $phone);
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return (int)$this->DBConnection->lastInsertId();
        } 
        
        throw new Exception('Failed to create user');
    }

    public function findByEmail(string $email): ?array
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->DBConnection->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to find user by email');
        }
    }

    public function findByPhone(string $phone): ?array
    {
        try {
            $sql = "SELECT * FROM users WHERE phone = :phone";
            $stmt = $this->DBConnection->prepare($sql);
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to find user by phone');
        }
    }

}
