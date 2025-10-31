<?php
// lead_details.php (Final SaaS Version with Email Modal & Marketing Insights) - FIXED VERSION

ini_set('display_errors', 1); // Enables displaying errors directly in the browser
ini_set('display_startup_errors', 1); // Enables displaying errors that occur during PHP's startup sequence
error_reporting(E_ALL); // Reports all PHP errors, warnings, and notices

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'];
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$lead_id) {
    header("Location: leads.php");
    exit;
}

// --- HANDLE NON-AJAX FORM SUBMISSIONS (ACTIVITY, TASKS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) { 
        die('Invalid CSRF token.'); 
    }

    if (isset($_POST['log_activity'])) {
        $activity_type = $_POST['activity_type'];
        $notes = trim($_POST['notes']);
        if (!empty($notes)) {
            $stmt = $pdo->prepare("INSERT INTO lead_activities (lead_id, user_id, activity_type, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$lead_id, $user_id, $activity_type, $notes]);
        }
    } elseif (isset($_POST['add_task'])) {
        $title = trim($_POST['title']);
        $due_date = $_POST['due_date'];
        if (!empty($title) && !empty($due_date)) {
            $stmt = $pdo->prepare("INSERT INTO lead_tasks (lead_id, user_id, title, due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$lead_id, $user_id, $title, $due_date]);
        }
    } elseif (isset($_POST['toggle_task_status'])) {
        $task_id = $_POST['task_id'];
        $sql = "UPDATE lead_tasks t JOIN leads l ON t.lead_id = l.id SET t.status = IF(t.status='Pending', 'Completed', 'Pending') WHERE t.id = ?";
        $params = [$task_id];
        if (!$is_superadmin) {
            $sql .= " AND l.team_id = ?";
            $params[] = $team_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    header("Location: lead_details.php?id=" . $lead_id);
    exit;
}

// --- FETCH ALL DATA FOR THE PAGE (WITH CORRECT PERMISSIONS) ---
try {
    // Fixed SQL query formatting
    $sql_lead = "SELECT 
                    l.id, l.status, l.ai_score, l.ai_reasoning, l.ai_icp, l.ai_persona,
                    b.id as business_id, b.name as company_name, b.phone_number, b.website, 
                    b.address, b.description, b.email 
                 FROM leads l 
                 JOIN businesses b ON l.company_id = b.id 
                 WHERE l.id = ?";
    
    $params_lead = [$lead_id];

    if (!$is_superadmin) {
        $sql_lead .= " AND l.team_id = ?";
        $params_lead[] = $team_id;
        if ($account_role === 'member') {
            $sql_lead .= " AND l.assigned_user_id = ?";
            $params_lead[] = $user_id;
        }
    }
    
    $stmt_lead = $pdo->prepare($sql_lead);
    $stmt_lead->execute($params_lead);
    $lead = $stmt_lead->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        die("Lead not found or you do not have permission to view it.");
    }

    $stmt_templates = $pdo->prepare("SELECT * FROM email_templates WHERE team_id = ? ORDER BY template_name ASC");
    $stmt_templates->execute([$team_id]);
    $templates = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_activities = $pdo->prepare("SELECT a.*, u.username FROM lead_activities a JOIN users u ON a.user_id = u.id WHERE a.lead_id = ? ORDER BY a.activity_date DESC");
    $stmt_activities->execute([$lead_id]);
    $activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);

    $stmt_tasks = $pdo->prepare("SELECT * FROM lead_tasks WHERE lead_id = ? ORDER BY due_date ASC");
    $stmt_tasks->execute([$lead_id]);
    $tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error on lead_details.php for user {$user_id}: " . $e->getMessage());
    die("A database error occurred. Please check the logs.");
}

$page_title = 'Lead Details: ' . htmlspecialchars($lead['company_name']);
// Make the CSRF token available to the header so it can be placed in the body tag
$GLOBALS['csrf_token_for_body'] = $csrf_token; 
include 'partials/header.php';
?>

<!-- THE FIX IS HERE: The body tag now contains all the data our JS needs -->
<body data-lead-id="<?php echo $lead['id']; ?>" 
      data-business-id="<?php echo $lead['business_id']; ?>"
      data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">

<div class="container-fluid">
    <a href="leads.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Leads</a>
    <h2 class="mb-1"><?php echo htmlspecialchars($lead['company_name']); ?></h2>
    <p class="lead text-muted">Status: <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($lead['status']); ?></span></p>

    <div class="row g-4">
        <!-- Left Column: Activity & Tasks -->
        <div class="col-lg-7">
            <!-- Activity Timeline Card -->
            <div class="card mb-4">
                <div class="card-header"><h4 class="mb-0">Activity Timeline</h4></div>
                <div class="card-body">
                    <form action="lead_details.php?id=<?php echo $lead_id; ?>" method="POST" class="mb-4">
                        <input type="hidden" name="log_activity" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="mb-2"><textarea name="notes" class="form-control" rows="3" placeholder="Log a call, email, meeting, etc..." required></textarea></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <select name="activity_type" class="form-select w-auto">
                                <option>Note</option>
                                <option>Call</option>
                                <option>Email</option>
                                <option>WhatsApp</option>
                                <option>Meeting</option>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Log Activity</button>
                        </div>
                    </form>
                    <hr>
                    <?php if (empty($activities)): ?>
                        <p class="text-center text-muted">No activities have been logged yet.</p>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="d-flex mb-4">
                            <div class="me-3 text-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi <?php 
                                        switch($activity['activity_type']){ 
                                            case 'Call': echo 'bi-telephone-fill'; break; 
                                            case 'Email': echo 'bi-envelope-fill'; break; 
                                            case 'WhatsApp': echo 'bi-whatsapp'; break; 
                                            case 'Meeting': echo 'bi-people-fill'; break; 
                                            default: echo 'bi-sticky-fill'; 
                                        } ?> fs-5 text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1"><strong><?php echo htmlspecialchars($activity['activity_type']); ?></strong> by <strong><?php echo htmlspecialchars($activity['username']); ?></strong></p>
                                <p class="mb-1 bg-light border p-2 rounded"><?php echo nl2br(htmlspecialchars($activity['notes'])); ?></p>
                                <small class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($activity['activity_date'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tasks Card -->
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Tasks</h4></div>
                <div class="card-body">
                    <form action="lead_details.php?id=<?php echo $lead_id; ?>" method="POST">
                        <input type="hidden" name="add_task" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="input-group mb-2">
                            <span class="input-group-text">Task</span>
                            <input type="text" name="title" class="form-control" placeholder="Follow up on proposal..." required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">Due</span>
                            <input type="datetime-local" name="due_date" class="form-control" required>
                            <button type="submit" class="btn btn-secondary">Add</button>
                        </div>
                    </form>
                </div>
                <?php if (!empty($tasks)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($tasks as $task): ?>
                    <li class="list-group-item">
                        <form action="lead_details.php?id=<?php echo $lead_id; ?>" method="POST" class="d-flex align-items-center form-check">
                            <input type="hidden" name="toggle_task_status" value="1">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="checkbox" class="form-check-input me-3" onchange="this.form.submit()" <?php if($task['status']==='Completed') echo 'checked'; ?> id="task-<?php echo $task['id']; ?>">
                            <label for="task-<?php echo $task['id']; ?>" class="<?php if($task['status']==='Completed') echo 'text-decoration-line-through text-muted'; ?>">
                                <?php echo htmlspecialchars($task['title']); ?><br>
                                <small class="<?php echo (new DateTime() > new DateTime($task['due_date']) && $task['status'] === 'Pending') ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                    Due: <?php echo date('M d, g:i A', strtotime($task['due_date'])); ?>
                                </small>
                            </label>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Details & Actions -->
        <div class="col-lg-5">
            <!-- AI Customer Profile Analysis Card -->
            <div class="card mb-4">
                <div class="card-header"><h4 class="mb-0"><i class="bi bi-person-bounding-box me-2"></i>AI Customer Profile Analysis</h4></div>
                <div class="card-body">
                    <div id="customer-profile-content">
                        <?php if (empty($lead['ai_icp']) && empty($lead['ai_persona'])): ?>
                            <!-- State 1: Profile has NOT been generated yet -->
                            <div id="generate-profile-container" class="text-center">
                                <p>Generate an Ideal Customer Profile (ICP) and a Customer Persona to better understand this lead's target audience.</p>
                                <button id="generate-profile-btn" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm d-none"></span>
                                    <i class="bi bi-magic"></i> Generate Profile with AI
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- State 2: Profile HAS been generated -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Ideal Customer Profile (ICP)</h5>
                                    <?php echo !empty($lead['ai_icp']) ? nl2br(htmlspecialchars($lead['ai_icp'])) : '<p class="text-muted">ICP not generated yet.</p>'; ?>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <h5>Customer Persona</h5>
                                    <?php echo !empty($lead['ai_persona']) ? nl2br(htmlspecialchars($lead['ai_persona'])) : '<p class="text-muted">Persona not generated yet.</p>'; ?>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <button id="generate-profile-btn" class="btn btn-outline-primary btn-sm">
                                    <span class="spinner-border spinner-border-sm d-none"></span>
                                    <i class="bi bi-arrow-clockwise"></i> Regenerate Profile
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header"><h4 class="mb-0">Actions</h4></div>
                <div class="card-body d-grid gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                        <i class="bi bi-envelope-fill me-2"></i>Send Email
                    </button>
                    <?php if (!empty($lead['phone_number']) && $lead['phone_number'] !== 'N/A'): ?>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#whatsappModal">
                        <i class="bi bi-whatsapp me-2"></i>Send WhatsApp Message
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-info" onclick="generateMarketingInsights(<?php echo $lead['business_id']; ?>)">
                        <i class="bi bi-lightbulb me-2"></i>Generate Marketing Insights
                    </button>
                </div>
            </div>

            <!-- Marketing Insights Card (Initially Hidden) -->
            <div class="card mb-4" id="insights-card" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Marketing Insights</h4>
                    <button class="btn btn-sm btn-outline-secondary" onclick="closeInsights()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="insights-loading" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Generating insights...</span>
                        </div>
                        <p class="mt-2 text-muted">Analyzing market data...</p>
                    </div>
                    <div id="insights-content"></div>
                </div>
            </div>

            <!-- Business Details Card -->
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Business Details</h4></div>
                <div class="card-body">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($lead['email'] ?? 'Not available'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($lead['phone_number'] ?? 'Not available'); ?></p>
                    <p><strong>Website:</strong> 
                        <?php if(!empty($lead['website']) && $lead['website'] != 'N/A'): ?>
                            <a href="<?php echo htmlspecialchars($lead['website']); ?>" target="_blank">Visit Site</a>
                        <?php else: ?>
                            Not available
                        <?php endif; ?>
                    </p>
                    <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($lead['address'])); ?></p>
                    <?php if(!empty($lead['description'])): ?>
                    <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($lead['description'])); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($lead['ai_score'])): ?>
                    <div class="mt-3 p-3 bg-light rounded">
                        <p class="mb-1"><strong>AI Lead Score:</strong> 
                            <span class="badge bg-<?php echo $lead['ai_score'] >= 70 ? 'success' : ($lead['ai_score'] >= 50 ? 'warning' : 'danger'); ?> fs-6">
                                <?php echo $lead['ai_score']; ?>/100
                            </span>
                        </p>
                        <?php if(!empty($lead['ai_reasoning'])): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($lead['ai_reasoning']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp Message Modal -->
<div class="modal fade" id="whatsappModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-whatsapp me-2 text-success"></i>
          Send WhatsApp Message to <?php echo htmlspecialchars($lead['company_name']); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($lead['phone_number']); ?>" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Message Purpose</label>
            <select id="whatsapp-goal" class="form-select">
              <option value="business_collaboration">Business Collaboration Proposal</option>
              <option value="partnership_request">Partnership Request</option>
              <option value="raw_material_supply">Raw Material Supply Offer</option>
              <option value="service_introduction">Service Introduction</option>
              <option value="vendor_inquiry">Vendor Inquiry</option>
              <option value="bulk_order_inquiry">Bulk Order Inquiry</option>
              <option value="franchise_opportunity">Franchise Opportunity</option>
              <option value="distribution_partnership">Distribution Partnership</option>
            </select>
          </div>
          <div class="d-grid">
            <button type="button" id="draft-whatsapp-btn" class="btn btn-success">
              <span class="spinner-border spinner-border-sm d-none"></span>
              <i class="bi bi-magic me-2"></i>Generate Message
            </button>
          </div>
          
          <!-- Draft Result Section -->
          <div id="whatsapp-draft-result" style="display: none;" class="mt-4">
            <label class="form-label">Generated Message</label>
            <textarea id="whatsapp-draft-text" class="form-control" rows="6" readonly></textarea>
            <div id="send-wa-btn-placeholder" class="mt-3"></div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Email to <?php echo htmlspecialchars($lead['company_name']); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div id="email-alert-placeholder"></div>
          <div class="mb-3">
            <label class="form-label">To Email</label>
            <input type="email" id="to-email" class="form-control" value="<?php echo htmlspecialchars($lead['email'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Load Template</label>
            <select id="email-template-select" class="form-select">
                <option value="" data-subject="" data-body="">-- No Template --</option>
                <?php foreach ($templates as $template): ?>
                <option value="<?php echo $template['id']; ?>" 
                        data-subject="<?php echo htmlspecialchars($template['subject']); ?>" 
                        data-body="<?php echo htmlspecialchars($template['body']); ?>">
                    <?php echo htmlspecialchars($template['template_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" id="email-subject" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Body</label>
            <textarea id="email-body" class="form-control" rows="8"></textarea>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="send-email-btn" class="btn btn-primary">
          <span class="spinner-border spinner-border-sm d-none"></span> Send Email
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Include Marked.js for markdown parsing -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="assets/js/lead_details.js"></script>

</body>
</html>

<?php include 'partials/footer.php'; ?>