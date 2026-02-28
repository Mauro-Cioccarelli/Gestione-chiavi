<?php
/**
 * AJAX: Lista utenti per Tabulator
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
header('Content-Type: application/json');

$db = db();

// Gli admin non-god non possono vedere gli utenti god
$where = "deleted_at IS NULL";
$params = [];

if (!has_role(ROLE_GOD)) {
    $where .= " AND role != 'god'";
}

// Query base: prendi tutti gli utenti non eliminati
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
$stmt->execute($params);
$data = $stmt->fetchAll();

// Restituisci direttamente l'array per il caricamento locale
echo json_encode($data);
