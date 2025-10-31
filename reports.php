<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// --- DATE FILTERING LOGIC ---
// Default to the current month
$default_start = date('Y-m-01');
$default_end = date('Y-m-t');

// Sanitize and use date inputs if provided
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : $default_start;
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : $default_end;

// --- DATA FETCHING ---

// 1. Lead Status Counts (for the pie chart and summary boxes)
$sql_counts = "SELECT status, COUNT(*) as count FROM leads WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status";
$stmt_counts = $pdo->prepare($sql_counts);
$stmt_counts->execute([$start_date, $end_date]);
$lead_counts = $stmt_counts->fetchAll(PDO::FETCH_KEY_PAIR); // Fetches as ['Status' => count]

// Prepare data for charts and display, ensuring all keys exist
$status_data = [
    'Follow-up' => $lead_counts['Follow-up'] ?? 0,
    'Connected' => $lead_counts['Connected'] ?? 0,
    'Committed' => $lead_counts['Committed'] ?? 0,
    'Not Interested' => $lead_counts['Not Interested'] ?? 0,
];
$total_leads_in_period = array_sum($status_data);

// 2. Leads Over Time (for the line chart)
$sql_timeline = "SELECT DATE(created_at) as date, COUNT(*) as count FROM leads WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt_timeline = $pdo->prepare($sql_timeline);
$stmt_timeline->execute([$start_date, $end_date]);
$timeline_data = $stmt_timeline->fetchAll(PDO::FETCH_ASSOC);

// --- Prepare data for JavaScript charts ---
$pie_chart_data = json_encode(array_values($status_data));
$pie_chart_labels = json_encode(array_keys($status_data));

$line_chart_labels = json_encode(array_column($timeline_data, 'date'));
$line_chart_data = json_encode(array_column($timeline_data, 'count'));


$page_title = 'Lead Reports - SME CRM';
include 'partials/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Lead Conversion Reports</h2>

    <!-- Date Filter Form -->
    <div class="filter-bar mb-4 p-3">
        <form action="reports.php" method="GET" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
             <div class="col-md-2 align-self-end">
                <a href="reports.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary Boxes -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Leads Converted</h5>
                    <p class="card-text fs-2 fw-bold"><?php echo $total_leads_in_period; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Committed</h5>
                    <p class="card-text fs-2 fw-bold"><?php echo $status_data['Committed']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h5 class="card-title">Follow-up</h5>
                    <p class="card-text fs-2 fw-bold"><?php echo $status_data['Follow-up']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <h5 class="card-title">Not Interested</h5>
                    <p class="card-text fs-2 fw-bold"><?php echo $status_data['Not Interested']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Leads Per Day</div>
                <div class="card-body">
                    <canvas id="leadsOverTimeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Lead Status Breakdown</div>
                <div class="card-body">
                    <canvas id="leadStatusPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pie Chart for Lead Status
    const pieCtx = document.getElementById('leadStatusPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $pie_chart_labels; ?>,
            datasets: [{
                label: 'Lead Status',
                data: <?php echo $pie_chart_data; ?>,
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } }
        }
    });

    // Line Chart for Leads Over Time
    const lineCtx = document.getElementById('leadsOverTimeChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?php echo $line_chart_labels; ?>,
            datasets: [{
                label: 'New Leads',
                data: <?php echo $line_chart_data; ?>,
                fill: false,
                borderColor: '#0d6efd',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<?php include 'partials/footer.php'; ?>