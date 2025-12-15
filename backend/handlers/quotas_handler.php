<?php

// backend/handlers/quotas_handler.php
// Handles company quotas per round

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
$db = getDB();

global $method, $entity, $id, $action, $input;

if ($entity === 'quotas') {
    if ($method === 'GET') {
        $company_id = $_GET['company_id'] ?? null;
        $round_id = $_GET['round_id'] ?? null;
        
        if ($company_id && $round_id) {
            // Get specific company quota for a round
            try {
                $stmt = $db->prepare("
                    SELECT q.*, c.name as company_name, r.name as round_name, r.default_company_quota
                    FROM company_round_quotas q
                    JOIN companies c ON q.company_id = c.id
                    JOIN application_rounds r ON q.round_id = r.id
                    WHERE q.company_id = ? AND q.round_id = ?
                ");
                $stmt->execute([$company_id, $round_id]);
                $quota = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($quota) {
                    // Calculate used quota and add to response
                    $used_quota = get_used_quota($company_id, $round_id);
                    $effective_quota = get_effective_quota($company_id, $round_id);
                    $quota['used_quota'] = $used_quota;
                    $quota['effective_quota'] = $effective_quota;
                    $quota['remaining_quota'] = max(0, $effective_quota - $used_quota);
                    echo json_encode($quota);
                } else {
                    // Return default quota from round with used quota
                    $stmt = $db->prepare("SELECT default_company_quota FROM application_rounds WHERE id = ?");
                    $stmt->execute([$round_id]);
                    $round = $stmt->fetch(PDO::FETCH_ASSOC);
                    $default_quota = $round['default_company_quota'] ?? 10;
                    $used_quota = get_used_quota($company_id, $round_id);
                    echo json_encode([
                        'quota_override' => null, 
                        'default_quota' => $default_quota,
                        'used_quota' => $used_quota,
                        'effective_quota' => $default_quota,
                        'remaining_quota' => max(0, $default_quota - $used_quota)
                    ]);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } elseif ($company_id) {
            // Get all quotas for a company
            requireRole(ROLE_COMPANY);
            if (getCurrentUserId() != $company_id) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            try {
                $stmt = $db->prepare("
                    SELECT q.*, r.name as round_name, r.term_id, r.default_company_quota, t.name as term_name
                    FROM company_round_quotas q
                    JOIN application_rounds r ON q.round_id = r.id
                    JOIN terms t ON r.term_id = t.id
                    WHERE q.company_id = ?
                    ORDER BY r.start_date DESC
                ");
                $stmt->execute([$company_id]);
                $quotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add used quota, effective quota, and remaining quota for each quota
                foreach ($quotas as &$quota) {
                    $used_quota = get_used_quota($company_id, $quota['round_id']);
                    $effective_quota = get_effective_quota($company_id, $quota['round_id']);
                    $quota['used_quota'] = $used_quota;
                    $quota['effective_quota'] = $effective_quota;
                    $quota['remaining_quota'] = max(0, $effective_quota - $used_quota);
                }
                
                echo json_encode($quotas);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } elseif ($round_id) {
            // Get all quotas for a round (admin only)
            requireRole(ROLE_ADMIN);
            try {
                $stmt = $db->prepare("
                    SELECT q.*, c.name as company_name
                    FROM company_round_quotas q
                    JOIN companies c ON q.company_id = c.id
                    WHERE q.round_id = ?
                    ORDER BY c.name
                ");
                $stmt->execute([$round_id]);
                $quotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($quotas);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'company_id or round_id required']);
        }
    } elseif ($method === 'POST') {
        if ($action === 'set') {
            // Set company quota for a round
            if (empty($input['company_id']) || empty($input['round_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'company_id and round_id required']);
                exit;
            }
            
            $company_id = $input['company_id'];
            $round_id = $input['round_id'];
            $quota = $input['quota'] ?? null; // null means use default
            
            // Check permissions: company can set own quota, admin can set any
            $userRole = getCurrentUserRole();
            $currentUserId = getCurrentUserId();
            
            if ($userRole === ROLE_COMPANY && $currentUserId != $company_id) {
                http_response_code(403);
                echo json_encode(['error' => 'You can only set your own quota']);
                exit;
            }
            
            if ($userRole !== ROLE_COMPANY && $userRole !== ROLE_ADMIN) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            try {
                // Check if quota already exists
                $stmt = $db->prepare("SELECT id FROM company_round_quotas WHERE company_id = ? AND round_id = ?");
                $stmt->execute([$company_id, $round_id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing
                    $stmt = $db->prepare("UPDATE company_round_quotas SET quota_override = ? WHERE company_id = ? AND round_id = ?");
                    $stmt->execute([$quota, $company_id, $round_id]);
                } else {
                    // Insert new
                    $stmt = $db->prepare("INSERT INTO company_round_quotas (company_id, round_id, quota_override) VALUES (?, ?, ?)");
                    $stmt->execute([$company_id, $round_id, $quota]);
                }
                
                // Return updated quota info
                $stmt = $db->prepare("
                    SELECT q.*, c.name as company_name, r.name as round_name, r.default_company_quota
                    FROM company_round_quotas q
                    JOIN companies c ON q.company_id = c.id
                    JOIN application_rounds r ON q.round_id = r.id
                    WHERE q.company_id = ? AND q.round_id = ?
                ");
                $stmt->execute([$company_id, $round_id]);
                $quota_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$quota_data) {
                    // Return default
                    $stmt = $db->prepare("SELECT default_company_quota FROM application_rounds WHERE id = ?");
                    $stmt->execute([$round_id]);
                    $round = $stmt->fetch(PDO::FETCH_ASSOC);
                    $default_quota = $round['default_company_quota'] ?? 10;
                    $quota_data = [
                        'quota_override' => null, 
                        'default_quota' => $default_quota,
                        'effective_quota' => $default_quota
                    ];
                }
                
                // Add used quota and remaining quota
                $used_quota = get_used_quota($company_id, $round_id);
                $effective_quota = get_effective_quota($company_id, $round_id);
                $quota_data['used_quota'] = $used_quota;
                $quota_data['effective_quota'] = $effective_quota;
                $quota_data['remaining_quota'] = max(0, $effective_quota - $used_quota);
                
                echo json_encode(['status' => 'success', 'message' => 'Quota updated', 'data' => $quota_data]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to set quota: ' . $e->getMessage()]);
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

// Get effective quota for a company in a round (helper function)
function get_effective_quota($company_id, $round_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT 
                COALESCE(q.quota_override, r.default_company_quota) as effective_quota,
                r.default_company_quota,
                q.quota_override
            FROM application_rounds r
            LEFT JOIN company_round_quotas q ON q.round_id = r.id AND q.company_id = ?
            WHERE r.id = ?
        ");
        $stmt->execute([$company_id, $round_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['effective_quota'] ?? 10; // Default fallback
    } catch (PDOException $e) {
        return 10; // Default fallback
    }
}

// Get used quota (finalized applications) for a company in a round
function get_used_quota($company_id, $round_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            WHERE i.company_id = ? 
            AND a.round_id = ?
            AND (a.status = 'Approved_By_Company' OR a.status = 'Finalized')
        ");
        $stmt->execute([$company_id, $round_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

?>

