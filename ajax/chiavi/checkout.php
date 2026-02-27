<?php
/**
 * AJAX: Consegna chiave (checkout)
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
    ->required('recipient_name', 'Ricevente')
    ->int('key_id', 'Chiave', 1)
    ->maxLength('recipient_name', 'Ricevente', 100);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$keyId = (int)$input['key_id'];
$recipientName = sanitize_string($input['recipient_name']);
$notes = isset($input['notes']) ? sanitize_string($input['notes']) : '';

// Verifica chiave esistente e disponibile
$stmt = $db->prepare("SELECT status, identifier FROM `keys` WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata']);
    exit;
}

if ($key['status'] !== KEY_AVAILABLE) {
    http_response_code(400);
    echo json_encode(['error' => 'Chiave non disponibile per la consegna (stato: ' . $key['status'] . ')']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Aggiorna stato chiave
    $stmt = $db->prepare("UPDATE `keys` SET status = 'in_delivery', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$keyId]);
    
    // Registra movimento
    log_key_movement($keyId, 'checkout', null, $recipientName, $notes);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Chiave "' . htmlspecialchars($key['identifier']) . '" consegnata a ' . htmlspecialchars($recipientName)
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Checkout key error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante la consegna']);
}
