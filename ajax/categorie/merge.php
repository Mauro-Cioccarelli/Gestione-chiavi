<?php
/**
 * AJAX: Unisci più categorie in una sola (Merge)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
// Admin, god e operatori possono unire categorie
if (!has_role(ROLE_ADMIN) && !has_role(ROLE_OPERATOR)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permessi insufficienti']);
    exit;
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

if (empty($input['target_id']) || empty($input['source_ids_csv'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Seleziona le categorie da unire e quella di destinazione (Sorgenti non può essere vuoto ed avere lo stesso valore di destinazione).']);
    exit;
}

$targetId = (int)$input['target_id'];
// Parse CSV from Javascript
$sourceIdsStr = explode(',', $input['source_ids_csv']);
$sourceIds = array_map('intval', array_filter($sourceIdsStr));

// Pulisci un potenziale merge "in se stesso"
$sourceIds = array_filter($sourceIds, function($id) use ($targetId) {
    return $id !== $targetId;
});

if (empty($sourceIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Le categorie sorgenti non possono corrispondere alla categoria di destinazione scelta. Riprova con parametri differenti.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Assicurati che Target sia valido
    $stmtTarget = $db->prepare("SELECT name FROM key_categories WHERE id = ? AND deleted_at IS NULL");
    $stmtTarget->execute([$targetId]);
    $targetCat = $stmtTarget->fetch();

    if (!$targetCat) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'La categoria di destinazione specificata non esiste o è dismessa.']);
        exit;
    }

    $targetName = $targetCat['name'];

    // 2. Troviamo tutte le chiavi (sia attive che dismesse) delle categorie sorgenti
    $placeholders = str_repeat('?,', count($sourceIds) - 1) . '?';
    $stmtKeys = $db->prepare("SELECT id, identifier FROM `keys` WHERE category_id IN ($placeholders)");
    $stmtKeys->execute($sourceIds);
    $keysToMove = $stmtKeys->fetchAll();

    $keysMovedCount = 0;

    foreach ($keysToMove as $k) {
        $keyId = $k['id'];

        // Aggiorna category_id
        $stmtUpdateKey = $db->prepare("UPDATE `keys` SET category_id = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdateKey->execute([$targetId, $keyId]);
        
        // Log movement
        log_key_movement($keyId, 'update', null, null, "Categoria chiave modificata in '{$targetName}' a causa di una fusione di categorie.");
        $keysMovedCount++;
    }

    // 3. Mark the source categories as deleted
    $stmtDeleteSources = $db->prepare("UPDATE key_categories SET deleted_at = NOW() WHERE id IN ($placeholders)");
    $stmtDeleteSources->execute($sourceIds);

    $db->commit();

    // Log audit per ogni categoria eliminata (merge)
    foreach ($sourceIds as $sourceId) {
        $stmtSourceName = $db->prepare("SELECT name FROM key_categories WHERE id = ?");
        $stmtSourceName->execute([$sourceId]);
        $sourceCat = $stmtSourceName->fetch();
        if ($sourceCat) {
            audit_log('category_merged', 'category', $sourceId, [
                'merged_into' => $targetName,
                'source_name' => $sourceCat['name']
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Fusione completata. ' . $keysMovedCount . ' chiavi sono state spostate in "' . htmlspecialchars($targetName) . '" e ' . count($sourceIds) . ' categorie sorgenti sono state eliminate.'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Merge categories error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'unione delle categorie']);
}
