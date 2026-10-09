<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Start Trip', 'active' => 'trips-start', 'page' => 'trips-start']);
?>
<header class="page-heading"><h2>Start Trip</h2><p>Begin a trip on your assigned route.</p></header>
<section id="driver-trip-page" class="driver-trip-form-wrap" aria-live="polite">
    <section class="driver-trip-section">
        <h3>Trip details</h3>
        <form id="driver-start-trip" class="stack">
            <div><label for="driver-trip-route">Assigned route</label><input id="driver-trip-route" readonly required></div>
            <div><label for="driver-trip-jeepney">Vehicle / plate number</label><select id="driver-trip-jeepney" name="jeepney_id" required><option value="">Loading...</option></select></div>
            <div><label for="driver-trip-vehicle-type">Vehicle fare type</label><select id="driver-trip-vehicle-type" name="vehicle_type" required><option value="traditional">Traditional Jeepney</option><option value="modern">Modern Jeepney</option></select></div>
            <div><label for="driver-start-odometer">Starting odometer</label><input id="driver-start-odometer" name="starting_odometer" type="number" min="0" step="0.1" required></div>
            <p id="driver-trip-form-message" role="status" hidden></p>
        </form>
    </section>
    <nav class="driver-step-navigation" aria-label="Trip process navigation">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('trips/history/')) ?>">Back</a>
        <span>Step 1 of 5 · Start with the assigned vehicle and odometer.</span>
        <button class="btn" type="submit" form="driver-start-trip">Start Trip &amp; Next</button>
    </nav>
</section>
<?php layout_end(['driver-trips.js', 'driver-sidebar.js']); ?>
