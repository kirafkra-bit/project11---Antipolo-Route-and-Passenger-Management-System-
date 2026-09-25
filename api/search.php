<?php
/**
 * Route search used by passengers and drivers.
 *
 * Query string:
 *   q              general search (route name / destinations)
 *   pickup         pickup text
 *   drop_off       drop-off text
 *   vehicle_type   Traditional Jeepney | Modern Jeepney (optional)
 *   passenger_type Regular | Student | Senior Citizen | PWD (optional)
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();
$pdo = get_pdo();

if (request_method() !== 'GET') {
    json_error('Use GET to search routes.', 405);
}

$q = trim_string((string) query_param('q', ''));
$pickup = trim_string((string) query_param('pickup', ''));
$dropOff = trim_string((string) query_param('drop_off', query_param('dropoff', '')));
$vehicleType = trim_string((string) query_param('vehicle_type', 'traditional'));
$passengerType = trim_string((string) query_param('passenger_type', 'regular'));

$sql = 'SELECT r.id, r.route_name, r.destinations, r.distance, r.fare,
               r.latitude, r.longitude,
               j.id AS jeepney_id, j.plate_number, j.jeepney_number,
               d.id AS driver_id, d.full_name AS driver_name, d.status AS driver_status
        FROM routes r
        LEFT JOIN jeepney j ON j.route_id = r.id
        LEFT JOIN drivers d ON d.id = j.drivers_id
        WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (r.route_name LIKE :q OR r.destinations LIKE :q2)';
    $like = like_search($q);
    $params['q'] = $like;
    $params['q2'] = $like;
}

if ($pickup !== '') {
    $sql .= ' AND (r.route_name LIKE :pickup OR r.destinations LIKE :pickup2)';
    $likePickup = like_search($pickup);
    $params['pickup'] = $likePickup;
    $params['pickup2'] = $likePickup;
}

if ($dropOff !== '') {
    $sql .= ' AND (r.route_name LIKE :drop_off OR r.destinations LIKE :drop_off2)';
    $likeDrop = like_search($dropOff);
    $params['drop_off'] = $likeDrop;
    $params['drop_off2'] = $likeDrop;
}

$sql .= ' ORDER BY r.route_name ASC, j.plate_number ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $results = [];
    foreach ($rows as $row) {
        $fare = null;
        $fareError = null;
        if ($row['distance'] !== null) {
            try {
                $fare = calculate_fare($vehicleType !== '' ? $vehicleType : 'traditional', $passengerType, $row['distance']);
            } catch (InvalidArgumentException $e) {
                $fareError = $e->getMessage();
            }
        }

        $results[] = [
            'route_id'       => (int) $row['id'],
            'route_name'     => $row['route_name'],
            'destinations'   => $row['destinations'],
            'pickup'         => $pickup !== '' ? $pickup : $row['route_name'],
            'drop_off'       => $dropOff !== '' ? $dropOff : $row['destinations'],
            'distance'       => $row['distance'] !== null ? (int) $row['distance'] : null,
            'stored_fare'    => (int) $row['fare'],
            'latitude'       => $row['latitude'],
            'longitude'      => $row['longitude'],
            'jeepney'        => $row['jeepney_id'] ? [
                'id'             => (int) $row['jeepney_id'],
                'plate_number'   => $row['plate_number'],
                'jeepney_number' => (int) $row['jeepney_number'],
            ] : null,
            'driver'         => $row['driver_id'] ? [
                'id'     => (int) $row['driver_id'],
                'name'   => $row['driver_name'],
                'status' => $row['driver_status'],
            ] : null,
            'estimated_fare' => $fare,
            'fare_error'     => $fareError,
        ];
    }

    json_success($results);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to search routes.');
}
