<?php
/**
 * Audit logging - Registra tutte le operazioni significative
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Registra operazione nel log audit
 * 
 * @param string $action Tipo di azione (es: 'login_success', 'key_created')
 * @param string|null $entityType Tipo di entità (es: 'user', 'key', 'category')
 * @param int|null $entityId ID dell'entità
 * @param array|null $details Dettagli aggiuntivi (verranno serializzati in JSON)
 * @param string|null $message Messaggio descrittivo opzionale
 */
function audit_log(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $details = null,
    ?string $message = null
): void {
    $db = db();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address, user_agent, details, message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            current_user_id(),
            $action,
            $entityType,
            $entityId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            $message
        ]);
    } catch (PDOException $e) {
        // Non bloccare l'esecuzione se il logging fallisce
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Registra movimento chiave
 *
 * @param int $keyId ID chiave
 * @param string $action Tipo di movimento (checkout, checkin, create, update, dismise)
 * @param int|null $recipientId ID utente ricevente (per checkout)
 * @param string|null $recipientName Nome ricevente (se non utente registrato)
 * @param string|null $notes Note aggiuntive
 * @return int ID del movimento inserito
 */
function log_key_movement(
    int $keyId,
    string $action,
    ?int $recipientId = null,
    ?string $recipientName = null,
    ?string $notes = null
): int {
    $db = db();

    try {
        $stmt = $db->prepare("
            INSERT INTO key_movements (key_id, user_id, action, recipient_id, recipient_name, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $keyId,
            current_user_id(),
            $action,
            $recipientId,
            $recipientName,
            $notes
        ]);

        $movementId = (int)$db->lastInsertId();

        // Prepara dettagli più esplicativi per audit
        $details = ['movement_id' => $movementId];
        
        if ($action === 'checkout') {
            $details['Consegnato a'] = $recipientName ?: 'N/A';
            if ($recipientId) {
                $details['ID Ricevente'] = $recipientId;
            }
            if ($notes) {
                $details['Note'] = $notes;
            }
        } elseif ($action === 'checkin') {
            $details['Stato'] = 'Rientrata';
            if ($notes) {
                $details['Note'] = $notes;
            }
        } elseif ($notes) {
            $details['Note'] = $notes;
        }

        // Log audit correlato
        audit_log($action, 'key', $keyId, $details);

        return $movementId;

    } catch (PDOException $e) {
        error_log("Key movement log error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Ottieni storico movimenti per chiave
 * 
 * @param int $keyId ID chiave
 * @param int $limit Limite risultati
 * @return array Lista movimenti
 */
function get_key_movements(int $keyId, int $limit = 100): array {
    $db = db();
    
    $stmt = $db->prepare("
        SELECT 
            km.id,
            km.action,
            km.recipient_id,
            km.recipient_name,
            km.notes,
            km.created_at,
            u.username as user_name,
            r.username as recipient_username
        FROM key_movements km
        LEFT JOIN users u ON km.user_id = u.id
        LEFT JOIN users r ON km.recipient_id = r.id
        WHERE km.key_id = ?
        ORDER BY km.created_at DESC, km.id DESC
        LIMIT ?
    ");
    
    $stmt->execute([$keyId, $limit]);
    
    return $stmt->fetchAll();
}

/**
 * Ottieni log audit con filtri
 * 
 * @param array $filters Filtri opzionali ['user_id', 'entity_type', 'entity_id', 'action', 'from_date', 'to_date']
 * @param int $limit Limite risultati
 * @param int $offset Offset per pagination
 * @return array ['data' => array, 'total' => int]
 */
function get_audit_log(array $filters = [], int $limit = 50, int $offset = 0): array {
    $db = db();
    
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['user_id'])) {
        $where[] = "al.user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['entity_type'])) {
        $where[] = "al.entity_type = ?";
        $params[] = $filters['entity_type'];
    }
    
    if (isset($filters['entity_id'])) {
        $where[] = "al.entity_id = ?";
        $params[] = $filters['entity_id'];
    }
    
    if (!empty($filters['action'])) {
        $where[] = "al.action = ?";
        $params[] = $filters['action'];
    }
    
    if (!empty($filters['from_date'])) {
        $where[] = "al.created_at >= ?";
        $params[] = $filters['from_date'];
    }
    
    if (!empty($filters['to_date'])) {
        $where[] = "al.created_at <= ?";
        $params[] = $filters['to_date'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Count totale
    $countSql = "SELECT COUNT(*) as total FROM audit_log al WHERE $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Dati
    $sql = "
        SELECT 
            al.id,
            al.action,
            al.entity_type,
            al.entity_id,
            al.message,
            al.details,
            al.ip_address,
            al.created_at,
            u.username as user_name
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE $whereClause
        ORDER BY al.created_at DESC, al.id DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    // Decodifica dettagli JSON
    foreach ($data as &$row) {
        if ($row['details']) {
            $row['details'] = json_decode($row['details'], true);
        }
    }
    
    return [
        'data' => $data,
        'total' => $total
    ];
}
