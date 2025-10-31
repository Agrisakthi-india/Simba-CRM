<?php
// partials/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Generate and make the CSRF token globally available for use in body data attributes
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$GLOBALS['csrf_token_for_body'] = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- THE FIX IS HERE: Added 'unsafe-eval' to the script-src directive -->
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self'; 
        script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com https://cdn.tiny.cloud https://unpkg.com 'unsafe-eval'; 
        style-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net https://cdn.tiny.cloud https://unpkg.com 'unsafe-inline'; 
        font-src 'self' https://cdn.jsdelivr.net data:; 
        img-src 'self' data: https://cdn.tiny.cloud https://sp.tinymce.com https://unpkg.com *.tile.openstreetmap.org; 
        connect-src 'self' https://cdn.tiny.cloud;
    ">
    
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'ZIMBA SME CRM |v2'; ?></title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- NEW: Add Quill.js stylesheet -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
    
    <!-- THE FIX: This is the complete, single-line, and correct Content Security Policy -->
    <!--<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://code.jquery.com https://unpkg.com https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com https://cdn.tiny.cloud; style-src 'self' https://cdn.jsdelivr.net https://unpkg.com https://cdn.datatables.net https://cdn.tiny.cloud 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: https://cdn.tiny.cloud https://sp.tinymce.com; connect-src 'self' https://cdn.tiny.cloud;">-->
    
   
    
    <!-- Custom Application CSS -->
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: 600; }
        .filter-bar { background-color: #ffffff; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 15px rgba(0,0,0,.05); }
        .btn-purple-ai { background-color: #6f42c1; color: white; border-color: #6f42c1; }
        .btn-purple-ai:hover, .btn-purple-ai:focus { background-color: #5a349c; color: white; border-color: #5a349c; }
        .card-actions { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 0.5rem; }
        /* Simba Widget Styling */
        #simba-chat-icon { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background: linear-gradient(45deg, #6f42c1, #9733EE); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; z-index: 1060; transition: transform 0.2s ease; }
        #simba-chat-icon:hover { transform: scale(1.1); }
        #simba-chat-window { position: fixed; bottom: 90px; right: 20px; width: 350px; max-width: 90%; height: 500px; background: #fff; border-radius: 15px; display: flex; flex-direction: column; overflow: hidden; z-index: 1060; border: 1px solid #dee2e6; }
        .chat-header { background: #343a40; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; }
        .chat-header h5 { margin: 0; font-size: 1rem; }
        #simba-close-btn { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; line-height: 1; padding: 0.25rem 0.5rem; }
        .chat-body { flex-grow: 1; padding: 15px; overflow-y: auto; background-color: #f8f9fa; }
        .chat-message { display: flex; margin-bottom: 15px; max-width: 100%; }
        .chat-message.user { justify-content: flex-end; }
        .chat-message .message-content { max-width: 85%; padding: 10px 15px; border-radius: 18px; line-height: 1.4; word-wrap: break-word; }
        .chat-message.simba .message-content { background: #e9ecef; color: #212529; border-top-left-radius: 0; }
        .chat-message.user .message-content { background: #0d6efd; color: white; border-top-right-radius: 0; }
        .chat-footer { padding: 10px; border-top: 1px solid #eee; background: #fff; }
        #simba-chat-form { display: flex; }
        #simba-chat-input { flex-grow: 1; border: 1px solid #ccc; border-radius: 20px; padding: 8px 15px; }
        #simba-send-btn { background: #0d6efd; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; margin-left: 10px; flex-shrink: 0; }
        .prompt-starters { margin-top: 10px; }
        .prompt-btn { background: #fff; border: 1px solid #ccc; color: #0d6efd; border-radius: 15px; padding: 5px 10px; font-size: 0.8rem; margin: 2px; cursor: pointer; }
    </style>
</head>
<body data-csrf-token="<?php echo htmlspecialchars($GLOBALS['csrf_token_for_body']); ?>">
    

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><i class="bi bi-person-rolodex"></i> Zimba SME CRM | v2 </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="leads.php"><i class="bi bi-funnel-fill me-1"></i>Leads</a></li>
        <li class="nav-item"><a class="nav-link" href="crm_ai.php"><i class="bi bi-robot me-1"></i>AI Assistant</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-bar-chart-line-fill me-1"></i> Reports</a>
          <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
            <li><a class="dropdown-item" href="reports.php">Lead Overview</a></li>
            <li><a class="dropdown-item" href="funnel_report.php">Sales Funnel</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="map.php"><i class="bi bi-map-fill me-1"></i>Map</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-database-fill-add me-1"></i> Data</a>
          <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
            <li><a class="dropdown-item" href="add_listing.php">Add Listing</a></li>
            <li><a class="dropdown-item" href="upload.php">Upload CSV</a></li>
          </ul>
        </li>
        
        <!-- User Account Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle me-1"></i> <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Account'; ?></a>
          <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php">Company Profile</a></li>
            <?php if (isset($_SESSION['account_role']) && $_SESSION['account_role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="team.php">Manage Team</a></li>
            <?php endif; ?>
            <?php if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item bg-warning text-dark" href="superadmin.php"><i class="bi bi-shield-lock-fill me-2"></i>Super Admin Panel</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container-fluid my-4">