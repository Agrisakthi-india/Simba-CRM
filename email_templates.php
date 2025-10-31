<?php
// email_templates.php

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$is_superadmin = $_SESSION['is_superadmin'];

// --- HANDLE POST REQUESTS (Create/Update/Delete) ---
// This is handled via AJAX now, but this is a good place for future non-AJAX actions

// --- FETCH DATA FOR THE PAGE ---
try {
    // Admins and Super Admins see all templates for their team/all teams
    // Members only see templates created by them
    $sql = "SELECT * FROM email_templates";
    $params = [];
    if (!$is_superadmin) {
        $sql .= " WHERE team_id = ?";
        $params[] = $team_id;
    }
    $sql .= " ORDER BY template_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Email Templates Error for user {$user_id}: " . $e->getMessage());
    die("A database error occurred.");
}

$page_title = 'Email Template Manager';
include 'partials/header.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Email Template Manager</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal" id="add-new-template-btn">
            <i class="bi bi-plus-circle me-2"></i>Create New Template
        </button>
    </div>

    <!-- Template List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Subject</th>
                            <th>Last Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="template-list-body">
                        <?php if (empty($templates)): ?>
                            <tr><td colspan="4" class="text-center text-muted">You haven't created any email templates yet.</td></tr>
                        <?php else: foreach ($templates as $template): ?>
                            <tr id="template-row-<?php echo $template['id']; ?>">
                                <td class="fw-bold"><?php echo htmlspecialchars($template['template_name']); ?></td>
                                <td><?php echo htmlspecialchars($template['subject']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($template['created_at'])); ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary edit-template-btn"
                                            data-id="<?php echo $template['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($template['template_name']); ?>"
                                            data-subject="<?php echo htmlspecialchars($template['subject']); ?>"
                                            data-body="<?php echo htmlspecialchars($template['body']); ?>">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-template-btn" data-id="<?php echo $template['id']; ?>">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="templateModalLabel">Create New Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="template-form">
          <div class="modal-body">
              <input type="hidden" id="template-id" name="template_id" value="0">
              <div class="mb-3">
                  <label for="template-name" class="form-label">Template Name*</label>
                  <input type="text" id="template-name" name="template_name" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label for="template-subject" class="form-label">Email Subject*</label>
                  <input type="text" id="template-subject" name="template_subject" class="form-control" required>
              </div>
              <div class="mb-3">
                  <label class="form-label">Email Body</label>
                  <!-- THE FIX: Replaced <textarea> with a <div> for Quill -->
                  <div id="template-body-editor" style="height: 250px;"></div>
                  <div class="form-text">You can use placeholders like `[Business Name]` or `[Contact Name]`.</div>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" id="save-template-btn" class="btn btn-primary">
                <span class="spinner-border spinner-border-sm d-none"></span> Save Template
            </button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- NEW: Quill.js library script -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
<!-- Link to our external JS file (which we will update next) -->
<script src="assets/js/email_templates.js"></script>

<?php include 'partials/footer.php'; ?>