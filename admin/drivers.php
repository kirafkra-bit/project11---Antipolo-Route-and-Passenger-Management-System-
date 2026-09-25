<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Drivers', 'active' => 'drivers', 'page' => 'drivers']);
?>
<div class="toolbar">
    <input id="search" class="input" placeholder="Search name, license, username">
    <button class="btn" id="add-btn" type="button">Add driver</button>
</div>
<section class="card">
    <table id="data-table">
        <thead><tr><th>Name</th><th>License</th><th>Contact</th><th>Status</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
