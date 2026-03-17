<?php
/**
 * AJAX: Elimina chiave (soft delete)
 */

define('APP_ROOT', true);

// Pulisce qualsiasi output precedente
while (ob_get_level()) {
    ob_end_clean();
}

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

// Verifica chiave esistente
$stmt = $db->prepare("SELECT id, identifier FROM `keys` WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata']);
    exit;
}

// Verifica che la chiave sia disponibile (non in consegna)
$stmt = $db->prepare("SELECT status FROM `keys` WHERE id = ?");
$stmt->execute([$keyId]);
$status = $stmt->fetch()['status'];

if ($status === KEY_IN_DELIVERY) {
    http_response_code(400);
    echo json_encode(['error' => 'Non è possibile eliminare una chiave in consegna. Effettua prima il rientro.']);
    exit;
}

try {
    $db->beginTransaction();

    // Soft delete
    $stmt = $db->prepare("UPDATE `keys` SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$keyId]);
    
    // Log movimento
    log_key_movement($keyId, 'dismise', null, null, 'Chiave eliminata (soft delete)');
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Chiave eliminata con successo'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete key error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'eliminazione']);
}
