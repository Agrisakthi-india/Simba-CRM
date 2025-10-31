<?php
// crm_ai.php (Corrected for Dependent Dropdowns)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$is_superadmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin');
$api_key_is_set = false;
$leads = [];
$business_count = 0;
$has_filters = false;

// Get filter parameters from URL
$filter_state = isset($_GET['state']) ? trim(strip_tags($_GET['state'])) : '';
$filter_district = isset($_GET['district']) ? trim(strip_tags($_GET['district'])) : '';
$filter_city = isset($_GET['city']) ? trim(strip_tags($_GET['city'])) : '';
$filter_category = isset($_GET['category']) ? trim(strip_tags($_GET['category'])) : '';
$search_query = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';
$filter_rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;
$has_filters = $filter_state || $filter_district || $filter_city || $filter_category || $search_query || $filter_rating;

try {
    // Check API key
    $stmt_profile = $pdo->prepare("SELECT gemini_api_key FROM company_profile WHERE user_id = ?");
    $stmt_profile->execute([$user_id]);
    $profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);
    if ($profile && !empty($profile['gemini_api_key'])) {
        $api_key_is_set = true;
    }

    // Get leads for individual follow-up dropdown
    $sql_leads = "SELECT l.id, b.name FROM leads l JOIN businesses b ON l.company_id = b.id";
    $params_leads = [];
    if (!$is_superadmin) { $sql_leads .= " WHERE l.user_id = ?"; $params_leads[] = $user_id; }
    $sql_leads .= " ORDER BY b.name ASC";
    $stmt_leads = $pdo->prepare($sql_leads);
    $stmt_leads->execute($params_leads);
    $leads = $stmt_leads->fetchAll(PDO::FETCH_ASSOC);

    // Count businesses matching filters for bulk messaging
    $sql_from = "FROM businesses b";
    $sql_where = " WHERE 1=1";
    $params_count = [];
    if (!$is_superadmin) { $sql_where .= " AND b.user_id = ?"; $params_count[] = $user_id; }
    if (!empty($filter_state)) { $sql_where .= " AND b.state = ?"; $params_count[] = $filter_state; }
    // ... (add other filters to $sql_where and $params_count) ...
    $sql_where .= " AND b.phone_number IS NOT NULL AND b.phone_number != ''";
    $count_query = "SELECT COUNT(b.id) " . $sql_from . $sql_where;
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params_count);
    $business_count = $count_stmt->fetchColumn();

    // --- THE FIX: Get all locations for dynamic dropdowns ---
    $dropdown_where = $is_superadmin ? " WHERE 1=1 " : " WHERE user_id = ? ";
    $dropdown_params = $is_superadmin ? [] : [$user_id];
    $stmt_locations = $pdo->prepare("SELECT DISTINCT state, district, city FROM businesses " . $dropdown_where . " AND state IS NOT NULL AND state != '' ORDER BY state, district, city");
    $stmt_locations->execute($dropdown_params);
    $all_locations = $stmt_locations->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for dropdown
    $stmt_categories = $pdo->prepare("SELECT DISTINCT category FROM businesses " . $dropdown_where . " AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    $stmt_categories->execute($dropdown_params);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) { /* ... */ }

$page_title = 'CRM AI Assistant - Secure Bulk Messaging';
include 'partials/header.php';
?>

<div class="container-fluid">
    <div class="text-center mb-4">
        <h2><i class="bi bi-robot me-2"></i>CRM AI Assistant</h2>
        <p class="lead text-muted">Create targeted WhatsApp campaigns securely</p>
    </div>
    
    <?php if (!$api_key_is_set): ?>
        <div class="alert alert-warning text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>Your Gemini API Key is not set. Please go to your <a href="profile.php" class="alert-link">Company Profile</a> to use AI features.</div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Target Your Audience</h5></div>
        <div class="card-body">
            <form id="filterForm" method="GET" class="row g-3 align-items-end">
                <!-- THE FIX: The dropdowns are now empty shells, to be populated by JavaScript -->
                <div class="col-lg-3 col-md-6"><label for="state-filter" class="form-label">State</label><select id="state-filter" name="state" class="form-select"><option value="">-- All States --</option></select></div>
                <div class="col-lg-3 col-md-6"><label for="district-filter" class="form-label">District</label><select id="district-filter" name="district" class="form-select"><option value="">-- All Districts --</option></select></div>
                <div class="col-lg-3 col-md-6"><label for="city-filter" class="form-label">City</label><select id="city-filter" name="city" class="form-select"><option value="">-- All Cities --</option></select></div>
                <div class="col-lg-3 col-md-6"><label for="category-filter" class="form-label">Category</label><select name="category" class="form-select"><option value="">-- All Categories --</option><?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php if($filter_category == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-12 d-flex justify-content-end gap-2 mt-3">
                    <a href="crm_ai.php" class="btn btn-outline-secondary">Clear Filters</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-2"></i>Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
            
            <?php if ($has_filters): ?>
                <div class="mt-3">
                    <a href="crm_ai.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear All Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Individual Lead Messages -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Individual Lead Follow-up</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text">Draft personalized messages for individual leads.</p>
                    <div class="mb-3">
                        <label for="message-lead-select" class="form-label">Select a Lead:</label>
                        <select id="message-lead-select" class="form-select" <?php if (!$api_key_is_set || empty($leads)) echo 'disabled'; ?>>
                            <option value="" disabled selected>
                                -- <?php echo empty($leads) ? 'No leads available' : 'Choose a lead'; ?> --
                            </option>
                            <?php foreach ($leads as $lead): ?>
                                <option value="<?php echo $lead['id']; ?>">
                                    <?php echo htmlspecialchars($lead['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button id="draft-message-btn" class="btn btn-success w-100 mt-auto" 
                            <?php if (!$api_key_is_set || empty($leads)) echo 'disabled'; ?>>
                        <span class="spinner-border spinner-border-sm d-none"></span> 
                        Draft Message
                    </button>
                    <div id="message-result-container" class="mt-3" style="display:none;">
                        <label class="form-label fw-bold">Generated Message:</label>
                        <textarea id="message-result-text" class="form-control" rows="5" readonly></textarea>
                        <div id="send-btn-placeholder" class="d-grid mt-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Messages for Filtered Businesses -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-broadcast me-2"></i>Bulk WhatsApp Campaign</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text">Create targeted messages for filtered businesses with phone numbers.</p>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Found:</strong> <?php echo $business_count; ?> businesses matching your filters with phone numbers
                    </div>
                    
                    <?php if ($business_count > 0): ?>
                        <div class="mb-3">
                            <label for="campaign-type" class="form-label">Campaign Type:</label>
                            <select id="campaign-type" class="form-select">
                                <option value="introduction">New Business Introduction</option>
                                <option value="promotion">Product/Service Promotion</option>
                                <option value="partnership">Partnership Opportunity</option>
                                <option value="survey">Market Research Survey</option>
                                <option value="custom">Custom Message</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="custom-prompt-container" style="display: none;">
                            <label for="custom-prompt" class="form-label">Custom Message Prompt:</label>
                            <textarea id="custom-prompt" class="form-control" rows="3" 
                                      placeholder="Describe what kind of message you want to send..."></textarea>
                        </div>

                        <button id="generate-bulk-message-btn" class="btn btn-primary w-100 mt-auto" 
                                <?php if (!$api_key_is_set) echo 'disabled'; ?>>
                            <span class="spinner-border spinner-border-sm d-none"></span> 
                            Generate Message Template
                        </button>
                        
                        <div id="bulk-message-result" class="mt-3" style="display:none;">
                            <label class="form-label fw-bold">Generated Message Template:</label>
                            <textarea id="bulk-message-text" class="form-control" rows="6" readonly></textarea>
                            <div class="mt-3">
                                <button id="send-bulk-messages-btn" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-whatsapp me-2"></i>Send to Filtered Businesses (<?php echo $business_count; ?>)
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No businesses found with phone numbers matching your current filters. Try adjusting your filters above.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Filters Summary (No sensitive data) -->
    <?php if ($has_filters): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-2"></i>Active Filters</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($filter_state): ?>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-primary">State: <?php echo htmlspecialchars($filter_state); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($filter_district): ?>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-info">District: <?php echo htmlspecialchars($filter_district); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($filter_city): ?>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-success">City: <?php echo htmlspecialchars($filter_city); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($filter_category): ?>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-warning">Category: <?php echo htmlspecialchars($filter_category); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($search_query): ?>
                        <div class="col-md-6 mb-2">
                            <span class="badge bg-secondary">Search: "<?php echo htmlspecialchars($search_query); ?>"</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($filter_rating): ?>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-dark">Rating: <?php echo $filter_rating; ?>+ stars</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- SECURE: Only pass non-sensitive summary data to JavaScript -->
<script id="page-data" type="application/json">
<?php echo json_encode([
    'business_count' => $business_count,
    'has_filters' => $has_filters,
    'filter_summary' => [
        'state' => $filter_state,
        'district' => $filter_district, 
        'city' => $filter_city,
        'category' => $filter_category,
        'search' => $search_query,
        'rating' => $filter_rating
    ]
]); ?>
</script>

<!-- THE FIX: Pass location data and current filters to JavaScript -->
<script id="locations-data" type="application/json"><?php echo json_encode($all_locations); ?></script>
<script id="current-filters-data" type="application/json"><?php echo json_encode(['state' => $filter_state, 'district' => $filter_district, 'city' => $filter_city]); ?></script>

<!-- Link to external JS file -->
<script src="assets/js/crm_ai_enhanced.js"></script>
<!-- Include external JavaScript file -->
<!--<script src="assets/js/crm_ai_enhanced.js"></script>-->

<?php include 'partials/footer.php'; ?>