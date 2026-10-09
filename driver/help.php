<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Help & FAQ', 'active' => 'Help', 'page' => 'help']);
?>
<header class="page-heading"><h2>Help &amp; FAQ</h2><p>Find answers to common questions about PoloNav Cab.</p></header>
<section class="driver-help">
    <label class="driver-help-search">
        <span class="sr-only">Search frequently asked questions</span>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input id="help-search" type="search" placeholder="Search frequently asked questions...">
    </label>
    <div id="help-faq-list" class="driver-faq-list">
        <details class="driver-faq"><summary>How do I start and complete a trip?</summary><p>Open Trips, choose Start Trip, select a vehicle assigned to your approved route, and enter the starting odometer. Record passenger boardings and drop-offs on the Passengers page, then choose Finish trip to send the trip for confirmation.</p></details>
        <details class="driver-faq"><summary>How do I confirm a trip?</summary><p>Open Trips, choose Confirm Trip, select a pending trip, optionally add notes, and choose Confirm trip. Confirmed trips are locked from further edits.</p></details>
        <details class="driver-faq"><summary>How is the fare estimated?</summary><p>Fare estimates use the vehicle type, passenger type, and distance entered. Applicable student, senior citizen, and PWD discounts are included in the estimate.</p></details>
        <details class="driver-faq"><summary>What if my jeepney is not showing?</summary><p>Only vehicles assigned to both your driver account and approved route are available for trips. Contact the PoloNav operations team to check your assignment.</p></details>
        <details class="driver-faq"><summary>Can I change my assigned route?</summary><p>Routes are assigned by the operations team. Contact your administrator if your route needs to be changed.</p></details>
        <details class="driver-faq"><summary>How do I update my profile information?</summary><p>Open Profile to view your driver information. Contact the operations team to request changes to your name, contact number, or license details.</p></details>
        <details class="driver-faq"><summary>What documents should I keep updated?</summary><p>Keep your driver's license and required vehicle registration, insurance, inspection, and emission documents current with the operations team.</p></details>
        <details class="driver-faq"><summary>Who do I contact for technical issues?</summary><p>Contact PoloNav support at support@polonav.ph or call the hotline at (02) 8-POLO-NAV.</p></details>
    </div>
    <p id="help-no-results" class="driver-help-empty" hidden>No questions match your search.</p>
    <a class="driver-help-contact" href="mailto:support@polonav.ph">
        <span class="driver-help-contact-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
        <span><strong>Still need help?</strong><small>Contact support at support@polonav.ph · Hotline: (02) 8-POLO-NAV</small></span>
    </a>
</section>
<?php layout_end(['driver.js', 'driver-sidebar.js']); ?>
