<?php
/**
 * Index - Redirect a login
 */

define('APP_ROOT', true);
require_once __DIR__ . '/includes/bootstrap.php';

header('Location: login.php');
exit;
