<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Routes', 'active' => 'routes', 'page' => 'routes']);
?>
<div class="toolbar">
    <input id="search" class="input" placeholder="Search route or destination">
    <button class="btn" id="add-btn" type="button">Add route</button>
</div>
<section class="card">
    <table id="data-table">
        <thead><tr><th>Route</th><th>Destinations</th><th>Distance</th><th>Stored fare</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
