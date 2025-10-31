<?php
// register.php (SaaS Teams Version - Final & Corrected)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// If a user is already logged in, they shouldn't be here.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/sme_config.php';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters.";
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address as your username.";
    } else {
        try {
            // Check if username already exists
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_check->execute([$username]);
            if ($stmt_check->fetch()) {
                $error_message = "An account with this email already exists.";
            } else {
                // Use a transaction to ensure all or nothing is inserted
                $pdo->beginTransaction();

                // 1. Create the user (unapproved by default)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt_user->execute([$username, $hashed_password]);
                $user_id = $pdo->lastInsertId();

                // 2. Create a new team for this user, making them the owner
                $team_name = strtok($username, '@') . "'s Team"; // e.g., "john's Team"
                $stmt_team = $pdo->prepare("INSERT INTO teams (team_name, owner_id) VALUES (?, ?)");
                $stmt_team->execute([$team_name, $user_id]);
                $team_id = $pdo->lastInsertId();

                // 3. Make this user the 'admin' of their new team
                $stmt_update_user = $pdo->prepare("UPDATE users SET team_id = ?, account_role = 'admin' WHERE id = ?");
                $stmt_update_user->execute([$team_id, $user_id]);

                // 4. Create an empty company profile FOR THE USER. THIS IS THE FIX.
                $stmt_profile = $pdo->prepare("INSERT INTO company_profile (user_id) VALUES (?)");
                $stmt_profile->execute([$user_id]);
                
                // If all queries were successful, commit the transaction
                $pdo->commit();
                
                // Redirect to login with a success message
                $_SESSION['success_message'] = "Registration successful! Your account is now awaiting admin approval.";
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            // If any query failed, roll back the entire transaction
            $pdo->rollBack();
            error_log("Register DB Error: " . $e->getMessage());
            $error_message = "A system error occurred. Please try again.";
        }
    }
}
$page_title = 'Register - SME CRM SaaS';
include 'partials/header_auth.php';
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card login-card p-4">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Create Your Account</h3>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Email / Username</label>
                            <input type="email" class="form-control" name="username" required> <!-- Changed to type="email" -->
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-login btn-lg">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Already have an account? Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'partials/footer_auth.php'; ?>