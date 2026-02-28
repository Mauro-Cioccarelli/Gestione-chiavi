<?php
/**
 * AJAX: Crea nuova categoria (con gestione soft-delete ripristino)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
// Admin, god e operatori possono creare categorie
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
    ->required('name', 'Nome Categoria')
    ->maxLength('name', 'Nome Categoria', 100);

if (!$validator->validate()) {
    http_response_code(400);
    echo json_encode(['error' => $validator->firstError()]);
    exit;
}

$name = trim(sanitize_string($input['name']));
$description = isset($input['description']) ? trim(sanitize_string($input['description'])) : null;

try {
    $db->beginTransaction();

    // 1. Controllo se esiste già un'attiva con questo nome
    $stmt = $db->prepare("SELECT id FROM key_categories WHERE LOWER(name) = LOWER(?) AND deleted_at IS NULL");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Esiste già una Categoria con questo nome.']);
        exit;
    }

    // 2. Controllo se esiste dismessa con questo nome esatto
    $stmt = $db->prepare("SELECT id FROM key_categories WHERE LOWER(name) = LOWER(?) AND deleted_at IS NOT NULL");
    $stmt->execute([$name]);
    $deletedRow = $stmt->fetch();

    if ($deletedRow) {
        // Ripristino soft-deleted riga
        $stmtUpdate = $db->prepare("UPDATE key_categories SET deleted_at = NULL, description = ? WHERE id = ?");
        $stmtUpdate->execute([$description, $deletedRow['id']]);

        $db->commit();
        
        audit_log('category_restored', 'category', $deletedRow['id'], [
            'name' => $name,
            'description' => $description
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Categoria riattivata con successo (esisteva precedentemente nel cestino)',
            'data' => ['id' => $deletedRow['id']]
        ]);
        exit;
    }

    // 3. Creazione normale
    $stmtInsert = $db->prepare("INSERT INTO key_categories (name, description, created_at) VALUES (?, ?, NOW())");
    $stmtInsert->execute([$name, $description]);
    $newId = $db->lastInsertId();

    $db->commit();
    
    audit_log('category_created', 'category', $newId, [
        'name' => $name,
        'description' => $description
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Categoria creata con successo',
        'data' => ['id' => $newId]
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Create category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante la creazione della categoria']);
}
