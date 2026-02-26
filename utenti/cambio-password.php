<?php
/**
 * Cambio Password Forzato
 * 
 * Pagina per il cambio password obbligatorio al primo accesso
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$pageTitle = 'Cambio Password';
$hideSidebar = true;

// Verifica se cambio password è forzato
$db = db();
$stmt = $db->prepare("SELECT force_password_change FROM users WHERE id = ?");
$stmt->execute([current_user_id()]);
$user = $stmt->fetch();

$force = isset($_GET['force']) && $_GET['force'] === '1';
$forceChange = $user['force_password_change'] ?? 0;

// Se non forzato e non è un accesso forzato, redirect a dashboard
if (!$force && !$forceChange) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Cambio Password Obbligatorio
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($force || $forceChange): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            È necessario cambiare la password prima di continuare.
                            <?php if ($force): ?>
                                La password è stata resettata da un amministratore.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="form-change-password">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="old_password" class="form-label">
                                Password Attuale
                            </label>
                            <div class="input-group">
                                <input type="password" name="old_password" id="old_password" 
                                       class="form-control" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary password-toggle" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label form-label-required">
                                Nuova Password
                            </label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password" 
                                       class="form-control" required minlength="6" 
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary password-toggle" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimo 6 caratteri</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label form-label-required">
                                Conferma Nuova Password
                            </label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" 
                                       class="form-control" required minlength="6" 
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary password-toggle" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg me-2"></i>Cambia Password e Continua
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('js/auth.js') ?>"></script>
<script>
// Override redirect dopo cambio password forzato
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-change-password');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Elaborazione...';
            
            fetchJSON(window.APP_URL + '/ajax/auth/change-password.php', {
                method: 'POST',
                body: formData
            })
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.href = window.APP_URL + '/dashboard.php';
                    }, 1500);
                } else {
                    showAlert('danger', data.error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                showAlert('danger', 'Errore: ' + err.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
