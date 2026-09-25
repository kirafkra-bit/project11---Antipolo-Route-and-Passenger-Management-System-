<?php
/**
 * Admin user management.
 *
 * GET    list / one user (never returns password hashes)
 * POST   create user (and passenger/driver profile when needed)
 * PUT    update user
 * DELETE delete user (cascades passenger/driver profiles)
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = get_pdo();
$method = request_method();
$input = request_input();
$user = require_role(['admin']);

if ($method !== 'GET') {
    require_csrf($input);
}

function user_select_sql(): string
{
    return 'SELECT id, username, role, created_at FROM users';
}

function fetch_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(user_select_sql() . ' WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

try {
    if ($method === 'GET') {
        $id = query_param('id');
        if ($id !== null && $id !== '') {
            $row = fetch_user($pdo, to_int($id, 'id'));
            if (!$row) {
                json_error('User not found.', 404);
            }
            json_success($row);
        }

        $search = trim_string((string) query_param('q', ''));
        $role = trim_string((string) query_param('role', ''));
        $sql = user_select_sql() . ' WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND username LIKE :q';
            $params['q'] = like_search($search);
        }
        if ($role !== '') {
            if (!in_array($role, ['admin', 'driver', 'passenger'], true)) {
                json_error('Role filter must be admin, driver, or passenger.', 422);
            }
            $sql .= ' AND role = :role';
            $params['role'] = $role;
        }

        $sql .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_success($stmt->fetchAll());
    }

    if ($method === 'POST') {
        require_fields($input, ['username', 'password', 'role']);
        $username = trim_string($input['username']);
        $role = trim_string($input['role']);
        $passwordHash = hash_password((string) $input['password']);

        if (!in_array($role, ['admin', 'driver', 'passenger'], true)) {
            json_error('Role must be admin, driver, or passenger.', 422);
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, role) VALUES (:username, :password, :role)'
        );
        $stmt->execute([
            'username' => $username,
            'password' => $passwordHash,
            'role'     => $role,
        ]);
        $newId = (int) $pdo->lastInsertId();

        if ($role === 'passenger') {
            require_fields($input, ['name']);
            $stmt = $pdo->prepare(
                'INSERT INTO passengers (name, contact_number, address, users_id)
                 VALUES (:name, :contact_number, :address, :users_id)'
            );
            $stmt->execute([
                'name'           => trim_string($input['name']),
                'contact_number' => trim_string($input['contact_number'] ?? '') ?: null,
                'address'        => trim_string($input['address'] ?? '') ?: null,
                'users_id'       => $newId,
            ]);
        }

        if ($role === 'driver') {
            require_fields($input, ['full_name', 'license_number']);
            $stmt = $pdo->prepare(
                'INSERT INTO drivers (full_name, contact_number, license_number, status, users_id)
                 VALUES (:full_name, :contact_number, :license_number, :status, :users_id)'
            );
            $stmt->execute([
                'full_name'      => trim_string($input['full_name']),
                'contact_number' => trim_string($input['contact_number'] ?? '') ?: null,
                'license_number' => trim_string($input['license_number']),
                'status'         => trim_string($input['status'] ?? 'active') ?: 'active',
                'users_id'       => $newId,
            ]);
        }

        $pdo->commit();
        json_success(fetch_user($pdo, $newId), 201);
    }

    if ($method === 'PUT') {
        require_fields($input, ['id']);
        $id = to_int($input['id'], 'id');
        $existing = fetch_user($pdo, $id);
        if (!$existing) {
            json_error('User not found.', 404);
        }

        $username = isset($input['username']) ? trim_string($input['username']) : $existing['username'];
        $role = isset($input['role']) ? trim_string($input['role']) : $existing['role'];

        if ($username === '') {
            json_error('Username cannot be empty.', 422);
        }
        if (!in_array($role, ['admin', 'driver', 'passenger'], true)) {
            json_error('Role must be admin, driver, or passenger.', 422);
        }

        if (!empty($input['password'])) {
            $stmt = $pdo->prepare(
                'UPDATE users SET username = :username, role = :role, password = :password WHERE id = :id'
            );
            $stmt->execute([
                'username' => $username,
                'role'     => $role,
                'password' => hash_password((string) $input['password']),
                'id'       => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET username = :username, role = :role WHERE id = :id');
            $stmt->execute([
                'username' => $username,
                'role'     => $role,
                'id'       => $id,
            ]);
        }

        json_success(fetch_user($pdo, $id));
    }

    if ($method === 'DELETE') {
        require_fields($input, ['id']);
        $id = to_int($input['id'], 'id');

        if ($id === (int) $user['id']) {
            json_error('You cannot delete your own admin account while logged in.', 422);
        }

        $existing = fetch_user($pdo, $id);
        if (!$existing) {
            json_error('User not found.', 404);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        json_success(['message' => 'User deleted.', 'id' => $id]);
    }

    json_error('Method not allowed.', 405);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $fallback = 'Unable to save the user.';
    if (request_method() === 'DELETE') {
        $fallback = 'Unable to delete this user because related jeepney or trip records still exist.';
    }
    handle_pdo_exception($e, $fallback);
}
