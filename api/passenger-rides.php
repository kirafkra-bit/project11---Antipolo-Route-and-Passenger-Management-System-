<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_BASE_PATH . '/includes/passenger_fares.php';

$user = require_role(['passenger']);
if (empty($user['passenger_id'])) {
    json_error('Passenger profile was not found for this account.', 404);
}

$pdo = get_pdo();
$passengerId = (int) $user['passenger_id'];
$method = request_method();

function passenger_active_trip_sql(): string
{
    return "SELECT t.id AS trip_id, d.id AS driver_id, t.vehicle_type, r.route_name, r.destinations,
                   j.plate_number, j.jeepney_number, d.full_name AS driver_name,
                   (SELECT COUNT(*) FROM trip_passenger_fares f
                    WHERE f.trip_id = t.id AND f.status = 'on_board') AS on_board_count
            FROM trips t
            INNER JOIN drivers d ON d.id = t.drivers_id
            INNER JOIN jeepney j ON j.id = t.jeepney_id AND j.drivers_id = d.id
            INNER JOIN routes r ON r.id = COALESCE(t.route_id, j.route_id)
            WHERE t.status = 'In progress'
              AND d.route_assignment_status = 'approved'
              AND d.route_id = r.id
              AND j.route_id = r.id";
}

if ($method === 'GET') {
    $view = (string) query_param('view', 'state');
    if ($view === 'locations') {
        json_success(tomtom_place_suggestions((string) query_param('q', '')));
    }
    if ($view !== 'state') {
        json_error('Unknown passenger ride view.', 422);
    }

    try {
        $activeTrips = $pdo->query(passenger_active_trip_sql() . ' ORDER BY t.recorded_at DESC, t.id DESC')->fetchAll();
        $currentStmt = $pdo->prepare(
            "SELECT f.id AS fare_id, f.trip_id, f.passenger_type, f.origin_name, f.destination_name,
                    f.distance_km, f.fare, f.boarded_at, t.vehicle_type, r.route_name,
                    j.plate_number, j.jeepney_number, d.full_name AS driver_name
             FROM trip_passenger_fares f
             INNER JOIN trips t ON t.id = f.trip_id AND t.status = 'In progress'
             INNER JOIN drivers d ON d.id = t.drivers_id
             INNER JOIN jeepney j ON j.id = t.jeepney_id
             LEFT JOIN routes r ON r.id = COALESCE(t.route_id, j.route_id)
             WHERE f.passenger_id = :passenger_id AND f.status = 'on_board'
             ORDER BY f.boarded_at DESC LIMIT 1"
        );
        $currentStmt->execute(['passenger_id' => $passengerId]);
        $historyStmt = $pdo->prepare(
            'SELECT f.id AS fare_id, f.trip_id, f.passenger_type, f.origin_name, f.destination_name,
                    f.distance_km, f.fare, f.status, f.boarded_at, f.dropped_off_at,
                    t.vehicle_type, t.status AS trip_status, r.route_name,
                    j.plate_number, j.jeepney_number, d.full_name AS driver_name
             FROM trip_passenger_fares f
             INNER JOIN trips t ON t.id = f.trip_id
             INNER JOIN drivers d ON d.id = t.drivers_id
             INNER JOIN jeepney j ON j.id = t.jeepney_id
             LEFT JOIN routes r ON r.id = COALESCE(t.route_id, j.route_id)
             WHERE f.passenger_id = :passenger_id
             ORDER BY f.boarded_at DESC, f.id DESC'
        );
        $historyStmt->execute(['passenger_id' => $passengerId]);
        $legacyStmt = $pdo->prepare(
            'SELECT t.id AS trip_id, t.pickup AS origin_name, t.drop_off AS destination_name,
                    t.recorded_at AS boarded_at, t.status AS trip_status, t.number_of_passengers,
                    r.route_name, j.plate_number, j.jeepney_number, d.full_name AS driver_name
             FROM trips t
             INNER JOIN drivers d ON d.id = t.drivers_id
             INNER JOIN jeepney j ON j.id = t.jeepney_id
             LEFT JOIN routes r ON r.id = COALESCE(t.route_id, j.route_id)
             WHERE t.passengers_id = :passenger_id
             ORDER BY t.recorded_at DESC, t.id DESC'
        );
        $legacyStmt->execute(['passenger_id' => $passengerId]);
        json_success([
            'active_trips' => $activeTrips,
            'current_ride' => $currentStmt->fetch() ?: null,
            'history' => $historyStmt->fetchAll(),
            'legacy_history' => $legacyStmt->fetchAll(),
        ]);
    } catch (PDOException $error) {
        handle_pdo_exception($error, 'Unable to load active rides.');
    }
}

