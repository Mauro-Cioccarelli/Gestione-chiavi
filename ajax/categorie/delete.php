<?php
/**
 * AJAX: Elimina categoria
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
// Admin, god e operatori possono eliminare categorie
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

$validator = new Validator($input);
$validator->required('id', 'ID')->int('id', 'ID', 1);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$categoryId = (int)$input['id'];

try {
    $db->beginTransaction();

    // Ottieni nome categoria per il log
    $stmtName = $db->prepare("SELECT name FROM key_categories WHERE id = ?");
    $stmtName->execute([$categoryId]);
    $category = $stmtName->fetch();
    $categoryName = $category ? $category['name'] : 'Sconosciuto';

    // Recupera gli ID delle chiavi attive da eliminare (prima del delete, per poter loggare i movimenti)
    $stmtKeys = $db->prepare("SELECT id FROM `keys` WHERE category_id = ? AND deleted_at IS NULL");
    $stmtKeys->execute([$categoryId]);
    $keyIds = $stmtKeys->fetchAll(PDO::FETCH_COLUMN);
    $activeKeysCount = count($keyIds);

    // Soft-delete tutte le chiavi attive associate alla categoria
    if ($activeKeysCount > 0) {
        $stmtDeleteKeys = $db->prepare("UPDATE `keys` SET deleted_at = NOW() WHERE category_id = ? AND deleted_at IS NULL");
        $stmtDeleteKeys->execute([$categoryId]);
    }

    // Soft-delete della categoria
    $stmtDelete = $db->prepare("UPDATE key_categories SET deleted_at = NOW() WHERE id = ?");
    $stmtDelete->execute([$categoryId]);

    $db->commit();

    // Registra un movimento "dismessa" per ogni chiave eliminata
    foreach ($keyIds as $keyId) {
        log_key_movement((int)$keyId, 'dismise', null, null, "Eliminata insieme alla categoria \"{$categoryName}\"");
    }

    // Log audit con descrizione in italiano
    $chiaveParola = $activeKeysCount === 1 ? 'chiave' : 'chiavi';
    $messaggioAudit = $activeKeysCount > 0
        ? "Categoria \"{$categoryName}\" eliminata con {$activeKeysCount} {$chiaveParola} associate."
        : "Categoria \"{$categoryName}\" eliminata (nessuna chiave associata).";

    audit_log('category_deleted', 'category', $categoryId, [
        'nome_categoria'   => $categoryName,
        'chiavi_eliminate' => $activeKeysCount
    ], $messaggioAudit);

    $message = $activeKeysCount > 0
        ? "Categoria eliminata con successo insieme a {$activeKeysCount} chiav" . ($activeKeysCount === 1 ? 'e' : 'i') . " associate."
        : 'Categoria eliminata con successo.';

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'eliminazione della categoria']);
}
