<?php
// Secure Command-Line Import Script

// Security Check: Only allow this script to be run from the command line (CLI)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden: This script can only be run from the command line.");
}

// Adjust the path to your config file
// require_once __DIR__ . '/../../sme_config.php'; 
require_once __DIR__ . '/sme_config.php';

// --- Data Cleaning ---
function normalizeCategory($category) {
    $category = trim($category);
    if (stripos($category, 'Bakery') !== false) return 'Bakery';
    if (stripos($category, 'School') !== false) return 'School';
    return ucfirst(strtolower($category)); // Standardize capitalization
}

$csvFile = __DIR__ . '/bangalore_CRM.csv';

if (!file_exists($csvFile)) {
    die("Error: The file $csvFile was not found." . PHP_EOL);
}

try {
    $pdo->exec("TRUNCATE TABLE businesses");

    $fileHandle = fopen($csvFile, "r");
    if ($fileHandle === false) {
        die("Error: Could not open the file $csvFile." . PHP_EOL);
    }

    fgetcsv($fileHandle); // Skip header

    $sql = "INSERT INTO businesses (district, city, category, name, address, rating, latitude, longitude, place_id, website, phone_number) 
            VALUES (:district, :city, :category, :name, :address, :rating, :latitude, :longitude, :place_id, :website, :phone_number)";
    $stmt = $pdo->prepare($sql);

    $rowCount = 0;
    echo "Starting import..." . PHP_EOL;

    while (($data = fgetcsv($fileHandle, 1000, ",")) !== false) {
        if (count($data) < 11) continue;

        $stmt->execute([
            ':district' => trim($data[0]),
            ':city' => trim($data[1]),
            ':category' => normalizeCategory($data[2]),
            ':name' => trim($data[3]),
            ':address' => trim($data[4]),
            ':rating' => !empty(trim($data[5])) ? (float)trim($data[5]) : null,
            ':latitude' => !empty(trim($data[6])) ? (float)trim($data[6]) : null,
            ':longitude' => !empty(trim($data[7])) ? (float)trim($data[7]) : null,
            ':place_id' => trim($data[8]),
            ':website' => (trim($data[9]) === 'N/A') ? null : trim($data[9]),
            ':phone_number' => (trim($data[10]) === 'N/A') ? null : trim($data[10])
        ]);
        $rowCount++;
    }

    fclose($fileHandle);

    echo "Import Complete! Successfully imported $rowCount rows." . PHP_EOL;

} catch (PDOException $e) {
    die("Database error during import: " . $e->getMessage() . PHP_EOL);
}