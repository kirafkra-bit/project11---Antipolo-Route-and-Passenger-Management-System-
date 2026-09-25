<?php
/**
 * Driver CRUD.
 * Admin: full access including status changes.
 * Driver: can view own profile only.
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

function driver_select_sql(): string
{
    return 'SELECT d.id, d.full_name, d.contact_number, d.license_number, d.status,
                   d.created_at, d.users_id, u.username
            FROM drivers d
            INNER JOIN users u ON u.id = d.users_id';
}

function fetch_driver(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(driver_select_sql() . ' WHERE d.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function normalize_driver_status($status): string
{
    $value = strtolower(trim_string((string) $status));
    if ($value === '') {
        return 'active';
    }
    if (!in_array($value, ['active', 'inactive'], true)) {
        json_error('Driver status must be active or inactive.', 422);
    }
    return $value;
}

try {
    if ($method === 'GET') {
        if ($user['role'] === 'driver') {
            if (empty($user['driver_id'])) {
                json_error('Driver profile was not found for this account.', 404);
            }
            json_success(fetch_driver($pdo, (int) $user['driver_id']));
        }

        require_role(['admin']);

        $id = query_param('id');
        if ($id !== null && $id !== '') {
            $row = fetch_driver($pdo, to_int($id, 'id'));
            if (!$row) {
                json_error('Driver not found.', 404);
            }
            json_success($row);
        }

        $search = trim_string((string) query_param('q', ''));
        $status = trim_string((string) query_param('status', ''));
        $sql = driver_select_sql() . ' WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (d.full_name LIKE :q OR d.license_number LIKE :q2 OR d.contact_number LIKE :q3 OR u.username LIKE :q4)';
            $like = like_search($search);
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }
        if ($status !== '') {
            $sql .= ' AND d.status = :status';
            $params['status'] = normalize_driver_status($status);
        }

        $sql .= ' ORDER BY d.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_success($stmt->fetchAll());
    }

    if ($method === 'POST') {
        require_fields($input, ['full_name', 'license_number', 'username', 'password']);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, role) VALUES (:username, :password, :role)'
        );
        $stmt->execute([
            'username' => trim_string($input['username']),
            'password' => hash_password((string) $input['password']),
            'role'     => 'driver',
        ]);
        $userId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO drivers (full_name, contact_number, license_number, status, users_id)
             VALUES (:full_name, :contact_number, :license_number, :status, :users_id)'
        );
        $stmt->execute([
            'full_name'      => trim_string($input['full_name']),
            'contact_number' => trim_string($input['contact_number'] ?? '') ?: null,
            'license_number' => trim_string($input['license_number']),
            'status'         => normalize_driver_status($input['status'] ?? 'active'),
            'users_id'       => $userId,
        ]);
        $driverId = (int) $pdo->lastInsertId();
        $pdo->commit();

        json_success(fetch_driver($pdo, $driverId), 201);
    }

    if ($method === 'PUT') {
        require_fields($input, ['id', 'full_name', 'license_number']);
        $id = to_int($input['id'], 'id');
        $existing = fetch_driver($pdo, $id);
        if (!$existing) {
            json_error('Driver not found.', 404);
        }

        $stmt = $pdo->prepare(
            'UPDATE drivers
             SET full_name = :full_name,
                 contact_number = :contact_number,
                 license_number = :license_number,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'full_name'      => trim_string($input['full_name']),
            'contact_number' => trim_string($input['contact_number'] ?? '') ?: null,
            'license_number' => trim_string($input['license_number']),
            'status'         => normalize_driver_status($input['status'] ?? $existing['status']),
            'id'             => $id,
        ]);

        json_success(fetch_driver($pdo, $id));
    }

    if ($method === 'DELETE') {
        require_fields($input, ['id']);
        $id = to_int($input['id'], 'id');
        $existing = fetch_driver($pdo, $id);
        if (!$existing) {
            json_error('Driver not found.', 404);
        }

        $jeepCount = count_where($pdo, 'SELECT COUNT(*) FROM jeepney WHERE drivers_id = :id', ['id' => $id]);
        if ($jeepCount > 0) {
            json_error('Unable to delete this driver because they are assigned to a jeepney.', 409);
        }

        $tripCount = count_where($pdo, 'SELECT COUNT(*) FROM trips WHERE drivers_id = :id', ['id' => $id]);
        if ($tripCount > 0) {
            json_error('Unable to delete this driver because they still have trip records.', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => (int) $existing['users_id']]);
        json_success(['message' => 'Driver deleted.', 'id' => $id]);
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_pdo_exception($e, 'Unable to save the driver.');
}
