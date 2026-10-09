<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Daily Earnings & Fares', 'active' => 'earnings', 'page' => 'earnings']);
?>
<header class="page-heading">
    <h2>Daily Earnings &amp; Fares</h2>
    <p>Review passenger fares recorded on your trips. Earnings are saved to your driver account.</p>
</header>
<section id="driver-earnings" class="driver-earnings" aria-live="polite">
    <header class="earnings-print-header">
        <h2>PoloNav Cab · Daily Earnings &amp; Fares</h2>
        <p>Driver: <?= htmlspecialchars((string) ($user['username'] ?? 'Driver'), ENT_QUOTES, 'UTF-8') ?> · Date: <span id="earnings-print-date">—</span></p>
    </header>
    <div class="earnings-route-info">
        <div><span>Assigned route</span><strong id="earnings-route-name">Loading route...</strong></div>
        <div><span>Fare basis</span><strong>Actual distance per passenger</strong></div>
    </div>
    <section class="earnings-day">
        <div class="earnings-day-heading">
            <div><h3>Daily totals</h3><p>Choose a date to review fares from your own trips.</p></div>
            <label for="earnings-date">Date
                <input id="earnings-date" type="date">
            </label>
            <button id="earnings-download-pdf" class="btn btn-ghost" type="button" title="Open the print dialog and choose Save as PDF"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download daily PDF</button>
        </div>
        <div class="earnings-summary">
            <article><span>Passengers recorded</span><strong id="earnings-passenger-count">0</strong></article>
            <article><span>Gross fares</span><strong id="earnings-gross">₱0.00</strong></article>
            <label>Terminal fee
                <select id="earnings-fee-rate">
                    <option value="0.02">2%</option>
                    <option value="0.04">4%</option>
                </select>
            </label>
            <article class="earnings-fee-display"><span>Terminal fee rate</span><strong id="earnings-fee-label">2%</strong></article>
            <article><span>Deduction amount</span><strong id="earnings-deduction">₱0.00</strong></article>
            <article class="earnings-net"><span>Net daily earnings</span><strong id="earnings-net">₱0.00</strong></article>
        </div>
        <div class="earnings-records-wrap">
            <h3>Passenger fare records</h3>
            <p id="earnings-message" class="earnings-message" role="status" hidden></p>
            <div class="table-wrap">
                <table id="earnings-records">
                    <thead><tr><th>Time</th><th>Trip</th><th>Route</th><th>Vehicle</th><th>Passenger type</th><th>From</th><th>To</th><th>Distance</th><th>Fare</th></tr></thead>
                    <tbody><tr><td colspan="9" class="muted">Loading fare records...</td></tr></tbody>
                </table>
            </div>
        </div>
    </section>
</section>
<?php layout_end(['earnings.js', 'driver-sidebar.js']); ?>
