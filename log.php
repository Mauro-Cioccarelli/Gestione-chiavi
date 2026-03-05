<?php
/**
 * Log Audit (tutti i ruoli)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$pageTitle = 'Log Audit';
$extraJs = ['/assets/js/log.js'];

$db = db();

// Utenti per filtro
$users = $db->query("SELECT id, username FROM users WHERE deleted_at IS NULL ORDER BY username ASC")->fetchAll();

// Azioni disponibili
$actions = [
    // Autenticazione
    'login_success', 'login_failed', 'logout',
    'password_changed', 'password_reset_requested',
    // Utenti
    'user_created', 'user_updated', 'user_deleted',
    // Chiavi
    'create', 'update', 'dismise', 'checkout', 'checkin',
    // Categorie
    'category_created', 'category_updated', 'category_deleted', 'category_merged', 'category_restored'
];

include __DIR__ . '/includes/layout/header.php';
?>

<div class="container">
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
                    <form id="filter-form" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Utente</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">Tutti</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>">
                                        <?= htmlspecialchars($u['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Azione</label>
                            <select name="action" id="action" class="form-select">
                                <option value="">Tutte</option>
                                <?php foreach ($actions as $a): ?>
                                    <option value="<?= $a ?>">
                                        <?= htmlspecialchars($a) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Entità</label>
                            <select name="entity_type" id="entity_type" class="form-select">
                                <option value="">Tutte</option>
                                <option value="user">Utente</option>
                                <option value="key">Chiave</option>
                                <option value="category">Categoria</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dal</label>
                            <input type="date" name="from_date" id="from_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Al</label>
                            <input type="date" name="to_date" id="to_date" class="form-control">
                        </div>
                    </form>
                    <div class="row mt-3">
                        <div class="col-12 d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="btn-apply-filters">
                                <i class="bi bi-search me-1"></i>Applica Filtri
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-reset-filters">
                                <i class="bi bi-x-circle me-1"></i>Reset
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-auto" id="btn-refresh">
                                <i class="bi bi-arrow-clockwise me-1"></i>Aggiorna
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella Log -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-list-ul me-2"></i>Registro Audit
                </div>
                <div class="card-body p-0">
                    <div id="log-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
