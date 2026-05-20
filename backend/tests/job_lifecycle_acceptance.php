<?php

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/app/repositories/DatabaseConnector.php';
require_once $root . '/app/repositories/UserRepository.php';
require_once $root . '/app/repositories/CategoryRepository.php';
require_once $root . '/app/repositories/RequestRepository.php';
require_once $root . '/app/repositories/OfferRepository.php';
require_once $root . '/app/repositories/ReviewRepository.php';
require_once $root . '/app/repositories/AuditLogRepository.php';
require_once $root . '/app/repositories/StatusHistoryRepository.php';
require_once $root . '/app/repositories/NotificationRepository.php';
require_once $root . '/app/services/RequestService.php';
require_once $root . '/app/services/OfferService.php';
require_once $root . '/app/services/ReviewService.php';

$pdo = (new DatabaseConnector($root . '/config/mySetting.ini'))->getConnection();

$requests = new RequestRepository($pdo);
$categories = new CategoryRepository($pdo);
$statusHistory = new StatusHistoryRepository($pdo);
$audit = new AuditLogRepository($pdo);
$offers = new OfferRepository($pdo);
$reviews = new ReviewRepository($pdo);
$users = new UserRepository($pdo);
$notifications = table_exists($pdo, 'NOTIFICATIONS') ? new NotificationRepository($pdo) : null;

$requestService = new RequestService($requests, $categories, $statusHistory, $audit, $offers);
$offerService = new OfferService($offers, $requests, $statusHistory, $audit, $notifications);
$reviewService = new ReviewService($reviews, $requests, $offers, $users, $audit, $statusHistory);

$pdo->beginTransaction();

