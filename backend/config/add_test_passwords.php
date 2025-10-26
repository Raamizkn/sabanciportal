<?php

/**
 * Add test passwords for demo users
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDB();
    
    // Test passwords for all users
    $password = 'password123'; // Simple test password
    
    // Update student passwords
    $stmt = $db->prepare("UPDATE students SET password_hash = ? WHERE email = ?");
    
    $students = [
        'student@example.com',
        'jane.smith@example.com',
        'ahmet.yilmaz@example.com'
    ];
    
    foreach ($students as $email) {
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
        echo "✓ Updated password for student: $email\n";
    }
    
    // Update company passwords
    $stmt = $db->prepare("UPDATE companies SET password_hash = ? WHERE email = ?");
    
    $companies = [
        'company@example.com',
        'innovate@example.com',
        'techsolutions@example.com'
    ];
    
    foreach ($companies as $email) {
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
        echo "✓ Updated password for company: $email\n";
    }
    
    // Add admin user if doesn't exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO admin_users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'admin@example.com',
            password_hash($password, PASSWORD_DEFAULT),
            'System Administrator'
        ]);
        echo "✓ Created admin user: admin@example.com\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    echo "\n✅ All test passwords set to: $password\n";
    echo "\nTest Credentials:\n";
    echo "Student: student@example.com / $password\n";
    echo "Company: company@example.com / $password\n";
    echo "Admin: admin@example.com / $password\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>

