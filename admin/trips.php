<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'All trips', 'active' => 'trips', 'page' => 'trips']);
?>
<div class="toolbar">
    <input id="search" class="input" placeholder="Search pickup, passenger, plate">
    <button class="btn" id="add-btn" type="button">Create trip</button>
</div>
<section class="card">
    <table id="data-table">
        <thead><tr><th>Path</th><th>Passenger</th><th>Driver</th><th>Plate</th><th>Count</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
