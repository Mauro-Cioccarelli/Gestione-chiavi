<?php
/**
 * Recupero Password
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

// Se già loggato, redirect a dashboard
if (is_logged_in()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$pageTitle = 'Recupero Password';
$hideSidebar = true;
$hideHeader = true;

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <i class="bi bi-key-fill" style="font-size: 2rem;"></i>
                    <h5 class="mb-0 mt-2">Recupero Password</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center mb-4">
                        Inserisci il tuo indirizzo email. Se sei registrato, 
                        riceverai le istruzioni per reimpostare la password.
                    </p>
                    
                    <div id="reset-result"></div>
                    
                    <form id="form-reset-password">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label form-label-required">
                                Indirizzo Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" name="email" id="email" 
                                       class="form-control" 
                                       placeholder="tua@email.it"
                                       required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Richiedi Reset Password
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="<?= APP_URL ?>/login.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Torna al Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/auth.js"></script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
