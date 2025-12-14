<?php
/**
 * Setup Script: Create student_evaluations table
 * Visit: http://localhost:8001/setup_evaluations_table.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Student Evaluations Table</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 15px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>📊 Setup Student Evaluations Table</h1>
    
<?php
try {
    $db = getDB();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/config/add_student_evaluations_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    echo "<div class='info'>🔧 Creating student_evaluations table...</div>";
    
    $db->exec($sql);
    
    echo "<div class='success'>✅ <strong>SUCCESS!</strong> The student_evaluations table has been created.</div>";
    
    // Verify table exists
    $stmt = $db->query("SHOW TABLES LIKE 'student_evaluations'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<div class='info'>
            <strong>✅ Table verified!</strong><br><br>
            You can now test student evaluations:<br>
            1. Go to: <a href='http://localhost:8000/student/student-evaluations.html'>http://localhost:8000/student/student-evaluations.html</a><br>
            2. Fill out the evaluation form<br>
            3. Submit and see it appear in the list!
        </div>";
        
        // Show table structure
        echo "<h2>📋 Table Structure:</h2>";
        $stmt = $db->query("DESCRIBE student_evaluations");
        $columns = $stmt->fetchAll();
        
        echo "<pre>";
        echo str_pad("Field", 30) . str_pad("Type", 30) . str_pad("Key", 10) . "\n";
        echo str_repeat("-", 70) . "\n";
        foreach ($columns as $col) {
            echo str_pad($col['Field'], 30) . 
                 str_pad($col['Type'], 30) . 
                 str_pad($col['Key'], 10) . "\n";
        }
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>Troubleshooting:</strong><br>
    1. Make sure the database connection is working<br>
    2. Check that you have the correct database permissions<br>
    3. Verify the SQL file exists at: backend/config/add_student_evaluations_table.sql</div>";
}
?>
    
    <hr>
    <p><a href="/test_setup.php">← Back to Setup Test</a></p>
    
</body>
</html>

