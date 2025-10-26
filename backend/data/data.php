<?php

/**
 * Data Loader - Loads data from database
 * Falls back to mock data if database connection fails
 */

require_once __DIR__ . '/../config/database.php';

$students = [];
$internships = [];
$applications = [];
$documents = [];
$terms = [];
$companies = [];

// Try to load from database
try {
    $db = getDB();
    
    // Load Students
    $stmt = $db->query("SELECT * FROM students WHERE is_active = 1");
    $studentsData = $stmt->fetchAll();
    $students = [];
    foreach ($studentsData as $row) {
        $students[$row['id']] = $row;
    }
    
    // Load Companies
    $stmt = $db->query("SELECT * FROM companies WHERE is_active = 1");
    $companiesData = $stmt->fetchAll();
    $companies = [];
    foreach ($companiesData as $row) {
        $companies[$row['id']] = $row;
    }
    
    // Load Internships
    $stmt = $db->query("SELECT * FROM internships WHERE status != 'Deleted'");
    $internshipsData = $stmt->fetchAll();
    $internships = [];
    foreach ($internshipsData as $row) {
        $internships[$row['id']] = $row;
    }
    
    // Load Applications
    $stmt = $db->query("SELECT * FROM applications");
    $applicationsData = $stmt->fetchAll();
    $applications = [];
    foreach ($applicationsData as $row) {
        $applications[$row['application_id']] = $row;
    }
    
    // Load Documents
    $stmt = $db->query("SELECT * FROM documents");
    $documentsData = $stmt->fetchAll();
    $documents = [];
    foreach ($documentsData as $row) {
        $documents[$row['document_id']] = $row;
    }
    
    // Load Terms
    $stmt = $db->query("SELECT * FROM terms");
    $termsData = $stmt->fetchAll();
    $terms = [];
    foreach ($termsData as $row) {
        $terms[$row['id']] = $row;
    }
    
} catch(Exception $e) {
    // Fallback to mock data if database fails
    error_log("Database load failed: " . $e->getMessage());
    
    // Load from mock_data.php as fallback
    if (file_exists(__DIR__ . '/mock_data.php')) {
        require_once __DIR__ . '/mock_data.php';
    }
}

?>

