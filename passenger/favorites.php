<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Favorite destinations', 'active' => 'favorites', 'page' => 'favorites']);
?>
<section class="card">
    <h3>Favorite destinations</h3>
    <p class="muted">Save places you visit often, such as Antipolo Simbahan, Masinag, Robinsons Antipolo, and Cogeo Gate 2.</p>
    <p class="empty">Favorites will appear here when saved.</p>
</section>
<?php layout_end(); ?>
