<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Complaints & reports', 'active' => 'complaints', 'page' => 'complaints']);
?>
<section class="grid two">
    <article class="card">
        <h3>Report a transportation concern</h3>
        <p class="muted">Provide enough detail for the operations team to investigate. Do not include passwords or sensitive information.</p>
        <form id="complaint-form" class="stack">
            <div><label for="category">Concern type</label>
                <select id="category" name="category" required>
                    <option value="incorrect_fare">Incorrect or excessive fare</option>
                    <option value="driver_behavior">Rude or inappropriate driver behavior</option>
                    <option value="reckless_driving">Reckless driving</option>
                    <option value="lost_item">Lost item</option>
                    <option value="unsafe_vehicle">Unsafe or overcrowded jeepney</option>
                    <option value="other">Other concern</option>
                </select>
            </div>
            <div><label for="subject">Short subject</label><input id="subject" name="subject" maxlength="150" required></div>
            <div><label for="trip_id">Related trip (optional)</label><select id="trip_id" name="trip_id"><option value="">Not linked to a specific trip</option></select></div>
            <div><label for="description">What happened?</label><textarea id="description" name="description" rows="5" maxlength="2000" required></textarea></div>
            <button class="btn" type="submit">Submit report</button>
        </form>
    </article>
    <article class="card">
        <h3>My reports</h3>
        <div id="complaint-list" class="stack"><p class="muted">Loading reports...</p></div>
    </article>
</section>
<?php layout_end(['passenger.js?v=2']); ?>