if ($method !== 'POST') {
    json_error('Method not allowed.', 405);
}
$input = request_input();
require_csrf($input);
$action = trim_string($input['action'] ?? '');

try {
    if ($action === 'board') {
        require_fields($input, ['trip_id']);
        $tripId = to_int($input['trip_id'], 'trip_id', false);
        $tripStmt = $pdo->prepare(
            passenger_active_trip_sql() . ' AND t.id = :trip_id LIMIT 1'
        );
        $tripStmt->execute(['trip_id' => $tripId]);
        $trip = $tripStmt->fetch();
        if (!$trip) {
            json_error('That jeepney is no longer on an active trip. Refresh and choose an active trip.', 409);
        }
        $fare = calculate_passenger_trip_fare($input, (string) $trip['vehicle_type']);

        $pdo->beginTransaction();
        $passengerLock = $pdo->prepare('SELECT passengers_id FROM passengers WHERE passengers_id = :id FOR UPDATE');
        $passengerLock->execute(['id' => $passengerId]);
        if (!$passengerLock->fetchColumn()) {
            $pdo->rollBack();
            json_error('Passenger profile was not found for this account.', 404);
        }
        $existingRide = $pdo->prepare(
            "SELECT f.id
             FROM trip_passenger_fares f
             INNER JOIN trips t ON t.id = f.trip_id AND t.status = 'In progress'
             WHERE f.passenger_id = :passenger_id AND f.status = 'on_board'
             LIMIT 1"
        );
        $existingRide->execute(['passenger_id' => $passengerId]);
        if ($existingRide->fetchColumn()) {
            $pdo->rollBack();
            json_error('You are already recorded aboard an active trip. Mark your current ride as dropped off first.', 409);
        }

        $tripLock = $pdo->prepare(
            passenger_active_trip_sql() . ' AND t.id = :trip_id LIMIT 1 FOR UPDATE'
        );
        $tripLock->execute(['trip_id' => $tripId]);
        $lockedTrip = $tripLock->fetch();
        if (!$lockedTrip) {
            $pdo->rollBack();
            json_error('That trip is no longer active. Refresh and choose an active trip.', 409);
        }
        $duplicate = $pdo->prepare(
            'SELECT id FROM trip_passenger_fares WHERE trip_id = :trip_id AND passenger_id = :passenger_id LIMIT 1'
        );
        $duplicate->execute(['trip_id' => $tripId, 'passenger_id' => $passengerId]);
        if ($duplicate->fetchColumn()) {
            $pdo->rollBack();
            json_error('Your account has already joined this trip.', 409);
        }

        $insert = $pdo->prepare(
            "INSERT INTO trip_passenger_fares
                (trip_id, driver_id, passenger_id, vehicle_type, passenger_type, origin_name, destination_name,
                 origin_latitude, origin_longitude, destination_latitude, destination_longitude,
                 distance_km, fare, status)
             VALUES
                (:trip_id, :driver_id, :passenger_id, :vehicle_type, :passenger_type, :origin_name, :destination_name,
                 :origin_latitude, :origin_longitude, :destination_latitude, :destination_longitude,
                 :distance_km, :fare, 'on_board')"
        );
        $insert->execute([
            'trip_id' => $tripId,
            'driver_id' => (int) $trip['driver_id'],
            'passenger_id' => $passengerId,
            'vehicle_type' => $fare['vehicle_type'],
            'passenger_type' => $fare['passenger_type'],
            'origin_name' => $fare['origin_name'],
            'destination_name' => $fare['destination_name'],
            'origin_latitude' => $fare['origin_latitude'],
            'origin_longitude' => $fare['origin_longitude'],
            'destination_latitude' => $fare['destination_latitude'],
            'destination_longitude' => $fare['destination_longitude'],
            'distance_km' => $fare['distance_km'],
            'fare' => $fare['fare'],
        ]);
        $fareId = (int) $pdo->lastInsertId();
        $updateTrip = $pdo->prepare(
            'UPDATE trips SET boarded_count = boarded_count + 1,
                number_of_passengers = number_of_passengers + 1
             WHERE id = :id AND status = \'In progress\''
        );
        $updateTrip->execute(['id' => $tripId]);
        $pdo->commit();
        json_success(['fare_id' => $fareId, 'fare' => $fare], 201);
    }

    if ($action === 'drop_off') {
        require_fields($input, ['fare_id']);
        $fareId = to_int($input['fare_id'], 'fare_id', false);
        $pdo->beginTransaction();
        $passengerLock = $pdo->prepare('SELECT passengers_id FROM passengers WHERE passengers_id = :id FOR UPDATE');
        $passengerLock->execute(['id' => $passengerId]);
        if (!$passengerLock->fetchColumn()) {
            $pdo->rollBack();
            json_error('Passenger profile was not found for this account.', 404);
        }
        $rideLookup = $pdo->prepare(
            "SELECT trip_id FROM trip_passenger_fares
             WHERE id = :fare_id AND passenger_id = :passenger_id AND status = 'on_board'
             LIMIT 1"
        );
        $rideLookup->execute(['fare_id' => $fareId, 'passenger_id' => $passengerId]);
        $tripId = $rideLookup->fetchColumn();
        if ($tripId === false) {
            $pdo->rollBack();
            json_error('Your active ride was not found. Refresh the page and try again.', 404);
        }
        $tripLock = $pdo->prepare(
            "SELECT id FROM trips WHERE id = :trip_id AND status = 'In progress' LIMIT 1 FOR UPDATE"
        );
        $tripLock->execute(['trip_id' => (int) $tripId]);
        if (!$tripLock->fetch()) {
            $pdo->rollBack();
            json_error('This trip has ended. Refresh the page to see your ride status.', 409);
        }
        $rideLock = $pdo->prepare(
            "SELECT id FROM trip_passenger_fares
             WHERE id = :fare_id AND passenger_id = :passenger_id AND status = 'on_board'
             LIMIT 1 FOR UPDATE"
        );
        $rideLock->execute(['fare_id' => $fareId, 'passenger_id' => $passengerId]);
        if (!$rideLock->fetch()) {
            $pdo->rollBack();
            json_error('Your active ride was already updated. Refresh the page.', 409);
        }
        $finishRide = $pdo->prepare(
            "UPDATE trip_passenger_fares SET status = 'dropped_off', dropped_off_at = CURRENT_TIMESTAMP
             WHERE id = :fare_id AND passenger_id = :passenger_id AND status = 'on_board'"
        );
        $finishRide->execute(['fare_id' => $fareId, 'passenger_id' => $passengerId]);
        $updateTrip = $pdo->prepare(
            "UPDATE trips SET dropped_off_count = dropped_off_count + 1
             WHERE id = :trip_id AND status = 'In progress'"
        );
        $updateTrip->execute(['trip_id' => (int) $tripId]);
        $pdo->commit();
        json_success(['fare_id' => $fareId, 'status' => 'dropped_off']);
    }

    json_error('Unsupported passenger ride action.', 422);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    handle_pdo_exception($error, 'Unable to update your passenger ride.');
}
