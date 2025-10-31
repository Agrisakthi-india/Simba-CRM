<?php
// map.php (SaaS Version - Filtered Map - CSP Compliant)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'];

// --- FILTER & SEARCH LOGIC ---
$filter_state = isset($_GET['state']) ? trim(strip_tags($_GET['state'])) : '';
$filter_district = isset($_GET['district']) ? trim(strip_tags($_GET['district'])) : '';
$filter_city = isset($_GET['city']) ? trim(strip_tags($_GET['city'])) : '';
$filter_category = isset($_GET['category']) ? trim(strip_tags($_GET['category'])) : '';
$search_query = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';

// --- SAAS-READY SQL QUERY CONSTRUCTION ---
$sql_from = "FROM businesses b";
$sql_where = " WHERE b.latitude IS NOT NULL AND b.longitude IS NOT NULL";
$params = [];

if (!$is_superadmin) {
    $sql_where .= " AND b.team_id = ?";
    $params[] = $team_id;
    if ($account_role === 'member') {
        $sql_where .= " AND EXISTS (SELECT 1 FROM leads l WHERE l.company_id = b.id AND l.assigned_user_id = ?)";
        $params[] = $user_id;
    }
}
if (!empty($filter_state)) { $sql_where .= " AND b.state = ?"; $params[] = $filter_state; }
if (!empty($filter_district)) { $sql_where .= " AND b.district = ?"; $params[] = $filter_district; }
if (!empty($filter_city)) { $sql_where .= " AND b.city = ?"; $params[] = $filter_city; }
if (!empty($filter_category)) { $sql_where .= " AND b.category = ?"; $params[] = $filter_category; }
if (!empty($search_query)) { $sql_where .= " AND (b.name LIKE ? OR b.address LIKE ?)"; $params[] = "%$search_query%"; $params[] = "%$search_query%"; }

try {
    // Fetch filtered business data for the map
    $sql_data = "SELECT name, address, latitude, longitude, category, district, city " . $sql_from . $sql_where;
    $stmt = $pdo->prepare($sql_data);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all locations and categories for dynamic dropdowns
    $dropdown_where = $is_superadmin ? " WHERE 1=1 " : " WHERE team_id = ? ";
    $dropdown_params = $is_superadmin ? [] : [$team_id];
    
    $stmt_locations = $pdo->prepare("SELECT DISTINCT state, district, city FROM businesses " . $dropdown_where . " AND state IS NOT NULL AND state != '' ORDER BY state, district, city");
    $stmt_locations->execute($dropdown_params);
    $all_locations = $stmt_locations->fetchAll(PDO::FETCH_ASSOC);

    $stmt_categories = $pdo->prepare("SELECT DISTINCT category FROM businesses " . $dropdown_where . " AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    $stmt_categories->execute($dropdown_params);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) { /* ... error handling ... */ }

$page_title = 'Map View - SME CRM';
include 'partials/header.php';
?>
<!-- Leaflet CSS (can also be moved to header.php if used on more pages) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #map { height: 70vh; width: 100%; border: 1px solid #ccc; border-radius: 8px; }
</style>

<div class="container-fluid">
    <h2 class="mb-3">Map View</h2>

    <!-- Filter Bar -->
    <div class="filter-bar mb-4">
        <h3 class="mb-3">Filter Map Data</h3>
        <form id="filterForm" action="map.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
            <div class="col-lg-3 col-md-6"><label for="state-filter" class="form-label">State</label><select id="state-filter" name="state" class="form-select"><option value="">-- All States --</option></select></div>
            <div class="col-lg-3 col-md-6"><label for="district-filter" class="form-label">District</label><select id="district-filter" name="district" class="form-select"><option value="">-- All Districts --</option></select></div>
            <div class="col-lg-2 col-md-6"><label for="city-filter" class="form-label">City</label><select id="city-filter" name="city" class="form-select"><option value="">-- All Cities --</option></select></div>
            <div class="col-lg-2 col-md-6"><label for="category-filter" class="form-label">Category</label><select id="category-filter" name="category" class="form-select"><option value="">-- All Categories --</option><?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php if($filter_category == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-12"><button type="submit" class="btn btn-secondary w-100">Apply Filters</button></div>
        </form>
    </div>

    <hr>
    
    <h4 class="mb-3">Showing <?php echo count($businesses); ?> businesses on the map.</h4>
    
    <div id="map"></div>

    <div class="mt-3 text-center">
        <a href="map.php" class="btn btn-outline-secondary">Reset Map View</a>
        <a href="index.php" class="btn btn-primary">Back to List View</a>
    </div>
</div>

<!-- DATA TRANSFER SCRIPT TAGS (CSP SAFE) -->
<script id="locations-data" type="application/json"><?php echo json_encode($all_locations); ?></script>
<script id="businesses-data" type="application/json"><?php echo json_encode($businesses); ?></script>
<script id="current-filters-data" type="application/json"><?php echo json_encode(['state' => $filter_state, 'district' => $filter_district, 'city' => $filter_city]); ?></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Link to our new, external JavaScript file -->
<script src="assets/js/map.js"></script>

<?php include 'partials/footer.php'; ?>