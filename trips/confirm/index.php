<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Confirm Trip', 'active' => 'trips-confirm', 'page' => 'trips-confirm']);
?>
<header class="page-heading"><h2>Confirm Trip</h2><p>Review and confirm a completed trip.</p></header>
<section id="driver-trip-page" class="driver-trip-form-wrap" aria-live="polite">
    <section class="driver-trip-section">
        <h3>Trip confirmation</h3>
        <p id="driver-no-pending" class="muted" hidden>No pending trips.</p>
        <form id="driver-confirm-trip" class="stack" hidden>
            <div><label for="driver-pending-trip">Pending trip</label><select id="driver-pending-trip" name="trip_id" required></select></div>
            <div><label for="driver-confirm-notes">Notes (optional)</label><textarea id="driver-confirm-notes" name="notes" rows="4" maxlength="4000"></textarea></div>
            <div class="driver-confirm-actions"><button class="btn" type="submit">Confirm trip</button><button id="driver-cancel-confirm" class="btn btn-ghost" type="button">Cancel</button></div>
        </form>
    </section>
    <nav class="driver-step-navigation" aria-label="Trip process navigation">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('trips/summary/')) ?>">Back</a>
        <span>Step 5 of 5 · Confirmation locks the selected trip.</span>
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('trips/history/')) ?>">Trip History</a>
    </nav>
</section>
<?php layout_end(['driver-trips.js', 'driver-sidebar.js']); ?>
