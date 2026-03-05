<?php
/**
 * AJAX: Ripristina utente eliminato
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

// Ripristina utente
$result = restore_user($userId);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Utente ripristinato con successo'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
}
