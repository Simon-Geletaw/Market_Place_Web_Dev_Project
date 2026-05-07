<?php

declare(strict_types=1);

require_once __DIR__ . '/../repositories/RequestRepository.php';

final class RequestService
{
    private RequestRepository $requestRepository;

    public function __construct(RequestRepository $requestRepository)
    {
        $this->requestRepository = $requestRepository;
    }

    public function createRequest(string $customerId, array $data): array
    {
        try {
            $requestId = $this->requestRepository->create($customerId, $data);
            return [
                'success' => true, 
                'message' => 'Service request created successfully',
                'request_id' => $requestId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create request: ' . $e->getMessage()];
        }
    }

    public function getAllPending(): array
    {
        return $this->requestRepository->findAll();
    }
}
