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
<section class="grid two">
    <article class="card hero">
        <h2>You can change the system.</h2>
        <p>This desk is for administrators only. Create accounts, assign jeepneys, publish routes, and correct trip records. Drivers and passengers cannot open these pages.</p>
    </article>
    <article class="card">
        <h3>Authority</h3>
        <p class="muted">Highest access: users, passengers, drivers, jeepneys, routes, and trips. CSRF-protected writes go through the existing APIs.</p>
    </article>
</section>
<section class="grid stats" style="margin-top:16px;">
    <article class="card stat-card"><h3>Accounts</h3><p id="stat-users">—</p></article>
    <article class="card stat-card"><h3>Passengers</h3><p id="stat-passengers">—</p></article>
    <article class="card stat-card"><h3>Drivers</h3><p id="stat-drivers">—</p></article>
    <article class="card stat-card"><h3>Active drivers</h3><p id="stat-active">—</p></article>
    <article class="card stat-card"><h3>Jeepneys</h3><p id="stat-jeepneys">—</p></article>
    <article class="card stat-card"><h3>Routes</h3><p id="stat-routes">—</p></article>
    <article class="card stat-card"><h3>Trips</h3><p id="stat-trips">—</p></article>
    <article class="card stat-card"><h3>Open complaints</h3><p id="stat-open-complaints">—</p></article>
</section>
<section class="card" style="margin-top:16px;">
    <h3>Latest trips</h3>
    <table id="recent-trips">
        <thead><tr><th>Path</th><th>Passenger</th><th>Driver</th><th>Plate</th><th>Count</th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
