<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Accounts', 'active' => 'users', 'page' => 'users']);
?>
<div class="toolbar">
    <input id="user-search" class="input" placeholder="Search username">
    <button class="btn" id="add-user" type="button">New account</button>
</div>
<section class="card">
    <table id="users-table">
        <thead><tr><th>Username</th><th>Role</th><th>Created</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin.js']); ?>
