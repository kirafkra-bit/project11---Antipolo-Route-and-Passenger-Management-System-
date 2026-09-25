<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
require_once dirname(__DIR__) . '/includes/antipolo_points.php';
$points = antipolo_points_from_database(get_pdo());
layout_start($user, ['title' => 'Estimate fare', 'active' => 'fare', 'page' => 'fare']);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #antipolo-map { height: 360px; border-radius: 12px; margin: 16px 0; }
    .map-status { min-height: 1.4em; }
</style>
<section class="grid two">
    <article class="card">
        <h3>LTFRB-style estimate</h3>
        <p class="muted">Choose two Antipolo landmarks to calculate a road distance for the fare estimate.</p>
        <div class="map-search">
            <label for="map-search-input">Search an Antipolo place</label>
            <div class="map-search-row">
                <input id="map-search-input" type="search" placeholder="e.g. Antipolo City Hall">
                <button id="map-search-button" class="btn btn-ghost" type="button">Search</button>
            </div>
            <div id="map-search-results" class="map-search-results" role="listbox"></div>
            <p id="map-selection-status" class="muted map-status" role="status">Click a result or map marker to choose From.</p>
        </div>
        <div id="antipolo-map" aria-label="Map of Antipolo landmarks"></div>
        <p id="map-status" class="muted map-status" role="status"></p>
        <form id="fare-form" class="stack">
            <div>
                <label>Vehicle</label>
                <select name="vehicle_type">
                    <option value="traditional">Traditional Jeepney</option>
                    <option value="modern">Modern Jeepney</option>
                </select>
            </div>
            <div>
                <label>You are a</label>
                <select name="passenger_type">
                    <option value="regular">Regular</option>
                    <option value="student">Student</option>
                    <option value="senior">Senior Citizen</option>
                    <option value="pwd">PWD</option>
                </select>
            </div>
            <div>
                <label for="point_a">From</label>
                <select name="point_a" id="point_a" required>
                    <?php foreach ($points as $point): ?>
                        <option value="<?= htmlspecialchars($point['id']) ?>"
                                data-lat="<?= htmlspecialchars((string) $point['lat']) ?>"
                                data-lng="<?= htmlspecialchars((string) $point['lng']) ?>">
                            <?= htmlspecialchars($point['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="point_b">To</label>
                <select name="point_b" id="point_b" required>
                    <?php foreach ($points as $index => $point): ?>
                        <option value="<?= htmlspecialchars($point['id']) ?>"
                                data-lat="<?= htmlspecialchars((string) $point['lat']) ?>"
                                data-lng="<?= htmlspecialchars((string) $point['lng']) ?>"
                                <?= $index === 1 ? 'selected' : '' ?>>
                            <?= htmlspecialchars($point['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="distance">Distance (km)</label>
                <input name="distance" id="distance" required>
                <small class="muted">Filled from the road route. You may override it if needed.</small>
            </div>
            <button class="btn" type="submit">Estimate</button>
        </form>
    </article>
    <div id="fare-result"></div>
</section>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
<?php layout_end(['passenger.js', 'antipolo-map.js']); ?>
