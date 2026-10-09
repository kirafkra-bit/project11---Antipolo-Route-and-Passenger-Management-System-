<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_role(['driver']);
if (empty($user['driver_id'])) {
    json_error('Driver profile was not found for this account.', 404);
}

$pdo = get_pdo();
$driverId = (int) $user['driver_id'];
$method = request_method();

function get_approved_incident_route(PDO $pdo, int $driverId): int
{
    $stmt = $pdo->prepare(
        "SELECT route_id FROM drivers
         WHERE id = :id AND route_assignment_status = 'approved' AND route_id IS NOT NULL
         LIMIT 1"
    );
    $stmt->execute(['id' => $driverId]);
    $routeId = $stmt->fetchColumn();
    if ($routeId === false) {
        json_error('Waiting for route assignment. Incident reports are available after your route is approved.', 403);
    }
    return (int) $routeId;
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT i.id, i.route_id, i.driver_id, i.incident_type, i.location_description,
                    i.latitude, i.longitude, i.description, i.status, i.reported_at,
                    d.full_name AS driver_name, r.route_name
             FROM incident_reports i
             INNER JOIN drivers d ON d.id = i.driver_id
             INNER JOIN routes r ON r.id = i.route_id
             WHERE i.status = \'active\'
               AND i.reported_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)
             ORDER BY i.reported_at DESC, i.id DESC
             LIMIT 100'
        );
        json_success($stmt->fetchAll());
    }

    if ($method !== 'POST') {
        json_error('Method not allowed.', 405);
    }

    $routeId = get_approved_incident_route($pdo, $driverId);
    $input = request_input();
    require_csrf($input);
    require_fields($input, ['incident_type', 'location_description', 'description']);

    $types = ['flooding', 'accident', 'road_construction', 'vehicle_breakdown', 'obstruction', 'other'];
    $type = trim_string($input['incident_type']);
    $location = trim_string($input['location_description']);
    $description = trim_string($input['description']);
    if (!in_array($type, $types, true)) {
        json_error('Choose a valid incident type.', 422);
    }
    if (strlen($location) > 255 || $location === '') {
        json_error('Enter a location of up to 255 characters.', 422);
    }
    if (strlen($description) < 5 || strlen($description) > 4000) {
        json_error('Incident details must be between 5 and 4000 characters.', 422);
    }

    $latitude = null;
    $longitude = null;
    $hasLatitude = isset($input['latitude']) && $input['latitude'] !== '';
    $hasLongitude = isset($input['longitude']) && $input['longitude'] !== '';
    if ($hasLatitude !== $hasLongitude) {
        json_error('Both latitude and longitude are required when providing coordinates.', 422);
    }
    if ($hasLatitude) {
        $latitude = to_float($input['latitude'], 'latitude');
        $longitude = to_float($input['longitude'], 'longitude');
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            json_error('The reported coordinates are outside valid latitude/longitude ranges.', 422);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO incident_reports
            (route_id, driver_id, incident_type, location_description, latitude, longitude, description)
         VALUES (:route_id, :driver_id, :incident_type, :location_description, :latitude, :longitude, :description)'
    );
    $stmt->execute([
        'route_id' => $routeId,
        'driver_id' => $driverId,
        'incident_type' => $type,
        'location_description' => $location,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'description' => $description,
    ]);

    $incidentId = (int) $pdo->lastInsertId();
    $read = $pdo->prepare(
        'SELECT i.id, i.route_id, i.driver_id, i.incident_type, i.location_description,
                i.latitude, i.longitude, i.description, i.status, i.reported_at,
                d.full_name AS driver_name, r.route_name
         FROM incident_reports i
         INNER JOIN drivers d ON d.id = i.driver_id
         INNER JOIN routes r ON r.id = i.route_id
         WHERE i.id = :id LIMIT 1'
    );
    $read->execute(['id' => $incidentId]);
    json_success($read->fetch(), 201);
} catch (PDOException $error) {
    handle_pdo_exception($error, 'Unable to load or save route incident reports.');
}
