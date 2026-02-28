<?php
/**
 * AJAX: Ottieni dati utente per modifica
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
header('Content-Type: application/json');

$db = db();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'ID utente non valido']);
    exit;
}

$stmt = $db->prepare("
    SELECT id, username, email, role, force_password_change, last_login
    FROM users
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Utente non trovato']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => $user
]);
