<?php
// convert_to_lead.php (Final AJAX Version for SaaS)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// Set the header to always return JSON, as this is an API endpoint.
header('Content-Type: application/json');

// Default output in case of an early error. Assume failure.
$output = ['success' => false, 'message' => 'An unknown error occurred.'];
http_response_code(400); // Bad Request by default

try {
    // 1. Security: Only allow POST requests.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    
    // 2. Read and decode the JSON payload from the JavaScript fetch call.
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received.');
    }

    // 3. Security: Validate the CSRF token from the JSON payload.
    $submitted_token = $input['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        throw new Exception('Invalid security token. Please refresh the page and try again.');
    }

    // 4. Get session data and validate input.
    $company_id = $input['id'] ?? 0;
    $user_id = $_SESSION['user_id'] ?? 0;
    $team_id = $_SESSION['team_id'] ?? 0;
    $is_superadmin = $_SESSION['is_superadmin'] ?? false;

    if ($company_id <= 0 || $user_id <= 0 || ($team_id <= 0 && !$is_superadmin)) {
        throw new Exception('Invalid company, user, or team ID.');
    }
    
    // If a superadmin doesn't have a team, we can't create a lead. This is a failsafe.
    if ($is_superadmin && !$team_id) {
        throw new Exception('Super Admin must be associated with a primary team to create leads.');
    }

    // 5. Execute the secure, team-scoped INSERT query.
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO leads (company_id, team_id, user_id, assigned_user_id, state) 
         SELECT 
            id,              -- company_id from businesses
            team_id,         -- team_id from businesses
            user_id,         -- original creator's user_id from businesses
            ?,               -- assigned_user_id (the current user who clicked 'Convert')
            state            -- state from businesses
         FROM businesses 
         WHERE id = ? AND team_id = ?"
    );
    
    $stmt->execute([$user_id, $company_id, $team_id]);
    
    // 6. Formulate the correct JSON response.
    if ($stmt->rowCount() > 0) {
        // A new lead was successfully created.
        $output = ['success' => true, 'message' => 'Lead converted successfully.'];
    } else {
        // No rows were inserted. This means the lead already existed (due to the UNIQUE key).
        // This is still a "success" from the user's perspective.
        $output = ['success' => true, 'message' => 'This business is already a lead.'];
    }

    http_response_code(200); // OK

} catch (Exception $e) {
    // If any error occurs, catch it and send it back as a JSON error.
    error_log("Convert to Lead Error for user {$user_id}: " . $e->getMessage());
    $output['message'] = $e->getMessage();
}

echo json_encode($output);