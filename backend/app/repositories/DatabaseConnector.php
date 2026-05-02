<?php

class DatabaseConnector
{
    private PDO $dbConnection;

    public function __construct($file = 'config/mySetting.ini')
    {
        try {
            $config = parse_ini_file($file);
            if (!$config) {
                throw new Exception('unable to open the file');
            }

            $dsn = $config['dsn'];
            $username = $config['username'];
            $password = $config['password'];

            $this->dbConnection = new PDO($dsn, $username, $password);
            $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    public function getConnection(): PDO
    {
        try {
            return $this->dbConnection;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception('Failed to get database connection');
        }
    }
}

?>