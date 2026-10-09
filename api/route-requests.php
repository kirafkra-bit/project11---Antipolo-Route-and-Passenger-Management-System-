<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_role(['admin']);
$method = request_method();
$pdo = get_pdo();

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT d.id AS driver_id, d.full_name, d.contact_number, d.license_number,
                d.created_at, d.requested_route_id, requested.route_name AS requested_route_name,
                u.username
         FROM drivers d
         INNER JOIN users u ON u.id = d.users_id
         LEFT JOIN routes requested ON requested.id = d.requested_route_id
         WHERE d.route_assignment_status = 'pending'
         ORDER BY d.created_at ASC, d.id ASC"
    );
    json_success($stmt->fetchAll());
}

if ($method !== 'POST') {
    json_error('Method not allowed.', 405);
}

$input = request_input();
require_csrf($input);
require_fields($input, ['driver_id', 'action']);
$driverId = to_int($input['driver_id'], 'driver_id', false);
$action = trim_string($input['action']);

if (!in_array($action, ['approve', 'reject'], true)) {
    json_error('Action must be approve or reject.', 422);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT id, route_assignment_status FROM drivers WHERE id = :id FOR UPDATE'
    );
    $stmt->execute(['id' => $driverId]);
    $driver = $stmt->fetch();
    if (!$driver) {
        $pdo->rollBack();
        json_error('Driver was not found.', 404);
    }
    if ($driver['route_assignment_status'] !== 'pending') {
        $pdo->rollBack();
        json_error('This route request has already been reviewed.', 409);
    }

    if ($action === 'approve') {
        require_fields($input, ['route_id']);
        $routeId = to_int($input['route_id'], 'route_id', false);
        if (!record_exists($pdo, 'routes', 'id', $routeId)) {
            $pdo->rollBack();
            json_error('The selected route was not found.', 422);
        }
        $stmt = $pdo->prepare(
            "UPDATE drivers
             SET route_id = :route_id, route_assignment_status = 'approved', status = 'active'
             WHERE id = :id"
        );
        $stmt->execute(['route_id' => $routeId, 'id' => $driverId]);
        $message = 'Route approved and assigned.';
    } else {
        $stmt = $pdo->prepare(
            "UPDATE drivers
             SET route_assignment_status = 'rejected', status = 'inactive'
             WHERE id = :id"
        );
        $stmt->execute(['id' => $driverId]);
        $message = 'Route request rejected.';
    }

    $pdo->commit();
    json_success(['message' => $message]);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_pdo_exception($error, 'Unable to review the route request.');
}
