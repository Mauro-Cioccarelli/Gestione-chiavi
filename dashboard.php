<?php
/**
 * Dashboard - Pagina principale dopo login
 */

define('APP_ROOT', true);
require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$pageTitle = 'Dashboard';

// Statistiche
$db = db();

// Totale chiavi
$stmt = $db->query("SELECT COUNT(*) as total FROM `keys` WHERE deleted_at IS NULL");
$totalKeys = $stmt->fetch()['total'];

// Chiavi disponibili
$stmt = $db->query("SELECT COUNT(*) as total FROM `keys` WHERE status = '" . KEY_AVAILABLE . "' AND deleted_at IS NULL");
$availableKeys = $stmt->fetch()['total'];

// Chiavi in consegna
$stmt = $db->query("SELECT COUNT(*) as total FROM `keys` WHERE status = '" . KEY_IN_DELIVERY . "' AND deleted_at IS NULL");
$inDeliveryKeys = $stmt->fetch()['total'];

// Chiavi dismesse
$stmt = $db->query("SELECT COUNT(*) as total FROM `keys` WHERE status = '" . KEY_DISMISED . "' AND deleted_at IS NULL");
$dismisedKeys = $stmt->fetch()['total'];

// Totale utenti
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
$totalUsers = $stmt->fetch()['total'];

// Ultimi movimenti
$stmt = $db->query("
    SELECT
        km.id,
        km.key_id,
        km.action,
        km.recipient_name,
        km.created_at,
        k.identifier as key_identifier,
        u.username
    FROM key_movements km
    LEFT JOIN `keys` k ON km.key_id = k.id
    LEFT JOIN users u ON km.user_id = u.id
    ORDER BY km.created_at DESC
    LIMIT 10
");
$recentMovements = $stmt->fetchAll();

// Azioni per tipo movimento
$actionLabels = [
    'checkout' => ['label' => 'Consegnata', 'icon' => 'box-arrow-up', 'class' => 'warning'],
    'checkin' => ['label' => 'Rientrata', 'icon' => 'box-arrow-in-down', 'class' => 'success'],
    'create' => ['label' => 'Creata', 'icon' => 'plus-circle', 'class' => 'info'],
    'update' => ['label' => 'Modificata', 'icon' => 'pencil', 'class' => 'secondary'],
    'dismise' => ['label' => 'Dismessa', 'icon' => 'trash', 'class' => 'danger']
];

include __DIR__ . '/includes/layout/header.php';
?>

<div class="container">
    <!-- Stats Row -->
    <div class="row mb-3">
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-primary card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= $totalKeys ?></div>
                            <div class="stat-label">Totale Chiavi</div>
                        </div>
                        <i class="bi bi-key-fill text-primary" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="<?= APP_URL ?>/chiavi/index.php" class="text-decoration-none small">
                        Vedi tutte <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-success card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= $availableKeys ?></div>
                            <div class="stat-label">Disponibili</div>
                        </div>
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="<?= APP_URL ?>/chiavi/index.php?status=available" class="text-decoration-none small">
                        Vedi tutte <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-warning card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= $inDeliveryKeys ?></div>
                            <div class="stat-label">In Consegna</div>
                        </div>
                        <i class="bi bi-box-arrow-up text-warning" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="<?= APP_URL ?>/chiavi/index.php?status=in_delivery" class="text-decoration-none small">
                        Vedi tutte <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-danger card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?= $totalUsers ?></div>
                            <div class="stat-label">Utenti</div>
                        </div>
                        <i class="bi bi-people-fill text-danger" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    </div>
                </div>
                <?php if (has_role(ROLE_ADMIN)): ?>
                <div class="card-footer bg-white border-0">
                    <a href="<?= APP_URL ?>/utenti/index.php" class="text-decoration-none small">
                        Gestisci <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <?php elseif (has_role(ROLE_OPERATOR)): ?>
                <div class="card-footer bg-white border-0">
                    <a href="<?= APP_URL ?>/utenti/index.php" class="text-decoration-none small">
                        Visualizza <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Recent Movements -->
        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Ultimi Movimenti</span>
                    <a href="<?= APP_URL ?>/log.php" class="btn btn-sm btn-outline-primary" <?= !has_role(ROLE_ADMIN) && !has_role(ROLE_OPERATOR) ? 'disabled' : '' ?>>
                        Vedi tutti
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentMovements)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Nessun movimento registrato</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Azione</th>
                                        <th>Chiave</th>
                                        <th>Utente</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentMovements as $movement): ?>
                                        <?php 
                                        $actionInfo = $actionLabels[$movement['action']] ?? ['label' => $movement['action'], 'icon' => 'circle', 'class' => 'secondary'];
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= $actionInfo['class'] ?>">
                                                    <i class="bi bi-<?= $actionInfo['icon'] ?> me-1"></i>
                                                    <?= $actionInfo['label'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= APP_URL ?>/chiavi/storia.php?id=<?= $movement['key_id'] ?>">
                                                    <?= htmlspecialchars($movement['key_identifier'] ?? 'N/A') ?>
                                                </a>
                                                <?php if ($movement['recipient_name']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="bi bi-person"></i> <?= htmlspecialchars($movement['recipient_name']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($movement['username'] ?? 'Sistema') ?></td>
                                            <td><?= format_datetime($movement['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-lightning-charge me-2"></i>Azioni Rapide
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= APP_URL ?>/chiavi/index.php?action=new" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Nuova Chiave
                        </a>
                        <a href="<?= APP_URL ?>/chiavi/index.php" class="btn btn-outline-primary">
                            <i class="bi bi-key me-2"></i>Gestione Chiavi
                        </a>
                        <?php if (has_role(ROLE_ADMIN)): ?>
                            <a href="<?= APP_URL ?>/utenti/index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-people me-2"></i>Gestione Utenti
                            </a>
                        <?php endif; ?>
                        <a href="<?= APP_URL ?>/utenti/profilo.php" class="btn btn-outline-secondary">
                            <i class="bi bi-person-gear me-2"></i>Il mio Profilo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
