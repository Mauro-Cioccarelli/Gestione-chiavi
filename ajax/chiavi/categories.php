<?php
/**
 * AJAX: Lista categorie per select
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

$db = db();

$stmt = $db->query("
    SELECT id, name, description 
    FROM key_categories 
    WHERE deleted_at IS NULL 
    ORDER BY name ASC
");

$categories = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => $categories
]);
