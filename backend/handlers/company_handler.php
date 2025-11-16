<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

$db = getDB();

function getCompanyById($company_id) {
    global $db;
    $stmt = $db->prepare("SELECT id, name, email, industry, website, phone, address, description, logo FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function ensureCompanyAccess($target_company_id) {
    $role = getCurrentUserRole();
    $current_id = getCurrentUserId();

    if ($role === ROLE_ADMIN) {
        return true;
    }

    if ($role === ROLE_COMPANY && (int)$current_id === (int)$target_company_id) {
        return true;
    }

    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

if ($entity === 'companies') {
    global $method, $action, $input;
    requireAuth();

    if ($method === 'GET') {
        if ($action === 'get_profile') {
            $requested_company_id = $_GET['company_id'] ?? getCurrentUserId();
            if (!$requested_company_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Company ID is required']);
                exit;
            }

            ensureCompanyAccess($requested_company_id);
            $company = getCompanyById($requested_company_id);

            if (!$company) {
                http_response_code(404);
                echo json_encode(['error' => 'Company not found']);
                exit;
            }

            echo json_encode(['status' => 'success', 'company' => $company]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported companies action']);
        }
    }
    elseif ($method === 'POST') {
        if ($action === 'update') {
            requireRole(ROLE_COMPANY);
            $company_id = getCurrentUserId();

            $allowed_fields = ['name', 'industry', 'website', 'phone', 'address', 'description', 'logo'];
            $updates = [];
            $values = [];

            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }

            $values[] = $company_id;

            try {
                $stmt = $db->prepare("UPDATE companies SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?");
                $stmt->execute($values);

                $company = getCompanyById($company_id);
                echo json_encode(['status' => 'success', 'company' => $company]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update company profile: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported companies action']);
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed for companies entity']);
    }

    exit;
}

?>
