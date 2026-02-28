<?php
/**
 * AJAX: Lista utenti per Tabulator (operatori - sola lettura)
 * Gli operatori non vedono gli utenti god
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

$db = db();

// Gli operatori non possono vedere gli utenti god
$where = "deleted_at IS NULL AND role != 'god'";

// Query base
$sql = "
    SELECT
        id,
        username,
        email,
        role,
        force_password_change,
        last_login,
        created_at
    FROM users
    WHERE $where
    ORDER BY username ASC
";

$stmt = $db->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll();

// Restituisci direttamente l'array per il caricamento locale
echo json_encode($data);
