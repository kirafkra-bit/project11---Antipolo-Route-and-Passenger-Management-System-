<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Route requests', 'active' => 'route-requests', 'page' => 'route-requests']);
?>
<header class="page-heading"><h2>Driver route requests</h2><p>Approve a requested route or assign a different route before enabling trip features.</p></header>
<section class="card driver-admin-requests">
    <div class="table-scroll"><table id="route-requests-table">
        <thead><tr><th>Driver</th><th>License</th><th>Requested route</th><th>Assign route</th><th>Actions</th></tr></thead>
        <tbody><tr><td colspan="5" class="empty">Loading route requests...</td></tr></tbody>
    </table></div>
</section>
<?php layout_end(['route-requests.js']); ?>
