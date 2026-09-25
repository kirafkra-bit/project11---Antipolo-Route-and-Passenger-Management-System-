<?php
/**
 * Trip records.
 *
 * GET  admin sees all, driver sees own, passenger sees own
 * POST driver records a trip for themselves; admin may create any trip
 * PUT/DELETE admin only
 *
 * The current trips table has no created_at column, so date/time
 * cannot be returned until the database is changed later.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = get_pdo();
$method = request_method();
$input = request_input();
$user = require_login();

if ($method !== 'GET') {
    require_csrf($input);
}

function trip_select_sql(): string
{
    return 'SELECT t.id, t.pickup, t.drop_off, t.number_of_passengers,
                   t.passengers_id, t.jeepney_id, t.drivers_id,
                   p.name AS passenger_name,
                   d.full_name AS driver_name,
                   j.plate_number, j.jeepney_number, j.route_id,
                   r.route_name, r.destinations, r.distance
            FROM trips t
            INNER JOIN passengers p ON p.passengers_id = t.passengers_id
            INNER JOIN drivers d ON d.id = t.drivers_id
            INNER JOIN jeepney j ON j.id = t.jeepney_id
            INNER JOIN routes r ON r.id = j.route_id';
}

function fetch_trip(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(trip_select_sql() . ' WHERE t.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function assert_trip_foreign_keys(PDO $pdo, int $passengerId, int $jeepneyId, int $driverId): array
{
    if (!record_exists($pdo, 'passengers', 'passengers_id', $passengerId)) {
        json_error('Passenger was not found.', 422);
    }
    if (!record_exists($pdo, 'jeepney', 'id', $jeepneyId)) {
        json_error('Jeepney was not found.', 422);
    }
    if (!record_exists($pdo, 'drivers', 'id', $driverId)) {
        json_error('Driver was not found.', 422);
    }

    $stmt = $pdo->prepare('SELECT drivers_id FROM jeepney WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $jeepneyId]);
    $assignedDriver = (int) $stmt->fetchColumn();

    if ($assignedDriver !== $driverId) {
        json_error('That jeepney is not assigned to this driver.', 422);
    }

    return ['assigned_driver_id' => $assignedDriver];
}

try {
    if ($method === 'GET') {
        $id = query_param('id');
        $search = trim_string((string) query_param('q', ''));
        $sql = trip_select_sql() . ' WHERE 1=1';
        $params = [];

        if ($user['role'] === 'driver') {
            if (empty($user['driver_id'])) {
                json_error('Driver profile was not found for this account.', 404);
            }
            $sql .= ' AND t.drivers_id = :me';
            $params['me'] = (int) $user['driver_id'];
        } elseif ($user['role'] === 'passenger') {
            if (empty($user['passenger_id'])) {
                json_error('Passenger profile was not found for this account.', 404);
            }
            $sql .= ' AND t.passengers_id = :me';
            $params['me'] = (int) $user['passenger_id'];
        } else {
            require_role(['admin']);
        }

        if ($id !== null && $id !== '') {
            $sql .= ' AND t.id = :id';
            $params['id'] = to_int($id, 'id');
        }

        if ($search !== '') {
            $sql .= ' AND (t.pickup LIKE :q OR t.drop_off LIKE :q2 OR p.name LIKE :q3 OR d.full_name LIKE :q4 OR j.plate_number LIKE :q5 OR r.route_name LIKE :q6)';
            $like = like_search($search);
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $params['q5'] = $like;
            $params['q6'] = $like;
        }

        $sql .= ' ORDER BY t.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($id !== null && $id !== '' && !$rows) {
            json_error('Trip not found.', 404);
        }

        json_success($id !== null && $id !== '' ? $rows[0] : $rows);
    }

    if ($method === 'POST') {
        if ($user['role'] === 'passenger') {
            json_error('Passengers cannot record trips. Ask a driver to record the trip.', 403);
        }

        require_fields($input, ['pickup', 'drop_off', 'number_of_passengers', 'passengers_id', 'jeepney_id']);

        $passengerId = to_int($input['passengers_id'], 'passengers_id');
        $jeepneyId = to_int($input['jeepney_id'], 'jeepney_id');
        $count = to_non_negative_int($input['number_of_passengers'], 'number_of_passengers');

        if ($user['role'] === 'driver') {
            if (empty($user['driver_id'])) {
                json_error('Driver profile was not found for this account.', 404);
            }
            $driverId = (int) $user['driver_id'];
        } else {
            require_role(['admin']);
            require_fields($input, ['drivers_id']);
            $driverId = to_int($input['drivers_id'], 'drivers_id');
        }

        assert_trip_foreign_keys($pdo, $passengerId, $jeepneyId, $driverId);

        $stmt = $pdo->prepare(
            'INSERT INTO trips (drop_off, pickup, number_of_passengers, passengers_id, jeepney_id, drivers_id)
             VALUES (:drop_off, :pickup, :number_of_passengers, :passengers_id, :jeepney_id, :drivers_id)'
        );
        $stmt->execute([
            'drop_off'             => trim_string($input['drop_off']),
            'pickup'               => trim_string($input['pickup']),
            'number_of_passengers' => $count,
            'passengers_id'        => $passengerId,
            'jeepney_id'           => $jeepneyId,
            'drivers_id'           => $driverId,
        ]);

        json_success(fetch_trip($pdo, (int) $pdo->lastInsertId()), 201);
    }

    if ($method === 'PUT') {
        require_role(['admin']);
        require_fields($input, ['id', 'pickup', 'drop_off', 'number_of_passengers', 'passengers_id', 'jeepney_id', 'drivers_id']);
        $id = to_int($input['id'], 'id');
        if (!fetch_trip($pdo, $id)) {
            json_error('Trip not found.', 404);
        }

        $passengerId = to_int($input['passengers_id'], 'passengers_id');
        $jeepneyId = to_int($input['jeepney_id'], 'jeepney_id');
        $driverId = to_int($input['drivers_id'], 'drivers_id');
        $count = to_non_negative_int($input['number_of_passengers'], 'number_of_passengers');

        assert_trip_foreign_keys($pdo, $passengerId, $jeepneyId, $driverId);

        $stmt = $pdo->prepare(
            'UPDATE trips
             SET drop_off = :drop_off,
                 pickup = :pickup,
                 number_of_passengers = :number_of_passengers,
                 passengers_id = :passengers_id,
                 jeepney_id = :jeepney_id,
                 drivers_id = :drivers_id
             WHERE id = :id'
        );
        $stmt->execute([
            'drop_off'             => trim_string($input['drop_off']),
            'pickup'               => trim_string($input['pickup']),
            'number_of_passengers' => $count,
            'passengers_id'        => $passengerId,
            'jeepney_id'           => $jeepneyId,
            'drivers_id'           => $driverId,
            'id'                   => $id,
        ]);

        json_success(fetch_trip($pdo, $id));
    }

    if ($method === 'DELETE') {
        require_role(['admin']);
        require_fields($input, ['id']);
        $id = to_int($input['id'], 'id');
        if (!fetch_trip($pdo, $id)) {
            json_error('Trip not found.', 404);
        }

        $stmt = $pdo->prepare('DELETE FROM trips WHERE id = :id');
        $stmt->execute(['id' => $id]);
        json_success(['message' => 'Trip deleted.', 'id' => $id]);
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to save the trip.');
}
