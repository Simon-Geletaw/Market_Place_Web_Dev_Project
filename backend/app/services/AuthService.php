<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';

final class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user['PASSWORD_HASH'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['role'] = $user['ROLE'];
        $_SESSION['name'] = $user['NAME'];

        return [
            'success' => true,
            'user' => [
                'id' => $user['USER_ID'],
                'name' => $user['NAME'],
                'role' => $user['ROLE']
            ]
        ];
    }

    public function register(array $data): array
    {
        if ($this->userRepository->findByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists','http_code' => 409,'field' => 'email'];
        }

        if ($this->userRepository->findByPhone($data['phone'])) {
            return ['success' => false, 'message' => 'Phone number already exists','http_code' => 409];
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $roleInput = strtolower(trim((string) ($data['role'] ?? '')));
        $role = match ($roleInput) {
            'customer' => UserRole::Customer,
            'provider' => UserRole::Provider,
            default => null,
        };

        if ($role === null) {
            return ['success' => false, 'message' => 'Invalid role selected'];
        }
        try {
            $userId = $this->userRepository->createUser(
                $data['email'],
                $passwordHash,
                $role,
                $data['name'],
                $data['location'],
                $data['phone']
            );

            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }
}
