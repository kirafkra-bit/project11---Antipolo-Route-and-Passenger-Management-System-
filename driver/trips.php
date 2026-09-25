<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Log a trip', 'active' => 'log', 'page' => 'log']);
?>
<section class="grid two">
    <article class="card">
        <h3>Record this run</h3>
        <p class="muted">The trip is saved under your driver profile. Jeepney must already be assigned to you.</p>
        <form id="trip-form" class="stack">
            <div>
                <label for="pickup">Pickup</label>
                <input id="pickup" name="pickup" required>
            </div>
            <div>
                <label for="drop_off">Drop-off</label>
                <input id="drop_off" name="drop_off" required>
            </div>
            <div>
                <label for="number_of_passengers">Number of passengers</label>
                <input id="number_of_passengers" name="number_of_passengers" value="1" required>
            </div>
            <div>
                <label for="passengers_id">Passenger account</label>
                <select id="passengers_id" name="passengers_id" required></select>
            </div>
            <div>
                <label for="jeepney_id">Your jeepney</label>
                <select id="jeepney_id" name="jeepney_id" required></select>
            </div>
            <button class="btn" type="submit">Save trip</button>
        </form>
    </article>
    <article class="card">
        <h3>Trips on your record</h3>
        <table id="trips-table">
            <thead><tr><th>Path</th><th>Passenger</th><th>Plate</th><th>Count</th></tr></thead>
            <tbody></tbody>
        </table>
    </article>
</section>
<?php layout_end(['driver.js']); ?>
