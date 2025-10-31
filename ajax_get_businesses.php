<?php
// ajax_get_businesses.php

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method.');
    }
    
    $city = $_GET['city'] ?? '';
    $date = $_GET['date'] ?? '';
    
    if (!$city || !$date) {
        throw new Exception('City and date are required.');
    }

    $user_id = $_SESSION['user_id'];
    $team_id = $_SESSION['team_id'];
    $account_role = $_SESSION['account_role'];
    $is_superadmin = $_SESSION['is_superadmin'];

    // Get businesses in the selected city with their visit plan status
    $sql = "SELECT 
                b.id,
                b.name,
                b.address,
                b.latitude,
                b.longitude,
                b.city,
                b.district,
                b.state,
                fv.id as visit_id,
                fv.visit_status,
                fv.planned_date,
                CASE WHEN fv.id IS NOT NULL THEN 1 ELSE 0 END as is_planned
            FROM businesses b 
            LEFT JOIN field_visits fv ON (
                b.id = fv.business_id 
                AND fv.user_id = ? 
                AND fv.planned_date = ?
                AND fv.visit_status != 'Cancelled'
            )
            WHERE b.city = ? 
            AND b.latitude IS NOT NULL 
            AND b.longitude IS NOT NULL";
    
    $params = [$user_id, $date, $city];

    // Apply permission scopes
    if (!$is_superadmin) {
        $sql .= " AND b.team_id = ?";
        $params[] = $team_id;
        
        if ($account_role === 'member') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM leads l 
                WHERE l.company_id = b.id 
                AND l.assigned_user_id = ?
            )";
            $params[] = $user_id;
        }
    }
    
    $sql .= " ORDER BY b.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug logging
    error_log("ajax_get_businesses.php - User: $user_id, City: $city, Date: $date");
    error_log("Found " . count($businesses) . " businesses");
    $planned_count = array_filter($businesses, function($b) { return $b['is_planned'] == 1; });
    error_log("Planned businesses: " . count($planned_count));

    echo json_encode([
        'success' => true,
        'data' => $businesses,
        'message' => count($businesses) . ' businesses found',
        'debug' => [
            'total_businesses' => count($businesses),
            'planned_businesses' => count($planned_count),
            'user_id' => $user_id,
            'city' => $city,
            'date' => $date
        ]
    ]);

} catch (Exception $e) {
    error_log("ajax_get_businesses.php Error for user {$user_id}: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>