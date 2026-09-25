<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Jeepneys', 'active' => 'jeepneys', 'page' => 'jeepneys']);
?>
<div class="toolbar">
    <input id="search" class="input" placeholder="Search plate, driver, route">
    <button class="btn" id="add-btn" type="button">Assign jeepney</button>
</div>
<section class="card">
    <table id="data-table">
        <thead><tr><th>Plate</th><th>Unit no.</th><th>Driver</th><th>Route</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
