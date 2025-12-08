<?php

// backend/handlers/rounds_handler.php
// Handles application rounds and company quotas

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
$db = getDB();

global $method, $entity, $id, $action, $input;

if ($entity === 'rounds') {
    if ($method === 'GET') {
        if ($id !== null) {
            // Get specific round
            requireRole(ROLE_ADMIN);
            try {
                $stmt = $db->prepare("
                    SELECT r.*, t.name as term_name, t.start_date as term_start, t.end_date as term_end
                    FROM application_rounds r
                    JOIN terms t ON r.term_id = t.id
                    WHERE r.id = ?
                ");
                $stmt->execute([$id]);
                $round = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($round) {
                    echo json_encode($round);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Round not found']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            // Get all rounds (optionally filtered by term_id)
            $term_id = $_GET['term_id'] ?? null;
            try {
                if ($term_id) {
                    $stmt = $db->prepare("
                        SELECT r.*, t.name as term_name
                        FROM application_rounds r
                        JOIN terms t ON r.term_id = t.id
                        WHERE r.term_id = ?
                        ORDER BY r.start_date DESC
                    ");
                    $stmt->execute([$term_id]);
                } else {
                    $stmt = $db->query("
                        SELECT r.*, t.name as term_name
                        FROM application_rounds r
                        JOIN terms t ON r.term_id = t.id
                        ORDER BY r.start_date DESC
                    ");
                }
                $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($rounds);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        }
    } elseif ($method === 'POST') {
        requireRole(ROLE_ADMIN);
        
        if ($action === 'create') {
            // Create new round
            if (empty($input['term_id']) || empty($input['name']) || empty($input['start_date']) || empty($input['end_date'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: term_id, name, start_date, end_date']);
                exit;
            }
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO application_rounds 
                    (term_id, name, start_date, end_date, max_applications_per_student, default_company_quota, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['term_id'],
                    $input['name'],
                    $input['start_date'],
                    $input['end_date'],
                    $input['max_applications_per_student'] ?? 3,
                    $input['default_company_quota'] ?? 10,
                    $input['is_active'] ?? true
                ]);
                
                $round_id = $db->lastInsertId();
                $stmt = $db->prepare("SELECT r.*, t.name as term_name FROM application_rounds r JOIN terms t ON r.term_id = t.id WHERE r.id = ?");
                $stmt->execute([$round_id]);
                $round = $stmt->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(201);
                echo json_encode(['status' => 'success', 'message' => 'Round created successfully', 'data' => $round]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create round: ' . $e->getMessage()]);
            }
        } elseif ($id !== null && $action === 'update') {
            // Update round
            try {
                $updates = [];
                $params = [];
                
                if (isset($input['name'])) {
                    $updates[] = 'name = ?';
                    $params[] = $input['name'];
                }
                if (isset($input['start_date'])) {
                    $updates[] = 'start_date = ?';
                    $params[] = $input['start_date'];
                }
                if (isset($input['end_date'])) {
                    $updates[] = 'end_date = ?';
                    $params[] = $input['end_date'];
                }
                if (isset($input['max_applications_per_student'])) {
                    $updates[] = 'max_applications_per_student = ?';
                    $params[] = $input['max_applications_per_student'];
                }
                if (isset($input['default_company_quota'])) {
                    $updates[] = 'default_company_quota = ?';
                    $params[] = $input['default_company_quota'];
                }
                if (isset($input['is_active'])) {
                    $updates[] = 'is_active = ?';
                    $params[] = $input['is_active'] ? 1 : 0;
                }
                
                if (empty($updates)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No fields to update']);
                    exit;
                }
                
                $params[] = $id;
                $stmt = $db->prepare("UPDATE application_rounds SET " . implode(', ', $updates) . " WHERE id = ?");
                $stmt->execute($params);
                
                $stmt = $db->prepare("SELECT r.*, t.name as term_name FROM application_rounds r JOIN terms t ON r.term_id = t.id WHERE r.id = ?");
                $stmt->execute([$id]);
                $round = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'message' => 'Round updated successfully', 'data' => $round]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update round: ' . $e->getMessage()]);
            }
        } elseif ($id !== null && $action === 'toggle_active') {
            // Toggle round active status
            try {
                $stmt = $db->prepare("UPDATE application_rounds SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$id]);
                
                $stmt = $db->prepare("SELECT r.*, t.name as term_name FROM application_rounds r JOIN terms t ON r.term_id = t.id WHERE r.id = ?");
                $stmt->execute([$id]);
                $round = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'message' => 'Round status toggled', 'data' => $round]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to toggle round: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    exit;
}

// Get active round for a given date (helper function)
function get_active_round($date = null) {
    global $db;
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    try {
        $stmt = $db->prepare("
            SELECT r.*, t.name as term_name
            FROM application_rounds r
            JOIN terms t ON r.term_id = t.id
            WHERE r.is_active = 1 
            AND r.start_date <= ? 
            AND r.end_date >= ?
            ORDER BY r.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$date, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Get term for a given date (helper function)
function get_term_for_date($date = null) {
    global $db;
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    try {
        // First, try to find an active term that contains the date
        $stmt = $db->prepare("
            SELECT * FROM terms
            WHERE is_active = 1 
            AND start_date <= ? AND end_date >= ?
            ORDER BY start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$date, $date]);
        $term = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no active term found for the date, try to find any active term
        if (!$term) {
            $stmt = $db->prepare("
                SELECT * FROM terms
                WHERE is_active = 1
                ORDER BY start_date DESC
                LIMIT 1
            ");
            $stmt->execute();
            $term = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // If still no term, try to find any term that contains the date (fallback)
        if (!$term) {
            $stmt = $db->prepare("
                SELECT * FROM terms
                WHERE start_date <= ? AND end_date >= ?
                ORDER BY start_date DESC
                LIMIT 1
            ");
            $stmt->execute([$date, $date]);
            $term = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $term;
    } catch (PDOException $e) {
        return null;
    }
}

?>

