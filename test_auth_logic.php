<?php

declare(strict_types=1);

require_once __DIR__ . '/backend/app/repositories/DatabaseConnector.php';
require_once __DIR__ . '/backend/app/repositories/UserRepository.php';
require_once __DIR__ . '/backend/app/services/AuthService.php';

// Set up the environment
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "--- Starting Authentication System Test ---\n";

try {
    $db = (new DatabaseConnector(__DIR__ . '/backend/config/mySetting.ini'))->getConnection();
    $userRepo = new UserRepository($db);
    $authService = new AuthService($userRepo);

    $testEmail = 'test_' . time() . '@example.com';
    $testPassword = 'password123';
    $testName = 'Test User';
    $testPhone = '09' . rand(10000000, 99999999);
    $testLocation = 'Addis Ababa';
    $testRole = 'Customer';

    // 1. Test Registration
    echo "1. Testing Registration for: $testEmail\n";
    $regResult = $authService->register([
        'email' => $testEmail,
        'password' => $testPassword,
        'name' => $testName,
        'phone' => $testPhone,
        'location' => $testLocation,
        'role' => $testRole
    ]);

    if ($regResult['success']) {
        echo "✅ Registration Successful! User ID: " . $regResult['user_id'] . "\n";
    } else {
        echo "❌ Registration Failed: " . $regResult['message'] . "\n";
        exit;
    }

    // 2. Test Login
    echo "2. Testing Login with correct credentials...\n";
    $loginResult = $authService->login($testEmail, $testPassword);

    if ($loginResult['success']) {
        echo "✅ Login Successful! Welcome " . $loginResult['user']['name'] . " (" . $loginResult['user']['role'] . ")\n";
    } else {
        echo "❌ Login Failed: " . $loginResult['message'] . "\n";
    }

    // 3. Test Login with WRONG password
    echo "3. Testing Login with WRONG password...\n";
    $wrongLogin = $authService->login($testEmail, 'wrong_password');
    if (!$wrongLogin['success']) {
        echo "✅ Correctly rejected wrong password.\n";
    } else {
        echo "❌ ERROR: Accepted wrong password!\n";
    }

} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
}

echo "--- Test Complete ---\n";
