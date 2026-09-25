<?php
/**
 * Central fare calculator.
 *
 * All fare estimates should go through calculate_fare().
 * Do not copy this formula into other files.
 */

function fare_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require APP_BASE_PATH . '/config/fare.php';
    }
    return $config;
}

function normalize_vehicle_type(string $vehicleType): string
{
    $value = strtolower(trim($vehicleType));
    $map = [
        'traditional'         => 'traditional',
        'traditional jeepney' => 'traditional',
        'modern'              => 'modern',
        'modern jeepney'      => 'modern',
    ];

    if (!isset($map[$value])) {
        throw new InvalidArgumentException('Vehicle type must be Traditional Jeepney or Modern Jeepney.');
    }

    return $map[$value];
}

function normalize_passenger_type(string $passengerType): string
{
    $value = strtolower(trim($passengerType));
    $map = [
        'regular'         => 'regular',
        'student'         => 'discounted',
        'senior'          => 'discounted',
        'senior citizen'  => 'discounted',
        'pwd'             => 'discounted',
        'discounted'      => 'discounted',
    ];

    if (!isset($map[$value])) {
        throw new InvalidArgumentException('Passenger type must be Regular, Student, Senior Citizen, or PWD.');
    }

    return $map[$value];
}

function passenger_type_label(string $passengerType): string
{
    $value = strtolower(trim($passengerType));
    $labels = [
        'regular'        => 'Regular',
        'student'        => 'Student',
        'senior'         => 'Senior Citizen',
        'senior citizen' => 'Senior Citizen',
        'pwd'            => 'PWD',
        'discounted'     => 'Discounted',
    ];

    return $labels[$value] ?? $passengerType;
}

/**
 * @return array{
 *   vehicle_type: string,
 *   passenger_type: string,
 *   discount_type: string,
 *   distance: float,
 *   base_distance_km: int,
 *   base_fare: float,
 *   succeeding_rate: float,
 *   additional_kilometers: float,
 *   estimated_fare: float,
 *   formula: string
 * }
 */
function calculate_fare(string $vehicleType, string $passengerType, $distance): array
{
    if ($distance === null || $distance === '' || !is_numeric($distance)) {
        throw new InvalidArgumentException('Distance must be a number.');
    }

    $distanceKm = (float) $distance;
    if ($distanceKm < 0) {
        throw new InvalidArgumentException('Distance cannot be negative.');
    }

    $vehicle = normalize_vehicle_type($vehicleType);
    $rateGroup = normalize_passenger_type($passengerType);
    $config = fare_config();
    $baseDistance = (int) $config['base_distance_km'];

    $rates = $config[$vehicle][$rateGroup] ?? null;
    if (!is_array($rates)) {
        throw new InvalidArgumentException('Fare rates are not configured for this combination.');
    }

    $baseFare = $rates['base'];
    $succeeding = $rates['succeeding'];

    if ($baseFare === null || $succeeding === null) {
        throw new InvalidArgumentException(
            'Modern jeepney discounted fare rates are not configured yet. Update config/fare.php when the missing rates are provided.'
        );
    }

    $baseFare = (float) $baseFare;
    $succeeding = (float) $succeeding;

    $additionalKm = 0.0;
    if ($distanceKm > $baseDistance) {
        $additionalKm = $distanceKm - $baseDistance;
    }

    $estimated = $baseFare + ($additionalKm * $succeeding);
    $estimated = round($estimated, 2);

    return [
        'vehicle_type'          => $vehicle === 'modern' ? 'Modern Jeepney' : 'Traditional Jeepney',
        'passenger_type'        => passenger_type_label($passengerType),
        'discount_type'         => $rateGroup === 'discounted' ? 'Discounted' : 'Regular',
        'distance'              => round($distanceKm, 2),
        'base_distance_km'      => $baseDistance,
        'base_fare'             => round($baseFare, 2),
        'succeeding_rate'       => round($succeeding, 2),
        'additional_kilometers' => round($additionalKm, 2),
        'estimated_fare'        => $estimated,
        'currency'              => 'PHP',
        'formula'               => $additionalKm > 0
            ? 'base fare + ((distance - ' . $baseDistance . ') × succeeding rate)'
            : 'distance is within the first ' . $baseDistance . ' km, so fare = base fare',
    ];
}

function public_fare_rates(): array
{
    $config = fare_config();

    return [
        'base_distance_km' => $config['base_distance_km'],
        'traditional'      => $config['traditional'],
        'modern'           => [
            'regular'    => $config['modern']['regular'],
            'discounted' => [
                'base'       => $config['modern']['discounted']['base'],
                'succeeding' => $config['modern']['discounted']['succeeding'],
                'configured' => $config['modern']['discounted']['base'] !== null
                    && $config['modern']['discounted']['succeeding'] !== null,
                'note'       => $config['modern']['discounted']['note'] ?? null,
            ],
        ],
        'passenger_types'  => ['Regular', 'Student', 'Senior Citizen', 'PWD'],
        'vehicle_types'    => ['Traditional Jeepney', 'Modern Jeepney'],
    ];
}
