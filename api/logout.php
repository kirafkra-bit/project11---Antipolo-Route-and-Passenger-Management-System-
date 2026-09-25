<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Use POST to log out.', 405);
}

logout_user();
json_success(['message' => 'You have been logged out.']);
