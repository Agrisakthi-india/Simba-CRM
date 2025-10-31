<?php
// ajax_upload_processor.php (Enhanced Final Version - Updated Field Structure)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// Set JSON response header
header('Content-Type: application/json');

// Permission checks
$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'] ?? false;

if ($account_role === 'member' && !$is_superadmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied. You do not have permission to upload businesses.']);
    exit;
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Get and validate JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input.');
    }

    if (!isset($input['rows']) || !is_array($input['rows'])) {
        throw new Exception('No valid rows data provided.');
    }

    $rows = $input['rows'];
    $expectedHeaders = $input['headers'] ?? [
        'name', 'category', 'state', 'district', 'city', 'address', 
        'description', 'website', 'rating', 'latitude', 'longitude', 
        'place_id', 'contacts'
    ];

    $inserted = 0;
    $skipped = 0;
    $malformed = 0;

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO businesses 
            (user_id, team_id, name, category, state, district, city, address, description, website, rating, latitude, longitude, place_id, contacts) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // Prepare duplicate check statement
    $duplicateCheckSql = "SELECT COUNT(*) FROM businesses WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND LOWER(TRIM(city)) = LOWER(TRIM(?)) AND team_id = ?";
    $duplicateStmt = $pdo->prepare($duplicateCheckSql);

    foreach ($rows as $rowData) {
        try {
            // Validate required fields
            if (empty($rowData['name']) || empty($rowData['category'])) {
                $malformed++;
                continue;
            }

            // Clean and prepare data
            $name = trim($rowData['name']);
            $category = trim($rowData['category']);
            $state = trim($rowData['state'] ?? '');
            $district = trim($rowData['district'] ?? '');
            $city = trim($rowData['city'] ?? '');
            $address = trim($rowData['address'] ?? '');
            $description = trim($rowData['description'] ?? '');
            $website = trim($rowData['website'] ?? '');
            $rating = $rowData['rating'] ?? null;
            $latitude = $rowData['latitude'] ?? null;
            $longitude = $rowData['longitude'] ?? null;
            $place_id = trim($rowData['place_id'] ?? '');
            $contacts_raw = $rowData['contacts'] ?? '[]';

            // Validate and process contacts JSON
            $contacts = [];
            if (!empty($contacts_raw) && $contacts_raw !== '[]') {
                $decoded = json_decode($contacts_raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Clean up contacts data
                    foreach ($decoded as $contact) {
                        if (isset($contact['name']) && !empty(trim($contact['name']))) {
                            $cleanContact = [
                                'name' => trim($contact['name']),
                                'role' => trim($contact['role'] ?? ''),
                                'phone' => normalize_indian_phone(trim($contact['phone'] ?? '')),
                                'email' => filter_var(trim($contact['email'] ?? ''), FILTER_SANITIZE_EMAIL)
                            ];
                            $contacts[] = $cleanContact;
                        }
                    }
                }
            }
            $contacts_json = json_encode($contacts, JSON_UNESCAPED_UNICODE);

            // Generate place_id if empty
            if (empty($place_id)) {
                $place_id = 'manual_' . strtolower(str_replace(' ', '_', $name)) . '_' . time() . '_' . $user_id;
            }

            // Validate numeric fields
            if ($rating !== null && (!is_numeric($rating) || $rating < 0 || $rating > 5)) {
                $rating = null;
            }
            if ($latitude !== null && (!is_numeric($latitude) || $latitude < -90 || $latitude > 90)) {
                $latitude = null;
            }
            if ($longitude !== null && (!is_numeric($longitude) || $longitude < -180 || $longitude > 180)) {
                $longitude = null;
            }

            // Check for duplicates (same name and city within team)
            $duplicateStmt->execute([$name, $city, $team_id]);
            if ($duplicateStmt->fetchColumn() > 0) {
                $skipped++;
                continue;
            }

            // Insert the business
            $success = $stmt->execute([
                $user_id, $team_id, $name, $category, $state, $district, $city,
                $address, $description, $website, $rating, $latitude, $longitude, 
                $place_id, $contacts_json
            ]);

            if ($success) {
                $inserted++;
            } else {
                $malformed++;
                error_log("Insert failed for business: " . $name);
            }

        } catch (PDOException $e) {
            $malformed++;
            error_log("Upload processor PDO error for row: " . json_encode($rowData) . " Error: " . $e->getMessage());
        } catch (Exception $e) {
            $malformed++;
            error_log("Upload processor error for row: " . json_encode($rowData) . " Error: " . $e->getMessage());
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'skipped' => $skipped,
        'malformed' => $malformed,
        'total_processed' => count($rows),
        'message' => "Processed " . count($rows) . " rows. Inserted: $inserted, Skipped: $skipped, Malformed: $malformed"
    ]);

} catch (Exception $e) {
    error_log("Upload processor general error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'inserted' => 0,
        'skipped' => 0,
        'malformed' => 0
    ]);
}