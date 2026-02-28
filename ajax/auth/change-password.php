<?php
/**
 * AJAX: Cambio password
 * Supporta sia il cambio password normale che il reset con token
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

$input = $_POST;

// Se è presente il token, è un reset password
if (isset($input['token'])) {
    // Reset password con token (non richiede login)
    $validator = new Validator($input);
    $validator
        ->required('token', 'Token')
        ->required('new_password', 'Nuova password')
        ->password('new_password', 'Nuova password');

    if (!$validator->validate()) {
        http_response_code(400);
        echo json_encode(['error' => $validator->firstError()]);
        exit;
    }

    $token = $input['token'];
    $newPassword = $input['new_password'];

    // Verifica password di conferma se presente
    if (isset($input['confirm_password']) && $input['confirm_password'] !== $newPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'Le password non coincidono']);
        exit;
    }

    $result = reset_password_with_token($token, $newPassword);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reimpostata con successo. Effettua il login.'
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['error']]);
    }
    exit;
}

// Cambio password normale (richiede login)
require_login();

$validator = new Validator($input);
$validator
    ->required('old_password', 'Password attuale')
    ->required('new_password', 'Nuova password')
    ->password('new_password', 'Nuova password');

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$oldPassword = $input['old_password'];
$newPassword = $input['new_password'];

// Verifica nuova password != vecchia
if ($oldPassword === $newPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'La nuova password deve essere diversa da quella attuale']);
    exit;
}

// Cambia password
$result = change_password(current_user_id(), $oldPassword, $newPassword);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Password cambiata con successo'
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
}
