<?php
/**
 * AJAX: Aggiorna chiave
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
    ->required('id', 'ID chiave')
    ->int('id', 'ID', 1)
    ->int('category_id', 'Categoria', 1);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$keyId = (int)$input['id'];
$category_id = isset($input['category_id']) ? (int)$input['category_id'] : null;
$identifier = isset($input['identifier']) ? sanitize_string($input['identifier']) : null;

// Verifica chiave esistente
$stmt = $db->prepare("SELECT id, status FROM keys WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata']);
    exit;
}

// Costruisci update dinamico
$updates = [];
$params = [];

if ($category_id !== null) {
    // Verifica categoria
    $stmt = $db->prepare("SELECT id FROM key_categories WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Categoria non trovata']);
        exit;
    }
    $updates[] = "category_id = ?";
    $params[] = $category_id;
}

if ($identifier !== null) {
    $updates[] = "identifier = ?";
    $params[] = $identifier;
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nessun campo da aggiornare']);
    exit;
}

$updates[] = "updated_at = NOW()";
$params[] = $keyId;

try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("UPDATE keys SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);
    
    // Log movimento
    log_key_movement($keyId, 'update', null, null, 'Chiave modificata');
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Chiave aggiornata con successo'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Update key error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'aggiornamento']);
}
