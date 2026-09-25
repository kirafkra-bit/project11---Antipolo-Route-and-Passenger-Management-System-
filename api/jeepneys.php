<?php
/**
 * Jeepney CRUD.
 * Admin: full access.
 * Driver: can view assigned jeepney only.
 * Passenger: can view jeepneys (needed for route search pages later).
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = get_pdo();
$method = request_method();
$input = request_input();
$user = require_login();

if ($method !== 'GET') {
    require_role(['admin']);
    require_csrf($input);
}

function jeepney_select_sql(): string
{
    return 'SELECT j.id, j.plate_number, j.jeepney_number, j.drivers_id, j.route_id,
                   d.full_name AS driver_name, d.status AS driver_status,
                   r.route_name, r.destinations, r.distance, r.fare
            FROM jeepney j
            INNER JOIN drivers d ON d.id = j.drivers_id
            INNER JOIN routes r ON r.id = j.route_id';
}

function fetch_jeepney(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(jeepney_select_sql() . ' WHERE j.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

try {
    if ($method === 'GET') {
        if ($user['role'] === 'driver') {
            if (empty($user['driver_id'])) {
                json_error('Driver profile was not found for this account.', 404);
            }
            $stmt = $pdo->prepare(jeepney_select_sql() . ' WHERE j.drivers_id = :id');
            $stmt->execute(['id' => (int) $user['driver_id']]);
            json_success($stmt->fetchAll());
        }

        $id = query_param('id');
        if ($id !== null && $id !== '') {
            require_role(['admin', 'passenger']);
            $row = fetch_jeepney($pdo, to_int($id, 'id'));
            if (!$row) {
                json_error('Jeepney not found.', 404);
            }
            json_success($row);
        }

        require_role(['admin', 'passenger']);

        $search = trim_string((string) query_param('q', ''));
        $routeId = query_param('route_id');
        $sql = jeepney_select_sql() . ' WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (j.plate_number LIKE :q OR d.full_name LIKE :q2 OR r.route_name LIKE :q3)';
            $like = like_search($search);
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if ($routeId !== null && $routeId !== '') {
            $sql .= ' AND j.route_id = :route_id';
            $params['route_id'] = to_int($routeId, 'route_id');
        }

        $sql .= ' ORDER BY j.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_success($stmt->fetchAll());
    }

    if ($method === 'POST') {
        require_fields($input, ['plate_number', 'jeepney_number', 'drivers_id', 'route_id']);
        $driversId = to_int($input['drivers_id'], 'drivers_id');
        $routeId = to_int($input['route_id'], 'route_id');

        if (!record_exists($pdo, 'drivers', 'id', $driversId)) {
            json_error('Assigned driver was not found.', 422);
        }
        if (!record_exists($pdo, 'routes', 'id', $routeId)) {
            json_error('Assigned route was not found.', 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO jeepney (plate_number, jeepney_number, drivers_id, route_id)
             VALUES (:plate_number, :jeepney_number, :drivers_id, :route_id)'
        );
        $stmt->execute([
            'plate_number'   => strtoupper(trim_string($input['plate_number'])),
            'jeepney_number' => to_int($input['jeepney_number'], 'jeepney_number'),
            'drivers_id'     => $driversId,
            'route_id'       => $routeId,
        ]);

        json_success(fetch_jeepney($pdo, (int) $pdo->lastInsertId()), 201);
    }

    if ($method === 'PUT') {
        require_fields($input, ['id', 'plate_number', 'jeepney_number', 'drivers_id', 'route_id']);
        $id = to_int($input['id'], 'id');
        if (!fetch_jeepney($pdo, $id)) {
            json_error('Jeepney not found.', 404);
        }

        $driversId = to_int($input['drivers_id'], 'drivers_id');
        $routeId = to_int($input['route_id'], 'route_id');

        if (!record_exists($pdo, 'drivers', 'id', $driversId)) {
            json_error('Assigned driver was not found.', 422);
        }
        if (!record_exists($pdo, 'routes', 'id', $routeId)) {
            json_error('Assigned route was not found.', 422);
        }

        $stmt = $pdo->prepare(
            'UPDATE jeepney
             SET plate_number = :plate_number,
                 jeepney_number = :jeepney_number,
                 drivers_id = :drivers_id,
                 route_id = :route_id
             WHERE id = :id'
        );
        $stmt->execute([
            'plate_number'   => strtoupper(trim_string($input['plate_number'])),
            'jeepney_number' => to_int($input['jeepney_number'], 'jeepney_number'),
            'drivers_id'     => $driversId,
            'route_id'       => $routeId,
            'id'             => $id,
        ]);

        json_success(fetch_jeepney($pdo, $id));
    }

    if ($method === 'DELETE') {
        require_fields($input, ['id']);
        $id = to_int($input['id'], 'id');
        if (!fetch_jeepney($pdo, $id)) {
            json_error('Jeepney not found.', 404);
        }

        $tripCount = count_where($pdo, 'SELECT COUNT(*) FROM trips WHERE jeepney_id = :id', ['id' => $id]);
        if ($tripCount > 0) {
            json_error('Unable to delete this jeepney because it still has trip records.', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM jeepney WHERE id = :id');
        $stmt->execute(['id' => $id]);
        json_success(['message' => 'Jeepney deleted.', 'id' => $id]);
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to save the jeepney.');
}
