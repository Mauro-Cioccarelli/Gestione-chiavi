<?php
/**
 * AJAX: Modifica categoria
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
if (!has_role(ROLE_ADMIN)) {
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
    // Controllo che non esista un'altra con lo stesso nome
    $stmt = $db->prepare("SELECT id FROM key_categories WHERE LOWER(name) = LOWER(?) AND id != ? AND deleted_at IS NULL");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Esiste già un\'altra Categoria in uso con questo nome.']);
        exit;
    }

    $stmtUpdate = $db->prepare("UPDATE key_categories SET name = ?, description = ? WHERE id = ?");
    $stmtUpdate->execute([$name, $description, $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Categoria aggiornata con successo'
    ]);

} catch (Exception $e) {
    error_log("Update category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'aggiornamento della categoria']);
}
