<?php
/**
 * AJAX: Modifica categoria
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
// Admin, god e operatori possono modificare categorie
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
$validator
    ->required('id', 'ID')
    ->int('id', 'ID', 1)
    ->required('name', 'Nome Categoria')
    ->maxLength('name', 'Nome Categoria', 100);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$id = (int)$input['id'];
$name = trim(sanitize_string($input['name']));
$description = isset($input['description']) ? trim(sanitize_string($input['description'])) : null;

try {
    // Ottieni dati precedenti per il log
    $stmtOld = $db->prepare("SELECT name, description FROM key_categories WHERE id = ?");
    $stmtOld->execute([$id]);
    $oldData = $stmtOld->fetch();
    
    // Controllo che non esista un'altra con lo stesso nome (tra quelle attive)
    $stmt = $db->prepare("SELECT id FROM key_categories WHERE LOWER(name) = LOWER(?) AND id != ? AND deleted_at IS NULL");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Esiste già un\'altra Categoria in uso con questo nome.']);
        exit;
    }

    // Se esiste una categoria eliminata con lo stesso nome, rinominala per evitare che
    // create.php la ripristini in futuro creando un duplicato attivo con lo stesso nome.
    // Le chiavi associate alla categoria eliminata rimangono eliminate.
    $stmtDeleted = $db->prepare("SELECT id FROM key_categories WHERE LOWER(name) = LOWER(?) AND id != ? AND deleted_at IS NOT NULL");
    $stmtDeleted->execute([$name, $id]);
    $deletedConflict = $stmtDeleted->fetch();
    if ($deletedConflict) {
        $stmtRename = $db->prepare("UPDATE key_categories SET name = CONCAT(name, '_eliminata_', id) WHERE id = ?");
        $stmtRename->execute([$deletedConflict['id']]);
    }

    $stmtUpdate = $db->prepare("UPDATE key_categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$name, $description, $id]);

    // Log audit
    $changes = [];
    if ($oldData['name'] !== $name) {
        $changes['Nome'] = ['da' => $oldData['name'], 'a' => $name];
    }
    if ($oldData['description'] !== $description) {
        $changes['Descrizione'] = ['da' => $oldData['description'] ?: 'N/A', 'a' => $description ?: 'N/A'];
    }
    
    audit_log('category_updated', 'category', $id, $changes);

    echo json_encode([
        'success' => true,
        'message' => 'Categoria aggiornata con successo'
    ]);

} catch (Exception $e) {
    error_log("Update category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'aggiornamento della categoria']);
}
