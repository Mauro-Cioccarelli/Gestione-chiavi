<?php
/**
 * Login page
 * 
 * Unica pagina pubblica dell'applicazione
 */

define('APP_ROOT', true);
require_once __DIR__ . '/includes/bootstrap.php';

// Verifica se già installato, altrimenti redirect a setup
if (!is_installed()) {
    header('Location: migrations/start.php');
    exit;
}

// Se già loggato, redirect a dashboard
if (is_logged_in()) {
    $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/dashboard.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

$error = '';

// Gestione form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if ($username && $password) {
        $result = authenticate($username, $password);
        
        if ($result['success']) {
            // Imposta cookie "ricordami" se richiesto (opzionale)
            if ($remember) {
                setcookie(
                    'remember_username',
                    $username,
                    time() + (30 * 24 * 60 * 60), // 30 giorni
                    APP_URL . '/',
                    '',
                    is_https(),
                    true
                );
            }
            
            // Redirect per cambio password forzato
            if ($result['force_password_change']) {
                session_write_close();
                header('Location: ' . APP_URL . '/utenti/cambio-password.php?force=1');
                exit;
            }

            // Redirect a dashboard o pagina originale
            $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/dashboard.php';
            unset($_SESSION['redirect_after_login']);
            session_write_close();
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['error'];
        }
    } else {
        $error = 'Inserisci username e password';
    }
}

// Recupera username da cookie se presente
$rememberedUsername = $_COOKIE['remember_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars(APP_NAME) ?></title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/assets/images/favicon/site.webmanifest">
    <link rel="shortcut icon" href="/assets/images/favicon/favicon.ico">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="/assets/bootstrap-icons-1.13.1/bootstrap-icons.min.css">
    
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
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="login-form">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person me-1"></i>Username
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="username" 
                               name="username" 
                               value="<?= htmlspecialchars($rememberedUsername) ?>"
                               required 
                               autofocus 
                               autocomplete="username"
                               placeholder="Inserisci username">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Password
                        </label>
                        <input type="password" 
                               class="form-control form-control-lg" 
                               id="password" 
                               name="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="Inserisci password">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="remember" 
                               name="remember"
                               <?= $rememberedUsername ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">
                            Ricorda username
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Accedi
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?= APP_URL ?>/utenti/recupera-password.php" class="text-decoration-none">
                        Password dimenticata?
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
        // Auto-focus username se vuoto
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (!usernameField.value) {
                usernameField.focus();
            }
        });
    </script>
</body>
</html>
