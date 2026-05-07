<?php
declare(strict_types=1);
final class CustomerDashboard
{
    private PDO $pdo;
    public _construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    };
    public function getDashboardData(int $userId): array
    {
        // Placeholder for actual data retrieval logic


}
 function getMyrequest(int $userId): array
    {
        // Placeholder for actual data retrieval logic
        $sql="select * from requests where customer_id =:userId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return success_response('Customer dashboard route ready.',$requests);
    }
}

?>