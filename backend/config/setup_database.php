<?php

/**
 * Database Setup Script
 * Run this script to set up the database schema
 */

require_once __DIR__ . '/database.php';

try {
    // Connect to MySQL server without database to create it
    $dbConfig = new Database();
    
    // Get connection to MySQL server
    $pdo = new PDO(
        "mysql:host=pro2-dev.sabanciuniv.edu;charset=utf8mb4",
        'shadowing',
        'QT8rvzZF'
    );
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS shadowing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database 'shadowing' ready\n";
    
    // Now connect to the shadowing database
    $pdo = new PDO(
        "mysql:host=pro2-dev.sabanciuniv.edu;dbname=shadowing;charset=utf8mb4",
        'shadowing',
        'QT8rvzZF'
    );
    
    // First, drop all existing tables to clean the database
    echo "Cleaning existing tables...\n";
    $existingTables = ['STUDENTS', 'activity_logs', 'messages', 'shadowing_opportunities', 'users'];
    foreach ($existingTables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "  Dropped table: $table\n";
        } catch(PDOException $e) {
            echo "  Warning: Could not drop $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Read and execute schema file
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch(PDOException $e) {
                // Show all errors for debugging
                echo "⚠ Error executing statement: " . $e->getMessage() . "\n";
                echo "   Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "✓ Database schema created successfully\n";
    echo "✓ Sample data inserted\n";
    echo "\n🎉 Database setup complete!\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database credentials in config/database.php\n";
    echo "2. MySQL server is running\n";
    echo "3. Network connectivity to pro2-dev.sabanciuniv.edu\n";
}

?>

