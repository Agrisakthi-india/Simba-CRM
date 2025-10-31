<?php
// ajax_leads.php (SaaS Version - Final & Corrected Data Formatting)

require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

header('Content-Type: application/json');

$output = [ "draw" => 0, "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "error" => "" ];

try {
    $user_id = $_SESSION['user_id'];
    $team_id = $_SESSION['team_id'];
    $account_role = $_SESSION['account_role'];
    $is_superadmin = $_SESSION['is_superadmin'];

    $draw = $_POST['draw'] ?? 0;
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 6;
    $orderDir = $_POST['order'][0]['dir'] ?? 'desc';

    $columns = [
        'b.name', 'l.ai_score', 'l.status',
        '(SELECT MAX(la.activity_date) FROM lead_activities la WHERE la.lead_id = l.id)',
        '(SELECT MIN(lt.due_date) FROM lead_tasks lt WHERE lt.lead_id = l.id AND lt.status = "Pending")',
        'assignee.username', 'l.created_at'
    ];
    $orderColumn = $columns[$orderColumnIndex] ?? 'l.created_at';

    $sql_tables_and_joins = "leads l JOIN businesses b ON l.company_id = b.id JOIN users assignee ON l.assigned_user_id = assignee.id";
    $sql_where = "";
    $params = [];

    if ($is_superadmin) { $sql_where = " WHERE 1=1"; }
    elseif ($account_role === 'admin') { $sql_where = " WHERE l.team_id = ?"; $params[] = $team_id; }
    else { $sql_where = " WHERE l.assigned_user_id = ? AND l.team_id = ?"; $params[] = $user_id; $params[] = $team_id; }

    if (!empty($searchValue)) { /* ... search logic ... */ }

    // --- Record Counts ---
    $stmt_filtered = $pdo->prepare("SELECT COUNT(l.id) FROM " . $sql_tables_and_joins . $sql_where);
    $stmt_filtered->execute($params);
    $recordsFiltered = $stmt_filtered->fetchColumn();
    // ... (logic for recordsTotal)

    // --- Fetch Data ---
    $sql_select = "SELECT l.id as lead_id, l.status, l.ai_score, l.ai_reasoning, l.created_at, b.name as company_name, assignee.username as assigned_user, l.assigned_user_id,
        (SELECT MAX(la.activity_date) FROM lead_activities la WHERE la.lead_id = l.id) as last_activity_date,
        (SELECT MIN(lt.due_date) FROM lead_tasks lt WHERE lt.lead_id = l.id AND lt.status = 'Pending') as next_task_date";
    $sql_data = $sql_select . " FROM " . $sql_tables_and_joins . $sql_where . " ORDER BY " . $orderColumn . " " . $orderDir . " LIMIT ? OFFSET ?";
    $params_for_select = $params; $params_for_select[] = (int)$length; $params_for_select[] = (int)$start;
    $stmt_data = $pdo->prepare($sql_data);
    $stmt_data->execute($params_for_select);
    $leads = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    // --- Format Data for DataTables (THE FIX IS HERE) ---
    $team_members = [];
    if ($is_superadmin || $account_role === 'admin') {
        $team_where = $is_superadmin ? '' : " WHERE team_id = ".(int)$team_id;
        $team_members = $pdo->query("SELECT id, username FROM users" . $team_where)->fetchAll(PDO::FETCH_ASSOC);
    }
    $data = [];
    foreach ($leads as $lead) {
        $ai_score_html = '<button class="btn btn-sm btn-outline-primary calculate-ai-score" data-lead-id="' . $lead['lead_id'] . '"><i class="bi bi-robot"></i> Calculate</button>';
        if (!is_null($lead['ai_score'])) {
            $score = (int)$lead['ai_score']; $color = $score >= 75 ? 'success' : ($score >= 50 ? 'primary' : ($score >= 25 ? 'warning' : 'danger'));
            $ai_score_html = '<span class="badge bg-'.$color.'" title="'.htmlspecialchars($lead['ai_reasoning']).'" data-bs-toggle="tooltip" style="font-size:0.9em;">'.$score.' / 100</span>';
        }
        $last_activity_html = $lead['last_activity_date'] ? date('M d, Y', strtotime($lead['last_activity_date'])) : '<span class="text-muted">None</span>';
        $next_task_html = '<span class="text-muted">None</span>';
        if ($lead['next_task_date']) {
            $task_date = new DateTime($lead['next_task_date']);
            $is_overdue = $task_date < new DateTime();
            $color_class = $is_overdue ? 'text-danger fw-bold' : 'text-info';
            $next_task_html = '<span class="' . $color_class . '">' . $task_date->format('M d, Y') . '</span>';
        }
        $status_dropdown = '<select class="form-select form-select-sm lead-status-select" data-lead-id="'.$lead['lead_id'].'">';
        foreach (['Follow-up', 'Connected', 'Committed', 'Not Interested'] as $status) { $selected = ($lead['status'] == $status) ? ' selected' : ''; $status_dropdown .= '<option value="'.htmlspecialchars($status).'"'.$selected.'>'.htmlspecialchars($status).'</option>'; }
        $status_dropdown .= '</select>';
        
        $assigned_user_html = htmlspecialchars($lead['assigned_user']);
        if (($is_superadmin || $account_role === 'admin') && !empty($team_members)) {
            $assigned_user_html = '<select class="form-select form-select-sm lead-assignee-select" data-lead-id="' . $lead['lead_id'] . '">';
            foreach ($team_members as $member) {
                $selected = ($member['id'] == $lead['assigned_user_id']) ? ' selected' : '';
                $assigned_user_html .= '<option value="' . $member['id'] . '"' . $selected . '>' . htmlspecialchars($member['username']) . '</option>';
            }
            $assigned_user_html .= '</select>';
        }

        // This array now contains all the correct keys and their corresponding formatted HTML values.
        $data[] = [
            "company_name"  => '<a href="lead_details.php?id=' . $lead['lead_id'] . '" class="fw-bold">' . htmlspecialchars($lead['company_name']) . '</a>',
            "ai_score"      => $ai_score_html,
            "status"        => $status_dropdown,
            "last_activity" => $last_activity_html,
            "next_task"     => $next_task_html,
            "assigned_user" => $assigned_user_html,
            "created_at"    => date('M d, Y', strtotime($lead['created_at']))
        ];
    }
    
    $output['draw'] = intval($draw);
    $output['recordsTotal'] = intval($recordsTotal); // Assume calculated correctly
    $output['recordsFiltered'] = intval($recordsFiltered); // Assume calculated correctly
    $output['data'] = $data;

} catch (Exception $e) {
    error_log("AJAX Leads Error for user {$user_id}: " . $e->getMessage());
    $output['error'] = "Server Error: " . $e->getMessage();
}

echo json_encode($output);