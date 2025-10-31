<?php
// simba_log.php

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// CRITICAL SECURITY: Only Super Admins can access this page.
if (!isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    die("Access Denied. You do not have permission to view this page.");
}

// Simple pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Fetch total count for pagination
$total_queries = $pdo->query("SELECT COUNT(*) FROM simba_queries")->fetchColumn();
$total_pages = ceil($total_queries / $limit);

// Fetch paginated log data
$stmt = $pdo->prepare(
    "SELECT q.*, u.username, t.team_name 
     FROM simba_queries q 
     JOIN users u ON q.user_id = u.id 
     JOIN teams t ON q.team_id = t.id 
     ORDER BY q.timestamp DESC 
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$queries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Super Admin - Simba Query Log';
include 'partials/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Simba AI Assistant - Query Log</h2>
    <p class="text-muted">Review questions asked by all users to identify new feature opportunities and improve Simba's responses.</p>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Team</th>
                            <th>User's Question</th>
                            <th>Simba's Intent</th>
                            <th>Simba's Final Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($queries)): ?>
                            <tr><td colspan="6" class="text-center">No queries have been logged yet.</td></tr>
                        <?php else: foreach ($queries as $query): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo date('Y-m-d H:i:s', strtotime($query['timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($query['username']); ?></td>
                                <td><?php echo htmlspecialchars($query['team_name']); ?></td>
                                <td style="max-width: 300px; word-wrap: break-word;"><?php echo htmlspecialchars($query['user_query']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($query['simba_intent']); ?></span></td>
                                <td style="max-width: 400px; word-wrap: break-word;"><?php echo htmlspecialchars($query['simba_response']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<?php include 'partials/footer.php'; ?>