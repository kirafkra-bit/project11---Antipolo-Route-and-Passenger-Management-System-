<?php
/**
 * Run from the project folder:
 *   php tests/fare_test.php
 *
 * This checks the fare formula without using MySQL.
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once APP_BASE_PATH . '/includes/fare_calculator.php';

function expect_fare(string $label, array $result, float $expected): void
{
    $actual = $result['estimated_fare'];
    $ok = abs($actual - $expected) < 0.001;
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . ' => ₱' . number_format($actual, 2);
    if (!$ok) {
        echo ' (expected ₱' . number_format($expected, 2) . ')';
    }
    echo PHP_EOL;
}

expect_fare('Traditional 4 km regular', calculate_fare('traditional', 'regular', 4), 13.00);
expect_fare('Traditional 5 km regular', calculate_fare('traditional', 'regular', 5), 14.80);
expect_fare('Traditional 6 km regular', calculate_fare('traditional', 'regular', 6), 16.60);
expect_fare('Traditional 10 km regular', calculate_fare('traditional', 'regular', 10), 23.80);
expect_fare('Traditional 5 km student', calculate_fare('traditional', 'student', 5), 11.84);
expect_fare('Traditional 6 km senior', calculate_fare('traditional', 'senior citizen', 6), 13.28);
expect_fare('Modern 4 km regular', calculate_fare('modern', 'regular', 4), 15.00);
expect_fare('Modern 5 km regular', calculate_fare('modern', 'regular', 5), 17.20);

try {
    calculate_fare('modern', 'pwd', 5);
    echo '[FAIL] Modern discounted should be blocked until rates are configured' . PHP_EOL;
} catch (InvalidArgumentException $e) {
    echo '[PASS] Modern discounted is blocked: ' . $e->getMessage() . PHP_EOL;
}
