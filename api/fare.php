<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$method = request_method();
$input = request_input();

if ($method === 'GET') {
    $vehicleType = query_param('vehicle_type');
    $passengerType = query_param('passenger_type');
    $distance = query_param('distance');

    if ($vehicleType !== null || $passengerType !== null || $distance !== null) {
        try {
            json_success(calculate_fare((string) $vehicleType, (string) $passengerType, $distance));
        } catch (InvalidArgumentException $e) {
            $extra = [];
            if (stripos($e->getMessage(), 'not configured yet') !== false) {
                $extra['missing_rate'] = true;
            }
            json_error($e->getMessage(), 422, $extra);
        }
    }

    json_success(public_fare_rates());
}

if ($method === 'POST') {
    require_fields($input, ['vehicle_type', 'passenger_type', 'distance']);
    try {
        if (isset($input['point_a']) || isset($input['point_b'])) {
            require_fields($input, ['point_a', 'point_b']);
            require_once APP_BASE_PATH . '/includes/antipolo_points.php';
            $points = antipolo_points_from_database(get_pdo());
            $pointIds = array_map('strval', array_column($points, 'id'));
            $knownPoints = in_array((string) $input['point_a'], $pointIds, true)
                && in_array((string) $input['point_b'], $pointIds, true);
            $searchedPoints = isset($input['point_a_lat'], $input['point_a_lng'], $input['point_b_lat'], $input['point_b_lng'])
                && is_numeric($input['point_a_lat']) && is_numeric($input['point_a_lng'])
                && is_numeric($input['point_b_lat']) && is_numeric($input['point_b_lng']);
            if (!$knownPoints && !$searchedPoints) {
                throw new InvalidArgumentException('Selected map points are not valid.');
            }
            if ($searchedPoints) {
                $coordinates = [
                    [(float) $input['point_a_lat'], (float) $input['point_a_lng']],
                    [(float) $input['point_b_lat'], (float) $input['point_b_lng']],
                ];
                foreach ($coordinates as [$latitude, $longitude]) {
                    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                        throw new InvalidArgumentException('Selected map coordinates are not valid.');
                    }
                }
            }
        }

        $result = calculate_fare(
            (string) $input['vehicle_type'],
            (string) $input['passenger_type'],
            $input['distance']
        );
        json_success($result);
    } catch (InvalidArgumentException $e) {
        $extra = [];
        if (stripos($e->getMessage(), 'not configured yet') !== false) {
            $extra['missing_rate'] = true;
        }
        json_error($e->getMessage(), 422, $extra);
    }
}

json_error('Method not allowed.', 405);
