<?php
/**
 * AJAX: Elimina utente (soft delete)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

require_csrf($_POST['csrf_token'] ?? null);

$db = db();
$input = $_POST;

// Validazione
$validator = new Validator($input);
$validator->required('id', 'ID utente')->int('id', 'ID', 1);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$userId = (int)$input['id'];

// Non permettere eliminazione di se stessi
if ($userId === current_user_id()) {
    http_response_code(400);
    echo json_encode(['error' => 'Non puoi eliminare il tuo account']);
    exit;
}

// Non permettere eliminazione utente god da parte di admin
$user = get_user_by_id($userId);
if ($user && $user['role'] === ROLE_GOD && !has_role(ROLE_GOD)) {
    http_response_code(400);
    echo json_encode(['error' => 'Non puoi eliminare un utente con ruolo god']);
    exit;
}

// Elimina utente
$result = delete_user($userId);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Utente eliminato con successo'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
}
