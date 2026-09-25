<?php
/**
 * Route CRUD.
 * Logged-in users can read routes.
 * Only admin can create, update, or delete.
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

function route_select_sql(): string
{
    return 'SELECT id, route_name, destinations, distance, fare, latitude, longitude, created_at FROM routes';
}

function fetch_route(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(route_select_sql() . ' WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function optional_decimal($value, string $field): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        json_error($field . ' must be a number.', 422);
    }
    return (string) $value;
}

function route_payload(array $row): array
{
    $distance = $row['distance'] !== null ? (int) $row['distance'] : null;
    $payload = $row;
    $payload['stored_fare'] = isset($row['fare']) ? (int) $row['fare'] : 0;
    $payload['estimated_fare_regular_traditional'] = null;

    if ($distance !== null && $distance >= 0) {
        try {
            $payload['estimated_fare_regular_traditional'] = calculate_fare(
                'traditional',
                'regular',
                $distance
            );
        } catch (InvalidArgumentException $e) {
            $payload['estimated_fare_regular_traditional'] = null;
        }
    }

    return $payload;
}

try {
    if ($method === 'GET') {
        $id = query_param('id');
        if ($id !== null && $id !== '') {
            $row = fetch_route($pdo, to_int($id, 'id'));
            if (!$row) {
                json_error('Route not found.', 404);
            }
            json_success(route_payload($row));
        }

        $search = trim_string((string) query_param('q', ''));
        $sql = route_select_sql() . ' WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (route_name LIKE :q OR destinations LIKE :q2)';
            $like = like_search($search);
            $params['q'] = $like;
            $params['q2'] = $like;
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = route_payload($row);
        }
        json_success($rows);
    }

    if ($method === 'POST') {
        require_fields($input, ['route_name']);
        $distance = isset($input['distance']) && $input['distance'] !== ''
            ? to_non_negative_int($input['distance'], 'distance')
            : null;
        $fare = isset($input['fare']) && $input['fare'] !== ''
            ? to_non_negative_int($input['fare'], 'fare')
            : 0;

        $stmt = $pdo->prepare(
            'INSERT INTO routes (route_name, destinations, distance, fare, latitude, longitude)
             VALUES (:route_name, :destinations, :distance, :fare, :latitude, :longitude)'
        );
        $stmt->execute([
            'route_name'   => trim_string($input['route_name']),
            'destinations' => trim_string($input['destinations'] ?? '') ?: null,
            'distance'     => $distance,
            'fare'         => $fare,
            'latitude'     => optional_decimal($input['latitude'] ?? null, 'latitude'),
            'longitude'    => optional_decimal($input['longitude'] ?? null, 'longitude'),
        ]);

        json_success(route_payload(fetch_route($pdo, (int) $pdo->lastInsertId())), 201);
    }

    if ($method === 'PUT') {
        require_fields($input, ['id', 'route_name']);
        $id = to_int($input['id'], 'id');
        if (!fetch_route($pdo, $id)) {
            json_error('Route not found.', 404);
        }

        $distance = isset($input['distance']) && $input['distance'] !== ''
            ? to_non_negative_int($input['distance'], 'distance')
            : null;
        $fare = isset($input['fare']) && $input['fare'] !== ''
            ? to_non_negative_int($input['fare'], 'fare')
            : 0;

        $stmt = $pdo->prepare(
            'UPDATE routes
             SET route_name = :route_name,
                 destinations = :destinations,
                 distance = :distance,
                 fare = :fare,
                 latitude = :latitude,
                 longitude = :longitude
             WHERE id = :id'
        );
        $stmt->execute([
            'route_name'   => trim_string($input['route_name']),
            'destinations' => trim_string($input['destinations'] ?? '') ?: null,
            'distance'     => $distance,
            'fare'         => $fare,
            'latitude'     => optional_decimal($input['latitude'] ?? null, 'latitude'),
            'longitude'    => optional_decimal($input['longitude'] ?? null, 'longitude'),
            'id'           => $id,
        ]);

        json_success(route_payload(fetch_route($pdo, $id)));
    }

    if ($method === 'DELETE') {
        require_fields($input, ['id']);
        $id = to_int($input['id'], 'id');
        if (!fetch_route($pdo, $id)) {
            json_error('Route not found.', 404);
        }

        $jeepCount = count_where($pdo, 'SELECT COUNT(*) FROM jeepney WHERE route_id = :id', ['id' => $id]);
        if ($jeepCount > 0) {
            json_error('Unable to delete this route because a jeepney is still assigned to it.', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM routes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        json_success(['message' => 'Route deleted.', 'id' => $id]);
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to save the route.');
}
