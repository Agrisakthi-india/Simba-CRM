<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$is_superadmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin');

// --- DATA FETCHING ---
$sql = "SELECT status, COUNT(*) as count FROM leads";
$params = [];
if (!$is_superadmin) {
    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;
}
$sql .= " GROUP BY status";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare data, ensuring all stages exist
$follow_up_count = $counts['Follow-up'] ?? 0;
$connected_count = $counts['Connected'] ?? 0;
$committed_count = $counts['Committed'] ?? 0;
$total_funnel_leads = $follow_up_count + $connected_count + $committed_count;

// Calculate conversion rates (avoid division by zero)
$rate1 = ($total_funnel_leads > 0) ? round(($connected_count + $committed_count) / $total_funnel_leads * 100, 1) : 0;
$rate2 = ($connected_count + $committed_count > 0) ? round($committed_count / ($connected_count + $committed_count) * 100, 1) : 0;

$page_title = 'Sales Funnel Report';
include 'partials/header.php';
?>
<style>
.funnel-container { max-width: 800px; margin: auto; }
.funnel-stage { position: relative; padding: 20px; color: white; text-align: center; margin-bottom: 5px; clip-path: polygon(5% 0, 95% 0, 100% 100%, 0% 100%); }
.funnel-follow-up { background-color: #0d6efd; width: 100%; }
.funnel-connected { background-color: #17a2b8; width: 80%; margin: auto; }
.funnel-committed { background-color: #28a745; width: 60%; margin: auto; }
.funnel-rate { text-align: center; font-weight: bold; color: #6c757d; padding: 10px; }
.funnel-rate .bi { font-size: 1.5rem; }
</style>

<div class="container">
    <h2 class="mb-4 text-center">Sales Funnel</h2>

    <div class="funnel-container">
        <!-- Stage 1: Follow-up -->
        <div class="funnel-stage funnel-follow-up">
            <h4>Follow-up</h4>
            <p class="fs-2 fw-bold mb-0"><?php echo $follow_up_count; ?></p>
        </div>
        <div class="funnel-rate">
            <i class="bi bi-arrow-down-circle-fill"></i><br>
            Conversion to Connected: <?php echo $rate1; ?>%
        </div>

        <!-- Stage 2: Connected -->
        <div class="funnel-stage funnel-connected">
            <h4>Connected</h4>
            <p class="fs-2 fw-bold mb-0"><?php echo $connected_count; ?></p>
        </div>
        <div class="funnel-rate">
            <i class="bi bi-arrow-down-circle-fill"></i><br>
            Conversion to Committed: <?php echo $rate2; ?>%
        </div>

        <!-- Stage 3: Committed -->
        <div class="funnel-stage funnel-committed">
            <h4>Committed</h4>
            <p class="fs-2 fw-bold mb-0"><?php echo $committed_count; ?></p>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>