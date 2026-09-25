<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Passenger dashboard', 'active' => 'dashboard', 'page' => 'dashboard']);
?>
<section class="grid two">
    <article class="card hero">
        <p class="eyebrow">Passenger portal</p>
        <h2>Welcome back, <span id="passenger-name">Passenger</span></h2>
        <p>Find a route, estimate your fare, review your trips, and report a transport concern from one simple dashboard.</p>
        <p class="dashboard-actions">
            <a class="btn" href="<?= htmlspecialchars(web_url('passenger/search.php')) ?>">Search routes</a>
            <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('passenger/fare.php')) ?>">Calculate fare</a>
        </p>
    </article>
    <article class="card">
        <h3>Quick summary</h3>
        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div class="stat-card"><h3>My trips</h3><p id="stat-trips">—</p></div>
            <div class="stat-card"><h3>Published routes</h3><p id="stat-routes">—</p></div>
            <div class="stat-card"><h3>My reports</h3><p id="stat-complaints">—</p></div>
        </div>
    </article>
</section>
<section class="grid two" style="margin-top:16px;">
    <article class="card">
        <h3>Available routes</h3>
        <div id="route-list" class="stack"></div>
    </article>
    <article class="card">
        <h3>Recent trips</h3>
        <table id="recent-trips">
            <thead><tr><th>Path</th><th>Driver</th><th>Route</th></tr></thead>
            <tbody></tbody>
        </table>
        <p><a class="btn btn-ghost btn-small" href="<?= htmlspecialchars(web_url('passenger/trips.php')) ?>">View all trips</a></p>
    </article>
</section>
<section class="grid two" style="margin-top:16px;">
    <article class="card">
        <h3>Report a problem</h3>
        <p class="muted">Tell the operations team about an incorrect fare, unsafe driving, lost item, or other concern.</p>
        <a class="btn" href="<?= htmlspecialchars(web_url('passenger/complaints.php')) ?>">Open complaints</a>
    </article>
    <article class="card">
        <h3>Transport updates</h3>
        <p class="muted">Announcements and route advisories will appear here when published by the operations team.</p>
        <span class="chip">No new announcements</span>
    </article>
</section>
<?php layout_end(['passenger.js']); ?>
