<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/repositories/DatabaseConnector.php';
require_once __DIR__ . '/../app/repositories/UserRepository.php';

try {
    // 1. Setup Connection (using the existing config)
    $connector = new DatabaseConnector(__DIR__ . '/../config/mySetting.ini',);
    $pdo = $connector->getConnection();
    $repository = new UserRepository($pdo);

    echo "--- Starting UserRepository Test ---\n";

    // 2. Test Data
    $email = "test_" . time() . "@example.com";
    $password = password_hash("password123", PASSWORD_BCRYPT);
    $name = "Test User";
    $location = "Addis Ababa";
    $phone = "09" . rand(10000000, 99999999);

    // 3. Test Create User (Customer)
    echo "Testing createUser (Customer)...\n";
    $userId = $repository->createUser($email, $password, UserRole::Customer, $name, $location, $phone);
    echo "User created with ID: $userId\n";

    // 4. Test findByEmail
    echo "Testing findByEmail...\n";
    $exists = $repository->findByEmail($email);
    echo "findByEmail ($email): " . ($exists ? "Found" : "Not Found") . "\n";

    // 5. Test findByPhone
    echo "Testing findByPhone...\n";
    $existsPhone = $repository->findByPhone($phone);
    echo "findByPhone ($phone): " . ($existsPhone ? "Found" : "Not Found") . "\n";

    // 6. Test Duplicate Email
    echo "Testing duplicate email check...\n";
    try {
        $repository->createUser($email, "another_pass", UserRole::Provider, "Another Name", "Another Loc", "0900000000");
        echo "FAIL: Duplicate email allowed!\n";
    } catch (Exception $e) {
        echo "SUCCESS: Duplicate email caught: " . $e->getMessage() . "\n";
    }

    echo "--- UserRepository Test Completed ---\n";

} catch (Exception $e) {
    echo "ERROR during test: " . $e->getMessage() . "\n";
}
