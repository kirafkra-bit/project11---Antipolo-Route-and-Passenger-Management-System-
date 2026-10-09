<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_role(['driver']);
if (empty($user['driver_id'])) {
    json_error('Driver profile was not found for this account.', 404);
}

$pdo = get_pdo();
$driverId = (int) $user['driver_id'];
$method = request_method();

function require_approved_driver(PDO $pdo, int $driverId, bool $lock = false): array
{
    $stmt = $pdo->prepare(
        'SELECT id, route_id, route_assignment_status
         FROM drivers
         WHERE id = :id' . ($lock ? ' FOR UPDATE' : '')
    );
    $stmt->execute(['id' => $driverId]);
    $driver = $stmt->fetch();
    if (!$driver) {
        json_error('Driver profile was not found for this account.', 404);
    }
    if ($driver['route_assignment_status'] !== 'approved' || empty($driver['route_id'])) {
        json_error('Waiting for route assignment. Trip features are unavailable until an administrator approves your route.', 403);
    }
    return $driver;
}

function driver_trip_select_sql(): string
{
    return 'SELECT t.id, t.drivers_id, t.jeepney_id, t.vehicle_type, t.route_id, t.pickup, t.drop_off,
                   t.recorded_at, t.status, t.starting_odometer, t.number_of_passengers,
                   t.boarded_count, t.dropped_off_count, t.confirmation_notes,
                   (SELECT COUNT(*) FROM trip_passenger_fares pf
                    WHERE pf.trip_id = t.id AND pf.status = \'on_board\') AS on_board_passenger_count,
                   j.plate_number, j.jeepney_number, r.route_name, r.destinations
            FROM trips t
            INNER JOIN jeepney j ON j.id = t.jeepney_id
            LEFT JOIN routes r ON r.id = COALESCE(t.route_id, j.route_id)';
}

function fetch_driver_trip(PDO $pdo, int $driverId, int $tripId, bool $lock = false): ?array
{
    $stmt = $pdo->prepare(
        driver_trip_select_sql() . ' WHERE t.drivers_id = :driver_id AND t.id = :trip_id' . ($lock ? ' FOR UPDATE' : '')
    );
    $stmt->execute(['driver_id' => $driverId, 'trip_id' => $tripId]);
    $trip = $stmt->fetch();
    return $trip ?: null;
}

if ($method === 'GET') {
    $driver = require_approved_driver($pdo, $driverId);
    $view = (string) query_param('view', 'history');

    if ($view === 'options') {
        $stmt = $pdo->prepare(
            'SELECT j.id, j.plate_number, j.jeepney_number
             FROM jeepney j
             WHERE j.drivers_id = :driver_id AND j.route_id = :route_id
             ORDER BY j.plate_number ASC'
        );
        $stmt->execute(['driver_id' => $driverId, 'route_id' => $driver['route_id']]);
        $routeStmt = $pdo->prepare('SELECT id, route_name, destinations FROM routes WHERE id = :id LIMIT 1');
        $routeStmt->execute(['id' => $driver['route_id']]);
        json_success(['route' => $routeStmt->fetch(), 'jeepneys' => $stmt->fetchAll()]);
    }

    if ($view === 'workflow') {
        $activeStmt = $pdo->prepare(
            driver_trip_select_sql() . " WHERE t.drivers_id = :driver_id AND t.status = 'In progress'
             ORDER BY t.id DESC LIMIT 1"
        );
        $activeStmt->execute(['driver_id' => $driverId]);
        $active = $activeStmt->fetch() ?: null;

        $pendingStmt = $pdo->prepare(
            driver_trip_select_sql() . " WHERE t.drivers_id = :driver_id AND t.status = 'Pending'
             ORDER BY t.id DESC"
        );
        $pendingStmt->execute(['driver_id' => $driverId]);
        $pendingTrips = $pendingStmt->fetchAll();
        $canFinishActive = $active !== null
            && (int) $active['boarded_count'] > 0
            && (int) $active['on_board_passenger_count'] === 0;
        json_success([
            'active' => $active,
            'pending_trips' => $pendingTrips,
            'can_start' => $active === null && count($pendingTrips) === 0,
            'can_passengers' => $active !== null || count($pendingTrips) > 0,
            'can_finish' => $canFinishActive || count($pendingTrips) > 0,
            'can_finish_active' => $canFinishActive,
            'can_summary' => count($pendingTrips) > 0,
            'can_confirm' => count($pendingTrips) > 0,
        ]);
    }

    if ($view === 'current') {
        $stmt = $pdo->prepare(
            driver_trip_select_sql() . " WHERE t.drivers_id = :driver_id AND t.status = 'In progress'
             ORDER BY t.id DESC LIMIT 1"
        );
        $stmt->execute(['driver_id' => $driverId]);
        json_success($stmt->fetch() ?: null);
    }

    if ($view === 'pending') {
        $stmt = $pdo->prepare(
            driver_trip_select_sql() . " WHERE t.drivers_id = :driver_id AND t.status = 'Pending'
             ORDER BY t.id DESC"
        );
        $stmt->execute(['driver_id' => $driverId]);
        json_success($stmt->fetchAll());
    }

    if ($view === 'summary') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total_trips,
                    COALESCE(SUM(boarded_count), SUM(number_of_passengers), 0) AS total_passengers,
                    COALESCE(SUM(status = 'Pending'), 0) AS awaiting_confirmation
             FROM trips WHERE drivers_id = :driver_id"
        );
        $stmt->execute(['driver_id' => $driverId]);
        json_success($stmt->fetch());
    }

    if ($view === 'history') {
        $stmt = $pdo->prepare(
            driver_trip_select_sql() . ' WHERE t.drivers_id = :driver_id ORDER BY t.id DESC'
        );
        $stmt->execute(['driver_id' => $driverId]);
        json_success($stmt->fetchAll());
    }

    if ($view === 'passengers') {
        $activeStmt = $pdo->prepare(
            driver_trip_select_sql() . " WHERE t.drivers_id = :driver_id AND t.status = 'In progress'
             ORDER BY t.id DESC LIMIT 1"
        );
        $activeStmt->execute(['driver_id' => $driverId]);
        $activeTrip = $activeStmt->fetch() ?: null;
        if (!$activeTrip) {
            json_success(['trip' => null, 'passengers' => []]);
        }
        $passengersStmt = $pdo->prepare(
            "SELECT f.id AS fare_id, p.name AS passenger_name, f.passenger_type,
                    f.origin_name, f.destination_name, f.distance_km, f.fare, f.boarded_at
             FROM trip_passenger_fares f
             INNER JOIN passengers p ON p.passengers_id = f.passenger_id
             WHERE f.trip_id = :trip_id AND f.driver_id = :driver_id AND f.status = 'on_board'
             ORDER BY f.boarded_at ASC, f.id ASC"
        );
        $passengersStmt->execute(['trip_id' => (int) $activeTrip['id'], 'driver_id' => $driverId]);
        json_success(['trip' => $activeTrip, 'passengers' => $passengersStmt->fetchAll()]);
    }

    if ($view === 'trip_fares') {
        $tripId = to_int(query_param('trip_id'), 'trip_id', false);
        $trip = fetch_driver_trip($pdo, $driverId, $tripId);
        if (!$trip) {
            json_error('Trip was not found for this driver.', 404);
        }
        $stmt = $pdo->prepare(
            'SELECT f.id, p.name AS passenger_name, f.vehicle_type, f.passenger_type,
                    f.origin_name, f.destination_name, f.distance_km, f.fare, f.status, f.boarded_at
             FROM trip_passenger_fares f
             LEFT JOIN passengers p ON p.passengers_id = f.passenger_id
             WHERE f.trip_id = :trip_id AND f.driver_id = :driver_id
             ORDER BY f.boarded_at ASC, f.id ASC'
        );
        $stmt->execute(['trip_id' => $tripId, 'driver_id' => $driverId]);
        json_success($stmt->fetchAll());
    }

    json_error('Unknown trip view.', 422);
}

