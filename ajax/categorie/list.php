<?php
/**
 * AJAX: Lista categorie per Tabulator
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

$db = db();

// Paginazione Tabulator 6
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 50;
$offset = ($page - 1) * $size;

// Ordinamento
$sortField = 'name';
$sortDir = 'ASC';
if (!empty($_GET['sort']) && is_array($_GET['sort'])) {
    $sort = reset($_GET['sort']);
    $allowedSortFields = ['id', 'name', 'keys_count'];
    if (in_array($sort['field'], $allowedSortFields)) {
        $sortField = $sort['field'];
        $sortDir = strtoupper($sort['dir']) === 'DESC' ? 'DESC' : 'ASC';
    }
}

// Filtri Tabulator 6
$search = '';
if (!empty($_GET['filter']) && is_array($_GET['filter'])) {
    foreach ($_GET['filter'] as $filter) {
        $field = $filter['field'] ?? '';
        $value = $filter['value'] ?? '';
        if ($field === 'search') {
            $search = $value;
        }
    }
}

$whereClause = 'c.deleted_at IS NULL';
$params = [];

if ($search) {
    $whereClause .= ' AND c.name LIKE ?';
    $params[] = '%' . sanitize_string($search) . '%';
}

// Conta totali per paginazione
$countSql = "SELECT COUNT(*) as total FROM key_categories c WHERE $whereClause";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalRow = $stmtCount->fetch();
$totalRecords = $totalRow ? (int)$totalRow['total'] : 0;
$lastPage = ceil($totalRecords / $size);

// Query dati principali
// Esegue un LEFT JOIN ed un COUNT con le chiavi (criterio se le chiavi non sono state eliminate -> o anche se eliminate? "purchè non abbia chiavi")
// Calcoliamo le 'keys_count' contando tutte le chiavi (anche dismesse) per bloccare l'eliminazione categorie
$sql = "
    SELECT 
        c.id, 
        c.name, 
        c.description,
        (SELECT COUNT(*) FROM `keys` k WHERE k.category_id = c.id AND k.deleted_at IS NULL) as active_keys_count,
        (SELECT COUNT(*) FROM `keys` k WHERE k.category_id = c.id AND k.deleted_at IS NULL) as keys_count
    FROM key_categories c
    WHERE $whereClause
    ORDER BY $sortField $sortDir
    LIMIT ? OFFSET ?
";

$params[] = $size;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

echo json_encode([
    'data' => $data,
    'last_page' => $lastPage
]);
