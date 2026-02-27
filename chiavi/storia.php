<?php
/**
 * Storia Chiave - Visualizza storico movimenti
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$pageTitle = 'Storico Chiave';

// Ottieni ID chiave
$keyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($keyId < 1) {
    $_SESSION['flash_error'] = 'Chiave non specificata';
    header('Location: index.php');
    exit;
}

$db = db();

// Ottieni dati chiave
$stmt = $db->prepare("
    SELECT 
        k.id,
        k.identifier,
        k.status,
        kc.name as category_name,
        k.created_at
    FROM `keys` k
    LEFT JOIN key_categories kc ON k.category_id = kc.id
    WHERE k.id = ? AND k.deleted_at IS NULL
");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    $_SESSION['flash_error'] = 'Chiave non trovata';
    header('Location: index.php');
    exit;
}

// Ottieni movimenti
$movements = get_key_movements($keyId, 200);

// Azioni per tipo movimento
$actionLabels = [
    'checkout' => ['label' => 'Consegnata', 'icon' => 'box-arrow-up', 'class' => 'warning', 'desc' => 'Consegnata a'],
    'checkin' => ['label' => 'Rientrata', 'icon' => 'box-arrow-in-down', 'class' => 'success', 'desc' => 'Rientrata da'],
    'create' => ['label' => 'Creata', 'icon' => 'plus-circle', 'class' => 'info', 'desc' => 'Chiave creata'],
    'update' => ['label' => 'Modificata', 'icon' => 'pencil', 'class' => 'secondary', 'desc' => 'Modificata'],
    'dismise' => ['label' => 'Dismessa', 'icon' => 'trash', 'class' => 'danger', 'desc' => 'Chiave dismessa']
];

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/chiavi/index.php">Chiavi</a></li>
            <li class="breadcrumb-item active">Storico</li>
        </ol>
    </nav>

    <!-- Info Chiave -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-key-fill me-2"></i>
                    <?= htmlspecialchars($key['identifier']) ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Categoria</label>
                            <div class="fw-semibold">
                                <i class="bi bi-folder me-1"></i>
                                <?= htmlspecialchars($key['category_name'] ?? 'N/A') ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Stato</label>
                            <div>
                                <?php
                                $statusLabels = [
                                    'available' => '<span class="badge bg-success">Disponibile</span>',
                                    'in_delivery' => '<span class="badge bg-warning text-dark">In consegna</span>',
                                    'dismised' => '<span class="badge bg-danger">Dismessa</span>'
                                ];
                                echo $statusLabels[$key['status']] ?? $key['status'];
                                ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">ID Chiave</label>
                            <div class="fw-semibold">#<?= $key['id'] ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Creata il</label>
                            <div class="fw-semibold"><?= format_datetime($key['created_at']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Torna all'inventario
                    </a>
                    <?php if ($key['status'] === 'available'): ?>
                        <button class="btn btn-warning btn-sm" onclick="openCheckout(<?= $key['id'] ?>, '<?= htmlspecialchars($key['identifier']) ?>')">
                            <i class="bi bi-box-arrow-up me-1"></i>Consegna
                        </button>
                    <?php elseif ($key['status'] === 'in_delivery'): ?>
                        <button class="btn btn-success btn-sm" onclick="openCheckin(<?= $key['id'] ?>, '<?= htmlspecialchars($key['identifier']) ?>')">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Rientro
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Azioni rapide -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <i class="bi bi-lightning-charge me-2"></i>Azioni Rapide
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($key['status'] === 'available'): ?>
                            <button class="btn btn-warning" onclick="openCheckout(<?= $key['id'] ?>, '<?= htmlspecialchars($key['identifier']) ?>')">
                                <i class="bi bi-box-arrow-up me-2"></i>Consegna Chiave
                            </button>
                        <?php elseif ($key['status'] === 'in_delivery'): ?>
                            <button class="btn btn-success" onclick="openCheckin(<?= $key['id'] ?>, '<?= htmlspecialchars($key['identifier']) ?>')">
                                <i class="bi bi-box-arrow-in-down me-2"></i>Registra Rientro
                            </button>
                        <?php endif; ?>
                        
                        <?php if (has_role(ROLE_ADMIN)): ?>
                            <hr>
                            <a href="modifica.php?id=<?= $key['id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-2"></i>Modifica Chiave
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Storico Movimenti -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Storico Movimenti
                </div>
                <div class="card-body">
                    <?php if (empty($movements)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Nessun movimento registrato</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($movements as $m): ?>
                                <?php 
                                $actionInfo = $actionLabels[$m['action']] ?? ['label' => $m['action'], 'icon' => 'circle', 'class' => 'secondary', 'desc' => ''];
                                ?>
                                <div class="timeline-item <?= $m['action'] ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-<?= $actionInfo['class'] ?> mb-1">
                                                <i class="bi bi-<?= $actionInfo['icon'] ?> me-1"></i>
                                                <?= $actionInfo['label'] ?>
                                            </span>
                                            
                                            <?php if ($m['recipient_name']): ?>
                                                <div class="mt-1">
                                                    <i class="bi bi-person me-1"></i>
                                                    <?= htmlspecialchars($m['recipient_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($m['notes']): ?>
                                                <div class="text-muted small mt-1">
                                                    <i class="bi bi-chat-left-text me-1"></i>
                                                    <?= nl2br(htmlspecialchars($m['notes'])) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="text-muted small mt-1">
                                                <i class="bi bi-person-circle me-1"></i>
                                                <?= htmlspecialchars($m['user_name'] ?? 'Sistema') ?>
                                            </div>
                                        </div>
                                        <div class="text-end text-muted small">
                                            <div><?= format_datetime($m['created_at']) ?></div>
                                            <div><?= format_time_ago($m['created_at']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Consegna (incluso da index.php) -->
<div class="modal fade" id="modalCheckout" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-checkout">
                <div class="modal-header">
                    <h5 class="modal-title">Consegna Chiave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="key_id" id="checkout-key-id">
                    <input type="hidden" name="action" value="checkout">
                    
                    <div class="mb-3">
                        <label class="form-label">Chiave</label>
                        <input type="text" class="form-control" id="checkout-key-name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkout-recipient" class="form-label">Ricevente</label>
                        <input type="text" name="recipient_name" id="checkout-recipient" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkout-notes" class="form-label">Note</label>
                        <textarea name="notes" id="checkout-notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">Consegna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rientro -->
<div class="modal fade" id="modalCheckin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-checkin">
                <div class="modal-header">
                    <h5 class="modal-title">Rientro Chiave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="key_id" id="checkin-key-id">
                    <input type="hidden" name="action" value="checkin">
                    
                    <div class="mb-3">
                        <label class="form-label">Chiave</label>
                        <input type="text" class="form-control" id="checkin-key-name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkin-notes" class="form-label">Note</label>
                        <textarea name="notes" id="checkin-notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Registra Rientro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/chiavi.js"></script>
<script>
// Override funzioni per questa pagina
function openCheckout(keyId, keyName) {
    document.getElementById('checkout-key-id').value = keyId;
    document.getElementById('checkout-key-name').value = keyName;
    new bootstrap.Modal(document.getElementById('modalCheckout')).show();
}

function openCheckin(keyId, keyName) {
    document.getElementById('checkin-key-id').value = keyId;
    document.getElementById('checkin-key-name').value = keyName;
    new bootstrap.Modal(document.getElementById('modalCheckin')).show();
}

// Gestione form checkout
document.getElementById('form-checkout')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    showLoading();
    fetchJSON(window.APP_URL + '/ajax/chiavi/checkout.php', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalCheckout')).hide();
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', data.error);
        }
    })
    .catch(err => showAlert('danger', err.message))
    .finally(() => hideLoading());
});

// Gestione form checkin
document.getElementById('form-checkin')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    showLoading();
    fetchJSON(window.APP_URL + '/ajax/chiavi/checkin.php', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalCheckin')).hide();
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', data.error);
        }
    })
    .catch(err => showAlert('danger', err.message))
    .finally(() => hideLoading());
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
