<?php

declare(strict_types=1);

final class MetricsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function totals(): array
    {
        return [
            'total_users' => $this->count('USERS'),
            'total_requests' => $this->count('SERVICE_REQUESTS'),
            'total_offers' => $this->count('OFFERS'),
            'total_reviews' => $this->count('REVIEWS'),
            'total_categories' => $this->count('SERVICE_CATEGORIES'),
        ];
    }

    public function usersByRole(): array
    {
        return $this->groupCount('USERS', 'ROLE', 'role');
    }

    public function requestsByStatus(): array
    {
        return $this->groupCount('SERVICE_REQUESTS', 'STATUS', 'status');
    }

    public function requestsByCategory(): array
    {
        $sql = 'SELECT sc.NAME AS category_name, COUNT(sr.REQUEST_ID) AS count
                FROM SERVICE_CATEGORIES sc
                LEFT JOIN SERVICE_REQUESTS sr ON sr.CATEGORY_ID = sc.CATEGORY_ID
                GROUP BY sc.CATEGORY_ID, sc.NAME
                ORDER BY count DESC, sc.NAME ASC';

        return $this->db->query($sql)->fetchAll();
    }

    private function count(string $table): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    private function groupCount(string $table, string $column, string $alias): array
    {
        $sql = "SELECT {$column} AS {$alias}, COUNT(*) AS count FROM {$table} GROUP BY {$column} ORDER BY count DESC";

        return $this->db->query($sql)->fetchAll();
    }
}
