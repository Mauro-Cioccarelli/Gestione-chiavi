<?php
/**
 * AJAX: Ottieni storico movimenti per chiave
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
header('Content-Type: application/json');

// Validazione
$keyId = isset($_GET['key_id']) ? (int)$_GET['key_id'] : 0;

if ($keyId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'ID chiave non valido']);
    exit;
}

// Verifica chiave esistente
$db = db();
$stmt = $db->prepare("SELECT id, identifier FROM keys WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    http_response_code(404);
    echo json_encode(['error' => 'Chiave non trovata']);
    exit;
}

// Ottieni movimenti
$movements = get_key_movements($keyId, 100);

// Formatta output
$formattedMovements = [];
$actionLabels = [
    'checkout' => ['label' => 'Consegnata', 'icon' => 'box-arrow-up', 'class' => 'warning'],
    'checkin' => ['label' => 'Rientrata', 'icon' => 'box-arrow-in-down', 'class' => 'success'],
    'create' => ['label' => 'Creata', 'icon' => 'plus-circle', 'class' => 'info'],
    'update' => ['label' => 'Modificata', 'icon' => 'pencil', 'class' => 'secondary'],
    'dismise' => ['label' => 'Dismessa', 'icon' => 'trash', 'class' => 'danger']
];

foreach ($movements as $m) {
    $actionInfo = $actionLabels[$m['action']] ?? ['label' => $m['action'], 'icon' => 'circle', 'class' => 'secondary'];
    
    $formattedMovements[] = [
        'id' => $m['id'],
        'action' => $m['action'],
        'action_label' => $actionInfo['label'],
        'action_icon' => $actionInfo['icon'],
        'action_class' => $actionInfo['class'],
        'recipient_name' => $m['recipient_name'],
        'notes' => $m['notes'],
        'user_name' => $m['user_name'] ?? 'Sistema',
        'created_at' => format_datetime($m['created_at']),
        'created_at_full' => $m['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'key' => [
        'id' => $key['id'],
        'identifier' => $key['identifier']
    ],
    'movements' => $formattedMovements
]);
