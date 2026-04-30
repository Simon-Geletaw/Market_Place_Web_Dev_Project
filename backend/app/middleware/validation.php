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
