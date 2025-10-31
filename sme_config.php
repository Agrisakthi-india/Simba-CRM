<?php
// sme_config.php (SaaS Version - Final)

// --- PRODUCTION-SAFE ERROR HANDLING ---
// In production, this will hide errors from users and log them to a file.
// To debug, you can temporarily change 'Off' to 'On'.
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
// Optional: Define a specific path for your error log file
// ini_set('error_log', '/path/to/your/private/logs/php_errors.log');
error_reporting(E_ALL);

// --- SECURE SESSION START ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'agrisakthiin_CRM');
define('DB_USER', 'agrisakthiin_CRM'); // Your DB username
define('DB_PASS', 'd_JfAdPxxxxx+');     // Your DB password

// --- ADD YOUR GEMINI API KEY HERE ---
define('GEMINI_API_KEY', 'AIxxxxxxxxx');

// 3. Site Configuration
define('SITE_URL', 'https://saas.agrisakthi.in/v2'); // Your base URL

// --- CSRF TOKEN GENERATION ---
// This creates a unique token to protect all POST forms from CSRF attacks.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- DATABASE CONNECTION (PDO) ---
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the database connection fails, log the error and show a generic message.
    error_log("DATABASE CONNECTION FAILED: " . $e->getMessage());
    http_response_code(503); // Service Unavailable
    die("Error: Unable to connect to the database. Please try again later.");
}

// --- HELPER FUNCTIONS ---
/**
 * Normalizes an Indian phone number to the +91XXXXXXXXXX format.
 *
 * @param string|null $phone The raw phone number string.
 * @return string|null The cleaned, normalized phone number or null if input is empty.
 */
function normalize_indian_phone($phone) {
    if (empty($phone)) {
        return null;
    }
    
    // 1. Remove all non-digit characters (+, -, spaces, etc.)
    $digits = preg_replace('/[^0-9]/', '', $phone);
    
    // 2. Remove leading 0 if it exists
    if (substr($digits, 0, 1) === '0') {
        $digits = substr($digits, 1);
    }
    
    // 3. Check if '91' is already the prefix for a 10-digit number
    if (strlen($digits) > 10 && strpos($digits, '91') === 0) {
        // It's likely already in the correct format, e.g., 919876543210
        return '+' . $digits;
    }
    
    // 4. If it's a 10-digit number, prepend '+91'
    if (strlen($digits) === 10) {
        return '+91' . $digits;
    }
    
    // If it's not a recognizable 10 or 12-digit format, return it as is but with a '+'
    // This handles landlines or other formats without losing them.
    return '+' . $digits;
}
/**
 * Generates a smart, responsive pagination block.
 * @param int $current_page The current page number.
 * @param int $total_pages The total number of pages.
 * @param array $params An array of GET parameters to preserve in the links.
 * @return string The generated HTML for the pagination control.
 */
function generate_pagination_links($current_page, $total_pages, $params = []) {
    if ($total_pages <= 1) return '';
    
    unset($params['page']); // Remove old page number to avoid duplication
    $query_string = http_build_query($params);
    $base_url = 'index.php?' . $query_string . (empty($query_string) ? '' : '&');

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center flex-wrap">';
    $window = 2; // Links to show on each side of the current page

    $html .= ($current_page > 1)
        ? '<li class="page-item"><a class="page-link" href="' . $base_url . 'page=1">« First</a></li><li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . ($current_page - 1) . '">Previous</a></li>'
        : '<li class="page-item disabled"><span class="page-link">« First</span></li><li class="page-item disabled"><span class="page-link">Previous</span></li>';

    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $window && $i <= $current_page + $window)) {
            $html .= ($i == $current_page)
                ? '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>'
                : '<li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . $i . '">' . $i . '</a></li>';
        } elseif ($i == $current_page - $window - 1 || $i == $current_page + $window + 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    $html .= ($current_page < $total_pages)
        ? '<li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . ($current_page + 1) . '">Next</a></li><li class="page-item"><a class="page-link" href="' . $base_url . 'page=' . $total_pages . '">Last »</a></li>'
        : '<li class="page-item disabled"><span class="page-link">Next</span></li><li class="page-item disabled"><span class="page-link">Last »</span></li>';

    $html .= '</ul></nav>';
    return $html;
}