<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'My trips', 'active' => 'trips', 'page' => 'trips']);
?>
<p class="muted">Only trips recorded for your passenger profile appear here. Editing and deleting trips is an administrator action.</p>
<section class="card" style="margin-top:16px;">
    <table id="trips-table">
        <thead><tr><th>Path</th><th>Driver</th><th>Plate</th><th>Route</th><th>Count</th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['passenger.js']); ?>
