<?php

declare(strict_types=1);

/**
 * Validates the login payload.
 * 
 * @param array $payload
 * @return array Array of errors, empty if valid.
 */
function validate_login_payload(array $payload): array
{
    $errors = [];

    if (empty($payload['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }

    if (empty($payload['password'])) {
        $errors['password'] = 'Password is required.';
    }

    return $errors;
}

/**
 * Validates the registration payload.
 * 
 * @param array $payload
 * @return array Array of errors, empty if valid.
 */
function validate_register_payload(array $payload): array
{
    $errors = [];

    // Name
    if (empty($payload['name'])) {
        $errors['name'] = 'Full name is required.';
    } elseif (strlen($payload['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    }

    // Email
    if (empty($payload['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }

    // Password
    if (empty($payload['password'])) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($payload['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    elseif(strlen($payload['password']) > 255){
        $errors['password'] = 'Password must not exceed 255 characters.';
    }

    // Role
    $allowedRoles = ['customer', 'provider'];
    if (empty($payload['role'])) {
        $errors['role'] = 'Role is required.';
    } elseif (!in_array(strtolower($payload['role']), $allowedRoles)) {
        $errors['role'] = 'Invalid role selected.';
    }

    // Phone (Simple check, frontend does more complex Ethiopian format check)
    if (empty($payload['phone'])) {
        $errors['phone'] = 'Phone number is required.';
    }

    // Location
    if (empty($payload['location'])) {
        $errors['location'] = 'Location is required.';
    }

    return $errors;
}
