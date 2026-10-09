<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['admin']);
layout_start($user, ['title' => 'Announcements', 'active' => 'announcements', 'page' => 'announcements']);
?>
<header class="page-heading admin-announcements-heading"><div><h2>Announcements</h2><p>Publish updates for drivers and passengers.</p></div><button id="announcement-add" class="btn" type="button">New announcement</button></header>
<section id="admin-announcements" class="admin-announcements" aria-live="polite">
    <article class="card empty">Loading announcements...</article>
</section>
<?php layout_end(['admin-announcements.js']); ?>
