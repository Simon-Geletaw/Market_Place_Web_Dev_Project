<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/DatabaseConnector.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';

final class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $db = (new DatabaseConnector())->getConnection();
        $userRepo = new UserRepository($db);
        $this->authService = new AuthService($userRepo);
    }

    public function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['email']) || !isset($input['password'])) {
            send_json(error_response('Email and password required', [], 400));
            return;
        }

        $result = $this->authService->login($input['email'], $input['password']);

        if ($result['success']) {
            send_json(success_response('Login successful', $result['user']));
        } else {
            send_json(error_response($result['message'], [], 401));
        }
    }

    public function register(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['email', 'password', 'role', 'name', 'location', 'phone'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                send_json(error_response("Field $field is required", [], 400));
                return;
            }
        }

        $result = $this->authService->register($input);

        if ($result['success']) {
            send_json(success_response('Registration successful', ['user_id' => $result['user_id']], 201));
        } else {
            send_json(error_response($result['message'], [], 400));
        }
    }

    public function logout(): void
    {
        $this->authService->logout();
        send_json(success_response('Logged out successfully'));
    }

    public function showLogin(): array
    {
        return success_response('Auth login route ready.');
    }

    public function showRegister(): array
    {
        return success_response('Auth register route ready.');
    }
}

    public function register(): array
    {
        return success_response('Auth register action ready.');
    }

    public function logout(): array
    {
        return success_response('Auth logout action ready.');
    }
}
