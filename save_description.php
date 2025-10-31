<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$is_superadmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin');

// We expect a JSON POST request
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$business_id = $input['business_id'] ?? 0;
$description = $input['description'] ?? '';

if (!$business_id || empty($description)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit;
}

try {
    // SECURITY: Ensure the user owns this business before updating it
    $sql = "UPDATE businesses SET description = ? WHERE id = ?";
    $params = [$description, $business_id];
    
    if (!$is_superadmin) {
        $sql .= " AND user_id = ?";
        $params[] = $user_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Check if any row was actually updated
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Description saved successfully!']);
    } else {
        // This can happen if the business_id doesn't belong to the user
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this business.']);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}