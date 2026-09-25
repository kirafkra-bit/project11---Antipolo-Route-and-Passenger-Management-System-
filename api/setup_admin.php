<?php
/**
 * One-time helper: create the first admin account if none exists.
 *
 * POST JSON:
 * {
 *   "username": "admin",
 *   "password": "yourpassword"
 * }
 *
 * This endpoint stops working after an admin already exists.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Use POST to create the first admin.', 405);
}

$pdo = get_pdo();
$adminCount = count_where($pdo, "SELECT COUNT(*) FROM users WHERE role = 'admin'");
if ($adminCount > 0) {
    json_error('An admin account already exists. Log in instead.', 409);
}

$input = request_input();
require_fields($input, ['username', 'password']);

try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password, role) VALUES (:username, :password, :role)'
    );
    $stmt->execute([
        'username' => trim_string($input['username']),
        'password' => hash_password((string) $input['password']),
        'role'     => 'admin',
    ]);

    json_success([
        'message' => 'First admin account created. You can now log in.',
        'user'    => [
            'id'       => (int) $pdo->lastInsertId(),
            'username' => trim_string($input['username']),
            'role'     => 'admin',
        ],
    ], 201);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to create the admin account.');
}
