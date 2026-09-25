<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Fare check', 'active' => 'fare', 'page' => 'fare']);
?>
<section class="grid two">
    <article class="card">
        <h3>Estimate for a passenger</h3>
        <form id="fare-form" class="stack">
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
            <div>
                <label>Distance (km)</label>
                <input name="distance" required>
            </div>
            <button class="btn" type="submit">Calculate</button>
        </form>
    </article>
    <div id="fare-result"></div>
</section>
<?php layout_end(['driver.js']); ?>
