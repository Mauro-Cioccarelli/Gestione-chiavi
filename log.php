<?php
/**
 * Log Audit (solo admin)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Log Audit';

$db = db();

// Filtri
$filters = [];
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$entityType = isset($_GET['entity_type']) ? sanitize_string($_GET['entity_type']) : null;
$action = isset($_GET['action']) ? sanitize_string($_GET['action']) : null;
$fromDate = isset($_GET['from_date']) ? sanitize_string($_GET['from_date']) : null;
$toDate = isset($_GET['to_date']) ? sanitize_string($_GET['to_date']) : null;

if ($userId) $filters['user_id'] = $userId;
if ($entityType) $filters['entity_type'] = $entityType;
if ($action) $filters['action'] = $action;
if ($fromDate) $filters['from_date'] = $fromDate . ' 00:00:00';
if ($toDate) $filters['to_date'] = $toDate . ' 23:59:59';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 50;
$offset = ($page - 1) * $pageSize;

// Ottieni log
$logData = get_audit_log($filters, $pageSize, $offset);
$totalPages = ceil($logData['total'] / $pageSize);

// Utenti per filtro
$users = $db->query("SELECT id, username FROM users WHERE deleted_at IS NULL ORDER BY username ASC")->fetchAll();

// Azioni disponibili
$actions = ['login_success', 'login_failed', 'logout', 'user_created', 'user_updated', 'user_deleted', 
            'password_changed', 'password_reset_requested', 'checkout', 'checkin', 'create', 'update', 'dismise'];

include __DIR__ . '/includes/layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($pageTitle) ?>
            </h2>
            <p class="text-muted">Registro completo di tutte le operazioni nel sistema</p>
        </div>
    </div>

    <!-- Filtri -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-funnel me-2"></i>Filtri
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Utente</label>
                            <select name="user_id" class="form-select">
                                <option value="">Tutti</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Azione</label>
                            <select name="action" class="form-select">
                                <option value="">Tutte</option>
                                <?php foreach ($actions as $a): ?>
                                    <option value="<?= $a ?>" <?= $action === $a ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Entità</label>
                            <select name="entity_type" class="form-select">
                                <option value="">Tutte</option>
                                <option value="user" <?= $entityType === 'user' ? 'selected' : '' ?>>Utente</option>
                                <option value="key" <?= $entityType === 'key' ? 'selected' : '' ?>>Chiave</option>
                                <option value="category" <?= $entityType === 'category' ? 'selected' : '' ?>>Categoria</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dal</label>
                            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Al</label>
                            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella Log -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-list-ul me-2"></i>
                        Registri trovati: <strong><?= $logData['total'] ?></strong>
                    </span>
                    <a href="?<?= http_build_query(array_filter($_GET)) ?>&export=csv" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($logData['data'])): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Nessun registro trovato con i filtri selezionati</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Data/Ora</th>
                                        <th>Utente</th>
                                        <th>Azione</th>
                                        <th>Entità</th>
                                        <th>Dettagli</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logData['data'] as $log): ?>
                                        <tr>
                                            <td>
                                                <?= format_datetime($log['created_at']) ?>
                                                <br>
                                                <small class="text-muted"><?= format_time_ago($log['created_at']) ?></small>
                                            </td>
                                            <td>
                                                <?= $log['user_name'] ? '<i class="bi bi-person me-1"></i>' . htmlspecialchars($log['user_name']) : '<span class="text-muted">Sistema</span>' ?>
                                            </td>
                                            <td>
                                                <?php
                                                $actionClass = match($log['action']) {
                                                    'login_success', 'checkin', 'user_created' => 'success',
                                                    'login_failed', 'checkout' => 'warning',
                                                    'logout', 'user_deleted', 'dismise' => 'danger',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $actionClass ?>">
                                                    <?= htmlspecialchars($log['action']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['entity_type'] && $log['entity_id']): ?>
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($log['entity_type']) ?> #<?= $log['entity_id'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['message']): ?>
                                                    <?= htmlspecialchars($log['message']) ?>
                                                <?php elseif ($log['details']): ?>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $details = is_string($log['details']) ? json_decode($log['details'], true) : $log['details'];
                                                        if (is_array($details)):
                                                            foreach (array_slice($details, 0, 3) as $k => $v):
                                                                echo "<div><strong>$k:</strong> " . htmlspecialchars(is_array($v) ? json_encode($v) : $v) . "</div>";
                                                            endforeach;
                                                        endif;
                                                        ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></code>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Pagination">
                        <ul class="pagination mb-0 justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i> Prec
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    Succ <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
