<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
layout_start($user, ['title' => 'Traffic & Incident Reports', 'active' => 'incidents', 'page' => 'incidents']);
?>
<header class="page-heading">
    <h2>Traffic &amp; Incident Reports</h2>
    <p>Check route traffic and view incident reports shared by drivers across all routes.</p>
</header>
<section class="driver-incidents" aria-live="polite">
    <section class="live-traffic-panel" aria-labelledby="live-traffic-heading">
        <div class="live-traffic-copy">
            <span class="live-traffic-kicker"><i class="bi bi-broadcast-pin" aria-hidden="true"></i> Live traffic · TomTom</span>
            <h3 id="live-traffic-heading">Check traffic on your route</h3>
            <p>Choose a configured checkpoint on your approved route to check its nearest road segment. Traffic levels are estimated by comparing current and free-flow speeds.</p>
        </div>
        <div class="live-traffic-controls">
            <label for="live-traffic-point">Route checkpoint</label>
            <select id="live-traffic-point" disabled>
                <option value="">Loading assigned route checkpoints...</option>
            </select>
            <button id="live-traffic-check" class="btn" type="button" disabled><i class="bi bi-broadcast-pin" aria-hidden="true"></i> Check selected location</button>
        </div>
        <div id="live-traffic-result" class="live-traffic-result" role="status" aria-live="polite" hidden></div>
    </section>
    <div class="traffic-status-notice"><i class="bi bi-info-circle" aria-hidden="true"></i>
        <span>Live status describes the selected checkpoint on your assigned route, not the whole route. Global driver-submitted reports are listed below.</span>
    </div>
    <div class="incident-layout">
        <section class="incident-list-section">
            <div class="incident-section-heading"><div><h3>Active reports from all drivers</h3><p>Recent active incident reports shared across all assigned routes.</p></div>
                <button id="incidents-refresh" class="btn btn-ghost" type="button"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh</button>
            </div>
            <div id="incident-list" class="incident-list"><p class="muted">Loading global incident reports...</p></div>
        </section>
        <section class="incident-report-section">
            <h3>Report an incident</h3>
            <p>Do not use this form while driving. Pull over safely before reporting.</p>
            <form id="incident-report-form" class="incident-report-form">
                <label>Incident type
                    <select name="incident_type" required>
                        <option value="">Choose a type...</option>
                        <option value="flooding">Flooding</option>
                        <option value="accident">Accident</option>
                        <option value="road_construction">Road construction</option>
                        <option value="vehicle_breakdown">Vehicle breakdown</option>
                        <option value="obstruction">Road obstruction</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>Location or landmark
                    <input id="incident-location" name="location_description" type="text" maxlength="255" required placeholder="Road, barangay, or nearby landmark">
                </label>
                <input id="incident-latitude" name="latitude" type="hidden">
                <input id="incident-longitude" name="longitude" type="hidden">
                <button id="incident-use-location" class="btn btn-ghost" type="button"><i class="bi bi-geo-alt" aria-hidden="true"></i> Add current coordinates</button>
                <p id="incident-location-message" class="incident-form-message" role="status" hidden></p>
                <label>Details
                    <textarea name="description" rows="4" minlength="5" maxlength="4000" required placeholder="Describe what other drivers should know"></textarea>
                </label>
                <p id="incident-form-message" class="incident-form-message" role="status" hidden></p>
                <button class="btn" type="submit">Submit route report</button>
            </form>
        </section>
    </div>
</section>
<?php layout_end(['incidents.js', 'driver-sidebar.js']); ?>
