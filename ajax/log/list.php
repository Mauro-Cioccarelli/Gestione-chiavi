<?php
/**
 * AJAX: Lista log per Tabulator (remote pagination)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

$db = db();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$size = max(1, min(100, (int)($_GET['size'] ?? 50)));

// Sort
$sortField = 'l.created_at';
$sortDir = 'DESC';
if (!empty($_GET['sort']) && is_array($_GET['sort']) && !empty($_GET['sort'][0])) {
    $sortField = $_GET['sort'][0]['field'] ?? 'l.created_at';
    $sortDir = (strtoupper($_GET['sort'][0]['dir'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';
}
$allowedSort = ['l.created_at', 'u.username', 'l.action', 'l.entity_type', 'l.ip_address'];
if (!in_array($sortField, $allowedSort)) {
    $sortField = 'l.created_at';
}

// Filters
$where = [];
$params = [];

// Extract Tabulator 6 remote filters
if (!empty($_GET['filter']) && is_array($_GET['filter'])) {
    foreach ($_GET['filter'] as $filter) {
        $field = $filter['field'] ?? '';
        $value = $filter['value'] ?? '';
        if ($field === 'user_id' && $value !== '') {
            $_GET['user_id'] = (int)$value;
        } elseif ($field === 'entity_type' && $value !== '') {
            $_GET['entity_type'] = $value;
        } elseif ($field === 'action' && $value !== '') {
            $_GET['action'] = $value;
        } elseif ($field === 'from_date' && $value !== '') {
            $_GET['from_date'] = $value;
        } elseif ($field === 'to_date' && $value !== '') {
            $_GET['to_date'] = $value;
        }
    }
}

// User filter
$userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
if ($userId) {
    $where[] = "l.user_id = ?";
    $params[] = $userId;
}

// Entity type filter
$entityType = $_GET['entity_type'] ?? '';
if ($entityType && in_array($entityType, ['user', 'key', 'category'])) {
    $where[] = "l.entity_type = ?";
    $params[] = $entityType;
}

// Action filter
$action = $_GET['action'] ?? '';
if ($action) {
    $where[] = "l.action = ?";
    $params[] = $action;
}

// Date filters
$fromDate = $_GET['from_date'] ?? '';
if ($fromDate) {
    $where[] = "l.created_at >= ?";
    $params[] = $fromDate . ' 00:00:00';
}

$toDate = $_GET['to_date'] ?? '';
if ($toDate) {
    $where[] = "l.created_at <= ?";
    $params[] = $toDate . ' 23:59:59';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSql = "SELECT COUNT(*) as total FROM audit_log l $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$lastPage = max(1, ceil($total / $size));

// Fetch data
$offset = ($page - 1) * $size;
$sql = "
    SELECT
        l.id,
        l.user_id,
        l.action,
        l.entity_type,
        l.entity_id,
        l.message,
        l.details,
        l.ip_address,
        l.created_at,
        u.username as user_name,
        CASE 
            WHEN l.action = 'user_deleted' AND l.entity_type = 'user' THEN (
                SELECT username FROM users WHERE id = l.entity_id
            )
            ELSE NULL
        END as deleted_user_name
    FROM audit_log l
    LEFT JOIN users u ON l.user_id = u.id
    $whereClause
    ORDER BY $sortField $sortDir
    LIMIT ? OFFSET ?
";

$params[] = $size;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Format data for display
foreach ($data as &$row) {
    $row['created_at_formatted'] = format_datetime($row['created_at']);
    $row['created_at_ago'] = format_time_ago($row['created_at']);
    
    // Unisci deleted_user_name nei details per user_deleted
    $details = $row['details'] ? json_decode($row['details'], true) : null;
    if ($row['action'] === 'user_deleted' && $row['deleted_user_name']) {
        if (!$details) {
            $details = [];
        }
        $details['deleted_user'] = $row['deleted_user_name'];
    }
    $row['details_decoded'] = $details;
}

// Return in Tabulator remote pagination format
echo json_encode([
    'data' => $data,
    'last_page' => $lastPage
]);
