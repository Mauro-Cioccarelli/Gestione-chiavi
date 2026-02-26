<?php
/**
 * AJAX: Lista chiavi per Tabulator (server-side)
 *
 * Parametri GET:
 *   - page: Numero pagina
 *   - size: Elementi per pagina
 *   - sort_field: Campo ordinamento
 *   - sort_dir: Direzione (ASC/DESC)
 *   - search: Termine ricerca
 *   - status: Filtro stato
 *   - category_id: Filtro categoria
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

// Parametri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['size']) ? max(1, min(100, (int)$_GET['size'])) : 20;

// Ordinamento (Tabulator invia array JSON)
$sortField = 'k.id';
$sortDir = 'DESC';
if (isset($_GET['sort']) && is_array($_GET['sort']) && count($_GET['sort']) > 0) {
    $sort = $_GET['sort'][0];
    if (isset($sort['field'])) {
        $sortField = $sort['field'];
    }
    if (isset($sort['dir'])) {
        $sortDir = $sort['dir'];
    }
}
// Whitelist campi ordinabili
$allowedSort = ['k.id', 'k.identifier', 'kc.name', 'k.status', 'k.created_at'];
if (!in_array($sortField, $allowedSort)) {
    $sortField = 'k.id';
}
$sortDir = $sortDir === 'ASC' ? 'ASC' : 'DESC';

// Ricerca e filtri
$search = sanitize_string($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;

// Filtri da Tabulator (array JSON)
if (isset($_GET['filter']) && is_array($_GET['filter'])) {
    foreach ($_GET['filter'] as $filter) {
        if (isset($filter['field']) && isset($filter['value'])) {
            switch ($filter['field']) {
                case 'search':
                    $search = sanitize_string($filter['value']);
                    break;
                case 'status':
                    $status = $filter['value'];
                    break;
                case 'category_id':
                    $categoryId = (int)$filter['value'];
                    break;
            }
        }
    }
}

$db = db();

// Query base
$where = ['k.deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[] = "(k.identifier LIKE ? OR kc.name LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

if ($status && in_array($status, ['available', 'in_delivery', 'dismised'])) {
    $where[] = "k.status = ?";
    $params[] = $status;
}

if ($categoryId) {
    $where[] = "k.category_id = ?";
    $params[] = $categoryId;
}

$whereClause = implode(' AND ', $where);

// Count totale
$countSql = "
    SELECT COUNT(*) as total
    FROM `keys` k
    LEFT JOIN key_categories kc ON k.category_id = kc.id
    WHERE $whereClause
";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];

// Query dati
$offset = ($page - 1) * $pageSize;
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

$params[] = $pageSize;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Output per Tabulator
echo json_encode([
    'data' => $data,
    'last_page' => ceil($total / $pageSize),
    'total' => $total
]);
