<?php

declare(strict_types=1);

function validate_payload(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $ruleList) {
        $rulesForField = is_array($ruleList) ? $ruleList : explode('|', (string) $ruleList);
        $value = $data[$field] ?? null;

        if (in_array('required', $rulesForField, true) && ($value === null || $value === '')) {
            $errors[$field][] = 'The ' . $field . ' field is required.';
            continue;
        }

        if ($value !== null && in_array('string', $rulesForField, true) && !is_string($value)) {
            $errors[$field][] = 'The ' . $field . ' field must be a string.';
        }
    }

    return $errors;
}

function run_named_validator(string $name, array $body, array $params = []): array
{
    $payload = array_merge($body, $params);

    if (isset($payload['id']) && !isset($payload['request_id']) && in_array($name, ['offer_submit', 'review_submit', 'request_complete'], true)) {
        $payload['request_id'] = $payload['id'];
    }

    if (isset($payload['id']) && !isset($payload['offer_id']) && in_array($name, ['offer_accept', 'offer_reject', 'offer_counter'], true)) {
        $payload['offer_id'] = $payload['id'];
    }

    return match ($name) {
        'auth_login' => validate_login_payload($payload),
        'auth_register' => validate_register_payload($payload),
        'request_create' => validate_request_create($payload),
        'request_complete' => validate_request_complete($payload),
        'offer_submit' => validate_offer_submit($payload),
        'offer_accept' => validate_offer_accept($payload),
        'offer_reject' => validate_offer_reject($payload),
        'offer_counter' => validate_offer_counter($payload),
        'review_submit' => validate_review_submit($payload),
        'category_create' => validate_category_payload($payload, true),
        'category_update' => validate_category_payload($payload, false),
        'profile_update' => validate_profile_update($payload),
        'password_update' => validate_password_update($payload),
        'forgot_password' => validate_forgot_password($payload),
        'reset_password' => validate_reset_password($payload),
        default => [],
    };
}

function validate_category_payload(array $data, bool $required): array
{
    $errors = [];

    if ($required && empty($data['name'])) {
        $errors['name'] = 'Category name is required.';
    }

    if (!empty($data['name']) && strlen(trim((string) $data['name'])) < 2) {
        $errors['name'] = 'Category name must be at least 2 characters.';
    }

    return $errors;
}

function validate_profile_update(array $data): array
{
    $errors = [];

    $name = trim((string) ($data['name'] ?? $data['full_name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    }

    if (empty($data['phone'])) {
        $errors['phone'] = 'Phone number is required.';
    }

    if (empty($data['location'])) {
        $errors['location'] = 'Location is required.';
    }

    return $errors;
}

function validate_password_update(array $data): array
{
    $errors = [];

    if (empty($data['current_password'])) {
        $errors['current_password'] = 'Current password is required.';
    }

    if (empty($data['new_password'])) {
        $errors['new_password'] = 'New password is required.';
    } elseif (strlen((string) $data['new_password']) < 8) {
        $errors['new_password'] = 'New password must be at least 8 characters.';
    }

    return $errors;
}

function validate_forgot_password(array $data): array
{
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['email' => 'A valid email address is required.'];
    }

    return [];
}

function validate_reset_password(array $data): array
{
    $errors = [];

    if (empty($data['token'])) {
        $errors['token'] = 'Reset token is required.';
    }

    if (empty($data['password'])) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen((string) $data['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    return $errors;
}
