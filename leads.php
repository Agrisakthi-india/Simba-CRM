<?php
// leads.php (Final Version with AJAX Status Update & CSP Compliant)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php'; // Includes CSRF token

$page_title = 'Manage Leads - SME CRM';
include 'partials/header.php';
?>
<!-- DataTables CSS is loaded from header.php -->

<div class="container-fluid">
    <h2 class="mb-4">Manage Leads</h2>
    <!-- This div will be used for displaying success/error "toast" messages -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1055"></div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <!-- We add the CSRF token as a data attribute on the table for our JS to use -->
                <table id="leads-table" class="table table-striped table-hover" style="width:100%" data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>AI Score</th>
                            <th>Status</th>
                            <th>Last Activity</th>
                            <th>Next Task Due</th>
                            <th>Assigned To</th>
                            <th>Created On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded by DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- The DataTables JS libraries are loaded in footer.php -->

<!-- ** THE FIX ** -->
<!-- We now load our page-specific logic from an external file, which is allowed by our CSP -->
<script src="assets/js/leads.js"></script>

<?php include 'partials/footer.php'; ?>