<?php

declare(strict_types=1);

final class RequestRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(string $customerId, array $data): string
    {
        $requestId = bin2hex(random_bytes(16)); // Generate UUID compatible ID
        
        $sql = "INSERT INTO service_requests (
                    REQUEST_ID, CUSTOMER_ID, TITLE, CATEGORY, 
                    DESCRIPTION, BUDGET, LOCATION, STATUS
                ) VALUES (
                    :id, :customer_id, :title, :category, 
                    :description, :budget, :location, 'PENDING'
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $requestId,
            'customer_id' => $customerId,
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
            'budget' => $data['budget'],
            'location' => $data['location']
        ]);

        return $requestId;
    }

    public function findAll(): array
    {
        $sql = "SELECT r.*, u.NAME as CUSTOMER_NAME 
                FROM service_requests r
                JOIN users u ON r.CUSTOMER_ID = u.USER_ID
                WHERE r.STATUS = 'PENDING'
                ORDER BY r.CREATED_AT DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
