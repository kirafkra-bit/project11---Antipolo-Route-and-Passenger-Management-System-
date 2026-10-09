<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Use POST to update account settings.', 405);
}

$user = require_login();
$input = request_input();
require_csrf($input);
require_fields($input, ['current_password', 'new_password']);

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $user['id']]);
    $storedHash = $stmt->fetchColumn();

    if (!is_string($storedHash) || !password_verify((string) $input['current_password'], $storedHash)) {
        json_error('Current password is incorrect.', 422);
    }

    $newHash = hash_password((string) $input['new_password']);
    $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $stmt->execute(['password' => $newHash, 'id' => $user['id']]);

    json_success(['message' => 'Password updated successfully.']);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to update your password right now.');
}
