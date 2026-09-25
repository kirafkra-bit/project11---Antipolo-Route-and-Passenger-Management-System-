<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Find a jeepney', 'active' => 'search', 'page' => 'search']);
?>
<section class="grid two">
    <article class="card">
        <h3>Search published routes</h3>
        <form id="search-form" class="stack">
            <div>
                <label>Keyword</label>
                <input name="q" placeholder="Route or destination">
            </div>
            <div>
                <label>Pickup</label>
                <input name="pickup">
            </div>
            <div>
                <label>Drop-off</label>
                <input name="drop_off">
            </div>
            <div>
                <label>Vehicle</label>
                <select name="vehicle_type">
                    <option value="traditional">Traditional Jeepney</option>
                    <option value="modern">Modern Jeepney</option>
                </select>
            </div>
            <div>
                <label>Passenger type</label>
                <select name="passenger_type">
                    <option value="regular">Regular</option>
                    <option value="student">Student</option>
                    <option value="senior">Senior Citizen</option>
                    <option value="pwd">PWD</option>
                </select>
            </div>
            <button class="btn" type="submit">Search</button>
        </form>
    </article>
    <div id="search-results" class="stack"></div>
</section>
<?php layout_end(['passenger.js']); ?>
