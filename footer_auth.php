</main> <!-- This closes the <main class="container my-4"> from header.php -->

<!-- JavaScript for Dependent Dropdowns -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pass the PHP location data to JavaScript
    const locations = <?php echo $locations_json; ?>;
    
    // Get the dropdown elements
    const stateFilter = document.getElementById('state-filter');
    const districtFilter = document.getElementById('district-filter');
    const cityFilter = document.getElementById('city-filter');

    // Get current filter values from PHP to pre-select dropdowns on page load
    const currentState = '<?php echo $filter_state; ?>';
    const currentDistrict = '<?php echo $filter_district; ?>';
    const currentCity = '<?php echo $filter_city; ?>';

    function populateStates() {
        const states = [...new Set(locations.map(loc => loc.state))];
        stateFilter.innerHTML = '<option value="">-- All States --</option>'; // Reset
        states.forEach(state => {
            // Create new option, set it as selected if it matches the current filter
            const option = new Option(state, state, state === currentState, state === currentState);
            stateFilter.add(option);
        });
        // If a state was already selected (from URL), trigger the district population
        if (currentState) {
            populateDistricts();
        }
    }

    function populateDistricts() {
        const selectedState = stateFilter.value;
        districtFilter.innerHTML = '<option value="">-- All Districts --</option>'; // Reset
        cityFilter.innerHTML = '<option value="">-- All Cities --</option>'; // Also reset city

        if (!selectedState) return;

        const districts = [...new Set(
            locations
                .filter(loc => loc.state === selectedState)
                .map(loc => loc.district)
        )];
        
        districts.sort().forEach(district => {
            const option = new Option(district, district, district === currentDistrict, district === currentDistrict);
            districtFilter.add(option);
        });
        // If a district was already selected, trigger the city population
        if (currentDistrict) {
            populateCities();
        }
    }

    function populateCities() {
        const selectedState = stateFilter.value;
        const selectedDistrict = districtFilter.value;
        cityFilter.innerHTML = '<option value="">-- All Cities --</option>'; // Reset

        if (!selectedDistrict) return;

        const cities = [...new Set(
            locations
                .filter(loc => loc.state === selectedState && loc.district === selectedDistrict)
                .map(loc => loc.city)
        )];
        
        cities.sort().forEach(city => {
            const option = new Option(city, city, city === currentCity, city === currentCity);
            cityFilter.add(option);
        });
    }

    // Event listeners to trigger updates when a selection changes
    stateFilter.addEventListener('change', populateDistricts);
    districtFilter.addEventListener('change', populateCities);

    // Initial population when the page first loads
    populateStates();
});
</script>
<!-- The main content container is closed in the login/register.php file -->

<footer class="auth-footer">
    <p>© <?php echo date('Y'); ?> SME CRM. All Rights Reserved.</p>
</footer>

<!-- Bootstrap 5 JS Bundle with Popper -->
<!-- This is required for Bootstrap components like dropdowns and the mobile navbar to work -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>