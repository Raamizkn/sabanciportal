<?php
/**
 * View all student evaluations in database
 * Visit: http://localhost:8001/view_evaluations.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>View Student Evaluations</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>📊 Student Evaluations Database View</h1>
    
<?php
try {
    $db = getDB();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'student_evaluations'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<div class='error'>❌ Table 'student_evaluations' does not exist yet!<br>
        Please run: <a href='/setup_evaluations_table.php'>Setup Evaluations Table</a></div>";
        exit;
    }
    
    // Get all evaluations
    $stmt = $db->query("
        SELECT 
            se.*,
            s.name as student_name,
            s.email as student_email,
            c.name as company_name
        FROM student_evaluations se
        LEFT JOIN students s ON se.student_id = s.id
        LEFT JOIN companies c ON se.company_id = c.id
        ORDER BY se.created_at DESC
    ");
    
    $evaluations = $stmt->fetchAll();
    $count = count($evaluations);
    
    echo "<div class='info'>📋 Total Evaluations: <strong>$count</strong></div>";
    
    if ($count > 0) {
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>Evaluation ID</th>
            <th>Student</th>
            <th>Company</th>
            <th>Program Satisfaction</th>
            <th>Overall Rating</th>
            <th>Submitted</th>
        </tr>";
        
        foreach ($evaluations as $eval) {
            echo "<tr>";
            echo "<td>{$eval['id']}</td>";
            echo "<td>{$eval['evaluation_id']}</td>";
            echo "<td>{$eval['student_name']}<br><small>{$eval['student_email']}</small></td>";
            echo "<td>{$eval['company_name']}</td>";
            echo "<td>{$eval['program_satisfaction']}/5</td>";
            echo "<td>" . number_format($eval['overall_rating'], 2) . "</td>";
            echo "<td>{$eval['submitted_date']}</td>";
            echo "</tr>";
            
            // Show details in a collapsible row
            echo "<tr><td colspan='7'>";
            echo "<details><summary>View Full Details</summary>";
            echo "<pre>" . print_r($eval, true) . "</pre>";
            echo "</details>";
            echo "</td></tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No evaluations submitted yet.<br>
        Test submitting one: <a href='/test_evaluation_api.php'>Test Evaluation API</a></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
    
    <hr>
    <p><a href="/test_setup.php">← Back to Setup Test</a></p>
    
</body>
</html>

