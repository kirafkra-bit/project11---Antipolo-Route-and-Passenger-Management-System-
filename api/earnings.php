<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_role(['driver']);
if (empty($user['driver_id'])) {
    json_error('Driver profile was not found for this account.', 404);
}
if (request_method() !== 'GET') {
    json_error('Method not allowed.', 405);
}

$date = (string) query_param('date', date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_error('Choose a valid earnings date.', 422);
}
[$year, $month, $day] = array_map('intval', explode('-', $date));
if (!checkdate($month, $day, $year)) {
    json_error('Choose a valid earnings date.', 422);
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT f.id, f.trip_id, f.vehicle_type, f.passenger_type, f.origin_name, f.destination_name,
                f.distance_km, f.fare, f.boarded_at, t.status AS trip_status, r.route_name
         FROM trip_passenger_fares f
         INNER JOIN trips t ON t.id = f.trip_id AND t.drivers_id = f.driver_id
         LEFT JOIN routes r ON r.id = t.route_id
         WHERE f.driver_id = :driver_id AND DATE(f.boarded_at) = :record_date
         ORDER BY f.boarded_at ASC, f.id ASC'
    );
    $stmt->execute([
        'driver_id' => (int) $user['driver_id'],
        'record_date' => $date,
    ]);
    $records = $stmt->fetchAll();
    $gross = 0.0;
    foreach ($records as $record) {
        $gross += (float) $record['fare'];
    }
    json_success([
        'date' => $date,
        'passenger_count' => count($records),
        'gross_fares' => round($gross, 2),
        'records' => $records,
    ]);
} catch (PDOException $error) {
    handle_pdo_exception($error, 'Unable to load this driver’s daily earnings.');
}
