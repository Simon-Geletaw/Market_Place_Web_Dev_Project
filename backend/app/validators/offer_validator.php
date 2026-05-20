<?php

declare(strict_types=1);

/**
 * Offer validator functions.
 */

function validate_offer_submit(array $data): array
{
    $errors = [];

    if (empty($data['request_id'])) {
        $errors['request_id'] = 'Request ID is required.';
    }

    if (!isset($data['price']) || $data['price'] === '') {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($data['price']) || (float) $data['price'] <= 0) {
        $errors['price'] = 'Price must be a positive number.';
    }

    if (!empty($data['message']) && strlen($data['message']) > 1000) {
        $errors['message'] = 'Message must not exceed 1000 characters.';
    }

    return $errors;
}

function validate_offer_counter(array $data): array
{
    $errors = [];

    if (!isset($data['counter_price']) || $data['counter_price'] === '') {
        $errors['counter_price'] = 'Counter price is required.';
    } elseif (!is_numeric($data['counter_price']) || (float) $data['counter_price'] <= 0) {
        $errors['counter_price'] = 'Counter price must be a positive number.';
    }

    if (!empty($data['counter_message']) && strlen($data['counter_message']) > 1000) {
        $errors['counter_message'] = 'Counter message must not exceed 1000 characters.';
    }

    return $errors;
}

function validate_offer_accept(array $data): array
{
    $errors = [];

    if (empty($data['location'])) {
        $errors['location'] = 'Location is required.';
    }

    $date = $data['preferred_date'] ?? $data['date'] ?? '';
    if (empty($date)) {
        $errors['date'] = 'Date is required.';
    } else {
        $parsed = \DateTime::createFromFormat('Y-m-d', (string) $date);
        if (!$parsed || $parsed->format('Y-m-d') !== (string) $date) {
            $errors['date'] = 'Date must be in YYYY-MM-DD format.';
        }
    }

    return $errors;
}

function validate_offer_reject(array $data): array
{
    return []; // No body required for reject.
}
