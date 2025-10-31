<?php
// Start the session to store feedback messages
session_start();

require_once 'auth_check.php';

// Adjust the path to your config file
// require_once __DIR__ . '/../../sme_config.php';
require_once __DIR__ . '/sme_config.php';
// --- UPLOAD PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security Check 1: Was a file uploaded without errors?
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file'];
        $uploadDir = __DIR__ . '/uploads/'; // Create an 'uploads' directory for temporary storage
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Security Check 2: Validate file type (MIME type is more reliable than extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Invalid file type. Please upload a valid CSV file.'];
            header("Location: upload.php");
            exit;
        }

        // Security Check 3: Validate file size (e.g., max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'File is too large. Maximum size is 5MB.'];
            header("Location: upload.php");
            exit;
        }

        $fileHandle = fopen($file['tmp_name'], 'r');
        if ($fileHandle === false) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Could not open the uploaded file.'];
            header("Location: upload.php");
            exit;
        }

        // Skip the header row
        fgetcsv($fileHandle);

        // Use INSERT IGNORE to automatically skip rows with duplicate place_id's
        $sql = "INSERT IGNORE INTO businesses (state, district, city, category, name, address, rating, latitude, longitude, place_id, website, phone_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        $totalRows = 0;
        $insertedRows = 0;

        $pdo->beginTransaction();
        try {
            while (($data = fgetcsv($fileHandle, 1000, ",")) !== false) {
                if (count($data) < 11) continue; // Skip malformed rows
                
                $totalRows++;

                // Normalize and clean data
                $rating = !empty(trim($data[5])) ? (float)trim($data[5]) : null;
                $website = (trim($data[9]) === 'N/A') ? null : trim($data[9]);
                $phone_number = (trim($data[10]) === 'N/A') ? null : trim($data[10]);

                $stmt->execute([
    trim($data[0]), // District
    trim($data[1]), // City
    trim($data[2]), // State - NEW
    trim($data[3]), // Category
    trim($data[4]), // Name
    trim($data[5]), // Address
    $rating,
    trim($data[7]), // Latitude
    trim($data[8]), // Longitude
    trim($data[9]), // Place ID
    $website,
    $phone_number
]);
                
                // PDO::rowCount() tells us if the last insert was successful (1) or ignored (0)
                $insertedRows += $stmt->rowCount();
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'An error occurred during the database import: ' . $e->getMessage()];
            header("Location: upload.php");
            exit;
        }
        
        fclose($fileHandle);

        $skippedRows = $totalRows - $insertedRows;
        $_SESSION['flash_message'] = [
            'type' => 'success', 
            'text' => "<strong>Import Complete!</strong><br>
                       - Total rows processed: $totalRows<br>
                       - New records added: $insertedRows<br>
                       - Duplicate records skipped: $skippedRows"
        ];
        
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'No file was uploaded or an error occurred.'];
    }
    
    header("Location: upload.php");
    exit;
}

// Set SEO variables for the header
$page_title = 'Upload CSV Data - SME CRM';
$meta_description = 'Upload and append new business data to the SME CRM via a CSV file.';
$canonical_url = SITE_URL . '/upload.php';

include 'partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Upload and Append CSV Data</h3>
            </div>
            <div class="card-body">
                
                <?php
                // Display feedback message if it exists
                if (isset($_SESSION['flash_message'])) {
                    $message = $_SESSION['flash_message'];
                    echo '<div class="alert alert-' . htmlspecialchars($message['type']) . '" role="alert">' . $message['text'] . '</div>';
                    unset($_SESSION['flash_message']); // Clear the message after displaying
                }
                ?>

                <p class="card-text">Upload a CSV file to add new businesses to the database. Existing businesses (matched by 'Place ID') will be automatically skipped.</p>
                <p>The CSV must have the following columns in order:</p>
                <code>State,District, City, Category, Name, Address, Rating, Latitude, Longitude, Place ID, Website, Phone Number</code>

                <form action="upload.php" method="POST" enctype="multipart/form-data" class="mt-4">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File (Max 5MB)</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-upload"></i> Upload and Process File
                    </button>
                </form>

            </div>
            <div class="card-footer text-center">
                <a href="<?php echo SITE_URL; ?>">Back to CRM Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>