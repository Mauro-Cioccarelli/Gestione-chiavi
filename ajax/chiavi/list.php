<?php
/**
 * AJAX: Lista chiavi per Tabulator (remote pagination)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

$db = db();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$size = max(1, min(100, (int)($_GET['size'] ?? 20)));

// Sort
$sortField = 'k.id';
$sortDir = 'DESC';
if (!empty($_GET['sort']) && is_array($_GET['sort']) && !empty($_GET['sort'][0])) {
    $sortField = $_GET['sort'][0]['field'] ?? 'k.id';
    $sortDir = (strtoupper($_GET['sort'][0]['dir'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';
}
$allowedSort = ['k.id', 'k.identifier', 'kc.name', 'k.status', 'k.created_at'];
if (!in_array($sortField, $allowedSort)) {
    $sortField = 'k.id';
}

// Filters
$where = ['k.deleted_at IS NULL'];
$params = [];

// Extract Tabulator 6 remote filters
if (!empty($_GET['filter']) && is_array($_GET['filter'])) {
    foreach ($_GET['filter'] as $filter) {
        $field = $filter['field'] ?? '';
        $value = $filter['value'] ?? '';
        if ($field === 'search') {
            $_GET['search'] = $value;
        } elseif ($field === 'status') {
            $_GET['status'] = $value;
        } elseif ($field === 'category_id') {
            $_GET['category_id'] = $value;
        }
    }
}

// Search
$search = $_GET['search'] ?? '';
if ($search) {
    $search = sanitize_string($search);
    $where[] = "(k.identifier LIKE ? OR kc.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Status filter
$status = $_GET['status'] ?? '';
if ($status && in_array($status, ['available', 'in_delivery', 'dismised'])) {
    $where[] = "k.status = ?";
    $params[] = $status;
}

// Category filter
$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
if ($categoryId) {
    $where[] = "k.category_id = ?";
    $params[] = $categoryId;
}

$whereClause = implode(' AND ', $where);

// Count total
$countSql = "SELECT COUNT(*) as total FROM `keys` k LEFT JOIN key_categories kc ON k.category_id = kc.id WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$lastPage = max(1, ceil($total / $size));

// Fetch data
$offset = ($page - 1) * $size;
$sql = "
    SELECT
        k.id,
        k.identifier,
        kc.name as category_name,
        k.status,
        k.created_at,
        k.updated_at,
        CASE
            WHEN k.status = 'in_delivery' THEN (
                SELECT km.recipient_name
                FROM key_movements km
                WHERE km.key_id = k.id AND km.action = 'checkout'
                ORDER BY km.created_at DESC
                LIMIT 1
            )
            ELSE NULL
        END as recipient_name,
        CASE
            WHEN k.status = 'in_delivery' THEN (
                SELECT DATE_FORMAT(km.created_at, '%d/%m/%Y %H:%i')
                FROM key_movements km
                WHERE km.key_id = k.id AND km.action = 'checkout'
                ORDER BY km.created_at DESC
                LIMIT 1
            )
            ELSE NULL
        END as checkout_date
    FROM `keys` k
    LEFT JOIN key_categories kc ON k.category_id = kc.id
    WHERE $whereClause
    ORDER BY $sortField $sortDir
    LIMIT ? OFFSET ?
";

$params[] = $size;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Return in Tabulator remote pagination format
echo json_encode([
    'data' => $data,
    'last_page' => $lastPage
]);

