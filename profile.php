<?php
// profile.php (Clean Version - Fixed Syntax)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

$user_id = $_SESSION['user_id'] ?? null;
$success_message = '';
$error_message = '';
$debug_messages = array();

// Check if user is logged in
if (!$user_id) {
    $error_message = "Please log in to access this page.";
    $debug_messages[] = "ERROR: User not logged in";
} else {
    $debug_messages[] = "User ID: " . $user_id;
}

// Check database connection
try {
    $pdo->query("SELECT 1");
    $debug_messages[] = "Database connection: OK";
} catch (Exception $e) {
    $error_message = "Database connection failed.";
    $debug_messages[] = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $debug_messages[] = "Processing form submission";
    
    // Get form data
    $company_name = trim($_POST['company_name'] ?? '');
    $services_description = trim($_POST['services_description'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $gemini_api_key = trim($_POST['gemini_api_key'] ?? '');
    
    $debug_messages[] = "Company Name: " . $company_name;
    $debug_messages[] = "Contact Person: " . $contact_person;
    $debug_messages[] = "API Key: " . (empty($gemini_api_key) ? 'Not provided' : 'Provided');
    
    // Validate required fields
    if (empty($company_name) || empty($services_description) || empty($contact_person)) {
        $error_message = "Please fill in all required fields.";
        $debug_messages[] = "Validation failed: Missing required fields";
    } else {
        $debug_messages[] = "Validation passed";
        
        try {
            // Check if profile exists
            $check_stmt = $pdo->prepare("SELECT id FROM company_profile WHERE user_id = ?");
            $check_stmt->execute([$user_id]);
            $existing_profile = $check_stmt->fetch();
            
            if ($existing_profile) {
                // Update existing profile
                $debug_messages[] = "Updating existing profile (ID: " . $existing_profile['id'] . ")";
                
                $sql = "UPDATE company_profile SET company_name = ?, services_description = ?, contact_person = ?, gemini_api_key = ?, updated_at = NOW() WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$company_name, $services_description, $contact_person, $gemini_api_key, $user_id]);
                
                if ($result) {
                    $success_message = "Profile updated successfully!";
                    $debug_messages[] = "UPDATE successful";
                } else {
                    $error_message = "Failed to update profile.";
                    $debug_messages[] = "UPDATE failed";
                }
                
            } else {
                // Insert new profile
                $debug_messages[] = "Creating new profile";
                
                $sql = "INSERT INTO company_profile (user_id, company_name, services_description, contact_person, gemini_api_key, updated_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$user_id, $company_name, $services_description, $contact_person, $gemini_api_key]);
                
                if ($result) {
                    $success_message = "Profile created successfully!";
                    $debug_messages[] = "INSERT successful - ID: " . $pdo->lastInsertId();
                } else {
                    $error_message = "Failed to create profile.";
                    $debug_messages[] = "INSERT failed";
                }
            }
            
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            $debug_messages[] = "Database exception: " . $e->getMessage();
        }
    }
}

// Load existing profile
$profile = array();
if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM company_profile WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $debug_messages[] = "Profile loaded from database";
        } else {
            $debug_messages[] = "No existing profile found";
            $profile = array(); // Ensure it's an empty array
        }
    } catch (PDOException $e) {
        $debug_messages[] = "Error loading profile: " . $e->getMessage();
    }
}

$page_title = 'My Company Profile';
include 'partials/header.php';
?>

<div class="container">
    <h2 class="mb-4">Company Profile</h2>
    <p class="text-muted">Enter your company details here. The AI will use this information to draft personalized messages to your leads.</p>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Debug Information -->
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning">
            <h6 class="mb-0">Debug Information</h6>
        </div>
        <div class="card-body">
            <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                <?php foreach ($debug_messages as $msg): ?>
                    <div><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="profile.php">
                <div class="mb-3">
                    <label for="company_name" class="form-label">Your Company Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="services_description" class="form-label">Your Products/Services Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="services_description" name="services_description" rows="5" required><?php echo htmlspecialchars($profile['services_description'] ?? ''); ?></textarea>
                    <div class="form-text">Describe what your company offers. Be clear and concise.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact_person" class="form-label">Your Name (Contact Person) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                               value="<?php echo htmlspecialchars($profile['contact_person'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="gemini_api_key" class="form-label">Google Gemini API Key</label>
                        <input type="password" class="form-control" id="gemini_api_key" name="gemini_api_key" 
                               value="<?php echo htmlspecialchars($profile['gemini_api_key'] ?? ''); ?>">
                        <div class="form-text">Optional: Your API key for AI requests.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Save Profile</button>
            </form>
        </div>
    </div>
    
    <!-- Current Data -->
    <div class="card mt-4">
        <div class="card-header">
            <h6>Current Profile Data</h6>
        </div>
        <div class="card-body">
            <pre style="font-size: 12px;"><?php 
                if ($profile) {
                    echo htmlspecialchars(print_r($profile, true));
                } else {
                    echo "No profile data found";
                }
            ?></pre>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>