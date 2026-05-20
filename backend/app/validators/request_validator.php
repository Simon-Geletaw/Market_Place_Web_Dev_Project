<?php

declare(strict_types=1);

/**
 * Request (service request) validator functions.
 */

function validate_request_create(array $data): array
{
    $errors = [];

    if (empty($data['category']) && empty($data['category_id'])) {
        $errors['category'] = 'Category is required.';
    }

    $description = trim((string) ($data['description'] ?? $data['title'] ?? ''));
    if ($description === '') {
        $errors['description'] = 'Description is required.';
    } elseif (strlen($description) < 10) {
        $errors['description'] = 'Description must be at least 10 characters.';
    } elseif (strlen($description) > 2000) {
        $errors['description'] = 'Description must not exceed 2000 characters.';
    }

    if (isset($data['budget']) && $data['budget'] !== '' && (!is_numeric($data['budget']) || (float) $data['budget'] < 0)) {
        $errors['budget'] = 'Budget must be a valid positive number.';
    }

    // Location and preferred date are now provided upon offer acceptance.

    if (!empty($data['preferred_date'])) {
        $date = \DateTime::createFromFormat('Y-m-d', $data['preferred_date']);
        if (!$date || $date->format('Y-m-d') !== $data['preferred_date']) {
            $errors['preferred_date'] = 'Preferred date must be in YYYY-MM-DD format.';
        }
    }

    return $errors;
}

function validate_request_complete(array $data): array
{
    // completion_photo is optional — no strict validation needed here.
    return [];
}
