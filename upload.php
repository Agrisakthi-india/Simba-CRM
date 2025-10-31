<?php
// upload.php (Enhanced Final Version - Updated Field Structure)
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

// Permission check
$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$account_role = $_SESSION['account_role'];
$is_superadmin = $_SESSION['is_superadmin'] ?? false;

if ($account_role === 'member' && !$is_superadmin) {
    http_response_code(403);
    die("Access Denied. You do not have permission to upload businesses.");
}

$page_title = 'Bulk Upload Businesses - SME CRM';
include 'partials/header.php';
?>

<div class="container">
    <a href="index.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    <h2 class="mb-4">Bulk Upload Businesses via CSV</h2>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-upload me-2"></i>CSV File Upload</h5>
                    <p>Select a UTF-8 encoded CSV file to upload. The system will process the file in your browser and import new data. Duplicates (based on the same <strong>Name</strong> and <strong>City</strong> within your team) will be skipped automatically.</p>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>CSV Format Requirements</h6>
                        <p class="mb-2">Your CSV file must have exactly these <strong>13 columns</strong> in this specific order:</p>
                        <code class="d-block bg-white p-2 rounded my-2 small">
                            name,category,state,district,city,address,description,website,rating,latitude,longitude,place_id,contacts
                        </code>
                        <ul class="mb-0 small">
                            <li><strong>name</strong> and <strong>category</strong> are required fields</li>
                            <li><strong>contacts</strong> field should contain JSON array of contact objects</li>
                            <li><strong>rating</strong> should be a decimal number (0-5)</li>
                            <li><strong>latitude/longitude</strong> should be decimal coordinates</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File to Upload</label>
                        <input class="form-control" type="file" id="csv_file" accept=".csv,text/csv">
                        <div class="form-text">Maximum file size: 10MB. Only CSV files are accepted.</div>
                    </div>
                    
                    <button id="start-upload-btn" class="btn btn-primary w-100">
                        <i class="bi bi-upload me-2"></i>Start Processing and Import
                    </button>
                    
                    <!-- Progress and Results Section -->
                    <div id="upload-progress" class="mt-4" style="display: none;">
                        <h5><i class="bi bi-gear-fill me-2"></i>Import Progress</h5>
                        <div class="progress" style="height: 25px;">
                            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div id="progress-status" class="mt-2 text-muted"></div>
                        <div id="upload-results" class="mt-3 alert" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-download me-2"></i>Sample CSV Template</h5>
                    <p class="small">Download our sample CSV file to understand the correct format:</p>
                    <button id="download-template-btn" class="btn btn-outline-success w-100 mb-3">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download Template
                    </button>
                    
                    <h6><i class="bi bi-lightbulb me-2"></i>Quick Tips</h6>
                    <ul class="small mb-3">
                        <li>Use UTF-8 encoding when saving CSV</li>
                        <li>Wrap text with commas in double quotes</li>
                        <li>JSON contacts format: <code>[{"name":"John","role":"CEO","phone":"98765 43210","email":"john@company.com"}]</code></li>
                        <li>Leave empty fields blank, don't use "null" or "N/A"</li>
                        <li>Phone numbers: Use Indian format (98765 43210)</li>
                    </ul>
                    
                    <h6><i class="bi bi-shield-check me-2"></i>Data Processing</h6>
                    <ul class="small mb-0">
                        <li>Files processed locally in browser</li>
                        <li>Data sent in secure chunks</li>
                        <li>Automatic duplicate detection</li>
                        <li>Invalid records are skipped</li>
                        <li>Detailed import report provided</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-question-circle me-2"></i>Need Help?</h6>
                    <p class="small mb-2">Common issues and solutions:</p>
                    <details class="small">
                        <summary class="fw-bold">Encoding Problems</summary>
                        <p class="mt-2 mb-1">Save your CSV with UTF-8 encoding. In Excel: File → Save As → CSV UTF-8</p>
                    </details>
                    <details class="small">
                        <summary class="fw-bold">JSON Format Errors</summary>
                        <p class="mt-2 mb-1">Contacts field must be valid JSON. Use double quotes and escape internal quotes.</p>
                    </details>
                    <details class="small">
                        <summary class="fw-bold">Duplicate Detection</summary>
                        <p class="mt-2 mb-1">Based on exact match of business name and city within your team.</p>
                    </details>
                </details>
            </div>
        </div>
    </div>
</div>

<!-- Hidden CSV template data for download -->
<div id="csv-template-data" style="display: none;">
name,category,state,district,city,address,description,website,rating,latitude,longitude,place_id,contacts
"Sample Business 1","Technology","Tamil Nadu","Chennai","Chennai","123 Sample Street, Chennai - 600001","Sample business description","https://samplebusiness.com",4.5,13.0827,80.2707,"sample_place_001","[{""name"":""John Doe"",""role"":""CEO"",""phone"":""98765 43210"",""email"":""john@samplebusiness.com""}]"
"Sample Business 2","Restaurant","Karnataka","Bangalore","Bangalore","456 Food Street, Bangalore - 560001","Multi-cuisine restaurant","",4.0,12.9716,77.5946,"sample_place_002","[{""name"":""Jane Smith"",""role"":""Manager"",""phone"":""98765 43211"",""email"":""jane@restaurant.com""},{""name"":""Chef Kumar"",""role"":""Head Chef"",""phone"":""98765 43212"",""email"":""chef@restaurant.com""}]"
</div>

<!-- Link to PapaParse library for CSV parsing -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
<!-- Link to our external, CSP-compliant JavaScript file -->
<script src="assets/js/upload.js"></script>

<?php include 'partials/footer.php'; ?>