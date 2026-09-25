<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Passengers', 'active' => 'passengers', 'page' => 'passengers']);
?>
<div class="toolbar">
    <input id="search" class="input" placeholder="Search name, contact, username">
    <button class="btn" id="add-btn" type="button">Add passenger</button>
</div>
<section class="card">
    <table id="data-table">
        <thead><tr><th>Name</th><th>Username</th><th>Contact</th><th>Address</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
