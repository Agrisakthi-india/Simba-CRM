<?php
// login.php (SaaS Teams Version - Final)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
require_once __DIR__ . '/sme_config.php';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($username) || empty($password)) { $error_message = "Username and password are required."; }
    else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, is_superadmin, team_id, account_role, is_approved FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_superadmin'] || $user['is_approved'] == 1) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_superadmin'] = (bool)$user['is_superadmin'];
                    $_SESSION['team_id'] = $user['team_id'];
                    $_SESSION['account_role'] = $user['account_role'];
                    header("Location: index.php");
                    exit;
                } else { $error_message = "Your account is awaiting administrator approval."; }
            } else { $error_message = "Invalid username or password."; }
        } catch (PDOException $e) { error_log("Login DB Error: " . $e->getMessage()); $error_message = "A system error occurred."; }
    }
}
$page_title = 'Login - Zimba CRM | v2';
include 'partials/header_auth.php';
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card login-card p-4">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Zimba SME CRM | V2 Login</h3>
                    <?php if (!empty($error_message)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
                    <form action="login.php" method="POST">
                        <div class="mb-3"><label for="username" class="form-label">Username</label><input type="text" class="form-control" name="username" required></div>
                        <div class="mb-3"><label for="password" class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
                        <div class="d-grid mt-4"><button type="submit" class="btn btn-login btn-lg">Log In</button></div>
                    </form>
                    <div class="text-center mt-3"><a href="register.php">Don't have an account? Register</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'partials/footer_auth.php'; ?>