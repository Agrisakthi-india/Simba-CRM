<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];

// Security: Only Account Admins can access this page.
if ($account_role !== 'admin') {
    die("Access Denied. You are not an account administrator.");
}

// Handle POST requests (add member, update permissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['add_member'])) {
        // Logic to add a new team member (simplified: create user, assign to team)
        // In a real app, this would be an invitation system.
        $username = $_POST['username'];
        $password = $_POST['password']; // In a real app, generate a random one and email it.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, team_id, account_role) VALUES (?, ?, ?, 'member')");
        $stmt->execute([$username, $hashed_password, $team_id]);
    }
    if (isset($_POST['update_permissions'])) {
        $member_id = $_POST['member_id'];
        $categories = $_POST['categories'] ?? [];
        
        // Clear old permissions
        $stmt_delete = $pdo->prepare("DELETE FROM user_category_access WHERE user_id = ? AND team_id = ?");
        $stmt_delete->execute([$member_id, $team_id]);
        
        // Insert new permissions
        $stmt_insert = $pdo->prepare("INSERT INTO user_category_access (user_id, team_id, category) VALUES (?, ?, ?)");
        foreach ($categories as $category) {
            $stmt_insert->execute([$member_id, $team_id, $category]);
        }
    }
    header("Location: team.php");
    exit;
}

// Fetch data for the page
$team_members = $pdo->prepare("SELECT id, username FROM users WHERE team_id = ?");
$team_members->execute([$team_id]);

$all_categories_stmt = $pdo->prepare("SELECT DISTINCT category FROM businesses WHERE team_id = ? AND category IS NOT NULL AND category != ''");
$all_categories_stmt->execute([$team_id]);
$all_categories = $all_categories_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Team Management';
include 'partials/header.php';
?>
<div class="container">
    <h2>Team Management</h2>
    <!-- Section to add new members -->
    <div class="card mb-4">
        <div class="card-header">Add New Team Member</div>
        <div class="card-body">
            <form action="team.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="row g-3">
                    <div class="col-md-5"><input type="text" name="username" class="form-control" placeholder="Username/Email" required></div>
                    <div class="col-md-5"><input type="password" name="password" class="form-control" placeholder="Temporary Password" required></div>
                    <div class="col-md-2"><button type="submit" name="add_member" class="btn btn-primary w-100">Add Member</button></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Section to manage existing members -->
    <div class="card">
        <div class="card-header">Manage Member Permissions</div>
        <div class="card-body">
            <?php foreach ($team_members as $member): if ($member['id'] == $user_id) continue; // Skip self ?>
                <h5><?php echo htmlspecialchars($member['username']); ?></h5>
                <form action="team.php" method="POST" class="mb-4 border-bottom pb-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                    <label class="form-label">Allowed Categories:</label>
                    <div class="row">
                        <?php
                        $member_permissions_stmt = $pdo->prepare("SELECT category FROM user_category_access WHERE user_id = ?");
                        $member_permissions_stmt->execute([$member['id']]);
                        $member_permissions = $member_permissions_stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($all_categories as $category):
                        ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo htmlspecialchars($category); ?>" id="cat-<?php echo $member['id'] . '-' . htmlspecialchars($category); ?>" <?php if (in_array($category, $member_permissions)) echo 'checked'; ?>>
                                    <label class="form-check-label" for="cat-<?php echo $member['id'] . '-' . htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="update_permissions" class="btn btn-success btn-sm mt-2">Update Permissions</button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>