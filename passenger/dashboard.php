<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Home', 'active' => 'dashboard', 'page' => 'dashboard']);
?>
<section class="dashboard-layout">
    <div class="dashboard-main">
        <article class="card hero">
            <p class="eyebrow">PASSENGER PORTAL</p>
            <h2>Welcome back, <span id="passenger-name">Passenger</span></h2>
            <p>Find a route, estimate your fare, and manage your trips in one place.</p>
            <div class="dashboard-actions">
                <a class="btn" href="<?= htmlspecialchars(web_url('passenger/search.php')) ?>">Find a jeepney <span aria-hidden="true">→</span></a>
                <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('passenger/fare.php')) ?>">Estimate fare</a>
            </div>
        </article>
        <article class="card route-panel">
            <h3>Available routes</h3>
            <div id="route-list" class="route-list"><p class="muted">Loading routes…</p></div>
        </article>
        <article class="card report-panel">
            <h3>Report a problem</h3>
            <p class="muted">Tell the operations team about an incorrect fare, unsafe driving, or another concern.</p>
            <a class="btn" href="<?= htmlspecialchars(web_url('passenger/complaints.php')) ?>">Open complaints</a>
        </article>
    </div>
    <aside class="dashboard-side">
        <article class="card summary-panel"><h3>Quick summary</h3><div class="summary-grid">
            <div class="summary-item"><strong id="stat-trips">—</strong><span>My trips</span></div>
            <div class="summary-item"><strong id="stat-routes">—</strong><span>Published routes</span></div>
            <div class="summary-item"><strong id="stat-complaints">—</strong><span>My reports</span></div>
        </div></article>
        <article class="card recent-panel"><h3>Recent trips</h3><div class="table-scroll">
            <table id="recent-trips"><thead><tr><th>Path</th><th>Driver</th><th>Route</th></tr></thead><tbody></tbody></table>
        </div><p class="align-right"><a class="text-link" href="<?= htmlspecialchars(web_url('passenger/trips.php')) ?>">View all trips →</a></p></article>
        <article class="card updates-panel"><h3>Transport updates</h3><p class="muted">Route changes and service notices appear here.</p>
            <a class="quiet-link" href="<?= htmlspecialchars(web_url('passenger/announcements.php')) ?>">View announcements</a></article>
    </aside>
</section>
<?php layout_end(['passenger.js']); ?>