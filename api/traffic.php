<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_role(['driver']);
if (empty($user['driver_id'])) {
    json_error('Driver profile was not found for this account.', 404);
}

$pdo = get_pdo();
$driverId = (int) $user['driver_id'];
$method = request_method();

function get_traffic_route(PDO $pdo, int $driverId): array
{
    $stmt = $pdo->prepare(
        "SELECT r.id, r.route_name, r.destinations, r.pickup_point_id, r.dropoff_point_id
         FROM drivers d
         INNER JOIN routes r ON r.id = d.route_id
         WHERE d.id = :driver_id
           AND d.route_assignment_status = 'approved'
         LIMIT 1"
    );
    $stmt->execute(['driver_id' => $driverId]);
    $route = $stmt->fetch();
    if (!$route) {
        json_error('Waiting for route assignment. Route traffic is available after your route is approved.', 403);
    }
    return $route;
}

function traffic_route_checkpoints(PDO $pdo, array $route): array
{
    $points = $pdo->query(
        'SELECT id, point_key, name, latitude, longitude
         FROM antipolo_points
         WHERE is_active = 1
         ORDER BY name ASC'
    )->fetchAll();
    $routeText = normalize_traffic_text(
        (string) $route['route_name'] . ' ' . (string) ($route['destinations'] ?? '')
    );
    $routeText = ' ' . $routeText . ' ';
    $checkpoints = [];
    foreach ($points as $point) {
        $key = normalize_traffic_text((string) $point['point_key']);
        $name = normalize_traffic_text((string) $point['name']);
        $isEndpoint = (int) $point['id'] === (int) $route['pickup_point_id']
            || (int) $point['id'] === (int) $route['dropoff_point_id'];
        $isMentioned = ($key !== '' && strpos($routeText, ' ' . $key . ' ') !== false)
            || ($name !== '' && strpos($routeText, ' ' . $name . ' ') !== false);
        if ($isEndpoint || $isMentioned) {
            $checkpoints[] = [
                'id' => (int) $point['id'],
                'name' => $point['name'],
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
            ];
        }
    }
    return $checkpoints;
}

function normalize_traffic_text(string $value): string
{
    $normalized = preg_replace('/[^a-z0-9]+/i', ' ', strtolower($value));
    return trim(is_string($normalized) ? $normalized : '');
}

try {
    $route = get_traffic_route($pdo, $driverId);
    if ($method === 'GET') {
        $checkpoints = traffic_route_checkpoints($pdo, $route);
        json_success([
            'route_name' => $route['route_name'],
            'checkpoints' => $checkpoints,
        ]);
    }

    if ($method !== 'POST') {
        json_error('Method not allowed.', 405);
    }

    $input = request_input();
    require_csrf($input);
    if (!isset($input['point_id']) || !is_numeric($input['point_id'])) {
        json_error('Choose a traffic location on your assigned route.', 422);
    }
    $pointId = to_int($input['point_id'], 'point_id');
    $point = null;
    foreach (traffic_route_checkpoints($pdo, $route) as $checkpoint) {
        if ($checkpoint['id'] === $pointId) {
            $point = $checkpoint;
            break;
        }
    }
    if (!$point) {
        json_error('That traffic location is not listed on your assigned route.', 422);
    }

    if (TOMTOM_TRAFFIC_API_KEY === '') {
        json_error('Live traffic is not configured. Add TOMTOM_TRAFFIC_API_KEY to the project .env file or web server environment.', 503);
    }

    $query = http_build_query([
        'key' => TOMTOM_TRAFFIC_API_KEY,
        'point' => number_format((float) $point['latitude'], 6, '.', '') . ','
            . number_format((float) $point['longitude'], 6, '.', ''),
        'unit' => 'kmph',
    ]);
    $url = 'https://api.tomtom.com/traffic/services/4/flowSegmentData/absolute/10/json?' . $query;
    $curl = curl_init($url);
    if ($curl === false) {
        json_error('Unable to initialize the live traffic request.', 500);
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        error_log('TomTom traffic request failed to complete.');
        json_error('The live traffic provider could not be reached. Please try again later.', 502);
    }
    if ($httpStatus === 401 || $httpStatus === 403) {
        error_log('TomTom traffic request rejected credentials with HTTP ' . $httpStatus . '.');
        json_error('The live traffic provider rejected its API key. Check TOMTOM_TRAFFIC_API_KEY.', 502);
    }
    if ($httpStatus === 429) {
        json_error('The live traffic provider rate limit was reached. Try again shortly.', 429);
    }
    if ($httpStatus === 404) {
        json_error('TomTom did not return traffic data for this route checkpoint.', 404);
    }
    if ($httpStatus < 200 || $httpStatus >= 300) {
        error_log('TomTom traffic request returned HTTP ' . $httpStatus . '.');
        json_error('The live traffic provider returned an error. Please try again later.', 502);
    }

    $payload = json_decode($response, true);
    $flow = is_array($payload) ? ($payload['flowSegmentData'] ?? null) : null;
    if (!is_array($flow)
        || !isset($flow['currentSpeed'], $flow['freeFlowSpeed'])
        || !is_numeric($flow['currentSpeed'])
        || !is_numeric($flow['freeFlowSpeed'])) {
        error_log('TomTom traffic response did not contain valid flow segment data.');
        json_error('The live traffic provider returned incomplete road-speed data.', 502);
    }

    $currentSpeed = max(0, (float) $flow['currentSpeed']);
    $freeFlowSpeed = max(0, (float) $flow['freeFlowSpeed']);
    $roadClosure = filter_var($flow['roadClosure'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $status = 'unknown';
    if ($roadClosure) {
        $status = 'closed';
    } elseif ($freeFlowSpeed > 0) {
        $speedRatio = $currentSpeed / $freeFlowSpeed;
        if ($speedRatio < 0.5) {
            $status = 'heavy';
        } elseif ($speedRatio < 0.8) {
            $status = 'moderate';
        } elseif ($speedRatio < 0.95) {
            $status = 'light';
        } else {
            $status = 'flowing';
        }
    }

    $delaySeconds = null;
    if (isset($flow['currentTravelTime'], $flow['freeFlowTravelTime'])
        && is_numeric($flow['currentTravelTime'])
        && is_numeric($flow['freeFlowTravelTime'])) {
        $delaySeconds = max(0, (int) $flow['currentTravelTime'] - (int) $flow['freeFlowTravelTime']);
    }

    json_success([
        'provider' => 'TomTom',
        'route_name' => $route['route_name'],
        'status' => $status,
        'current_speed_kmph' => round($currentSpeed, 1),
        'free_flow_speed_kmph' => round($freeFlowSpeed, 1),
        'delay_seconds' => $delaySeconds,
        'confidence' => isset($flow['confidence']) && is_numeric($flow['confidence'])
            ? round((float) $flow['confidence'], 2)
            : null,
        'road_closure' => $roadClosure,
        'checked_at' => date(DATE_ATOM),
        'location' => [
            'name' => $point['name'],
            'latitude' => round((float) $point['latitude'], 5),
            'longitude' => round((float) $point['longitude'], 5),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Driver route traffic lookup failed: ' . $e->getMessage());
    json_error('Unable to load traffic checkpoints for your assigned route.', 500);
}
