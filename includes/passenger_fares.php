<?php

function passenger_place_signature(string $label, float $latitude, float $longitude): string
{
    $payload = trim($label) . '|' . number_format($latitude, 6, '.', '') . '|' .
        number_format($longitude, 6, '.', '');
    return hash_hmac('sha256', $payload, csrf_token());
}

function tomtom_trip_request(string $url): array
{
    if (TOMTOM_TRAFFIC_API_KEY === '') {
        json_error('Passenger fare calculation is not configured. Set TOMTOM_TRAFFIC_API_KEY on the server.', 503);
    }
    $curl = curl_init($url);
    if ($curl === false) {
        json_error('Unable to initialize the place or routing request.', 500);
    }
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($response === false) {
        error_log('TomTom passenger fare request failed: ' . $curlError);
        json_error('TomTom could not be reached. Check the server connection and try again.', 502);
    }
    if ($httpStatus === 401 || $httpStatus === 403) {
        error_log('TomTom passenger fare request rejected credentials with HTTP ' . $httpStatus . '.');
        json_error('TomTom rejected the server API key. Check that Geocoding and Routing API access is enabled.', 502);
    }
    if ($httpStatus === 429) {
        json_error('TomTom rate limit reached. Try again shortly.', 429);
    }
    if ($httpStatus < 200 || $httpStatus >= 300) {
        error_log('TomTom passenger fare request returned HTTP ' . $httpStatus . '.');
        json_error(
            'TomTom rejected the request (HTTP ' . $httpStatus . '). Check the TomTom key, API permissions, and place query.',
            502
        );
    }
    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        json_error('TomTom returned invalid data.', 502);
    }
    return $payload;
}

function validate_passenger_coordinates($latitude, $longitude, string $field): array
{
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        json_error('Select a suggested location for the ' . $field . '.', 422);
    }
    $lat = (float) $latitude;
    $lng = (float) $longitude;
    if (!is_finite($lat) || !is_finite($lng)
        || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        json_error('The selected ' . $field . ' coordinates are invalid.', 422);
    }
    return [$lat, $lng];
}

function tomtom_place_suggestions(string $query): array
{
    $query = trim($query);
    if (strlen($query) < 3 || strlen($query) > 120) {
        json_error('Enter at least 3 characters and no more than 120 to search for a place.', 422);
    }
    $url = 'https://api.tomtom.com/search/2/geocode/' . rawurlencode($query) . '.json?' . http_build_query([
        'key' => TOMTOM_TRAFFIC_API_KEY,
        'countrySet' => 'PH',
        'language' => 'en-GB',
        'limit' => 5,
        'lat' => 14.60,
        'lon' => 121.15,
    ]);
    $payload = tomtom_trip_request($url);
    $suggestions = [];
    foreach (($payload['results'] ?? []) as $result) {
        $position = $result['position'] ?? null;
        $address = $result['address']['freeformAddress'] ?? '';
        $placeName = $result['poi']['name'] ?? '';
        if (!is_array($position) || !is_numeric($position['lat'] ?? null) || !is_numeric($position['lon'] ?? null)) {
            continue;
        }
        $label = trim($placeName . ($placeName !== '' && $address !== '' ? ', ' : '') . $address);
        if ($label === '') {
            continue;
        }
        $latitude = (float) $position['lat'];
        $longitude = (float) $position['lon'];
        $suggestions[] = [
            'label' => $label,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'signature' => passenger_place_signature($label, $latitude, $longitude),
        ];
    }
    return $suggestions;
}

function calculate_passenger_trip_fare(array $input, string $vehicleType): array
{
    require_fields($input, [
        'origin_name', 'destination_name', 'origin_latitude', 'origin_longitude',
        'destination_latitude', 'destination_longitude', 'origin_signature',
        'destination_signature', 'passenger_type',
    ]);
    $originName = trim_string($input['origin_name']);
    $destinationName = trim_string($input['destination_name']);
    if ($originName === '' || strlen($originName) > 255
        || $destinationName === '' || strlen($destinationName) > 255) {
        json_error('Choose valid boarding and destination place suggestions.', 422);
    }
    [$originLatitude, $originLongitude] = validate_passenger_coordinates(
        $input['origin_latitude'],
        $input['origin_longitude'],
        'boarding point'
    );
    [$destinationLatitude, $destinationLongitude] = validate_passenger_coordinates(
        $input['destination_latitude'],
        $input['destination_longitude'],
        'destination'
    );
    if (!hash_equals(
        passenger_place_signature($originName, $originLatitude, $originLongitude),
        (string) $input['origin_signature']
    ) || !hash_equals(
        passenger_place_signature($destinationName, $destinationLatitude, $destinationLongitude),
        (string) $input['destination_signature']
    )) {
        json_error('Select both places from the TomTom suggestions before recording your ride.', 422);
    }
    $passengerType = strtolower(trim_string($input['passenger_type']));
    if (!in_array($passengerType, ['regular', 'student', 'senior', 'pwd'], true)) {
        json_error('Choose a valid passenger fare category.', 422);
    }

    $routePoints = number_format($originLatitude, 6, '.', '') . ',' . number_format($originLongitude, 6, '.', '')
        . ':' . number_format($destinationLatitude, 6, '.', '') . ',' . number_format($destinationLongitude, 6, '.', '');
    $url = 'https://api.tomtom.com/routing/1/calculateRoute/' . $routePoints . '/json?' . http_build_query([
        'key' => TOMTOM_TRAFFIC_API_KEY,
        'routeType' => 'fastest',
        'traffic' => 'false',
        'travelMode' => 'car',
    ]);
    $payload = tomtom_trip_request($url);
    $distanceMeters = $payload['routes'][0]['summary']['lengthInMeters'] ?? null;
    if (!is_numeric($distanceMeters) || (float) $distanceMeters <= 0) {
        json_error('No road route was found between the selected passenger locations.', 422);
    }

    $distanceKm = (float) $distanceMeters / 1000;
    try {
        $fare = calculate_fare($vehicleType, $passengerType, $distanceKm);
    } catch (InvalidArgumentException $error) {
        json_error($error->getMessage(), 422);
    }
    return [
        'vehicle_type' => $vehicleType,
        'passenger_type' => $passengerType,
        'origin_name' => $originName,
        'destination_name' => $destinationName,
        'origin_latitude' => $originLatitude,
        'origin_longitude' => $originLongitude,
        'destination_latitude' => $destinationLatitude,
        'destination_longitude' => $destinationLongitude,
        'distance_km' => round($distanceKm, 2),
        'fare' => $fare['estimated_fare'],
    ];
}
