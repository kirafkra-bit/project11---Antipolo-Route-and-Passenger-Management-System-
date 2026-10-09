<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'My trips', 'active' => 'trips', 'page' => 'trips']);
?>
<header class="page-heading"><h2>My trips</h2><p>Join the jeepney you are riding, enter your journey, and mark your own drop-off.</p></header>
<section id="passenger-rides-page" class="passenger-rides-page">
    <section id="passenger-current-ride" class="card passenger-current-ride" hidden></section>
    <section id="passenger-join-panel" class="card passenger-join-panel">
        <h3>Choose the jeepney you are riding</h3>
        <p class="muted">Only currently active trips are listed. Check the route, driver, and plate before joining.</p>
        <div id="passenger-active-trips" class="passenger-active-trips" aria-live="polite">
            <p class="empty">Loading active trips...</p>
        </div>
        <form id="passenger-ride-form" class="passenger-ride-form" hidden>
            <div id="passenger-selected-trip" class="passenger-selected-trip"></div>
            <label for="passenger-ride-origin">Boarding point</label>
            <input id="passenger-ride-origin" name="origin_name" type="search" maxlength="255" autocomplete="off" required placeholder="Search an address or landmark">
            <input id="passenger-ride-origin-latitude" name="origin_latitude" type="hidden">
            <input id="passenger-ride-origin-longitude" name="origin_longitude" type="hidden">
            <input id="passenger-ride-origin-signature" name="origin_signature" type="hidden">
            <div id="passenger-ride-origin-results" class="passenger-place-results" role="listbox" hidden></div>
            <label for="passenger-ride-destination">Destination</label>
            <input id="passenger-ride-destination" name="destination_name" type="search" maxlength="255" autocomplete="off" required placeholder="Search an address or landmark">
            <input id="passenger-ride-destination-latitude" name="destination_latitude" type="hidden">
            <input id="passenger-ride-destination-longitude" name="destination_longitude" type="hidden">
            <input id="passenger-ride-destination-signature" name="destination_signature" type="hidden">
            <div id="passenger-ride-destination-results" class="passenger-place-results" role="listbox" hidden></div>
            <label for="passenger-ride-type">Passenger fare category</label>
            <select id="passenger-ride-type" name="passenger_type" required>
                <option value="regular">Regular</option>
                <option value="student">Student (discounted)</option>
                <option value="senior">Senior citizen (discounted)</option>
                <option value="pwd">PWD (discounted)</option>
            </select>
            <div class="passenger-ride-actions">
                <button id="passenger-ride-cancel" class="btn btn-ghost" type="button">Choose another jeepney</button>
                <button class="btn" type="submit">Join trip and calculate fare</button>
            </div>
            <p id="passenger-ride-message" class="muted" role="status" hidden></p>
        </form>
    </section>
    <section class="card passenger-ride-history">
        <h3>My ride history</h3>
        <div class="table-scroll"><table id="passenger-ride-history">
            <thead><tr><th>Date</th><th>Route / Jeepney</th><th>Driver</th><th>From</th><th>To</th><th>Fare</th><th>Status</th></tr></thead>
            <tbody><tr><td colspan="7" class="empty">Loading your ride history...</td></tr></tbody>
        </table></div>
    </section>
</section>
<?php layout_end(['passenger.js']); ?>