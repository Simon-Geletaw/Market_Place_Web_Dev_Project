<?php
try {
    $config = parse_ini_file(__DIR__ . '/backend/config/mySetting.ini');
    $dsn = $config['dsn'];
    $username = $config['username'];
    $password = $config['password'];
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create a test user
    $email = 'test@example.com';
    $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
    $role = 'Customer';
    $name = 'Test User';
    $location = 'Test Location';
    $phone = '1234567890';
    $userId = 'test-uuid-123';

    $sql = "INSERT INTO USERS (USER_ID, EMAIL, PASSWORD_HASH, ROLE, LOCATION, NAME, PHONE) 
            VALUES (:user_id, :email, :password_hash, :ROLE, :location, :Name, :PHONE)
            ON DUPLICATE KEY UPDATE NAME = VALUES(NAME)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':ROLE' => $role,
        ':location' => $location,
        ':Name' => $name,
        ':PHONE' => $phone
    ]);
    echo "Test user created successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
