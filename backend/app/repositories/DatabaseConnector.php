<?php

declare(strict_types=1);

/**
 * DatabaseConnector
 *
 * Singleton-style wrapper around PDO.
 * Reads credentials from config/mySetting.ini.
 * Column names in MySQL schema are UPPER_CASE — PDO FETCH_ASSOC returns them uppercase.
 */
final class DatabaseConnector
{
    private static ?PDO $instance = null;
    private PDO $dbConnection;

    public function __construct(string $file = __DIR__ . '/../../config/mySetting.ini')
    {
        if (self::$instance !== null) {
            $this->dbConnection = self::$instance;
            return;
        }

        $config = parse_ini_file($file);
        if ($config === false || empty($config['dsn'])) {
            throw new RuntimeException('Database configuration file missing or invalid: ' . $file);
        }

        try {
            $pdo = new PDO(
                $config['dsn'],
                $config['username'] ?? '',
                $config['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_CASE               => PDO::CASE_UPPER,
                ]
            );
            self::$instance = $pdo;
            $this->dbConnection = $pdo;
        } catch (PDOException $e) {
            error_log('[DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Check configuration.');
        }
    }

    public function getConnection(): PDO
    {
        return $this->dbConnection;
    }

    /**
     * Reset singleton (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}