<?php
// cleanup_contacts.php - Run this once to fix existing data
require_once __DIR__ . '/sme_config.php';

try {
    // Get all businesses with contacts data
    $stmt = $pdo->query("SELECT id, contacts FROM businesses WHERE contacts IS NOT NULL AND contacts != ''");
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated_count = 0;
    
    foreach ($businesses as $business) {
        $contacts_json = $business['contacts'];
        $decoded = json_decode($contacts_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Skipping business ID {$business['id']} - invalid JSON\n";
            continue;
        }
        
        if (!is_array($decoded)) {
            continue;
        }
        
        // Clean up the contacts
        $cleaned_contacts = [];
        foreach ($decoded as $contact) {
            if (is_array($contact)) {
                $cleaned_contacts[] = [
                    'name' => $contact['name'] ?? '',
                    'role' => $contact['role'] ?? '',
                    'phone' => $contact['phone'] ?? '',
                    'email' => $contact['email'] ?? ''
                ];
            }
        }
        
        // Re-encode as clean JSON
        $clean_json = json_encode($cleaned_contacts, JSON_UNESCAPED_UNICODE);
        
        // Update the database
        $update_stmt = $pdo->prepare("UPDATE businesses SET contacts = ? WHERE id = ?");
        $update_stmt->execute([$clean_json, $business['id']]);
        
        $updated_count++;
        echo "Updated business ID {$business['id']}\n";
    }
    
    echo "Cleanup complete. Updated {$updated_count} records.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>