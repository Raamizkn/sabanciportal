<?php

/**
 * Database Configuration for Sabanci Internship Portal
 * 
 * Database: shadowing
 * Host: pro2-dev.sabanciuniv.edu
 */

class Database {
    private $host = 'pro2-dev.sabanciuniv.edu';
    private $dbname = 'shadowing';
    private $username = 'shadowing'; // Database username
    private $password = 'QT8rvzZF'; // Database password
    private $charset = 'utf8mb4';
    private $conn;
    private $useMockData = false; // Set to true to use mock data instead of database

    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->useMockData) {
            throw new Exception("Using mock data mode");
        }
        
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            // Don't throw exception, let calling code handle fallback
            throw new Exception("Database connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }

    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return ['status' => 'success', 'message' => 'Database connection successful'];
        } catch(Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

// Global database instance
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}

?>

