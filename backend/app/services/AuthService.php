<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditLogRepository.php';
require_once __DIR__ . '/../repositories/PasswordResetRepository.php';

/**
 * AuthService
 *
 * Owns the business rules for registration and login.
 * Manages PHP sessions — the single source of auth truth for this app.
 */
final class AuthService
{
    private UserRepository    $users;
    private AuditLogRepository $audit;
    private ?PasswordResetRepository $passwordResets;

    public function __construct(UserRepository $users, AuditLogRepository $audit, ?PasswordResetRepository $passwordResets = null)
    {
        $this->users = $users;
        $this->audit = $audit;
        $this->passwordResets = $passwordResets;
    }

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    /**
     * Validate credentials, start session, return safe user data.
     *
     * @return array{success: bool, user?: array, message?: string}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        // Use constant-time comparison to resist timing attacks.
        $validPassword = $user && password_verify($password, $user['PASSWORD_HASH']);

        if (!$user || !$validPassword) {
            $this->audit->log(null, 'login_failed', 'user', '', "Email: $email");
            return ['success' => false, 'message' => 'Incorrect password or email.'];
        }

        $this->startSession();

        // Regenerate session ID on login — prevents session fixation attacks.
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['role']    = $user['ROLE'];
        $_SESSION['name']    = $user['NAME'];

        $safeUser = $this->buildSafeUser($user);

        $this->audit->log($user['USER_ID'], 'login_success', 'user', $user['USER_ID']);

        return ['success' => true, 'user' => $safeUser];
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    /**
     * Create a new customer or provider account.
     *
     * @return array{success: bool, user_id?: string, message?: string, field?: string, http_code?: int}
     */
    public function register(array $data): array
    {
        // Duplicate email check
        if ($this->users->findByEmail($data['email'])) {
            return ['success' => false, 'message' => 'This email is already registered.', 'http_code' => 409, 'field' => 'email'];
        }

        // Duplicate phone check
        if (!empty($data['phone']) && $this->users->findByPhone($data['phone'])) {
            return ['success' => false, 'message' => 'This phone number is already registered.', 'http_code' => 409, 'field' => 'phone'];
        }

        // Map role string → enum
        $roleInput = strtolower(trim((string) ($data['role'] ?? '')));
        $role = match ($roleInput) {
            'customer' => UserRole::Customer,
            'provider' => UserRole::Provider,
            default    => null,
        };

        if ($role === null) {
            return ['success' => false, 'message' => 'Invalid role. Must be customer or provider.', 'http_code' => 422];
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $userId = $this->users->createUser(
                $data['email'],
                $passwordHash,
                $role,
                $data['name'],
                $data['location'] ?? '',
                $data['phone']    ?? ''
            );

            $this->audit->log($userId, 'user_registered', 'user', $userId, "Role: {$role->value}");

            $createdUser = $this->users->findById($userId);
            if ($createdUser) {
                $this->startSession();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $createdUser['USER_ID'];
                $_SESSION['role'] = $createdUser['ROLE'];
                $_SESSION['name'] = $createdUser['NAME'];
            }

            return [
                'success' => true,
                'user_id' => $userId,
                'user' => $createdUser ? $this->buildSafeUser($createdUser) : null,
            ];
        } catch (\Throwable $e) {
            error_log('[AuthService] Registration failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.', 'http_code' => 500];
        }
    }

    // ------------------------------------------------------------------
    // Logout
    // ------------------------------------------------------------------

    public function logout(): void
    {
        $this->startSession();
        $userId = $_SESSION['user_id'] ?? null;
        session_unset();
        session_destroy();
        if ($userId) {
            $this->audit->log($userId, 'logout', 'user', $userId);
        }
    }

    // ------------------------------------------------------------------
    // Current user (me)
    // ------------------------------------------------------------------

    /**
     * Return the currently authenticated user from session, or null.
     */
    public function currentUser(): ?array
    {
        $this->startSession();
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $user = $this->users->findById($_SESSION['user_id']);
        if (!$user) {
            // Session points to a deleted user — clean up
            session_unset();
            session_destroy();
            return null;
        }

        return $this->buildSafeUser($user);
    }

    public function updateProfile(string $userId, array $data): array
    {
        $fields = [
            'NAME' => trim((string) ($data['name'] ?? $data['full_name'] ?? '')),
            'PHONE' => trim((string) ($data['phone'] ?? '')),
            'LOCATION' => trim((string) ($data['location'] ?? '')),
        ];

        $this->users->updateProfile($userId, $fields);
        $user = $this->users->findById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.', 'http_code' => 404];
        }

        $_SESSION['name'] = $user['NAME'];
        $this->audit->log($userId, 'profile_updated', 'user', $userId);

        return ['success' => true, 'user' => $this->buildSafeUser($user)];
    }

    public function updatePassword(string $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.', 'http_code' => 404];
        }

        if (!password_verify($currentPassword, $user['PASSWORD_HASH'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.', 'http_code' => 422];
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]));
        $this->audit->log($userId, 'password_updated', 'user', $userId);

        return ['success' => true];
    }

    public function forgotPassword(string $email): array
    {
        $user = $this->users->findByEmail($email);

        if (!$user || $this->passwordResets === null) {
            return ['success' => true, 'message' => 'If that email exists, a reset link has been prepared.'];
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

        $this->passwordResets->create($user['USER_ID'], $tokenHash, $expiresAt);
        $this->audit->log($user['USER_ID'], 'password_reset_requested', 'user', $user['USER_ID']);

        return [
            'success' => true,
            'message' => 'Password reset token created.',
            'dev_token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function resetPassword(string $token, string $password): array
    {
        if ($this->passwordResets === null) {
            return ['success' => false, 'message' => 'Password reset is not configured.', 'http_code' => 500];
        }

        $reset = $this->passwordResets->findValidByTokenHash(hash('sha256', $token));
        if (!$reset) {
            return ['success' => false, 'message' => 'Reset token is invalid or expired.', 'http_code' => 422];
        }

        $this->users->updatePassword($reset['USER_ID'], password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]));
        $this->passwordResets->markUsed($reset['RESET_ID']);
        $this->audit->log($reset['USER_ID'], 'password_reset_completed', 'user', $reset['USER_ID']);

        return ['success' => true];
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Build a safe user payload for the frontend (no password hash, no internal fields).
     */
    private function buildSafeUser(array $user): array
    {
        return [
            'id'             => $user['USER_ID'],
            'name'           => $user['NAME'],
            'email'          => $user['EMAIL'],
            'role'           => strtolower($user['ROLE']),
            'phone'          => $user['PHONE']          ?? null,
            'location'       => $user['LOCATION']       ?? null,
            'is_verified'    => (bool) ($user['IS_VERIFIED']    ?? false),
            'rating_average' => (float) ($user['RATING_AVERAGE'] ?? 0.0),
            'total_reviews'  => (int) ($user['TOTAL_REVIEWS']  ?? 0),
        ];
    }
}
