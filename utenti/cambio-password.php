<?php
/**
 * Cambio Password Forzato
 *
 * Pagina per il cambio password obbligatorio al primo accesso
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio Password - <?= htmlspecialchars(APP_NAME) ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="/assets/bootstrap-icons-1.13.1/font/bootstrap-icons.min.css">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #e8f4f8 0%, #f5f7fa 100%);
        }

        .login-card {
            max-width: 500px;
            margin: auto;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #5a8de8 0%, #7c6fd9 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .login-body {
            padding: 2rem;
            background: white;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #5a8de8;
            box-shadow: 0 0 0 0.2rem rgba(90, 141, 232, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #5a8de8 0%, #7c6fd9 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .alert-info {
            background: linear-gradient(135deg, #e7f3ff 0%, #d1e7ff 100%);
            border: 1px solid #b3d9ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <h3 class="mb-1">Cambio Password Obbligatorio</h3>
                <small class="opacity-75">v<?= APP_VERSION ?></small>
            </div>

            <!-- Body -->
            <div class="login-body">
                <?php if ($force || $forceChange): ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        È necessario cambiare la password prima di continuare.
                        <?php if ($force): ?>
                            La password è stata resettata da un amministratore.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form id="form-change-password">
                    <div class="mb-3">
                        <label for="old_password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Password Attuale
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" name="old_password" id="old_password"
                                   class="form-control form-control-lg" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary password-toggle" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="bi bi-key me-1"></i>Nuova Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" name="new_password" id="new_password"
                                   class="form-control form-control-lg" required minlength="6"
                                   autocomplete="new-password" placeholder="Minimo 6 caratteri">
                            <button class="btn btn-outline-secondary password-toggle" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-check-circle me-1"></i>Conferma Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-check-circle"></i>
                            </span>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   class="form-control form-control-lg" required minlength="6"
                                   autocomplete="new-password" placeholder="Conferma la password">
                            <button class="btn btn-outline-secondary password-toggle" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login btn-lg">
                            <i class="bi bi-check-lg me-2"></i>Cambia Password e Continua
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <small class="text-muted d-block mb-2">
                        Dopo il cambio password verrai reindirizzato alla dashboard
                    </small>
                    <a href="<?= APP_URL ?>/logout.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-left me-1"></i>Esci e torna al login
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center py-3 bg-light">
                <small class="text-muted">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="/assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Configurazione globale
    window.APP_URL = '';

    // Funzione helper per fetch JSON
    function fetchJSON(url, options = {}) {
        const defaults = {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        };
        if (options.headers) {
            options.headers = Object.assign({}, defaults.headers, options.headers);
        }
        return fetch(url, Object.assign(defaults, options))
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('HTTP ' + response.status + ': ' + text);
                    });
                }
                return response.json();
            });
    }

    // Gestione form cambio password
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
                        // Mostra successo e redirect
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success';
                        alertDiv.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + data.message;
                        form.insertBefore(alertDiv, form.firstChild);

                        setTimeout(() => {
                            window.location.href = window.APP_URL + '/dashboard.php';
                        }, 1500);
                    } else {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger';
                        alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + data.error;
                        form.insertBefore(alertDiv, form.firstChild);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Errore: ' + err.message;
                    form.insertBefore(alertDiv, form.firstChild);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }

        // Toggle visibilità password
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.closest('.input-group').querySelector('input');
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    });
    </script>
</body>
</html>
