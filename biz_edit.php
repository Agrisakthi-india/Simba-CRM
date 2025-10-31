<?php
// biz_edit.php (Final CSP-Safe Version - NO INLINE SCRIPTS)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// --- PERMISSION CHECKS & DATA FETCHING ---
$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'] ?? false;

if ($account_role === 'member' && !$is_superadmin) {
    http_response_code(403);
    die("Access Denied. You do not have permission to edit business details.");
}

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$business_id) {
    die("Error: No business ID was provided in the URL.");
}

$success_message = '';
$error_message = '';

// --- FETCH THE BUSINESS RECORD TO EDIT ---
try {
    $sql = "SELECT * FROM businesses WHERE id = ?";
    $params = [$business_id];
    if (!$is_superadmin) {
        $sql .= " AND team_id = ?";
        $params[] = $team_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $business = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$business) {
        http_response_code(404);
        die("Business not found or you do not have permission to edit it.");
    }
} catch (PDOException $e) { 
    error_log("Biz Edit Fetch Error: " . $e->getMessage());
    die("A database error occurred while fetching the business details.");
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    // Sanitize main business data
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
    
    // Handle contacts data - collect from individual form fields
    $contacts = [];
    $contact_index = 0;
    
    // Loop through posted contact data
    while (isset($_POST["contact_name_$contact_index"])) {
        $contact_name = trim($_POST["contact_name_$contact_index"] ?? '');
        $contact_role = trim($_POST["contact_role_$contact_index"] ?? '');
        $contact_phone = trim($_POST["contact_phone_$contact_index"] ?? '');
        $contact_email = trim($_POST["contact_email_$contact_index"] ?? '');
        
        // Only add if name is provided
        if (!empty($contact_name)) {
            $contacts[] = [
                'name' => $contact_name,
                'role' => $contact_role,
                'phone' => normalize_indian_phone($contact_phone),
                'email' => filter_var($contact_email, FILTER_SANITIZE_EMAIL)
            ];
        }
        $contact_index++;
    }
    
    $contacts_json = json_encode($contacts, JSON_UNESCAPED_UNICODE);
    
    if (empty($name) || empty($category)) {
        $error_message = "Business Name and Category are required.";
    } else {
        try {
            $update_sql = "UPDATE businesses SET 
                name = ?, category = ?, state = ?, district = ?, city = ?, 
                address = ?, description = ?, website = ?, rating = ?, contacts = ?
                WHERE id = ? AND team_id = ?";
            
            $update_params = [
                $name, $category, $state, $district, $city, 
                $address, $description, $website, $rating, 
                $contacts_json, $business_id, $team_id
            ];

            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($update_params);
            
            $success_message = "Business details updated successfully! <a href='index.php' class='alert-link'>Return to Dashboard</a>";
            
            // Re-fetch updated data
            $stmt->execute($params);
            $business = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Biz Edit Update Error: " . $e->getMessage());
            $error_message = "A database error occurred while updating.";
        }
    }
}

// --- PREPARE EXISTING CONTACTS FOR DISPLAY ---
$existing_contacts = [];
$contacts_raw = $business['contacts'] ?? '[]';

if (!empty($contacts_raw) && $contacts_raw !== 'null') {
    $decoded = json_decode($contacts_raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $existing_contacts = $decoded;
    }
}

// Ensure at least one empty contact row
if (empty($existing_contacts)) {
    $existing_contacts = [['name' => '', 'role' => '', 'phone' => '', 'email' => '']];
}

$page_title = 'Edit Business: ' . htmlspecialchars($business['name']);
include 'partials/header.php';
?>

<div class="container">
    <a href="index.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    <h2 class="mb-4">Edit Business Details</h2>

    <?php if (!empty($success_message)): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if (!empty($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form id="edit-biz-form" action="biz_edit.php?id=<?php echo $business_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <!-- Main Business Details -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="business-name" class="form-label">Business Name*</label>
                        <input type="text" id="business-name" name="name" class="form-control" value="<?php echo htmlspecialchars($business['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="business-category" class="form-label">Category*</label>
                        <input type="text" id="business-category" name="category" class="form-control" value="<?php echo htmlspecialchars($business['category'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="business-state" class="form-label">State</label>
                        <input type="text" id="business-state" name="state" class="form-control" value="<?php echo htmlspecialchars($business['state'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="business-district" class="form-label">District</label>
                        <input type="text" id="business-district" name="district" class="form-control" value="<?php echo htmlspecialchars($business['district'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="business-city" class="form-label">City</label>
                        <input type="text" id="business-city" name="city" class="form-control" value="<?php echo htmlspecialchars($business['city'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="business-address" class="form-label">Address</label>
                        <textarea id="business-address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($business['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="business-description" class="form-label">Description</label>
                        <textarea id="business-description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($business['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-8">
                        <label for="business-website" class="form-label">Website</label>
                        <input type="url" id="business-website" name="website" class="form-control" value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="business-rating" class="form-label">Rating</label>
                        <input type="number" id="business-rating" name="rating" step="0.1" class="form-control" value="<?php echo htmlspecialchars($business['rating'] ?? ''); ?>">
                    </div>
                </div>
                
                <hr class="my-4">

                <!-- Static Contacts Section - No JavaScript Required -->
                <h4 class="mb-3">Contact Persons</h4>
                <div id="contacts-container">
                    <?php foreach ($existing_contacts as $index => $contact): ?>
                    <div class="row g-3 mb-3 border rounded p-2 align-items-center contact-row" data-index="<?php echo $index; ?>">
                        <div class="col-md-3">
                            <input type="text" name="contact_name_<?php echo $index; ?>" class="form-control" 
                                   value="<?php echo htmlspecialchars($contact['name'] ?? ''); ?>" placeholder="Contact Name">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="contact_role_<?php echo $index; ?>" class="form-control" 
                                   value="<?php echo htmlspecialchars($contact['role'] ?? ''); ?>" placeholder="Role/Position">
                        </div>
                        <div class="col-md-3">
                            <input type="tel" name="contact_phone_<?php echo $index; ?>" class="form-control" 
                                   value="<?php echo htmlspecialchars($contact['phone'] ?? ''); ?>" placeholder="Phone Number">
                        </div>
                        <div class="col-md-2">
                            <input type="email" name="contact_email_<?php echo $index; ?>" class="form-control" 
                                   value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>" placeholder="Email">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-danger remove-contact">×</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="add-contact-btn" class="btn btn-sm btn-outline-success mt-2">
                    <i class="bi bi-plus-circle"></i> Add Contact
                </button>

                <hr class="my-4">
                <button type="submit" class="btn btn-primary btn-lg w-100">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<!-- Hidden data for JavaScript - CSP safe way -->
<div id="contact-counter" data-count="<?php echo count($existing_contacts); ?>" style="display: none;"></div>

<!-- External JavaScript file -->
<script src="assets/js/biz_edit.js"></script>

<?php include 'partials/footer.php'; ?>