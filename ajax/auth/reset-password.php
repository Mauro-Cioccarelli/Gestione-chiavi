<?php
/**
 * AJAX: Richiesta reset password
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

require_csrf($_POST['csrf_token'] ?? null);

$email = sanitize_email($_POST['email'] ?? '');

if (!is_valid_email($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email non valida']);
    exit;
}

// Genera token
$result = generate_password_reset_token($email);

if ($result['success']) {
    // In produzione: inviare email con link
    // Per ora, ritorniamo il token per testing
    
    echo json_encode([
        'success' => true,
        'message' => $result['message'] ?? 'Se l\'email è registrata, riceverai le istruzioni per il reset',
        // Solo sviluppo - rimuovere in produzione
        'token' => $result['token'] ?? null,
        'username' => $result['username'] ?? null
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
}
