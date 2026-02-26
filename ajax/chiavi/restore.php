<?php
/**
 * AJAX: Ripristina chiave dismessa
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
$validator->required('id', 'ID chiave')->int('id', 'ID', 1);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$keyId = (int)$input['id'];

// Verifica chiave esistente e dismessa
$stmt = $db->prepare("
    SELECT id, identifier, status, deleted_at 
    FROM keys 
    WHERE id = ? AND deleted_at IS NOT NULL
");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata o non dismessa']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Ripristina chiave
    $stmt = $db->prepare("
        UPDATE keys 
        SET deleted_at = NULL, status = 'available', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$keyId]);
    
    // Log movimento
    log_key_movement($keyId, 'create', null, null, 'Chiave ripristinata da dismessa');
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Chiave "' . htmlspecialchars($key['identifier']) . '" ripristinata con successo',
        'data' => ['id' => $keyId, 'restored' => true]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Restore key error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante il ripristino']);
}
