<?php
/**
 * Passenger CRUD.
 * Admin: full access.
 * Passenger: can view own record only.
 * Driver: can list names/contacts when recording a trip.
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

function passenger_select_sql(): string
{
    return 'SELECT p.passengers_id, p.name, p.contact_number, p.address, p.created_at,
                   p.users_id, u.username, u.role
            FROM passengers p
            INNER JOIN users u ON u.id = p.users_id';
}

function fetch_passenger(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(passenger_select_sql() . ' WHERE p.passengers_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

try {
    if ($method === 'GET') {
        $id = query_param('id');

        if ($user['role'] === 'passenger') {
            if (empty($user['passenger_id'])) {
                json_error('Passenger profile was not found for this account.', 404);
            }
            $row = fetch_passenger($pdo, (int) $user['passenger_id']);
            json_success($row);
        }

        if ($user['role'] === 'driver') {
            $search = trim_string((string) query_param('q', ''));
            $sql = 'SELECT p.passengers_id, p.name, p.contact_number
                    FROM passengers p
                    WHERE 1=1';
            $params = [];
            if ($search !== '') {
                $sql .= ' AND (p.name LIKE :q OR p.contact_number LIKE :q2)';
                $like = like_search($search);
                $params['q'] = $like;
                $params['q2'] = $like;
            }
            $sql .= ' ORDER BY p.name ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_success($stmt->fetchAll());
        }

        require_role(['admin']);

        if ($id !== null && $id !== '') {
            $row = fetch_passenger($pdo, to_int($id, 'id'));
            if (!$row) {
                json_error('Passenger not found.', 404);
            }
            json_success($row);
        }

        $search = trim_string((string) query_param('q', ''));
        $sql = passenger_select_sql() . ' WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (p.name LIKE :q OR p.contact_number LIKE :q2 OR u.username LIKE :q3)';
            $like = like_search($search);
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        $sql .= ' ORDER BY p.passengers_id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_success($stmt->fetchAll());
    }

    if ($method === 'POST') {
        require_fields($input, ['name', 'username', 'password']);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, role) VALUES (:username, :password, :role)'
        );
        $stmt->execute([
            'username' => trim_string($input['username']),
            'password' => hash_password((string) $input['password']),
            'role'     => 'passenger',
        ]);
        $userId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO passengers (name, contact_number, address, users_id)
             VALUES (:name, :contact_number, :address, :users_id)'
        );
        $stmt->execute([
            'name'           => trim_string($input['name']),
            'contact_number' => trim_string($input['contact_number'] ?? '') ?: null,
            'address'        => trim_string($input['address'] ?? '') ?: null,
            'users_id'       => $userId,
        ]);
        $passengerId = (int) $pdo->lastInsertId();
        $pdo->commit();

        json_success(fetch_passenger($pdo, $passengerId), 201);
    }

    if ($method === 'PUT') {
        require_fields($input, ['passengers_id', 'name']);
        $id = to_int($input['passengers_id'], 'passengers_id');
        $existing = fetch_passenger($pdo, $id);
        if (!$existing) {
            json_error('Passenger not found.', 404);
        }

        $stmt = $pdo->prepare(
            'UPDATE passengers
             SET name = :name, contact_number = :contact_number, address = :address
             WHERE passengers_id = :id'
        );
        $stmt->execute([
            'name'           => trim_string($input['name']),
            'contact_number' => trim_string($input['contact_number'] ?? '') ?: null,
            'address'        => trim_string($input['address'] ?? '') ?: null,
            'id'             => $id,
        ]);

        json_success(fetch_passenger($pdo, $id));
    }

    if ($method === 'DELETE') {
        require_fields($input, ['passengers_id']);
        $id = to_int($input['passengers_id'], 'passengers_id');
        $existing = fetch_passenger($pdo, $id);
        if (!$existing) {
            json_error('Passenger not found.', 404);
        }

        $tripCount = count_where(
            $pdo,
            'SELECT COUNT(*) FROM trips WHERE passengers_id = :id',
            ['id' => $id]
        );
        if ($tripCount > 0) {
            json_error('Unable to delete this passenger because they still have trip records.', 409);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => (int) $existing['users_id']]);
        json_success(['message' => 'Passenger deleted.', 'passengers_id' => $id]);
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_pdo_exception($e, 'Unable to save the passenger.');
}
