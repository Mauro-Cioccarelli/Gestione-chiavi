<?php
/**
 * Gestione Utenti
 * - Admin: può creare, modificare, eliminare
 * - Operator: sola visualizzazione
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

// Accesso consentito a operator e superiori
require_role(ROLE_OPERATOR);

$pageTitle = 'Gestione Utenti';
$extraJs = ['/assets/js/utenti.js'];

// Solo admin e god possono creare nuovi utenti
$canCreate = has_role(ROLE_ADMIN);

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-people me-2"></i><?= htmlspecialchars($pageTitle) ?>
                </h2>
                <?php if ($canCreate): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewUser">
                    <i class="bi bi-plus-lg me-1"></i> Nuovo Utente
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabella Utenti -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Filtri -->
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <input type="text" id="search-input" class="form-control" 
                                   placeholder="Cerca per username o email...">
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-secondary" id="btn-refresh" title="Aggiorna">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tabulator -->
                    <div id="users-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuovo Utente (solo admin) -->
<?php if ($canCreate): ?>
<div class="modal fade" id="modalNewUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-new-user">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>Nuovo Utente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="new-username" class="form-label form-label-required">Username</label>
                        <input type="text" name="username" id="new-username" class="form-control"
                               required maxlength="50" placeholder="Username">
                    </div>

                    <div class="mb-3">
                        <label for="new-email" class="form-label form-label-required">Email</label>
                        <input type="email" name="email" id="new-email" class="form-control"
                               required maxlength="255" placeholder="email@esempio.it">
                    </div>

                    <div class="mb-3">
                        <label for="new-password" class="form-label form-label-required">Password</label>
                        <input type="password" name="password" id="new-password" class="form-control"
                               required minlength="6" placeholder="Password iniziale">
                        <div class="form-text">La password dovrà essere cambiata al primo accesso</div>
                    </div>

                    <div class="mb-3">
                        <label for="new-role" class="form-label">Ruolo</label>
                        <select name="role" id="new-role" class="form-select">
                            <option value="operator">Operatore</option>
                            <option value="admin">Amministratore</option>
                            <?php if (has_role(ROLE_GOD)): ?>
                                <option value="god">God</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Modifica Utente (solo admin) -->
<?php if ($canCreate): ?>
<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-edit-user">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Modifica Utente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="edit-user-id">

                    <div class="mb-3">
                        <label for="edit-username" class="form-label">Username</label>
                        <input type="text" name="username" id="edit-username" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="edit-email" class="form-label form-label-required">Email</label>
                        <input type="email" name="email" id="edit-email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-role" class="form-label">Ruolo</label>
                        <select name="role" id="edit-role" class="form-select" <?= has_role(ROLE_GOD) ? '' : 'disabled' ?>>
                            <option value="operator">Operatore</option>
                            <option value="admin">Amministratore</option>
                            <?php if (has_role(ROLE_GOD)): ?>
                                <option value="god">God</option>
                            <?php endif; ?>
                        </select>
                        <?php if (!has_role(ROLE_GOD)): ?>
                            <div class="form-text">Solo un utente god può modificare i ruoli</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="force_password_change" id="edit-force-pw" class="form-check-input">
                        <label class="form-check-label" for="edit-force-pw">
                            Forza cambio password al prossimo accesso
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
echo "<script>
    window.USER_ROLE = '" . (current_role() ?? '') . "';
    window.CURRENT_USER_ID = " . (current_user_id() ?? 0) . ";
</script>";
include __DIR__ . '/../includes/layout/footer.php'; 
?>
