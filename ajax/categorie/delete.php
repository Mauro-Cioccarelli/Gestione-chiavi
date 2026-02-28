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

    // Controlla se esistono chiavi "attive" associate
    // Cancellare è permesso SOLO se tutte le chiavi sono state dismesse ('deleted_at' non nullo)
    $stmt = $db->prepare("SELECT COUNT(*) as active_count FROM `keys` WHERE category_id = ? AND deleted_at IS NULL");
    $stmt->execute([$categoryId]);
    $row = $stmt->fetch();

    if ($row && $row['active_count'] > 0) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Non è possibile eliminare la categoria. Sono presenti ancora ' . $row['active_count'] . ' chiavi collegate e non dismesse.']);
        exit;
    }

    // Marca come eliminata (soft delete)
    $stmtDelete = $db->prepare("UPDATE key_categories SET deleted_at = NOW() WHERE id = ?");
    $stmtDelete->execute([$categoryId]);

    $db->commit();

    // Log audit
    audit_log('category_deleted', 'category', $categoryId, [
        'name' => $categoryName
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Categoria eliminata con successo'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'eliminazione della categoria']);
}
