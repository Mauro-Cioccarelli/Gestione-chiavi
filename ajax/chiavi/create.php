<?php
/**
 * AJAX: Crea nuova chiave
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
    ->required('category_id', 'Categoria')
    ->required('identifier', 'Identificativo chiave')
    ->int('category_id', 'Categoria', 1)
    ->maxLength('identifier', 'Identificativo', 100);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$category_id = (int)$input['category_id'];
$identifier = sanitize_string($input['identifier']);

// Verifica categoria esistente
$stmt = $db->prepare("SELECT id FROM key_categories WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$category_id]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Categoria non trovata']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Controllo se esiste già una chiave ATTIVA
    $stmt = $db->prepare("
        SELECT id, status
        FROM `keys`
        WHERE category_id = ? AND identifier = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$category_id, $identifier]);
    $existingActive = $stmt->fetch();

    if ($existingActive) {
        // Chiave attiva già esistente - ERRORE
        $db->rollBack();
        http_response_code(400);
        echo json_encode([
            'error' => 'Esiste già una chiave attiva con questo identificativo nella stessa categoria',
            'existing_id' => $existingActive['id'],
            'existing_type' => 'active'
        ]);
    }

    // 2. Controllo se esiste una chiave DISMESSA
    $stmt = $db->prepare("
        SELECT id, status
        FROM `keys`
        WHERE category_id = ? AND identifier = ? AND deleted_at IS NOT NULL
    ");
    $stmt->execute([$category_id, $identifier]);
    $existingDeleted = $stmt->fetch();

    if ($existingDeleted) {
        // Chiave dismessa esistente - chiedi conferma
        $db->rollBack();
        http_response_code(409); // 409 Conflict - serve azione utente
        echo json_encode([
            'confirm_required' => true,
            'confirm_type' => 'restore',
            'message' => 'Esiste una chiave dismessa con questo identificativo. Vuoi ripristinarla?',
            'existing_id' => $existingDeleted['id'],
            'existing_type' => 'deleted'
        ]);
    }

    // 3. Nessuna chiave esistente - creo nuova
    $stmt = $db->prepare("
        INSERT INTO `keys` (category_id, identifier, status, created_at)
        VALUES (?, ?, 'available', NOW())
    ");
    $stmt->execute([$category_id, $identifier]);

    $keyId = (int)$db->lastInsertId();

    // Log movimento
    log_key_movement($keyId, 'create', null, null, 'Chiave creata');

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Chiave creata con successo',
        'data' => ['id' => $keyId]
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Create key error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante la creazione']);
}
