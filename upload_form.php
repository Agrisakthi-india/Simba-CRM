<?php
require_once 'auth_check.php';
require_once __DIR__ . '/sme_config.php';

$page_title = 'Upload CSV Data - SME CRM';
include 'partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="mb-0">Upload and Process CSV Data</h3></div>
            <div class="card-body">
                
                <div id="upload-form-container">
                    <p class="card-text">Upload a large CSV file to add businesses. The system will process it in batches to avoid server timeouts.</p>
                    <p>Required 12 columns: <code>State, District, City, Category, Name, ...</code></p>
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File</label>
                        <input class="form-control" type="file" id="csv_file" accept=".csv,text/csv">
                    </div>
                    <button id="process-btn" class="btn btn-primary w-100"><i class="bi bi-upload"></i> Start Processing File</button>
                </div>

                <div id="progress-container" class="mt-4" style="display: none;">
                    <div class="progress" style="height: 25px;">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <div id="status-log" class="mt-3 p-3 border rounded bg-light" style="font-family: monospace; max-height: 200px; overflow-y: auto;"></div>
                    <div id="final-results" class="alert mt-3" style="display: none;"></div>
                </div>
            </div>
            <div class="card-footer text-center"><a href="<?php echo SITE_URL; ?>">Back to Dashboard</a></div>
        </div>
    </div>
</div>

<!-- Papa Parse Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const processBtn = document.getElementById('process-btn');
    const fileInput = document.getElementById('csv_file');
    
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const statusLog = document.getElementById('status-log');
    const finalResults = document.getElementById('final-results');

    processBtn.addEventListener('click', async function() {
        const file = fileInput.files[0];
        if (!file) {
            alert('Please select a file to process.');
            return;
        }

        // --- UI RESET ---
        this.disabled = true;
        fileInput.disabled = true;
        progressContainer.style.display = 'block';
        finalResults.style.display = 'none';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressBar.classList.remove('bg-success', 'bg-danger');
        statusLog.innerHTML = 'Parsing file... please wait.<br>';
        
        // --- PARSE THE ENTIRE FILE FIRST ---
        const allRows = await parseCsv(file);
        const header = allRows.shift(); // Remove header row
        const totalRows = allRows.length;
        
        if (totalRows === 0) {
            logMessage('File is empty or could not be parsed.', 'danger');
            this.disabled = false;
            fileInput.disabled = false;
            return;
        }

        logMessage(`File parsed. Found ${totalRows} records to process. Starting upload...`, 'info');

        const BATCH_SIZE = 100; // Smaller batch size is safer
        let totalInserted = 0, totalSkipped = 0, totalMalformed = 0;
        let hasError = false;
        
        // --- SEQUENTIAL BATCH PROCESSING ---
        for (let i = 0; i < totalRows; i += BATCH_SIZE) {
            const batch = allRows.slice(i, i + BATCH_SIZE);
            
            try {
                const result = await sendBatch(batch);
                
                if (result.error) {
                    logMessage(`Batch ${i / BATCH_SIZE + 1} failed: ${result.error}`, 'danger');
                    hasError = true;
                    break; // Stop processing on a fatal server error
                }
                
                totalInserted += result.inserted;
                totalSkipped += result.skipped;
                totalMalformed += result.malformed;
                
                logMessage(`Batch ${i / BATCH_SIZE + 1} complete. Inserted: ${result.inserted}, Skipped: ${result.skipped}.`, 'success');
                updateProgress(((i + batch.length) / totalRows) * 100);

            } catch (error) {
                logMessage(`Batch ${i / BATCH_SIZE + 1} failed with a network error: ${error.message}`, 'danger');
                hasError = true;
                break; // Stop processing on network error
            }
        }
        
        // --- DISPLAY FINAL RESULTS ---
        displayFinalResults(hasError, totalRows, totalInserted, totalSkipped, totalMalformed);
        this.disabled = false;
        fileInput.disabled = false;
    });

    function parseCsv(file) {
        return new Promise(resolve => {
            Papa.parse(file, {
                complete: function(results) {
                    resolve(results.data);
                }
            });
        });
    }

    async function sendBatch(dataToSend) {
        const response = await fetch('upload.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rows: dataToSend })
        });
        return await response.json();
    }

    function updateProgress(percentage) {
        const percent = Math.round(percentage);
        progressBar.style.width = `${percent}%`;
        progressBar.textContent = `${percent}%`;
    }
    
    function logMessage(message, type = 'info') {
        const colorClass = type === 'danger' ? 'text-danger' : (type === 'success' ? 'text-success' : '');
        statusLog.innerHTML += `<span class="${colorClass}">${message}</span><br>`;
        statusLog.scrollTop = statusLog.scrollHeight; // Auto-scroll to bottom
    }

    function displayFinalResults(hasError, total, inserted, skipped, malformed) {
        let finalHtml = `<strong>Processing Finished!</strong><br>
                         - Total Records in File: ${total}<br>
                         - Records Successfully Added: ${inserted}<br>
                         - Duplicates Skipped: ${skipped}<br>
                         - Malformed Rows Skipped: ${malformed}`;
        
        finalResults.style.display = 'block';
        if (hasError) {
            finalResults.className = 'alert alert-danger mt-3';
            finalHtml += `<br><br><strong>Processing stopped due to an error. Please check the log above and your server's error logs.</strong>`;
        } else {
            finalResults.className = 'alert alert-success mt-3';
            progressBar.classList.add('bg-success');
            progressBar.textContent = 'Complete!';
        }
        finalResults.innerHTML = finalHtml;
    }
});
</script>

<?php include 'partials/footer.php'; ?>