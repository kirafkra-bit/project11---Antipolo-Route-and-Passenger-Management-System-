<?php
/**
 * Session authentication and role checks.
 */

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'           => (int) $_SESSION['user_id'],
        'username'     => $_SESSION['username'] ?? '',
        'role'         => $_SESSION['role'] ?? '',
        'passenger_id' => isset($_SESSION['passenger_id']) ? (int) $_SESSION['passenger_id'] : null,
        'driver_id'    => isset($_SESSION['driver_id']) ? (int) $_SESSION['driver_id'] : null,
    ];
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        json_error('Please log in first.', 401);
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        json_error('You are not allowed to access this resource.', 403);
    }
    return $user;
}

function load_role_profile(PDO $pdo, int $userId, string $role): array
{
    $passengerId = null;
    $driverId = null;

    if ($role === 'passenger') {
        $stmt = $pdo->prepare('SELECT passengers_id FROM passengers WHERE users_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $passengerId = $stmt->fetchColumn() ?: null;
    }

    if ($role === 'driver') {
        $stmt = $pdo->prepare('SELECT id FROM drivers WHERE users_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $driverId = $stmt->fetchColumn() ?: null;
    }

    return [
        'passenger_id' => $passengerId !== null ? (int) $passengerId : null,
        'driver_id'    => $driverId !== null ? (int) $driverId : null,
    ];
}

function login_user(array $userRow, array $profile): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $userRow['id'];
    $_SESSION['username'] = $userRow['username'];
    $_SESSION['role'] = $userRow['role'];
    $_SESSION['passenger_id'] = $profile['passenger_id'];
    $_SESSION['driver_id'] = $profile['driver_id'];

    csrf_token();
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
    }

    session_destroy();
}

function authenticate(PDO $pdo, string $username, string $password): array
{
    $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_error('Invalid login credentials.', 401);
    }

    $profile = load_role_profile($pdo, (int) $user['id'], $user['role']);
    login_user($user, $profile);

    return session_user_payload();
}

function session_user_payload(): array
{
    $user = current_user();
    if ($user === null) {
        json_error('Please log in first.', 401);
    }

    return [
        'user'       => $user,
        'csrf_token' => csrf_token(),
        'dashboard'  => dashboard_path_for_role($user['role']),
    ];
}

function dashboard_path_for_role(string $role): string
{
    if ($role === 'admin') {
        return '/admin/dashboard.php';
    }
    if ($role === 'driver') {
        return '/driver/dashboard.php';
    }
    return '/passenger/dashboard.php';
}

function hash_password(string $password): string
{
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        json_error('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.', 422);
    }

    return password_hash($password, PASSWORD_DEFAULT);
}
