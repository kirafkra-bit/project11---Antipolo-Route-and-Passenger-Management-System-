<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Trip History', 'active' => 'trips-history', 'page' => 'trips-history']);
?>
<header class="page-heading"><h2>Trip History</h2><p>Your recorded trips, newest first.</p></header>
<section id="driver-trip-page" class="driver-trip-page" aria-live="polite">
    <div class="driver-trip-history-table"><div class="table-scroll"><table id="driver-trip-history">
        <thead><tr><th>Trip ID</th><th>Date</th><th>Route</th><th>Passengers</th><th>Status</th></tr></thead>
        <tbody><tr><td colspan="5" class="empty">Loading trips...</td></tr></tbody>
    </table></div></div>
</section>
<?php layout_end(['driver-trips.js', 'driver-sidebar.js']); ?>
