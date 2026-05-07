<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/DatabaseConnector.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../validators/auth_validator.php';

/**
 * AuthController
 *
 * Thin HTTP handler for authentication endpoints.
 * All business logic lives in AuthService.
 */
final class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $db   = (new DatabaseConnector())->getConnection();
        $users = new UserRepository($db);
        $audit = new AuditLogRepository($db);
        $this->authService = new AuthService($users, $audit);
    }

    // POST /api/auth/login
    public function login(): array
    {
        $input  = request_json_body();
        $errors = validate_login_payload($input);

        if (!empty($errors)) {
            return error_response('Validation failed.', $errors, 422);
        }

        $result = $this->authService->login($input['email'], $input['password']);

        if ($result['success']) {
            return success_response('Login successful.', ['user' => $result['user']]);
        }

        return error_response($result['message'], [], 401);
    }

    // POST /api/auth/register
    public function register(): array
    {
        $input  = request_json_body();
        $errors = validate_register_payload($input);

        if (!empty($errors)) {
            return error_response('Validation failed.', $errors, 422);
        }

        $result = $this->authService->register($input);

        if ($result['success']) {
            return success_response('Registration successful.', ['user_id' => $result['user_id']], 201);
        }

        $code = $result['http_code'] ?? 400;
        return error_response($result['message'], [], $code);
    }

    // POST /api/auth/logout
    public function logout(): array
    {
        $this->authService->logout();
        return success_response('Logged out successfully.');
    }

    // GET /api/auth/me
    public function me(): array
    {
        $user = $this->authService->currentUser();

        if (!$user) {
            return error_response('Not authenticated.', [], 401);
        }

        return success_response('Authenticated.', ['user' => $user]);
    }
}
