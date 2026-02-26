<?php
/**
 * Logout
 */

define('APP_ROOT', true);
require_once __DIR__ . '/includes/bootstrap.php';

// Esegui logout
logout();

// Redirect a login
header('Location: ' . APP_URL . '/login.php');
exit;
