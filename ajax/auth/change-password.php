<?php
/**
 * AJAX: Cambio password
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

require_csrf($_POST['csrf_token'] ?? null);

$input = $_POST;

// Validazione
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
