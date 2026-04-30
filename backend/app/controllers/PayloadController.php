<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/validation.php';

final class PayloadController
{
    public function validateDemo(): array
    {
        $input = request_input();
        $body = $input['body'];
        $errors = validate_payload($body, [
            'name' => ['required', 'string'],
        ]);

        if ($errors !== []) {
            return error_response('Validation failed.', ['errors' => $errors], 422);
        }

        return success_response('Payload accepted.', [
            'query' => $input['query'],
            'body' => $body,
        ]);
    }
}
