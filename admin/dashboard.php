<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';

$user = require_web_role(['admin']);
layout_start($user, [
    'title'   => 'Operations overview',
    'active'  => 'dashboard',
    'page'    => 'dashboard',
    'scripts' => ['admin.js'],
]);
?>
<section class="overview-heading">
    <div>
        <p class="eyebrow">PoloNav Operations</p>
        <h2>Operations overview</h2>
    </div>
    <small class="muted">Last updated: <?= date('M j, Y · H:i') ?></small>
</section>
<section class="overview-stats">
    <article class="card stat-card stat-card-large"><i class="bi bi-people" aria-hidden="true"></i><div><p class="stat-label">Total accounts</p><strong id="stat-users">—</strong><small class="stat-trend">↗ All registered users</small></div></article>
    <article class="card stat-card stat-card-large"><i class="bi bi-person-check" aria-hidden="true"></i><div><p class="stat-label">Passengers</p><strong id="stat-passengers">—</strong><small class="stat-trend">↗ Passenger accounts</small></div></article>
    <article class="card stat-card stat-card-large"><i class="bi bi-person-badge" aria-hidden="true"></i><div><p class="stat-label">Drivers</p><strong id="stat-drivers">—</strong><small class="stat-trend">↗ Registered drivers</small></div></article>
    <article class="card stat-card stat-card-large"><i class="bi bi-car-front" aria-hidden="true"></i><div><p class="stat-label">Active drivers</p><strong id="stat-active">—</strong><small class="stat-trend">↗ Currently active</small></div></article>
</section>
<section class="overview-middle">
    <div class="overview-mini-grid">
        <article class="card stat-card stat-card-mini"><i class="bi bi-truck-front" aria-hidden="true"></i><div><strong id="stat-jeepneys">—</strong><span>Jeepneys</span></div></article>
        <article class="card stat-card stat-card-mini"><i class="bi bi-signpost-2" aria-hidden="true"></i><div><strong id="stat-routes">—</strong><span>Routes</span></div></article>
        <article class="card stat-card stat-card-mini"><i class="bi bi-map" aria-hidden="true"></i><div><strong id="stat-trips">—</strong><span>Trips recorded</span></div></article>
        <article class="card stat-card stat-card-mini"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><div><strong id="stat-open-complaints">—</strong><span>Open complaints</span></div></article>
    </div>
    <article class="card activity-card">
        <div class="section-heading"><div><h3>Trip activity</h3><p class="muted">Trips recorded over the past 7 days</p></div><span class="chart-legend"><i></i> Trips <i></i> Passengers</span></div>
        <div class="activity-chart" role="img" aria-label="Trip activity overview chart">
            <svg viewBox="0 0 720 150" preserveAspectRatio="none" aria-hidden="true">
                <path class="chart-grid" d="M0 20H720 M0 55H720 M0 90H720 M0 125H720"></path>
                <path class="chart-area" d="M0 118 C75 118 82 92 150 94 S230 70 300 88 S390 102 450 56 S535 82 590 67 S650 72 720 58 V135 H0Z"></path>
                <path class="chart-line chart-line-trips" d="M0 118 C75 118 82 92 150 94 S230 70 300 88 S390 102 450 56 S535 82 590 67 S650 72 720 58"></path>
                <path class="chart-line chart-line-passengers" d="M0 126 C75 126 82 109 150 108 S230 96 300 104 S390 108 450 88 S535 96 590 91 S650 95 720 84"></path>
            </svg>
            <div class="chart-labels"><span>12 Sep</span><span>13 Sep</span><span>14 Sep</span><span>15 Sep</span><span>16 Sep</span><span>17 Sep</span><span>18 Sep</span></div>
        </div>
    </article>
</section>
<section class="card latest-trips">
    <div class="section-heading"><div><h3>Latest trips</h3><p class="muted">Most recent trip records in the system</p></div><a class="text-link" href="<?= htmlspecialchars(web_url('admin/trips.php')) ?>">View all →</a></div>
    <table id="recent-trips">
        <thead><tr><th>Routes</th><th>Passenger</th><th>Driver</th><th>Plate</th><th>Passengers</th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
