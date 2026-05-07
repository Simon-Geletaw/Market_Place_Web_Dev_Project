<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/RequestService.php';

final class RequestController
{
    private RequestService $requestService;

    public function __construct(PDO $db)
    {
        $repo = new RequestRepository($db);
        $this->requestService = new RequestService($repo);
    }

    public function browse(): array
    {
        $requests = $this->requestService->getAllPending();
        return success_response('Available requests retrieved', ['requests' => $requests]);
    }

    public function create(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title']) || empty($input['category'])) {
            return error_response('Missing required fields: title and category');
        }

        // Get customer ID from session (enforced by middleware)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $customerId = $_SESSION['user_id'];

        $result = $this->requestService->createRequest($customerId, $input);
        
        return $result['success'] 
            ? success_response($result['message'], ['request_id' => $result['request_id']], 201)
            : error_response($result['message']);
    }

    public function show(): array
    {
        return success_response('Request detail route ready.', [
            'params' => route_params(),
        ]);
    }

    public function markCompleted(): array
    {
        return success_response('Request completion action ready.', [
            'params' => route_params(),
        ]);
    }
}
