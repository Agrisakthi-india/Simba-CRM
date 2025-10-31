<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $lead_id = $input['lead_id'] ?? 0;
    $to_email = $input['to_email'] ?? ''; // In a real app, you'd add an email field to the businesses table
    $subject = $input['subject'] ?? 'A message from your CRM';
    $body = $input['body'] ?? '';

    if (!$lead_id || empty($to_email) || empty($body)) {
        throw new Exception("Missing required email data.");
    }

    // Fetch user's Zeptomail credentials and lead info securely
    $profile = $pdo->query("SELECT zeptomail_token, zeptomail_from_email, contact_person FROM company_profile WHERE user_id = ".(int)$user_id)->fetch(PDO::FETCH_ASSOC);
    $lead = $pdo->query("SELECT b.name as company_name FROM leads l JOIN businesses b ON l.company_id=b.id WHERE l.id = ".(int)$lead_id." AND l.user_id = ".(int)$user_id)->fetch(PDO::FETCH_ASSOC);

    if (!$profile || empty($profile['zeptomail_token']) || empty($profile['zeptomail_from_email'])) {
        throw new Exception("Zeptomail is not configured in your integrations settings.");
    }
    if (!$lead) {
        throw new Exception("Lead not found or permission denied.");
    }

    // Replace placeholders
    $final_body = str_replace(['[Business Name]', '[Contact Person]'], [$lead['company_name'], $profile['contact_person']], $body);

    // --- ZOHO ZEPTOMAIL API CALL ---
    $api_url = 'https://api.zeptomail.com/v1.1/email';
    $data = [
        "from" => ["address" => $profile['zeptomail_from_email']],
        "to" => [["email_address" => ["address" => $to_email]]],
        "subject" => $subject,
        "htmlbody" => $final_body
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Zoho-enczapikey ' . $profile['zeptomail_token']
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        // Log this successful activity
        $stmt_log = $pdo->prepare("INSERT INTO lead_activities (lead_id, user_id, activity_type, notes) VALUES (?, ?, 'Email', ?)");
        $stmt_log->execute([$lead_id, $user_id, "Sent email with subject: " . $subject]);
        echo json_encode(['success' => true, 'message' => 'Email sent successfully via Zoho Zeptomail!']);
    } else {
        throw new Exception("Failed to send email. Zoho API responded with HTTP " . $http_code . ". Response: " . $response);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}