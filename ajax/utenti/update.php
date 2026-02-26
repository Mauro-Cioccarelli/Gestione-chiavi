<?php
/**
 * AJAX: Aggiorna utente
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
$validator
    ->required('id', 'ID utente')
    ->int('id', 'ID', 1);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$userId = (int)$input['id'];

// Verifica utente esistente
$user = get_user_by_id($userId);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Utente non trovato']);
    exit;
}

// Non permettere modifica di se stessi come ruolo
if ($userId === current_user_id() && isset($input['role']) && $input['role'] !== current_role()) {
    http_response_code(400);
    echo json_encode(['error' => 'Non puoi modificare il tuo stesso ruolo']);
    exit;
}

// Costruisci dati aggiornamento
$updateData = [];

if (isset($input['email'])) {
    $email = sanitize_email($input['email']);
    if (!is_valid_email($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email non valida']);
        exit;
    }
    
    // Verifica email non duplicata
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email già in uso']);
        exit;
    }
    
    $updateData['email'] = $email;
}

if (isset($input['role']) && has_role(ROLE_GOD)) {
    // Solo god può cambiare ruoli
    if (!in_array($input['role'], ['operator', 'admin', 'god'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Ruolo non valido']);
        exit;
    }
    $updateData['role'] = $input['role'];
}

if (isset($input['force_password_change'])) {
    $updateData['force_password_change'] = (bool)$input['force_password_change'];
}

if (empty($updateData)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nessun campo da aggiornare']);
    exit;
}

// Aggiorna utente
$result = update_user($userId, $updateData);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Utente aggiornato con successo'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
}
