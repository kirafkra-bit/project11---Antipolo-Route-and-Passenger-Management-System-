<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'My jeepney', 'active' => 'jeepney', 'page' => 'jeepney']);
?>
<header class="page-heading"><h2>My jeepney</h2><p>Your assigned vehicle details.</p></header>
<div id="jeepney-detail" class="grid"></div>
<?php layout_end(['driver.js', 'driver-sidebar.js']); ?>
