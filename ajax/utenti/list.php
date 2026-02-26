<?php
/**
 * AJAX: Lista utenti per Tabulator
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
header('Content-Type: application/json');

// Parametri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['size']) ? max(1, min(100, (int)$_GET['size'])) : 20;
$search = sanitize_string($_GET['search'] ?? '');

$db = db();

// Query base
$where = "deleted_at IS NULL";
$params = [];

if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

// Count totale
$countSql = "SELECT COUNT(*) as total FROM users WHERE $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];

// Query dati
$offset = ($page - 1) * $pageSize;
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
    LIMIT ? OFFSET ?
";

$params[] = $pageSize;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

echo json_encode([
    'data' => $data,
    'last_page' => ceil($total / $pageSize),
    'total' => $total
]);
