<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Announcements', 'active' => 'announcements', 'page' => 'announcements']);
?>
<section class="card">
    <h3>Transport announcements</h3>
    <p class="muted">Route changes, road closures, traffic advisories, and service updates will appear here.</p>
    <p class="empty">No announcements have been published.</p>
</section>
<?php layout_end(); ?>
