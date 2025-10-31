<?php
// approve_user.php

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// --- SECURITY: ONLY SUPER ADMINS CAN RUN THIS SCRIPT ---
$is_superadmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin');
if (!$is_superadmin) {
    die("Access Denied.");
}

// --- SECURITY: CSRF Protection ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token.');
}

$user_to_approve_id = $_POST['user_id'] ?? 0;

if ($user_to_approve_id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$user_to_approve_id]);
    } catch (PDOException $e) {
        // Log the error
        error_log("Failed to approve user {$user_to_approve_id}: " . $e->getMessage());
    }
}

// Redirect back to the Super Admin dashboard
header("Location: superadmin.php");
exit;