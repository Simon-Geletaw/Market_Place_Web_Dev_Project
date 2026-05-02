<?php


declare(strict_types=1);

final class UserRepository
{
    private  PDO $DBConnection;
    public function __construct(PDO $DBConnection)
    {


        $this->DBConnection = $DBConnection;

    }
    public function createUser(string $email, string $passwordHash,UserRole $Role,string $Name,string $location,string $phone): int
    {
        enum UserRole: string
        {
          case Customer='Customer';
          case Provider='Provide';


        }
        $sql = "INSERT INTO users (EMAIL,PASSWORD_HASH,ROLE,LOCATION,NAME,PHONE) VALUES (:email, :password_hash ,:ROLE,:location,:Name,:PHONE)";
        $stmt = $this->DBConnection->prepare($sql);
        if($this->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            throw new Exception('Email already exists');
        }
        if($this->findByPhone($phone)) {
            http_response_code(409);
            echo json_encode(['error' => 'phone already exists']);
            throw new Exception('phone already exists');
        }
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':ROLE', $Role);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':Name', $Name);
        $stmt->execute();
        if($stmt->rowCount() > 0) {
            $user_id=$this->DBConnection->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'User created successfully']);
            session_start();
            $_SESSION['user_id']=$user_id;
            $_SESSION['role']=$Role;
            if($Role== 'Customer') {
                header('Location: /dashboard/customer');
            }
            else {
                header('Location: /dashboard/provider');
            }
        } 
        else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user']);
            throw new Exception('Failed to create user');
        }
        return 0;

    }

    public function findByEmail(string $email): bool
    {
       try{ $sql = "SELECT EMAIL From users WHERE email = :email";
        $stmt = $this->DBConnection->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if($stmt->fetch()) {
            return true;
        }
        else {
            return false;
        }}
        catch(PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to find user by email');
        }
    }
    public function findByPhone(string $phone): bool
    {
       try{ $sql = "SELECT PHONE FROM users WHERE phone = :phone";
        $stmt = $this->DBConnection->prepare($sql);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        if($stmt->fetch()) {
            return true;
        }
        else {
            return false;
        }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to find user by phone');
        }
    }

}
