<?php
/**
 * Gestione Chiavi - Categorie
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

// Admin e god possono modificare, operatori hanno accesso completo comunque
$canEdit = true;

$pageTitle = 'Gestione Categorie';
$extraJs = ['/assets/js/categorie.js'];

// Ottieni categorie per le select del modulo fusione (sarà anche fatto via API o qui)
$db = db();
$categories = $db->query("SELECT id, name FROM key_categories WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-folder-fill me-2"></i><?= htmlspecialchars($pageTitle) ?>
                </h2>
                <?php if ($canEdit): ?>
                <div class="d-flex gap-2">
                    <button class="btn btn-warning opacity-50" id="btn-merge" disabled title="Seleziona almeno una riga per unire">
                        <i class="bi bi-intersect me-1"></i> Unisci
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewCategory">
                        <i class="bi bi-plus-lg me-1"></i> Nuova Categoria
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtri e Tabella -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Filtri -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="search-input" class="form-label visually-hidden">Cerca</label>
                            <input type="text" id="search-input" class="form-control" 
                                   placeholder="Cerca per nome categoria...">
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-outline-secondary" id="btn-refresh" title="Aggiorna">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tabulator -->
                    <div id="categories-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuova Categoria -->
<div class="modal fade" id="modalNewCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-new-category">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Nuova Categoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="new-name" class="form-label form-label-required">Nome Categoria</label>
                        <input type="text" name="name" id="new-name" class="form-control" 
                               placeholder="Es: Condominio Roma 1" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="new-description" class="form-label">Descrizione (opzionale)</label>
                        <textarea name="description" id="new-description" class="form-control" rows="2" maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Crea
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Categoria -->
<div class="modal fade" id="modalEditCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-edit-category">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Modifica Categoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label form-label-required">Nome Categoria</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="edit-description" class="form-label">Descrizione (opzionale)</label>
                        <textarea name="description" id="edit-description" class="form-control" rows="2" maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Unione Categorie -->
<div class="modal fade" id="modalMergeCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-merge-category">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark">
                        <i class="bi bi-intersect me-2"></i>Unisci Categorie
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <p class="text-muted small mb-3">
                        Le chiavi delle categorie sorgenti verranno spostate nella categoria "di Destinazione" e le categorie "Sorgenti" verranno subito eliminate.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Categorie Sorgenti Selezionate (da eliminare):</label>
                        <input type="hidden" name="source_ids_csv" id="merge-source-ids">
                        <ul class="list-group" id="merge-source-list" style="max-height: 150px; overflow-y: auto;">
                            <!-- Popolato traminte categorie.js -->
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="merge-target" class="form-label form-label-required">Categoria di Destinazione (quella che rimarrà)</label>
                        <select name="target_id" id="merge-target" class="form-select" required>
                            <!-- Popolato dinamicamente da Js -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="bi bi-exclamation-triangle me-1"></i>Conferma Unione
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Elenco Chiavi della Categoria -->
<div class="modal fade" id="modalCategoryKeys" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-key me-2"></i>Chiavi in <span id="category-keys-title" class="fw-bold">...</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="category-keys-loading" class="text-center my-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Caricamento chiavi in corso...</div>
                </div>
                <div id="category-keys-empty" class="text-center my-4 text-muted d-none">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2">Questa categoria non contiene alcuna chiave.</p>
                </div>
                <ul class="list-group" id="category-keys-list">
                    <!-- Popolato da JS -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<?php
echo "<script>
    window.USER_ROLE = '" . (current_role() ?? '') . "';
    window.CAN_EDIT_CATEGORIES = " . ($canEdit ? 'true' : 'false') . ";
</script>";
include __DIR__ . '/../includes/layout/footer.php';
?>
