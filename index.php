<?php
// index.php (SaaS Version - Final & Secure with Corrected Query)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'];

// --- PAGINATION & FILTER LOGIC ---
$records_per_page = 30;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Sanitize all inputs from the URL
$filter_state = isset($_GET['state']) ? trim(strip_tags($_GET['state'])) : '';
$filter_district = isset($_GET['district']) ? trim(strip_tags($_GET['district'])) : '';
$filter_city = isset($_GET['city']) ? trim(strip_tags($_GET['city'])) : '';
$filter_category = isset($_GET['category']) ? trim(strip_tags($_GET['category'])) : '';
$search_query = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';
$filter_rating = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;
$filter_has_website = isset($_GET['has_website']) && $_GET['has_website'] === 'true';
// <?php
// Add this function at the top of index.php after the require statements (around line 8):

/**
 * Format phone number for WhatsApp link
 * Handles Indian numbers with proper +91 country code
 */
function formatWhatsAppNumber($phone) {
    // Remove all non-numeric characters
    $cleanNumber = preg_replace('/[^0-9]/', '', $phone);
    
    // If number is empty after cleaning, return empty
    if (empty($cleanNumber)) {
        return '';
    }
    
    // Handle different Indian number formats
    if (strlen($cleanNumber) == 10) {
        // 10-digit number (without country code) - add +91
        return '91' . $cleanNumber;
    } elseif (strlen($cleanNumber) == 11 && substr($cleanNumber, 0, 1) == '0') {
        // 11-digit number starting with 0 (old format) - replace 0 with 91
        return '91' . substr($cleanNumber, 1);
    } elseif (strlen($cleanNumber) == 12 && substr($cleanNumber, 0, 2) == '91') {
        // 12-digit number starting with 91 (country code without +) - use as is
        return $cleanNumber;
    } elseif (strlen($cleanNumber) == 13 && substr($cleanNumber, 0, 3) == '919') {
        // 13-digit number starting with 919 (country code 91 + 9) - use as is
        return $cleanNumber;
    } elseif (strlen($cleanNumber) > 10) {
        // For any other long number, assume it already has country code
        return $cleanNumber;
    }
    
    // Default: if it's a valid 10-digit Indian mobile number, add country code
    if (strlen($cleanNumber) == 10 && in_array(substr($cleanNumber, 0, 1), ['6', '7', '8', '9'])) {
        return '91' . $cleanNumber;
    }
    
    // If none of the above conditions match, return the cleaned number as is
    return $cleanNumber;
}

// --- SAAS-READY SQL QUERY CONSTRUCTION ---
$sql_from = "FROM businesses b LEFT JOIN leads l ON (b.id = l.company_id AND l.team_id = b.team_id)";
$sql_where = " WHERE 1=1";
$params = [];

if (!$is_superadmin) {
    $sql_where .= " AND b.team_id = ?";
    $params[] = $team_id;
    if ($account_role === 'member') {
        $stmt_cats = $pdo->prepare("SELECT category FROM user_category_access WHERE user_id = ?");
        $stmt_cats->execute([$user_id]);
        $allowed_categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($allowed_categories)) {
            $placeholders = implode(',', array_fill(0, count($allowed_categories), '?'));
            $sql_where .= " AND b.category IN (" . $placeholders . ")";
            $params = array_merge($params, $allowed_categories);
        } else {
            $sql_where .= " AND 1=0";
        }
    }
}

if (!empty($filter_state)) { $sql_where .= " AND b.state = ?"; $params[] = $filter_state; }
if (!empty($filter_district)) { $sql_where .= " AND b.district = ?"; $params[] = $filter_district; }
if (!empty($filter_city)) { $sql_where .= " AND b.city = ?"; $params[] = $filter_city; }
if (!empty($filter_category)) { $sql_where .= " AND b.category = ?"; $params[] = $filter_category; }
if ($filter_rating > 0) { $sql_where .= " AND b.rating >= ?"; $params[] = $filter_rating; }
if ($filter_has_website) { $sql_where .= " AND b.website IS NOT NULL AND b.website != '' AND b.website != 'N/A'"; }
if (!empty($search_query)) { $sql_where .= " AND (b.name LIKE ? OR b.address LIKE ?)"; $params[] = "%$search_query%"; $params[] = "%$search_query%"; }

