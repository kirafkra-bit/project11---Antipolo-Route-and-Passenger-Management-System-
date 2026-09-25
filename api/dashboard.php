<?php
/**
 * Role-based dashboard statistics. Numbers come from MySQL, not hard-coded values.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$pdo = get_pdo();

if (request_method() !== 'GET') {
    json_error('Use GET to load dashboard data.', 405);
}

function fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

try {
    if ($user['role'] === 'admin') {
        json_success([
            'role'  => 'admin',
            'stats' => [
                'total_users'      => count_where($pdo, 'SELECT COUNT(*) FROM users'),
                'total_passengers' => count_where($pdo, 'SELECT COUNT(*) FROM passengers'),
                'total_drivers'    => count_where($pdo, 'SELECT COUNT(*) FROM drivers'),
                'total_jeepneys'   => count_where($pdo, 'SELECT COUNT(*) FROM jeepney'),
                'total_routes'     => count_where($pdo, 'SELECT COUNT(*) FROM routes'),
                'total_trips'      => count_where($pdo, 'SELECT COUNT(*) FROM trips'),
                'total_complaints' => count_where($pdo, 'SELECT COUNT(*) FROM complaints'),
                'open_complaints'  => count_where($pdo, "SELECT COUNT(*) FROM complaints WHERE status IN ('submitted', 'in_review')"),
                'active_drivers'   => count_where($pdo, "SELECT COUNT(*) FROM drivers WHERE status = 'active'"),
            ],
            'recent_trips' => fetch_all(
                $pdo,
                'SELECT t.id, t.pickup, t.drop_off, t.number_of_passengers,
                        p.name AS passenger_name, d.full_name AS driver_name, j.plate_number
                 FROM trips t
                 INNER JOIN passengers p ON p.passengers_id = t.passengers_id
                 INNER JOIN drivers d ON d.id = t.drivers_id
                 INNER JOIN jeepney j ON j.id = t.jeepney_id
                 ORDER BY t.id DESC
                 LIMIT 10'
            ),
            'recent_passengers' => fetch_all(
                $pdo,
                'SELECT passengers_id, name, contact_number, created_at
                 FROM passengers
                 ORDER BY passengers_id DESC
                 LIMIT 10'
            ),
            'recent_drivers' => fetch_all(
                $pdo,
                'SELECT id, full_name, license_number, status, created_at
                 FROM drivers
                 ORDER BY id DESC
                 LIMIT 10'
            ),
            'active_jeepneys' => fetch_all(
                $pdo,
                "SELECT j.id, j.plate_number, j.jeepney_number, d.full_name AS driver_name, r.route_name
                 FROM jeepney j
                 INNER JOIN drivers d ON d.id = j.drivers_id
                 INNER JOIN routes r ON r.id = j.route_id
                 WHERE d.status = 'active'
                 ORDER BY j.id DESC"
            ),
            'available_routes' => fetch_all(
                $pdo,
                'SELECT id, route_name, destinations, distance, fare
                 FROM routes
                 ORDER BY route_name ASC'
            ),
        ]);
    }

    if ($user['role'] === 'driver') {
        if (empty($user['driver_id'])) {
            json_error('Driver profile was not found for this account.', 404);
        }
        $driverId = (int) $user['driver_id'];

        json_success([
            'role'    => 'driver',
            'driver'  => fetch_all($pdo, 'SELECT id, full_name, contact_number, license_number, status FROM drivers WHERE id = :id', ['id' => $driverId])[0] ?? null,
            'jeepney' => fetch_all(
                $pdo,
                'SELECT j.id, j.plate_number, j.jeepney_number, r.route_name, r.destinations, r.distance, r.fare, r.latitude, r.longitude
                 FROM jeepney j
                 INNER JOIN routes r ON r.id = j.route_id
                 WHERE j.drivers_id = :id',
                ['id' => $driverId]
            ),
            'recent_trips' => fetch_all(
                $pdo,
                'SELECT t.id, t.pickup, t.drop_off, t.number_of_passengers, p.name AS passenger_name, j.plate_number, r.route_name
                 FROM trips t
                 INNER JOIN passengers p ON p.passengers_id = t.passengers_id
                 INNER JOIN jeepney j ON j.id = t.jeepney_id
                 INNER JOIN routes r ON r.id = j.route_id
                 WHERE t.drivers_id = :id
                 ORDER BY t.id DESC
                 LIMIT 10',
                ['id' => $driverId]
            ),
            'stats' => [
                'my_trips'    => count_where($pdo, 'SELECT COUNT(*) FROM trips WHERE drivers_id = :id', ['id' => $driverId]),
                'my_jeepneys' => count_where($pdo, 'SELECT COUNT(*) FROM jeepney WHERE drivers_id = :id', ['id' => $driverId]),
            ],
        ]);
    }

    if ($user['role'] === 'passenger') {
        if (empty($user['passenger_id'])) {
            json_error('Passenger profile was not found for this account.', 404);
        }
        $passengerId = (int) $user['passenger_id'];

        json_success([
            'role'      => 'passenger',
            'passenger' => fetch_all(
                $pdo,
                'SELECT passengers_id, name, contact_number, address, created_at FROM passengers WHERE passengers_id = :id',
                ['id' => $passengerId]
            )[0] ?? null,
            'available_routes' => fetch_all(
                $pdo,
                'SELECT id, route_name, destinations, distance, fare FROM routes ORDER BY route_name ASC'
            ),
            'recent_trips' => fetch_all(
                $pdo,
                'SELECT t.id, t.pickup, t.drop_off, t.number_of_passengers, d.full_name AS driver_name, j.plate_number, r.route_name
                 FROM trips t
                 INNER JOIN drivers d ON d.id = t.drivers_id
                 INNER JOIN jeepney j ON j.id = t.jeepney_id
                 INNER JOIN routes r ON r.id = j.route_id
                 WHERE t.passengers_id = :id
                 ORDER BY t.id DESC
                 LIMIT 10',
                ['id' => $passengerId]
            ),
            'stats' => [
                'my_trips'        => count_where($pdo, 'SELECT COUNT(*) FROM trips WHERE passengers_id = :id', ['id' => $passengerId]),
                'available_routes'=> count_where($pdo, 'SELECT COUNT(*) FROM routes'),
                'my_complaints'   => count_where($pdo, 'SELECT COUNT(*) FROM complaints WHERE passenger_id = :id', ['id' => $passengerId]),
            ],
        ]);
    }

    json_error('Unknown user role.', 403);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to load dashboard data.');
}
