<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Passengers', 'active' => 'trips-passengers', 'page' => 'trips-passengers']);
?>
<header class="page-heading"><h2>Passengers</h2><p>See the passengers currently aboard. Passengers add themselves from their own accounts and mark their own drop-off.</p></header>
<section id="driver-trip-page" class="driver-trip-form-wrap" aria-live="polite">
    <section class="driver-trip-section">
        <h3>Current trip</h3>
        <div class="driver-trip-counters">
            <div><span>Boarded</span><strong id="driver-boarded-count">0</strong></div>
            <div><span>Dropped off</span><strong id="driver-dropped-count">0</strong></div>
            <div><span>On board now</span><strong id="driver-onboard-count">0</strong></div>
        </div>
        <div id="driver-current-trip-label" class="driver-current-trip-label"></div>
        <section class="trip-fare-records">
            <h4>Currently aboard</h4>
            <div class="table-wrap">
                <table id="driver-trip-fares">
                    <thead><tr><th>Passenger</th><th>Boarding point</th><th>Destination</th><th>Fare category</th><th>Distance</th><th>Fare</th><th>Boarded</th></tr></thead>
                    <tbody><tr><td colspan="7" class="muted">Loading passengers...</td></tr></tbody>
                </table>
            </div>
        </section>
    </section>
    <nav class="driver-step-navigation" aria-label="Trip process navigation">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('trips/start/')) ?>">Back</a>
        <span>Refreshes automatically while your trip is active.</span>
        <a id="driver-step-next" class="btn is-locked" href="<?= htmlspecialchars(web_url('trips/finish/')) ?>" aria-disabled="true">Next</a>
    </nav>
</section>
<?php layout_end(['driver-trips.js', 'driver-sidebar.js']); ?>
