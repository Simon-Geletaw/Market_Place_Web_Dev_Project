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

    /**
     * Authenticate a user by email and password.
     * 
     * @param array $credentials Contains 'email' and 'password'
     * @return array The user data if successful
     * @throws Exception If authentication fails
     */
    public function authenticate(array $credentials): array
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required');
        }

        $user = $this->userRepository->getUserWithPasswordByEmail($email);

        if (!$user || !password_verify($password, $user['PASSWORD_HASH'])) {
            throw new Exception('Invalid email or password');
        }

        // Remove password hash from response
        unset($user['PASSWORD_HASH']);
        
        // Start session and store user info if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = $user;

        return $user;
    }
}
