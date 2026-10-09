<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Assigned Route', 'active' => 'assigned-route', 'page' => 'assigned-route']);
?>
<header class="page-heading"><h2>Assigned Route</h2><p>Your designated jeepney route and stop information.</p></header>
<div id="assigned-route-content" class="grid"><article class="card muted">Loading assigned route...</article></div>
<?php layout_end(['driver.js', 'driver-sidebar.js']); ?>
