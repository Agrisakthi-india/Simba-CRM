<?php
// ajax_update_lead_status.php (Final & Secure)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

header('Content-Type: application/json');
http_response_code(500); // Default to an error status

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON received.');
    }

    $lead_id = $input['lead_id'] ?? 0;
    $new_status = $input['new_status'] ?? '';
    $submitted_token = $input['csrf_token'] ?? '';
    $user_id = $_SESSION['user_id'];
    $is_superadmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin');
    
    // Security: Validate CSRF Token
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        http_response_code(403); // Forbidden
        throw new Exception('Invalid CSRF token. Action blocked.');
    }
    
    // Validate Input
    $allowed_statuses = ['Follow-up', 'Connected', 'Committed', 'Not Interested'];
    if (!$lead_id || !in_array($new_status, $allowed_statuses)) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid lead ID or status provided.');
    }

    // Build and Execute Secure, User-Scoped Query
    $sql = "UPDATE leads SET status = ? WHERE id = ?";
    $params = [$new_status, $lead_id];
    
    if (!$is_superadmin) {
        // Critical: Ensure a regular user can only update their own leads
        $sql .= " AND user_id = ?";
        $params[] = $user_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
    } else {
        http_response_code(404); // Not Found
        throw new Exception('Lead not found or you do not have permission to modify it.');
    }

} catch (Exception $e) {
    // Catch any error and return it as a structured JSON response
    error_log("Update Lead Status Error for user {$user_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}