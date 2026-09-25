<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Shift board', 'active' => 'dashboard', 'page' => 'dashboard']);
?>
<section class="grid two">
    <article class="card hero">
        <p class="eyebrow">Driver cab</p>
        <h2 id="driver-name">Driver</h2>
        <p>This is not the operations console. You can log trips for your assigned jeepney. You cannot edit routes, accounts, or other drivers.</p>
    </article>
    <article class="card">
        <h3>Your status</h3>
        <p>License <strong id="driver-license">—</strong></p>
        <p>Duty <span class="chip" id="driver-status">—</span></p>
        <div class="grid" style="grid-template-columns:1fr 1fr; margin-top:12px;">
            <div class="stat-card"><h3>My trips</h3><p id="stat-trips">—</p></div>
            <div class="stat-card"><h3>Assigned units</h3><p id="stat-jeepneys">—</p></div>
        </div>
    </article>
</section>
<section class="grid two" style="margin-top:16px;">
    <div id="jeepney-cards" class="grid"></div>
    <article class="card">
        <h3>Recent trips you logged</h3>
        <table id="recent-trips">
            <thead><tr><th>Path</th><th>Passenger</th><th>Plate</th><th>Count</th></tr></thead>
            <tbody></tbody>
        </table>
    </article>
</section>
<?php layout_end(['driver.js']); ?>
