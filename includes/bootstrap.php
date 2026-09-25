<?php
/**
 * Loads config and helpers for every API page.
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once APP_BASE_PATH . '/config/database.php';
require_once APP_BASE_PATH . '/includes/functions.php';
require_once APP_BASE_PATH . '/includes/csrf.php';
require_once APP_BASE_PATH . '/includes/auth.php';
require_once APP_BASE_PATH . '/includes/fare_calculator.php';

start_app_session();

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

set_exception_handler(function (Throwable $e): void {
    error_log('PoloNav uncaught error: ' . $e->getMessage());
    if (function_exists('json_error')) {
        json_error('A server error occurred.', 500);
    }
    http_response_code(500);
    echo 'A server error occurred.';
});