try {
    $customerId = insert_user($pdo, 'Customer');
    $otherCustomerId = insert_user($pdo, 'Customer');
    $providerAId = insert_user($pdo, 'Provider');
    $providerBId = insert_user($pdo, 'Provider');
    $providerCId = insert_user($pdo, 'Provider');
    $categoryId = insert_category($pdo);

    $created = $requestService->createRequest($customerId, [
        'category_id' => $categoryId,
        'title' => 'Lifecycle acceptance request',
        'description' => 'Lifecycle acceptance request for plumbing repair.',
        'budget' => 1200,
        'location' => 'Bole',
        'preferred_date' => '2026-06-15',
    ]);
    assert_true($created['success'], 'request creation succeeds');
    $requestId = $created['request_id'];

    $firstOffer = $offerService->submitOffer($providerAId, [
        'request_id' => $requestId,
        'price' => 950,
        'message' => 'I can handle this repair tomorrow.',
    ]);
    assert_true($firstOffer['success'], 'first offer succeeds');
    assert_request_status($pdo, $requestId, 'Negotiating', 'first offer moves request to Negotiating');

    $duplicate = $offerService->submitOffer($providerAId, [
        'request_id' => $requestId,
        'price' => 900,
        'message' => 'Duplicate offer attempt.',
    ]);
    assert_same(409, $duplicate['http_code'] ?? null, 'duplicate provider offer returns 409');

    $secondOffer = $offerService->submitOffer($providerBId, [
        'request_id' => $requestId,
        'price' => 875,
        'message' => 'I can do this with quality materials.',
    ]);
    assert_true($secondOffer['success'], 'second provider offer succeeds');

    $counter = $offerService->counterOffer($secondOffer['offer_id'], $customerId, [
        'counter_price' => 800,
        'counter_message' => 'Can you meet this price?',
    ]);
    assert_true($counter['success'], 'customer can counter pending offer');
    assert_offer_status($pdo, $secondOffer['offer_id'], 'Countered', 'counter moves offer to Countered');

    $rejected = $offerService->rejectOffer($secondOffer['offer_id'], $customerId);
    assert_true($rejected['success'], 'customer can reject countered offer');
    assert_offer_status($pdo, $secondOffer['offer_id'], 'Rejected', 'reject moves offer to Rejected');
    assert_request_status($pdo, $requestId, 'Negotiating', 'reject keeps request Negotiating');

    $winningOffer = $offerService->submitOffer($providerCId, [
        'request_id' => $requestId,
        'price' => 825,
        'message' => 'I am available on the requested date.',
    ]);
    assert_true($winningOffer['success'], 'winning provider offer succeeds');

    $nonOwnerAccept = $offerService->acceptOffer($winningOffer['offer_id'], $otherCustomerId, [
        'location' => 'Bole',
        'date' => '2026-06-20',
    ]);
    assert_same(403, $nonOwnerAccept['http_code'] ?? null, 'non-owner cannot accept offer');

    $accepted = $offerService->acceptOffer($winningOffer['offer_id'], $customerId, [
        'location' => 'Bole',
        'date' => '2026-06-20',
    ]);
    assert_true($accepted['success'], 'owner can accept pending offer');
    assert_request_status($pdo, $requestId, 'Assigned', 'accept moves request to Assigned');
    assert_offer_status($pdo, $winningOffer['offer_id'], 'Accepted', 'winning offer becomes Accepted');
    assert_offer_status($pdo, $firstOffer['offer_id'], 'Rejected', 'other pending offer becomes Rejected');

    $lateOffer = $offerService->submitOffer($providerBId, [
        'request_id' => $requestId,
        'price' => 700,
        'message' => 'Late offer after assignment.',
    ]);
    assert_same(422, $lateOffer['http_code'] ?? null, 'offers on assigned request return 422');

    $wrongProviderComplete = $requestService->markCompleted($requestId, $providerAId, null);
    assert_same(403, $wrongProviderComplete['http_code'] ?? null, 'non-assigned provider cannot complete');

    $completed = $requestService->markCompleted($requestId, $providerCId, '/uploads/completions/test.jpg');
    assert_true($completed['success'], 'assigned provider can complete');
    assert_request_status($pdo, $requestId, 'Completed', 'completion moves request to Completed');

    $nonOwnerReview = $reviewService->submitReview($otherCustomerId, [
        'request_id' => $requestId,
        'rating' => 5,
        'comment' => 'Not my job.',
    ]);
    assert_same(403, $nonOwnerReview['http_code'] ?? null, 'non-owner cannot review');

    $reviewed = $reviewService->submitReview($customerId, [
        'request_id' => $requestId,
        'rating' => 5,
        'comment' => 'Excellent work.',
    ]);
    assert_true($reviewed['success'], 'owner can review completed request');
    assert_request_status($pdo, $requestId, 'Reviewed', 'review moves request to Reviewed');

    $duplicateReview = $reviewService->submitReview($customerId, [
        'request_id' => $requestId,
        'rating' => 4,
        'comment' => 'Second review attempt.',
    ]);
    assert_same(409, $duplicateReview['http_code'] ?? null, 'duplicate review returns 409');

    $provider = $users->findById($providerCId);
    assert_same(1, (int) $provider['TOTAL_REVIEWS'], 'provider total reviews updates');
    assert_same(5.0, (float) $provider['RATING_AVERAGE'], 'provider rating average updates');

    echo "Job lifecycle acceptance checks passed.\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

function insert_user(PDO $pdo, string $role): string
{
    $id = uuid();
    $email = strtolower($role) . '-' . bin2hex(random_bytes(5)) . '@example.test';

    $stmt = $pdo->prepare('INSERT INTO USERS (USER_ID, EMAIL, PASSWORD_HASH, ROLE, NAME, PHONE, LOCATION, IS_VERIFIED)
        VALUES (:id, :email, :hash, :role, :name, :phone, :location, :verified)');
    $stmt->execute([
        ':id' => $id,
        ':email' => $email,
        ':hash' => password_hash('Password123!', PASSWORD_BCRYPT),
        ':role' => $role,
        ':name' => $role . ' Test User',
        ':phone' => '09' . random_int(10000000, 99999999),
        ':location' => 'Bole',
        ':verified' => $role === 'Provider' ? 1 : 0,
    ]);

    return $id;
}

function insert_category(PDO $pdo): string
{
    $id = uuid();
    $stmt = $pdo->prepare('INSERT INTO SERVICE_CATEGORIES (CATEGORY_ID, NAME, DESCRIPTION, ICON, IS_ACTIVE)
        VALUES (:id, :name, :description, :icon, TRUE)');
    $stmt->execute([
        ':id' => $id,
        ':name' => 'Lifecycle Test ' . bin2hex(random_bytes(4)),
        ':description' => 'Temporary lifecycle acceptance category',
        ':icon' => 'test',
    ]);

    return $id;
}

function assert_request_status(PDO $pdo, string $requestId, string $expected, string $message): void
{
    $stmt = $pdo->prepare('SELECT STATUS FROM SERVICE_REQUESTS WHERE REQUEST_ID = :id');
    $stmt->execute([':id' => $requestId]);
    assert_same($expected, $stmt->fetchColumn(), $message);
}

function assert_offer_status(PDO $pdo, string $offerId, string $expected, string $message): void
{
    $stmt = $pdo->prepare('SELECT STATUS FROM OFFERS WHERE OFFER_ID = :id');
    $stmt->execute([':id' => $offerId]);
    assert_same($expected, $stmt->fetchColumn(), $message);
}

function assert_true(bool $actual, string $message): void
{
    if (!$actual) {
        throw new RuntimeException($message);
    }
}

function assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')');
    }
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1');
    $stmt->execute([':table' => $table]);
    return (bool) $stmt->fetchColumn();
}

function uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
