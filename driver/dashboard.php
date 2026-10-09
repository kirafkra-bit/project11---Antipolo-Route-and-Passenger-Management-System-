<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Shift board', 'active' => 'dashboard', 'page' => 'dashboard']);
?>
<header class="page-heading"><h2>Shift board</h2><p>Your current shift status and recent activity.</p></header>
<section class="driver-shift-layout">
    <article class="driver-hero">
        <p class="eyebrow">Driver cab</p>
        <span id="driver-avatar" class="driver-avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
        <h2 id="driver-name">Driver</h2>
        <p>License No. <span id="driver-license">Loading...</span></p>
        <div class="driver-hero-tags"><span id="driver-status-chip" class="chip">Loading...</span><span id="driver-route-chip" class="chip">Route not assigned</span></div>
    </article>
    <div class="driver-summary">
        <article class="card driver-summary-card"><span>Duty status</span><strong id="driver-status">Loading...</strong></article>
        <article class="card driver-summary-card"><span>My trips today</span><strong id="stat-trips">0</strong></article>
        <article class="card driver-summary-card"><span>Assigned unit</span><strong id="driver-assigned-unit">No unit assigned</strong></article>
    </div>
</section>
<section class="card driver-recent">
    <h3>Recent trips</h3>
    <div id="recent-trips-list"><p class="muted" style="padding:12px 18px">Loading recent trips...</p></div>
</section>
<?php layout_end(['driver.js', 'driver-sidebar.js']); ?>
