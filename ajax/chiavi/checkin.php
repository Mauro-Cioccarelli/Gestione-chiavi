<?php
/**
 * AJAX: Rientro chiave (checkin)
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

$db = db();
$input = $_POST;

// Validazione
$validator = new Validator($input);
$validator
    ->required('key_id', 'Chiave')
    ->int('key_id', 'Chiave', 1);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$keyId = (int)$input['key_id'];
$notes = isset($input['notes']) ? sanitize_string($input['notes']) : '';

// Verifica chiave esistente e in consegna
$stmt = $db->prepare("SELECT status, identifier FROM `keys` WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata']);
    exit;
}

if ($key['status'] !== KEY_IN_DELIVERY) {
    http_response_code(400);
    echo json_encode(['error' => 'Chiave non è in consegna (stato: ' . $key['status'] . ')']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Aggiorna stato chiave
    $stmt = $db->prepare("UPDATE `keys` SET status = 'available', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$keyId]);
    
    // Registra movimento
    log_key_movement($keyId, 'checkin', null, null, $notes ?: 'Chiave rientrata');
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Chiave "' . htmlspecialchars($key['identifier']) . '" rientrata con successo'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Checkin key error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante il rientro']);
}
