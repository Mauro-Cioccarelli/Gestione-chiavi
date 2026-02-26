<?php
/**
 * AJAX: Login
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

// Se già loggato
if (is_logged_in()) {
    json_success('Già autenticato', ['redirect' => APP_URL . '/dashboard.php']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

require_csrf($_POST['csrf_token'] ?? null);

$username = sanitize_string($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Inserisci username e password']);
    exit;
}

$result = authenticate($username, $password);

if ($result['success']) {
    $response = [
        'success' => true,
        'message' => 'Login effettuato',
        'role' => $result['role']
    ];
    
    if ($result['force_password_change']) {
        $response['redirect'] = APP_URL . '/utenti/cambio-password.php?force=1';
    } else {
        $response['redirect'] = APP_URL . '/dashboard.php';
    }
    
    echo json_encode($response);
} else {
    http_response_code(401);
    echo json_encode(['error' => $result['error']]);
}
