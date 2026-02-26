<?php
/**
 * Start - Installazione iniziale dell'applicazione
 * 
 * Questo file crea le tabelle del database e inserisce l'utente admin iniziale.
 * Dopo il primo utilizzo, viene creato il file .installed e l'accesso viene bloccato.
 * 
 * Per reinstallare: eliminare il file .installed nella root
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Verifica se già installato
if (is_installed()) {
    die('L\'applicazione è già installata. Per reinstallare, eliminare il file .installed nella root.');
}

$errors = [];
$steps = [];
$installed = false;

// Gestione form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']);
    
    if (!$confirm) {
        $errors[] = 'Devi confermare l\'installazione';
    } else {
        try {
            // Passo 1: Connetti al database (senza selezionare DB)
            $steps[] = 'Connessione al server MySQL...';
            $db = db_no_db();
            
            // Passo 2: Crea database se non esiste
            $steps[] = 'Creazione/verifica database...';
            $db->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db->exec("USE `" . DB_NAME . "`");
            
            // Passo 3: Esegui migrazioni
            $steps[] = 'Esecuzione migrazioni...';
            $migrationsPath = __DIR__;
            $migrationFiles = glob($migrationsPath . '/0*.sql');
            sort($migrationFiles);
            
            if (empty($migrationFiles)) {
                throw new Exception('Nessun file di migrazione trovato');
            }
            
            // Crea tabella migrations
            $db->exec("
                CREATE TABLE IF NOT EXISTS `migrations` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `migration` VARCHAR(255) NOT NULL,
                    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_migration` (`migration`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Esegui ogni migrazione
            foreach ($migrationFiles as $file) {
                $migrationName = basename($file);
                
                // Verifica se già eseguita
                $stmt = $db->prepare("SELECT id FROM migrations WHERE migration = ?");
                $stmt->execute([$migrationName]);
                
                if (!$stmt->fetch()) {
                    $steps[] = "Esecuzione: $migrationName";
                    $sql = file_get_contents($file);
                    $db->exec($sql);
                    
                    // Registra migrazione
                    $stmt = $db->prepare("INSERT INTO migrations (migration, executed_at) VALUES (?, NOW())");
                    $stmt->execute([$migrationName]);
                }
            }
            
            // Passo 4: Crea file .installed
            $steps[] = 'Creazione file installazione...';
            $installData = [
                'installed_at' => date('Y-m-d H:i:s'),
                'version' => APP_VERSION,
                'db_name' => DB_NAME
            ];
            file_put_contents(INSTALLED_FILE, json_encode($installData, JSON_PRETTY_PRINT));
            
            $installed = true;
            
        } catch (Exception $e) {
            $errors[] = 'Errore: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installazione - <?= htmlspecialchars(APP_NAME) ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/bootstrap-5.3.8-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="/assets/bootstrap-icons-1.13.1/font/bootstrap-icons.min.css">
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .install-card {
            max-width: 600px;
            margin: auto;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .install-body {
            padding: 2rem;
            background: white;
        }
        
        .step-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .step-item:last-child {
            border-bottom: none;
        }
        
        .step-success {
            color: #198754;
        }
        
        .step-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <!-- Header -->
            <div class="install-header">
                <i class="bi bi-gear-fill" style="font-size: 3rem;"></i>
                <h3 class="mb-1">Installazione</h3>
                <small class="opacity-75"><?= htmlspecialchars(APP_NAME) ?> v<?= APP_VERSION ?></small>
            </div>
            
            <!-- Body -->
            <div class="install-body">
                <?php if ($installed): ?>
                    <!-- Installazione completata -->
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-success">Installazione completata!</h4>
                        <p class="text-muted">
                            L'applicazione è stata installata correttamente.<br>
                            Puoi ora accedere con le seguenti credenziali:
                        </p>
                        
                        <div class="alert alert-info text-start mt-3">
                            <strong>Username:</strong> admin<br>
                            <strong>Password:</strong> admin<br>
                            <small class="text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Importante:</strong> La password verrà richiesta al primo accesso.
                            </small>
                        </div>
                        
                        <a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-lg mt-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Vai al Login
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- Form installazione -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($steps)): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="bi bi-list-task me-2"></i>Operazioni eseguite:
                            </div>
                            <div class="card-body">
                                <?php foreach ($steps as $step): ?>
                                    <div class="step-item step-success">
                                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($step) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Attenzione:</strong> Questa procedura creerà il database e le tabelle necessarie.
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Credenziali admin iniziali:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>admin</code><br>
                        <small>La password dovrà essere cambiata al primo accesso.</small>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                            <label class="form-check-label" for="confirm">
                                Confermo di voler procedere con l'installazione
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-play-fill me-2"></i>Installa
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="text-center py-3 bg-light rounded-bottom">
                <small class="text-muted">
                    <?= htmlspecialchars(APP_NAME) ?> v<?= APP_VERSION ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="/assets/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