if (!in_array($method, ['POST', 'PATCH'], true)) {
    json_error('Method not allowed.', 405);
}

$input = request_input();
require_csrf($input);
$action = trim_string($input['action'] ?? '');

try {
    $pdo->beginTransaction();
    $driver = require_approved_driver($pdo, $driverId, true);

    if ($method === 'POST' && $action === 'start') {
        require_fields($input, ['jeepney_id', 'starting_odometer', 'vehicle_type']);
        $jeepneyId = to_int($input['jeepney_id'], 'jeepney_id', false);
        $odometer = to_float($input['starting_odometer'], 'starting_odometer');
        $vehicleType = strtolower(trim_string($input['vehicle_type']));
        if (!in_array($vehicleType, ['traditional', 'modern'], true)) {
            $pdo->rollBack();
            json_error('Choose Traditional Jeepney or Modern Jeepney for this trip.', 422);
        }
        if ($odometer < 0) {
            $pdo->rollBack();
            json_error('Starting odometer cannot be negative.', 422);
        }

        $unitStmt = $pdo->prepare(
            'SELECT id FROM jeepney WHERE id = :id AND drivers_id = :driver_id AND route_id = :route_id LIMIT 1'
        );
        $unitStmt->execute([
            'id' => $jeepneyId,
            'driver_id' => $driverId,
            'route_id' => $driver['route_id'],
        ]);
        if (!$unitStmt->fetch()) {
            $pdo->rollBack();
            json_error('Select a vehicle assigned to both your driver account and approved route.', 422);
        }

        $activeStmt = $pdo->prepare("SELECT id FROM trips WHERE drivers_id = :id AND status = 'In progress' LIMIT 1 FOR UPDATE");
        $activeStmt->execute(['id' => $driverId]);
        if ($activeStmt->fetch()) {
            $pdo->rollBack();
            json_error('Finish your in-progress trip before starting another.', 409);
        }

        $pendingStmt = $pdo->prepare("SELECT id FROM trips WHERE drivers_id = :id AND status = 'Pending' LIMIT 1 FOR UPDATE");
        $pendingStmt->execute(['id' => $driverId]);
        if ($pendingStmt->fetch()) {
            $pdo->rollBack();
            json_error('Confirm all pending trips before starting a new trip.', 409);
        }

        $routeStmt = $pdo->prepare('SELECT route_name, destinations FROM routes WHERE id = :id LIMIT 1');
        $routeStmt->execute(['id' => $driver['route_id']]);
        $route = $routeStmt->fetch();
        if (!$route) {
            $pdo->rollBack();
            json_error('Your assigned route is no longer available. Contact an administrator.', 409);
        }
        $stmt = $pdo->prepare(
            "INSERT INTO trips (pickup, drop_off, number_of_passengers, passengers_id, jeepney_id, vehicle_type,
                                route_id, drivers_id, recorded_at, status, starting_odometer)
             VALUES (:pickup, :drop_off, 0, NULL, :jeepney_id, :vehicle_type, :route_id, :driver_id,
                     CURRENT_TIMESTAMP, 'In progress', :odometer)"
        );
        $stmt->execute([
            'pickup' => $route['route_name'],
            'drop_off' => $route['destinations'],
            'jeepney_id' => $jeepneyId,
            'vehicle_type' => $vehicleType,
            'route_id' => (int) $driver['route_id'],
            'driver_id' => $driverId,
            'odometer' => $odometer,
        ]);
        $trip = fetch_driver_trip($pdo, $driverId, (int) $pdo->lastInsertId());
        $pdo->commit();
        json_success($trip, 201);
    }

    if ($method === 'PATCH' && $action === 'confirm') {
        require_fields($input, ['trip_id']);
        $tripId = to_int($input['trip_id'], 'trip_id', false);
        $notes = trim_string($input['notes'] ?? '');
        if (strlen($notes) > 4000) {
            $pdo->rollBack();
            json_error('Confirmation notes cannot exceed 4,000 characters.', 422);
        }
        $trip = fetch_driver_trip($pdo, $driverId, $tripId, true);
        if (!$trip) {
            $pdo->rollBack();
            json_error('Trip was not found for this driver.', 404);
        }
        if ($trip['status'] !== 'Pending') {
            $pdo->rollBack();
            json_error('Only pending trips can be confirmed.', 409);
        }
        $stmt = $pdo->prepare(
            "UPDATE trips SET status = 'Confirmed', confirmation_notes = :notes
             WHERE id = :id AND drivers_id = :driver_id AND status = 'Pending'"
        );
        $stmt->execute(['notes' => $notes !== '' ? $notes : null, 'id' => $tripId, 'driver_id' => $driverId]);
        $confirmed = fetch_driver_trip($pdo, $driverId, $tripId);
        $pdo->commit();
        json_success($confirmed);
    }

    if ($method === 'PATCH' && $action === 'finish') {
        $activeStmt = $pdo->prepare(
            driver_trip_select_sql() . " WHERE t.drivers_id = :driver_id AND t.status = 'In progress'
             ORDER BY t.id DESC LIMIT 1 FOR UPDATE"
        );
        $activeStmt->execute(['driver_id' => $driverId]);
        $trip = $activeStmt->fetch();
        if (!$trip) {
            $pdo->rollBack();
            json_error('There is no trip in progress.', 409);
        }
        if ((int) $trip['boarded_count'] < 1) {
            $pdo->rollBack();
            json_error('At least one passenger must join before finishing this trip.', 422);
        }
        $onBoardStmt = $pdo->prepare(
            "SELECT id FROM trip_passenger_fares
             WHERE trip_id = :trip_id AND status = 'on_board' LIMIT 1 FOR UPDATE"
        );
        $onBoardStmt->execute(['trip_id' => (int) $trip['id']]);
        if ($onBoardStmt->fetch()) {
            $pdo->rollBack();
            json_error('Wait for all passengers to mark themselves dropped off before finishing this trip.', 409);
        }
        $stmt = $pdo->prepare(
            "UPDATE trips SET status = 'Pending'
             WHERE id = :id AND drivers_id = :driver_id AND status = 'In progress'"
        );
        $stmt->execute(['id' => $trip['id'], 'driver_id' => $driverId]);
        $updated = fetch_driver_trip($pdo, $driverId, (int) $trip['id']);
        $pdo->commit();
        json_success($updated);
    }

    $pdo->rollBack();
    json_error('Unsupported trip action.', 422);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_pdo_exception($error, 'Unable to update the trip.');
}
