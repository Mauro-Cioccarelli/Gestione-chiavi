<?php
/**
 * AJAX: Aggiorna chiave
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();

// Assicura output pulito per JSON
if (ob_get_level()) {
    ob_end_clean();
}
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
    ->int('id', 'ID', 1);

if (!empty($input['category_id'])) {
    $validator->int('category_id', 'Categoria', 1);
}

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$keyId = (int)$input['id'];
$category_id = isset($input['category_id']) ? (int)$input['category_id'] : null;
$identifier = isset($input['identifier']) ? sanitize_string($input['identifier']) : null;

// Verifica chiave esistente e ottieni dati attuali
$stmt = $db->prepare("
    SELECT k.id, k.identifier, k.status, k.category_id, kc.name as category_name
    FROM `keys` k
    LEFT JOIN key_categories kc ON k.category_id = kc.id
    WHERE k.id = ? AND k.deleted_at IS NULL
");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata']);
    exit;
}

// Costruisci update dinamico e dettagli per il log
$updates = [];
$params = [];
$changes = [];

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
    
    // Ottieni nome categoria nuova
    $stmt = $db->prepare("SELECT name FROM key_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $newCategory = $stmt->fetch();
    
    if ($key['category_id'] != $category_id) {
        $changes['Categoria'] = $key['category_name'] . ' → ' . ($newCategory['name'] ?? 'N/A');
    }
}

if ($identifier !== null) {
    $updates[] = "identifier = ?";
    $params[] = $identifier;
    
    if ($key['identifier'] !== $identifier) {
        $changes['Identificativo'] = $key['identifier'] . ' → ' . $identifier;
    }
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

    $stmt = $db->prepare("UPDATE `keys` SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);

    // Log movimento con dettagli modifiche
    $notes = 'Chiave modificata';
    if (!empty($changes)) {
        $notes .= ': ' . implode('; ', array_map(function($k, $v) {
            return "$k: $v";
        }, array_keys($changes), $changes));
    }
    log_key_movement($keyId, 'update', null, null, $notes);

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
