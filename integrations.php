<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';
$user_id = $_SESSION['user_id'];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['zeptomail_token'] ?? '');
    $from_email = trim($_POST['zeptomail_from_email'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE company_profile SET zeptomail_token = ?, zeptomail_from_email = ? WHERE user_id = ?");
    $stmt->execute([$token, $from_email, $user_id]);
    $success_message = "Zeptomail settings saved successfully!";
}

$profile = $pdo->query("SELECT zeptomail_token, zeptomail_from_email FROM company_profile WHERE user_id = " . (int)$user_id)->fetch(PDO::FETCH_ASSOC);

$page_title = 'API Integrations';
include 'partials/header.php';
?>
<div class="container">
    <h2 class="mb-4">API Integrations</h2>
    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Zoho Zeptomail for Transactional Email</h5></div>
        <div class="card-body">
            <p>Integrate with Zoho Zeptomail to send high-deliverability emails (proposals, follow-ups) directly from your CRM.</p>
            <form action="integrations.php" method="POST">
                <div class="mb-3">
                    <label for="zeptomail_token" class="form-label">Send Mail Token</label>
                    <input type="password" class="form-control" id="zeptomail_token" name="zeptomail_token" value="<?php echo htmlspecialchars($profile['zeptomail_token'] ?? ''); ?>">
                    <div class="form-text">Get this from your Zoho Zeptomail account under "Mail Agents".</div>
                </div>
                <div class="mb-3">
                    <label for="zeptomail_from_email" class="form-label">Verified "From" Email Address</label>
                    <input type="email" class="form-control" id="zeptomail_from_email" name="zeptomail_from_email" value="<?php echo htmlspecialchars($profile['zeptomail_from_email'] ?? ''); ?>" placeholder="e.g., sales@yourcompany.com">
                    <div class="form-text">This must be a verified sending domain/email in your Zeptomail account.</div>
                </div>
                <button type="submit" class="btn btn-primary">Save Zeptomail Settings</button>
            </form>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>