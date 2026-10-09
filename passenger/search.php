<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Find a jeepney', 'active' => 'search', 'page' => 'search']);
?>
<header class="page-heading"><h2>Find a jeepney</h2><p>Browse jeepneys and view route, fare, and driver information.</p></header>
<section class="card search-panel">
    <form id="search-form" class="search-toolbar" role="search">
        <label class="search-input-wrap" for="route-query"><i class="bi bi-search" aria-hidden="true"></i><input id="route-query" name="q" type="search" placeholder="Search by route, driver, or plate number" autocomplete="off"></label>
        <span id="search-result-count" class="search-result-count" aria-live="polite">Loading routes…</span>
    </form>
    <div id="search-results" class="search-results"><p class="empty">Loading available jeepneys…</p></div>
</section>
<?php layout_end(['passenger.js']); ?>