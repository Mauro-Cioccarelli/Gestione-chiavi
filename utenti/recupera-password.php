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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupero Password - <?= htmlspecialchars(APP_NAME) ?></title>

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
                <i class="bi bi-key-fill"></i>
                <h3 class="mb-1"><?= htmlspecialchars(APP_NAME) ?></h3>
                <small class="opacity-75">v<?= APP_VERSION ?></small>
            </div>

            <!-- Body -->
            <div class="login-body">
                <div id="reset-result"></div>

                <p class="text-muted text-center mb-4">
                    Inserisci il tuo username. Se sei registrato,
                    riceverai le istruzioni per reimpostare la password.
                </p>

                <form id="form-reset-password">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person me-1"></i>Username
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" name="username" id="username"
                                   class="form-control form-control-lg"
                                   placeholder="Il tuo username"
                                   required autofocus>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login btn-lg">
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
