<?php
session_start();
// Adjust path to your config file
require_once __DIR__ . '/sme_config.php';

// Fetch ALL businesses that have valid coordinates
$stmt = $pdo->query("SELECT name, address, latitude, longitude, category FROM businesses WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$businesses = $stmt->fetchAll();

// Convert the PHP array into a JSON object for JavaScript to use
$businesses_json = json_encode($businesses);

// SEO Meta
$page_title = 'Map of All Businesses - SME CRM';
$meta_description = 'An interactive map showing the locations of all businesses listed in the SME CRM for Bangalore.';
$canonical_url = SITE_URL . '/map.php';

include 'partials/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #map { 
        height: 70vh; /* 70% of the viewport height */
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 8px;
    }
</style>

<div class="container-fluid">
    <h2 class="mb-3">Business Locations Map</h2>
    <p>Showing <?php echo count($businesses); ?> businesses with valid locations. Click on a marker for details.</p>
    <div id="map"></div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    // Pass the PHP data to JavaScript
    const businesses = <?php echo $businesses_json; ?>;

    // Initialize the map, centered on Bangalore
    const map = L.map('map').setView([12.9716, 77.5946], 11);

    // Add the OpenStreetMap tile layer (the map background)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Loop through the businesses and add a marker for each one
    businesses.forEach(business => {
        const lat = parseFloat(business.latitude);
        const lon = parseFloat(business.longitude);

        if (!isNaN(lat) && !isNaN(lon)) {
            const marker = L.marker([lat, lon]).addTo(map);

            // Create a popup for the marker
            const popupContent = `
                <b>${business.name}</b><br>
                <i>${business.category}</i><br>
                ${business.address}<br>
                <a href="https://www.google.com/maps?q=${lat},${lon}" target="_blank">View on Google Maps</a>
            `;
            marker.bindPopup(popupContent);
        }
    });
</script>

<?php include 'partials/footer.php'; ?>