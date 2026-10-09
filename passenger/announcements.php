<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Announcements', 'active' => 'announcements', 'page' => 'announcements']);
?>
<header class="page-heading announcements-heading">
    <h2>LTFRB Official Announcements</h2>
    <p>Official updates from the Land Transportation Franchising and Regulatory Board.</p>
    <a href="https://ltfrb.gov.ph/" target="_blank" rel="noopener noreferrer">Visit ltfrb.gov.ph <span aria-hidden="true">↗</span></a>
</header>
<nav class="announcement-categories" aria-label="Filter announcements">
    <button type="button" data-announcement-category="all" aria-pressed="true">All News &amp; Updates</button>
    <button type="button" data-announcement-category="fare" aria-pressed="false">Fare Announcements</button>
    <button type="button" data-announcement-category="violations" aria-pressed="false">Violations</button>
    <button type="button" data-announcement-category="advisories" aria-pressed="false">Advisories</button>
</nav>
<section id="portal-announcements" class="announcement-grid" aria-live="polite">
    <article class="card empty">Loading announcements...</article>
</section>
<nav id="announcement-pagination" class="announcement-pagination" aria-label="Announcement pages"></nav>
<?php layout_end(['announcements.js']); ?>