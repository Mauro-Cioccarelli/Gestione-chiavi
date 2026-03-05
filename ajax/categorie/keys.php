<?php
/**
 * AJAX: Lista sommaria delle chiavi per una categoria
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
if (!has_role(ROLE_OPERATOR)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permessi insufficienti']);
    exit;
}

header('Content-Type: application/json');

$db = db();
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID categoria non valido']);
    exit;
}

try {
    // Ottieni tutte le chiavi (incluse quelle dismesse se presenti) della categoria
    $stmt = $db->prepare("
        SELECT id, identifier, status, deleted_at 
        FROM `keys` 
        WHERE category_id = ? AND deleted_at IS NULL 
        ORDER BY deleted_at ASC, identifier ASC
    ");
    $stmt->execute([$categoryId]);
    $keys = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $keys
    ]);

} catch (Exception $e) {
    error_log("Load category keys error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel caricamento delle chiavi']);
}
