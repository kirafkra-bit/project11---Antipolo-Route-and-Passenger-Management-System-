<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Complaints', 'active' => 'complaints', 'page' => 'complaints']);
?>
<div class="toolbar">
    <input id="complaint-search" class="input" placeholder="Search passenger, subject, or type">
    <select id="complaint-status" class="input">
        <option value="">All statuses</option>
        <option value="submitted">Submitted</option>
        <option value="in_review">In review</option>
        <option value="resolved">Resolved</option>
        <option value="rejected">Rejected</option>
    </select>
</div>
<section class="card">
    <table id="complaints-table">
        <thead><tr><th>Passenger</th><th>Concern</th><th>Subject</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</section>
<?php layout_end(['admin-complaints.js']); ?>