try {
    $count_query = "SELECT COUNT(b.id) " . $sql_from . $sql_where;
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // --- CORRECTED DATA FETCHING QUERY ---
    // Use unnamed '?' placeholders for LIMIT and OFFSET for consistency.
    $sql_final = "SELECT b.*, l.status as lead_status " . $sql_from . $sql_where . " ORDER BY b.name ASC LIMIT ? OFFSET ?";
    
    // Add the pagination values to the end of the existing parameters array.
    $final_params = $params;
    $final_params[] = $records_per_page;
    $final_params[] = $offset;
    
    $stmt = $pdo->prepare($sql_final);
    
    // Execute with the single, unified array of parameters.
    $stmt->execute($final_params);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Get data for dynamic dropdowns ---
    $dropdown_where = $is_superadmin ? " WHERE 1=1 " : " WHERE team_id = ? ";
    $dropdown_params = $is_superadmin ? [] : [$team_id];
    $stmt_locations = $pdo->prepare("SELECT DISTINCT state, district, city FROM businesses " . $dropdown_where . " AND state IS NOT NULL AND state != '' ORDER BY state, district, city");
    $stmt_locations->execute($dropdown_params);
    $all_locations = $stmt_locations->fetchAll(PDO::FETCH_ASSOC);
    $stmt_categories = $pdo->prepare("SELECT DISTINCT category FROM businesses " . $dropdown_where . " AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    $stmt_categories->execute($dropdown_params);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("DB Error in index.php for user {$user_id}: " . $e->getMessage());
    die("<div class='alert alert-danger m-5'>A critical database error occurred. Please contact support. Error details have been logged.</div>");
}

$page_title = 'SME CRM Dashboard';
include 'partials/header.php';
?>

<div class="container-fluid">
    <!-- AI Search Bar -->
    <div class="mb-4">
        <form id="aiSearchForm" class="d-flex">
            <input class="form-control form-control-lg me-2" type="search" id="ai-search-input" placeholder="Try: Top rated bakeries in Jayanagar with a website..." aria-label="Search">
            <button class="btn btn-primary" type="submit" id="ai-search-btn" title="AI Search"><i class="bi bi-robot"></i><span class="d-none d-md-inline ms-2">AI Search</span></button>
        </form>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-4">
        <h3 class="mb-3">Filter Businesses</h3>
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
            <div class="col-lg-3 col-md-6"><label for="state-filter" class="form-label">State</label><select id="state-filter" name="state" class="form-select"><option value="">-- All States --</option></select></div>
            <div class="col-lg-3 col-md-6"><label for="district-filter" class="form-label">District</label><select id="district-filter" name="district" class="form-select"><option value="">-- All Districts --</option></select></div>
            <div class="col-lg-2 col-md-6"><label for="city-filter" class="form-label">City</label><select id="city-filter" name="city" class="form-select"><option value="">-- All Cities --</option></select></div>
            <div class="col-lg-2 col-md-6"><label for="category-filter" class="form-label">Category</label><select id="category-filter" name="category" class="form-select"><option value="">-- All Categories --</option><?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php if($filter_category == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-12"><button type="submit" class="btn btn-secondary w-100">Apply Filters</button></div>
        </form>
    </div>
    <hr>
    
    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4>Showing <?php echo $total_records > 0 ? ($offset + 1) . '-' . ($offset + count($businesses)) : '0'; ?> of <?php echo number_format($total_records); ?> results</h4>
        <?php if ($filter_state || $filter_district || $filter_city || $filter_category || $search_query || $filter_rating || $filter_has_website): ?>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Clear All</a>
        <?php endif; ?>
    </div>
    
    <!-- Business Cards Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
   <?php if (count($businesses) > 0): foreach ($businesses as $business): ?>
    <div class="col d-flex align-items-stretch">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <!-- THE FIX: The name is now a button that triggers the modal -->
                    <h5 class="card-title mb-0">
                        <a href="#" class="text-dark text-decoration-none view-business-btn" data-bs-toggle="modal" data-bs-target="#businessDetailModal" data-id="<?php echo $business['id']; ?>">
                            <?php echo htmlspecialchars($business['name']); ?>
                        </a>
                    </h5>
                    <?php if ($is_superadmin || $account_role === 'admin'): ?>
                        <a href="biz_edit.php?id=<?php echo $business['id']; ?>" class="btn btn-sm btn-outline-secondary ms-2" title="Edit Business"><i class="bi bi-pencil-fill"></i></a>
                    <?php endif; ?>
                </div>
                    <!-- --- END OF FIX --- -->
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($business['name']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($business['category']); ?></h6>
                    <?php if (!empty($business['rating'])): ?><p class="mb-2"><strong>Rating:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($business['rating']); ?> ★</span></p><?php endif; ?>
                    <p class="card-text small mb-auto"><i class="bi bi-geo-alt-fill text-danger"></i> <?php echo htmlspecialchars($business['address']); ?></p>
                    
                    <div class="mt-3 card-actions">
                        <div class="d-flex gap-1">
                            <!--<?php if (!empty($business['phone_number'])): ?><a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $business['phone_number']); ?>" class="btn btn-sm btn-success" target="_blank" title="WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif; ?>-->
                            <?php if (!empty($business['phone_number'])): ?>
    <a href="https://wa.me/<?php echo formatWhatsAppNumber($business['phone_number']); ?>" class="btn btn-sm btn-success" target="_blank" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>
                            <?php if (!empty($business['website']) && $business['website'] != 'N/A'): ?><a href="<?php echo htmlspecialchars($business['website']); ?>" class="btn btn-sm btn-info" target="_blank" title="Website"><i class="bi bi-globe"></i></a><?php endif; ?>
                            <?php if (!empty($business['latitude']) && !empty($business['longitude'])): ?><a href="https://www.google.com/maps?q=<?php echo $business['latitude']; ?>,<?php echo $business['longitude']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="View on Map"><i class="bi bi-map"></i></a><?php endif; ?>
                            <button type="button" class="btn btn-sm btn-purple-ai js-ai-insights-btn" title="Generate AI Marketing Insights" data-business-id="<?php echo $business['id']; ?>"><i class="bi bi-robot"></i></button>
                        </div>
                        <div class="text-end">
                            <?php if (!empty($business['lead_status'])): ?>
                                <span class="btn btn-sm btn-outline-primary w-100 pe-none"><i class="bi bi-check-circle-fill"></i> Lead: <?php echo htmlspecialchars($business['lead_status']); ?></span>
                            <?php else: ?>
                                <form class="convert-lead-form">
    <input type="hidden" name="id" value="<?php echo $business['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <button type="submit" class="btn btn-sm btn-warning w-100 js-convert-to-lead-btn">
        <i class="bi bi-person-plus-fill"></i> Convert to Lead
    </button>
</form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <?php endforeach; else: ?>
        <div class="col-12"><div class="alert alert-warning text-center">No businesses found. Try the 'Data' menu to add a new listing or upload a CSV.</div></div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <div class="mt-5"><?php echo generate_pagination_links($current_page, $total_pages, $_GET); ?></div>
</div>

<!-- ***** NEW: BUSINESS DETAIL MODAL ***** -->
<div class="modal fade" id="businessDetailModal" tabindex="-1" aria-labelledby="businessDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="businessDetailModalLabel">Business Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modal-loader" class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>
        <div id="modal-content-container" style="display:none;">
          <!-- Nav tabs -->
          <ul class="nav nav-tabs" id="businessTab" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab">Business Details</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#tab-contacts" type="button" role="tab">Contact Persons</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="lead-tab" data-bs-toggle="tab" data-bs-target="#tab-lead" type="button" role="tab">Lead Status</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#tab-analytics" type="button" role="tab">Business Analytics</button></li>
          </ul>
          <!-- Tab panes -->
          <div class="tab-content pt-3" id="businessTabContent">
            <div class="tab-pane fade show active" id="tab-details" role="tabpanel"></div>
            <div class="tab-pane fade" id="tab-contacts" role="tabpanel"></div>
            <div class="tab-pane fade" id="tab-lead" role="tabpanel"></div>
            <div class="tab-pane fade" id="tab-analytics" role="tabpanel"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- AI Insights Modal -->
<div class="modal fade" id="aiInsightsModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="aiInsightsModalLabel"><i class="bi bi-robot me-2"></i>AI Insights</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="ai-modal-spinner" class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">Gemini is analyzing...</p></div><div id="ai-modal-content" style="display: none;"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary me-auto" id="save-ai-description-btn" style="display: none;"><span class="spinner-border spinner-border-sm d-none"></span> Save to Description</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>

<!-- DATA TRANSFER SCRIPTS for JS -->
<script id="locations-data" type="application/json"><?php echo json_encode($all_locations ?? []); ?></script>
<script id="current-filters-data" type="application/json"><?php echo json_encode(['state' => $filter_state, 'district' => $filter_district, 'city' => $filter_city]); ?></script>

<script src="assets/js/index.js"></script>

<?php include 'partials/footer.php'; ?>