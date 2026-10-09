<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Trip Summary', 'active' => 'trips-summary', 'page' => 'trips-summary']);
?>
<header class="page-heading"><h2>Trip Summary</h2><p>Review the completed trip before confirming it.</p></header>
<section id="driver-trip-page" aria-live="polite">
    <section id="driver-trip-review" class="driver-trip-review" hidden>
        <h3>Trip ready for confirmation</h3>
        <div id="driver-trip-review-details" class="driver-trip-review-details"></div>
    </section>
    <div class="driver-trip-summary">
        <div class="driver-summary-stat"><span>Total trips</span><strong id="driver-total-trips">—</strong></div>
        <div class="driver-summary-stat"><span>Total passengers</span><strong id="driver-total-passengers">—</strong></div>
        <div class="driver-summary-stat"><span>Awaiting confirmation</span><strong id="driver-awaiting-confirmation">—</strong></div>
    </div>
    <nav class="driver-step-navigation" aria-label="Trip process navigation">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('trips/finish/')) ?>">Back</a>
        <span>Step 4 of 5 · Review your completed trip before confirmation.</span>
        <a class="btn" href="<?= htmlspecialchars(web_url('trips/confirm/')) ?>">Next</a>
    </nav>
</section>
<?php layout_end(['driver-trips.js', 'driver-sidebar.js']); ?>
