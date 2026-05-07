<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

final class AuthController
{
<<<<<<< Updated upstream
    public function login(): array
    {
        return [];
=======
    private AuthService $authService;

    public function __construct(PDO $db)
    {
        $userRepository = new UserRepository($db);
        $this->authService = new AuthService($userRepository);
    }

    public function showLogin(): array
    {
        return success_response('Auth login route ready.');
    }

    public function login(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $user = $this->authService->authenticate($input);
            return success_response('Login successful', $user);
        } catch (Exception $e) {
            return error_response($e->getMessage(), [], 401);
        }
    }

    public function showRegister(): array
    {
        return success_response('Auth register route ready.');
>>>>>>> Stashed changes
    }

    public function register(): array
    {
<<<<<<< Updated upstream
        return [];
=======
        return success_response('Auth register action ready.');
    }

    public function logout(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return success_response('Logout successful');
>>>>>>> Stashed changes
    }
}
