<?php
/**
 * Migration: Add file_content column to documents table
 * This allows storing documents directly in the database instead of filesystem
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDB();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM documents LIKE 'file_content'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'file_content' already exists. Skipping migration.\n";
        exit(0);
    }
    
    // Add file_content column as LONGBLOB
    $db->exec("ALTER TABLE documents ADD COLUMN file_content LONGBLOB NULL AFTER file_path");
    
    echo "✓ Successfully added 'file_content' column to documents table\n";
    echo "Documents can now be stored directly in the database.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

