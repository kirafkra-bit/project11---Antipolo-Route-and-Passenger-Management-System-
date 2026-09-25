<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();
json_success(session_user_payload());
