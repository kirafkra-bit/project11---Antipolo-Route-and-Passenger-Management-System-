<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Config', 'active' => 'config', 'page' => 'config']);
?>
<section class="grid two">
    <article class="card">
        <h3>Profile & settings</h3>
        <p class="muted">Manage your passenger account settings here.</p>
        <p class="empty">Profile editing, password changes, and notification preferences will appear here.</p>
    </article>
    <article class="card">
        <h3>Current account</h3>
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Role:</strong> Passenger</p>
    </article>
</section>
<?php layout_end(); ?>
