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

$token = $_GET['token'] ?? '';
$tokenValid = !empty($token);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuova Password - <?= htmlspecialchars(APP_NAME) ?></title>

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
            max-width: 420px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <i class="bi bi-shield-lock-fill"></i>
                <h3 class="mb-1"><?= htmlspecialchars(APP_NAME) ?></h3>
                <small class="opacity-75">v<?= APP_VERSION ?></small>
            </div>

            <!-- Body -->
            <div class="login-body">
                <?php if (!$tokenValid): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Token non valido o scaduto
                    </div>
                    <p class="text-muted text-center mb-4">
                        Il link di reset password non è valido o è scaduto.
                    </p>
                    <div class="d-grid">
                        <a href="<?= APP_URL ?>/utenti/recupera-password.php" class="btn btn-primary btn-login">
                            <i class="bi bi-arrow-clockwise me-2"></i>Richiedi Nuovo Reset
                        </a>
                    </div>
                <?php else: ?>
                    <div id="reset-result"></div>

                    <p class="text-muted text-center mb-4">
                        Inserisci la tua nuova password.
                    </p>

                    <form id="form-reset-confirm">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="bi bi-lock me-1"></i>Nuova Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="new_password" id="new_password"
                                       class="form-control form-control-lg" minlength="6"
                                       placeholder="Minimo 6 caratteri"
                                       required>
                                <button type="button" class="btn btn-outline-secondary password-toggle">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>Conferma Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" name="confirm_password" id="confirm_password"
                                       class="form-control form-control-lg" minlength="6"
                                       placeholder="Conferma la password"
                                       required>
                                <button type="button" class="btn btn-outline-secondary password-toggle">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-login btn-lg">
                                <i class="bi bi-check-lg me-2"></i>Salva Nuova Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <div class="text-center">
                    <a href="<?= APP_URL ?>/login.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Torna al Login
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

    <script src="/assets/js/auth.js"></script>
</body>
</html>
