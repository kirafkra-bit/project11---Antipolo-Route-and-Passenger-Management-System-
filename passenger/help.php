<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Help & FAQ', 'active' => 'Help', 'page' => 'help']);
?>
<header class="page-heading"><h2>Help &amp; FAQ</h2><p>Find answers to common questions about using PoloNav as a passenger.</p></header>
<section class="faq-layout"><article class="card faq-card">
    <label class="faq-search-box" for="faq-search"><i class="bi bi-search" aria-hidden="true"></i><input id="faq-search" type="search" placeholder="Search frequently asked questions…"></label>
    <div class="faq-list">
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">How do I find a jeepney for my route?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Open Find a jeepney and search by route name, driver, or plate number. Use View to see the route and assigned vehicle details.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">How do I estimate my fare before boarding?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Open Estimate fare, choose your origin and destination on the map, select your vehicle and passenger type, then calculate the estimate.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">What is the difference between Regular, Student, and Senior/PWD fares?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Select the passenger type that applies to you. The estimator applies the available fare rates for that category.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">How do I file a complaint about a driver or fare?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Open Complaints &amp; reports, choose a concern type, describe what happened, and optionally link the trip before submitting.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">Where can I see my trip history?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Open My trips to see trips recorded for your passenger profile.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">How do I save a favorite destination?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Open Favorite destinations and add a place. Saved places remain in this browser on this device.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">Where do transport advisories and route changes appear?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Check Announcements for service notices and route advisories.</p></article>
        <article class="faq-item"><button class="faq-question" type="button" aria-expanded="false">Who do I contact for an urgent concern?<span aria-hidden="true">+</span></button><p class="faq-answer" hidden>Submit a report through Complaints &amp; reports so the operations team can review it.</p></article>
    </div>
    <a class="faq-contact" href="<?= htmlspecialchars(web_url('passenger/complaints.php')) ?>"><span class="contact-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span><span class="contact-copy"><strong>Still need help?</strong><small>Send a report to the PoloNav operations team.</small></span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</article></section>
<?php layout_end(['passenger.js']); ?>