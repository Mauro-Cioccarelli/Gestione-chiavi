<?php
/**
 * Gestione Utenti - Visualizzazione per Operatori (sola lettura)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$pageTitle = 'Utenti';
$extraJs = ['/assets/js/utenti-operator.js'];

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-people me-2"></i><?= htmlspecialchars($pageTitle) ?>
            </h2>
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

<?php
echo "<script>
    window.USER_ROLE = '" . (current_role() ?? '') . "';
    window.CURRENT_USER_ID = " . (current_user_id() ?? 0) . ";
</script>";
include __DIR__ . '/../includes/layout/footer.php';
?>
