<?php
/**
 * Conferma Reset Password (con token)
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

// Se già loggato, redirect a dashboard
if (is_logged_in()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$pageTitle = 'Nuova Password';
$hideSidebar = true;
$hideHeader = true;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token non fornito';
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <i class="bi bi-shield-lock-fill" style="font-size: 2rem;"></i>
                    <h5 class="mb-0 mt-2">Imposta Nuova Password</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($token)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error ?? 'Token non valido') ?>
                        </div>
                        <p class="text-muted text-center">
                            Il link di reset password non è valido o è scaduto.
                        </p>
                        <div class="text-center">
                            <a href="<?= APP_URL ?>/utenti/recupera-password.php" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Richiedi Nuovo Reset
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-4">
                            Inserisci la tua nuova password.
                        </p>

                        <div id="reset-result"></div>

                        <form id="form-reset-confirm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                            <div class="mb-3">
                                <label for="new_password" class="form-label form-label-required">
                                    Nuova Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" name="new_password" id="new_password"
                                           class="form-control" minlength="6"
                                           placeholder="Minimo 6 caratteri"
                                           required>
                                    <button type="button" class="btn btn-outline-secondary password-toggle">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label form-label-required">
                                    Conferma Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" name="confirm_password" id="confirm_password"
                                           class="form-control" minlength="6"
                                           placeholder="Conferma la password"
                                           required>
                                    <button type="button" class="btn btn-outline-secondary password-toggle">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Salva Nuova Password
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="<?= APP_URL ?>/login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>Torna al Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/auth.js"></script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
