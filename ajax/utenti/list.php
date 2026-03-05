<?php
/**
 * AJAX: Lista utenti per Tabulator
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

// Accesso consentito a operator e superiori
require_role(ROLE_OPERATOR);

header('Content-Type: application/json');

$db = db();

// Filtro per utenti god
$where = "1=1";
$params = [];

if (!has_role(ROLE_GOD)) {
    $where .= " AND role != 'god'";
}

// Query base: prendi tutti gli utenti (anche eliminati)
$sql = "
    SELECT
        id,
        username,
        email,
        role,
        force_password_change,
        last_login,
        created_at,
        deleted_at
    FROM users
    WHERE $where
    ORDER BY deleted_at DESC, username ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Restituisci direttamente l'array per il caricamento locale
echo json_encode($data);
