<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Use POST to log in.', 405);
}

$input = request_input();
require_fields($input, ['username', 'password']);

try {
    $user = authenticate(get_pdo(), trim_string($input['username']), (string) $input['password']);
    json_success($user);
} catch (PDOException $e) {
    handle_pdo_exception($e, 'Unable to log in right now.');
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 500);
}
