<?php
session_start();

// If the user_id session variable is not set, the user is not logged in.
// Redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit; // Stop script execution
}