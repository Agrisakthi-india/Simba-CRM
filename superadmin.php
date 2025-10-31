<?php
// superadmin.php

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// --- CRITICAL SECURITY: Only Super Admins can access this page ---
if (!isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    // If a non-superadmin tries to access this page, block them.
    die("Access Denied. You do not have sufficient permissions to view this page.");
}

$user_id = $_SESSION['user_id'];

// --- HANDLE APPROVAL/DISAPPROVAL ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    $user_to_update_id = $_POST['user_id'] ?? 0;
    
    if ($user_to_update_id > 0) {
        if (isset($_POST['approve_user'])) {
            $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$user_to_update_id]);
        } elseif (isset($_POST['disapprove_user'])) {
            $stmt = $pdo->prepare("UPDATE users SET is_approved = 0 WHERE id = ?");
            $stmt->execute([$user_to_update_id]);
        }
    }
    // Redirect to the same page to see the changes
    header("Location: superadmin.php");
    exit;
}

// --- FETCH ALL USERS FOR DISPLAY ---
try {
    // Fetch all users and join with their team name for a comprehensive view
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.is_approved, u.account_role, u.is_superadmin, t.team_name 
        FROM users u 
        LEFT JOIN teams t ON u.team_id = t.id 
        ORDER BY u.created_at DESC
    ");
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: Could not fetch users. " . $e->getMessage());
}

$page_title = 'Super Admin Panel - User Management';
include 'partials/header.php';
?>

<div class="container">
    <h2 class="mb-4">Super Admin - User Management</h2>
    
    <!-- NEW: Quick Navigation Links -->
    <div class="list-group list-group-horizontal-md mb-4">
        <a href="superadmin.php" class="list-group-item list-group-item-action active" aria-current="true">
            <i class="bi bi-people-fill me-2"></i>User Management
        </a>
        <a href="simba_log.php" class="list-group-item list-group-item-action">
            <i class="bi bi-chat-left-text-fill me-2"></i>Simba Query Log
        </a>
    </div>
    <div class="card">
        <div class="card-header">
            All Registered Users
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Team Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['is_superadmin']): ?>
                                        <span class="badge bg-danger">Super Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['team_name'] ?? 'N/A'); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($user['account_role'])); ?></td>
                                <td>
                                    <?php if ($user['is_approved']): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?else: ?>
                                        <span class="badge bg-warning text-dark">Pending Approval</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!$user['is_superadmin']): // A superadmin cannot disapprove themselves ?>
                                        <form action="superadmin.php" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <?php if ($user['is_approved']): ?>
                                                <button type="submit" name="disapprove_user" class="btn btn-sm btn-outline-danger">Disapprove</button>
                                            <?php else: ?>
                                                <button type="submit" name="approve_user" class="btn btn-sm btn-success">Approve</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>