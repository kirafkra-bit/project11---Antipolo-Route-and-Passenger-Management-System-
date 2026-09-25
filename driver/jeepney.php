<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'My jeepney', 'active' => 'jeepney', 'page' => 'jeepney']);
?>
<p class="muted">Assignment is controlled by an administrator. You can view the unit and its route here.</p>
<div id="jeepney-detail" class="grid" style="margin-top:16px;"></div>
<?php layout_end(['driver.js']); ?>
