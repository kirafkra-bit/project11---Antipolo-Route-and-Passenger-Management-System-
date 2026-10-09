<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Finish Trip', 'active' => 'trips-finish', 'page' => 'trips-finish']);
?>
<header class="page-heading"><h2>Finish Trip</h2><p>Check the passenger counts and finish your current trip.</p></header>
<section id="driver-trip-page" class="driver-trip-form-wrap" aria-live="polite">
    <section class="driver-trip-section">
        <h3>Final trip check</h3>
        <div id="driver-finish-trip-details" class="driver-trip-review-details">Loading current trip...</div>
        <p id="driver-finish-trip-message" class="muted" role="status" hidden></p>
        <button id="driver-finish-trip" class="btn driver-finish-trip" type="button">Finish Trip</button>
    </section>
    <nav class="driver-step-navigation" aria-label="Trip process navigation">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('trips/passengers/')) ?>">Back</a>
        <span>Step 3 of 5 · Finish to submit this trip for review.</span>
        <a id="driver-step-next" class="btn is-locked" href="<?= htmlspecialchars(web_url('trips/summary/')) ?>" aria-disabled="true">Next</a>
    </nav>
</section>
<?php layout_end(['driver-trips.js', 'driver-sidebar.js']); ?>
