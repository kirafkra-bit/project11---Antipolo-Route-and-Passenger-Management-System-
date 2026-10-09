<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'My Route', 'active' => 'my-route', 'page' => 'my-route']);
?>
<header class="page-heading"><h2>My Route</h2><p>Your requested route and current assignment status.</p></header>
<section id="driver-my-route" class="driver-my-route" aria-live="polite">
    <article class="card empty">Loading route assignment...</article>
</section>
<?php layout_end(['driver-portal.js', 'driver-sidebar.js']); ?>
