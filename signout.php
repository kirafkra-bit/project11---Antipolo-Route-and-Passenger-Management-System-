<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/web.php';

logout_user();
web_redirect('signin.php');
