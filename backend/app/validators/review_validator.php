<?php

declare(strict_types=1);

/**
 * Review validator functions.
 */

function validate_review_submit(array $data): array
{
    $errors = [];

    if (empty($data['request_id'])) {
        $errors['request_id'] = 'Request ID is required.';
    }

    if (!isset($data['rating']) || $data['rating'] === '') {
        $errors['rating'] = 'Rating is required.';
    } elseif (!is_numeric($data['rating']) || (int) $data['rating'] < 1 || (int) $data['rating'] > 5) {
        $errors['rating'] = 'Rating must be between 1 and 5.';
    }

    if (!empty($data['comment']) && strlen($data['comment']) > 2000) {
        $errors['comment'] = 'Comment must not exceed 2000 characters.';
    }

    return $errors;
}
