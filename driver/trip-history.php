<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Trip History', 'active' => 'trip-history', 'page' => 'trip-history']);
?>
<header class="page-heading"><h2>Trip History</h2><p>All your past recorded trips.</p></header>
<article class="card driver-table-card">
    <label class="driver-history-search">
        <span class="sr-only">Search trip history</span>
        <input id="trip-history-search" type="search" placeholder="Search by route, passenger, plate...">
    </label>
    <div class="table-scroll"><table id="trip-history-table"><thead><tr>
        <th><button type="button" data-sort="path">Route / Path <span aria-hidden="true">↕</span></button></th>
        <th><button type="button" data-sort="passenger">Passenger <span aria-hidden="true">↕</span></button></th>
        <th><button type="button" data-sort="plate_number">Plate <span aria-hidden="true">↕</span></button></th>
        <th aria-sort="descending"><button type="button" data-sort="recorded_date">Date <span aria-hidden="true">↓</span></button></th>
        <th><button type="button" data-sort="number_of_passengers">Pax count <span aria-hidden="true">↕</span></button></th>
    </tr></thead><tbody><tr><td colspan="5" class="empty">Loading trips...</td></tr></tbody></table></div>
</article>
<?php layout_end(['driver.js', 'driver-sidebar.js']); ?>
