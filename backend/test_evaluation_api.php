<?php
/**
 * Test Student Evaluation API
 * Visit: http://localhost:8001/test_evaluation_api.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Student Evaluation API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🧪 Test Student Evaluation API</h1>
    
    <div class="test info">
        <strong>Prerequisites:</strong><br>
        1. Student evaluations table must be created<br>
        2. You need at least one student (student_id) and one application (application_id)<br>
        3. Backend server must be running on port 8001
    </div>
    
    <h2>Test 1: Submit a Test Evaluation</h2>
    <button onclick="submitTestEvaluation()">📝 Submit Test Evaluation</button>
    <div id="submit-result"></div>
    
    <h2>Test 2: Get Student Evaluations</h2>
    <label>Student ID: <input type="number" id="student_id" value="1"></label>
    <button onclick="getEvaluations()">📋 Get Evaluations</button>
    <div id="list-result"></div>
    
    <h2>Test 3: Check Database Tables</h2>
    <button onclick="checkTables()">🔍 Check Tables</button>
    <div id="tables-result"></div>
    
    <script>
        const API_BASE = 'http://localhost:8001';
        
        async function submitTestEvaluation() {
            const resultDiv = document.getElementById('submit-result');
            resultDiv.innerHTML = '<div class="test info">⏳ Submitting test evaluation...</div>';
            
            const testData = {
                program_satisfaction: 5,
                future_participation: 5,
                consultant_care: 'yes',
                consultant_satisfaction: 5,
                institution_selection: 5,
                institution_recommendation: 5,
                benefits: 'Professional Development, Networking, Hands-on Experience',
                department: 'Software Engineering',
                problems_encountered: 'None - everything went smoothly!',
                additional_feedback: 'Great internship experience. Highly recommend!'
            };
            
            try {
                // First check if we have applications
                const appsResponse = await fetch(`${API_BASE}/index.php?entity=applications&student_id=1`);
                const appsData = await appsResponse.json();
                
                if (!appsData.data || appsData.data.length === 0) {
                    resultDiv.innerHTML = '<div class="test error">❌ No applications found for student_id=1. You need at least one application to test evaluations.</div>';
                    return;
                }
                
                const applicationId = appsData.data[0].id;
                
                // Submit evaluation
                const response = await fetch(`${API_BASE}/index.php?entity=student_evaluations&action=submit&application_id=${applicationId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    resultDiv.innerHTML = `<div class="test success">✅ Success! Evaluation submitted.<br>Evaluation ID: ${result.evaluation_id}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="test error">❌ Error: ${result.error || 'Unknown error'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="test error">❌ Network Error: ${error.message}</div>`;
            }
        }
        
        async function getEvaluations() {
            const studentId = document.getElementById('student_id').value;
            const resultDiv = document.getElementById('list-result');
            resultDiv.innerHTML = '<div class="test info">⏳ Fetching evaluations...</div>';
            
            try {
                const response = await fetch(`${API_BASE}/index.php?entity=student_evaluations&action=list&student_id=${studentId}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    resultDiv.innerHTML = `<div class="test success">✅ Found ${result.data.length} evaluation(s)</div>`;
                    if (result.data.length > 0) {
                        resultDiv.innerHTML += '<pre>' + JSON.stringify(result.data, null, 2) + '</pre>';
                    }
                } else {
                    resultDiv.innerHTML = `<div class="test error">❌ Error: ${result.error || 'Unknown error'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="test error">❌ Network Error: ${error.message}</div>`;
            }
        }
        
        async function checkTables() {
            const resultDiv = document.getElementById('tables-result');
            resultDiv.innerHTML = '<div class="test info">⏳ Checking database...</div>';
            
            try {
                // Check students
                const studentsResponse = await fetch(`${API_BASE}/index.php?entity=students`);
                const students = await studentsResponse.json();
                
                // Check applications
                const appsResponse = await fetch(`${API_BASE}/index.php?entity=applications`);
                const apps = await appsResponse.json();
                
                let html = '<div class="test success">';
                html += `<strong>Database Check:</strong><br><br>`;
                html += `📊 Students: ${students.data ? students.data.length : 0}<br>`;
                html += `📋 Applications: ${apps.data ? apps.data.length : 0}<br>`;
                html += '</div>';
                
                if (students.data && students.data.length > 0) {
                    html += '<h3>Sample Student:</h3><pre>' + JSON.stringify(students.data[0], null, 2) + '</pre>';
                }
                
                if (apps.data && apps.data.length > 0) {
                    html += '<h3>Sample Application:</h3><pre>' + JSON.stringify(apps.data[0], null, 2) + '</pre>';
                }
                
                resultDiv.innerHTML = html;
            } catch (error) {
                resultDiv.innerHTML = `<div class="test error">❌ Network Error: ${error.message}</div>`;
            }
        }
    </script>
    
    <hr>
    <p><a href="/test_setup.php">← Back to Setup Test</a></p>
    
</body>
</html>

