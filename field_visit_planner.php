<?php
// field_visit_planner.php (Final SaaS Version with Dependent Filters)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'];

try {
    // --- Get ALL locations the user has access to for the filters ---
    $sql_locations = "SELECT DISTINCT b.state, b.district, b.city 
                      FROM businesses b ";
    $params_locations = [];

    // Apply permission scopes
    if (!$is_superadmin) {
        $sql_locations .= " WHERE b.team_id = ?";
        $params_locations[] = $team_id;
        if ($account_role === 'member') {
            $sql_locations .= " AND EXISTS (SELECT 1 FROM leads l WHERE l.company_id = b.id AND l.assigned_user_id = ?)";
            $params_locations[] = $user_id;
        }
    }
    
    $sql_locations .= " ORDER BY b.state, b.district, b.city";
    $stmt_locations = $pdo->prepare($sql_locations);
    $stmt_locations->execute($params_locations);
    $all_locations = $stmt_locations->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Field Visit Planner Error for user {$user_id}: " . $e->getMessage());
    die("A database error occurred.");
}

$page_title = 'Field Visit Planner';
include 'partials/header.php';
?>
<!-- Pass data from PHP to JavaScript securely -->
<script id="locations-data" type="application/json"><?php echo json_encode($all_locations); ?></script>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    #map { height: 500px; width: 100%; border-radius: 0.5rem; border: 1px solid #dee2e6; }
    #business-list, #visit-plan-list { max-height: 450px; overflow-y: auto; }
</style>

<div class="container-fluid">
    <h2 class="mb-4">Field Visit Planner</h2>

    <!-- Filter Bar for selecting the area to plan -->
    <div class="filter-bar mb-4">
        <div id="plan-filters" class="row g-3 align-items-end">
            <div class="col-md-3"><label for="plan-date" class="form-label">Visit Date</label><input type="date" id="plan-date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="col-md-3"><label for="state-filter" class="form-label">State</label><select id="state-filter" class="form-select"><option value="">-- Select State --</option></select></div>
            <div class="col-md-3"><label for="district-filter" class="form-label">District</label><select id="district-filter" class="form-select" disabled><option value="">-- Select District --</option></select></div>
            <div class="col-md-3"><label for="city-filter" class="form-label">City</label><select id="city-filter" class="form-select" disabled><option value="">-- Select City --</option></select></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Map -->
        <div class="col-lg-7"><div id="map"></div></div>
        
        <!-- Right Column: Business List -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Businesses to Visit</h5></div>
                <div id="business-list" class="list-group list-group-flush">
                    <div class="list-group-item text-center text-muted p-5">Please select a city to see businesses.</div>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="my-4">
    
    <!-- Daily Visit Plan Section -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Visit Plan for <span id="plan-date-display"><?php echo date('M d, Y'); ?></span> (<span id="plan-count">0</span> stops)</h4>
            <button id="optimize-route-btn" class="btn btn-info" style="display: none;"><i class="bi bi-magic me-2"></i>Optimize Route</button>
        </div>
        <div class="card-body">
            <ul id="visit-plan-list" class="list-group">
                <li class="list-group-item text-center text-muted">Your plan is empty. Use the "Add to Plan" button on businesses above.</li>
            </ul>
        </div>
    </div>
</div>

<!-- Check-in Modal -->
<div class="modal fade" id="checkinModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Log Visit for <span id="checkin-business-name"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="checkin-visit-id">
        <div class="mb-3"><label for="visit-notes" class="form-label">Visit Notes</label><textarea id="visit-notes" class="form-control" rows="4" placeholder="e.g., Met manager, proposal sent..."></textarea></div>
        <div id="checkin-gps-status" class="text-muted small"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="confirm-checkin-btn" class="btn btn-primary">Confirm Check-in</button></div>
</div></div></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- External JS file for this page -->
<script src="assets/js/field_visit.js"></script>

<?php include 'partials/footer.php'; ?>