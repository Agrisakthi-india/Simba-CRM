</main> <!-- This closes the <main> container from header.php -->

<footer class="text-center py-4 bg-light mt-5 border-top">
    <div class="container">
        <p class="mb-0 text-muted">© <?php echo date('Y'); ?> SME CRM.</p>
    </div>
</footer>

<!-- ***** SIMBA AI CHAT WIDGET HTML ***** -->
<div id="simba-chat-widget">
    <div id="simba-chat-icon" class="shadow-lg"><i class="bi bi-robot"></i></div>
    <div id="simba-chat-window" class="shadow-lg" style="display: none;">
        <div class="chat-header">
            <h5>Simba AI Assistant</h5>
            <button id="simba-close-btn">×</button>
        </div>
        <div class="chat-body" id="simba-chat-body">
            <div class="chat-message simba">
                <div class="message-content">
                    Hello <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! How can I help you today?
                    <div class="prompt-starters">
                        <button class="prompt-btn">What are my overdue tasks?</button>
                        <button class="prompt-btn">Show me restaurants in Jayanagar</button>
                        <button class="prompt-btn">How many leads are in 'Follow-up'?</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="chat-footer">
            <form id="simba-chat-form">
                <input type="text" id="simba-chat-input" placeholder="Ask Simba anything..." autocomplete="off">
                <button type="submit" id="simba-send-btn"><i class="bi bi-send-fill"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- REQUIRED JS LIBRARIES -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- CUSTOM APPLICATION SCRIPTS -->
<!-- NOTE: The page-specific scripts like leads.js and app.js should be included on their respective pages, NOT here in the global footer -->
<script src="assets/js/simba.js"></script> <!-- Simba is global, so it goes here -->
<!-- CUSTOM APPLICATION SCRIPTS -->
<script src="assets/js/app.js"></script> <!-- For index.php, crm_ai.php, etc. -->
<script src="assets/js/leads.js"></script> <!-- SPECIFICALLY for leads.php -->

</body>
</html>