<?php
/**
 * Migrazioni - Gestione migrazioni database
 * 
 * Accessibile solo agli utenti con ruolo GOD.
 * Permette di:
 * - Visualizzare lo stato delle migrazioni
 * - Eseguire migrazioni pendenti
 * - Migrare dati dal vecchio database legacy
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

// Solo ruolo GOD
require_god();

$pageTitle = 'Gestione Migrazioni';
$db = db();

$errors = [];
$success = [];
$migrations = [];
$legacyConnected = false;
$legacyTables = [];

// Ottieni migrazioni già eseguite
$stmt = $db->query("SELECT migration, executed_at FROM migrations ORDER BY executed_at ASC");
$executedMigrations = $stmt->fetchAll();

// Ottieni file SQL disponibili
$migrationsPath = __DIR__;
$availableMigrations = glob($migrationsPath . '/0*.sql');
sort($availableMigrations);

// Calcola migrazioni pendenti
$pendingMigrations = [];
foreach ($availableMigrations as $file) {
    $migrationName = basename($file);
    $alreadyExecuted = false;
    
    foreach ($executedMigrations as $executed) {
        if ($executed['migration'] === $migrationName) {
            $alreadyExecuted = true;
            break;
        }
    }
    
    if (!$alreadyExecuted) {
        $pendingMigrations[] = [
            'file' => $migrationName,
            'path' => $file
        ];
    }
}

// Verifica connessione a database legacy
try {
    // Prova a connettersi al database legacy (stesse credenziali, DB diverso)
    $legacyDbName = 'chiavi_old';
    $db->exec("USE `$legacyDbName`");
    $legacyConnected = true;
    
    // Ottieni tabelle legacy
    $stmt = $db->query("SHOW TABLES");
    $legacyTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Torna al DB principale
    $db->exec("USE `" . DB_NAME . "`");
} catch (Exception $e) {
    $legacyConnected = false;
}

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'run_pending' && isset($_POST['confirm'])) {
        try {
            foreach ($pendingMigrations as $migration) {
                $sql = file_get_contents($migration['path']);
                $db->exec($sql);
                
                $stmt = $db->prepare("INSERT INTO migrations (migration, executed_at) VALUES (?, NOW())");
                $stmt->execute([$migration['file']]);
                
                $success[] = "Migrazione eseguita: {$migration['file']}";
            }
            
            if (empty($pendingMigrations)) {
                $success[] = 'Nessuna migrazione pendente';
            }
        } catch (Exception $e) {
            $errors[] = 'Errore migrazione: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'migrate_data' && isset($_POST['confirm'])) {
        try {
            // Migrazione dati da legacy
            $legacyDb = 'chiavi_old';
            $mainDb = DB_NAME;
            
            $db->exec("USE `$legacyDb`");
            
            // 1. Migrazione utenti (salvo admin già esistente)
            $stmt = $db->query("
                INSERT INTO `$mainDb`.users (id, username, email, password_hash, role, force_password_change, last_login, created_at)
                SELECT 
                    us_id,
                    us_name,
                    us_email,
                    us_pwd,
                    CASE WHEN us_level >= 2 THEN 'admin' ELSE 'operator' END,
                    us_pw_cng,
                    us_last_login,
                    NOW()
                FROM keys_users
                WHERE us_id > 1
                ON DUPLICATE KEY UPDATE username = VALUES(username)
            ");
            $usersMigrated = $stmt->rowCount();
            
            // 2. Migrazione categorie
            $stmt = $db->query("
                INSERT INTO `$mainDb`.key_categories (name, description, created_at)
                SELECT DISTINCT 
                    k_cat,
                    CONCAT('Categoria migrata da legacy - ', k_cat),
                    NOW()
                FROM keys_k
                WHERE k_cat != ''
                ON DUPLICATE KEY UPDATE name = VALUES(name)
            ");
            
            // 3. DEDUPLICAZIONE CHIAVI
            // Crea tabella temporanea per mappare vecchi ID -> nuovi ID
            $db->exec("
                CREATE TEMPORARY TABLE IF NOT EXISTS _key_id_mapping (
                    old_id INT,
                    new_id INT,
                    is_deduplicated TINYINT(1) DEFAULT 0,
                    kept_id INT DEFAULT NULL
                )
            ");
            $db->exec("TRUNCATE TABLE _key_id_mapping");
            
            // 3a. Identifica chiavi duplicate e determina quale mantenere
            // Priorità: NON DISMESSA (k_canc != 'c') > DISMESSA, poi ID più alto
            $stmt = $db->query("
                INSERT INTO _key_id_mapping (old_id, new_id, is_deduplicated, kept_id)
                SELECT
                    k.k_id AS old_id,
                    CASE
                        -- Questa è la chiave da mantenere?
                        WHEN k.k_id = (
                            SELECT k2.k_id
                            FROM keys_k k2
                            WHERE k2.k_cat = k.k_cat AND k2.k_name = k.k_name
                            ORDER BY
                                -- Priorità: non dismesse prima (k_canc != 'c')
                                CASE
                                    WHEN k2.k_canc IS NULL OR k2.k_canc != 'c' THEN 0
                                    ELSE 1
                                END ASC,
                                -- Poi ID più alto
                                k2.k_id DESC
                            LIMIT 1
                        ) THEN k.k_id  -- Questa è la vincitrice, new_id = old_id
                        ELSE NULL  -- Questa sarà deduplicata, new_id sarà aggiornato dopo
                    END AS new_id,
                    CASE
                        WHEN k.k_id = (
                            SELECT k2.k_id
                            FROM keys_k k2
                            WHERE k2.k_cat = k.k_cat AND k2.k_name = k.k_name
                            ORDER BY
                                CASE
                                    WHEN k2.k_canc IS NULL OR k2.k_canc != 'c' THEN 0
                                    ELSE 1
                                END ASC,
                                k2.k_id DESC
                            LIMIT 1
                        ) THEN 0
                        ELSE 1
                    END AS is_deduplicated,
                    (
                        SELECT k2.k_id
                        FROM keys_k k2
                        WHERE k2.k_cat = k.k_cat AND k2.k_name = k.k_name
                        ORDER BY
                            CASE
                                WHEN k2.k_canc IS NULL OR k2.k_canc != 'c' THEN 0
                                ELSE 1
                            END ASC,
                            k2.k_id DESC
                        LIMIT 1
                    ) AS kept_id
                FROM keys_k k
            ");
            
            // 3b. Aggiorna new_id per le chiavi deduplicate (puntano alla vincitrice)
            $stmt = $db->query("
                UPDATE _key_id_mapping m
                INNER JOIN _key_id_mapping winner ON m.kept_id = winner.old_id
                SET m.new_id = winner.new_id
                WHERE m.is_deduplicated = 1
            ");
            
            // 3c. Inserisci chiavi nel DB principale (solo vincitrici)
            $stmt = $db->query("
                INSERT INTO `$mainDb`.keys (id, category_id, identifier, status, created_at, deleted_at)
                SELECT DISTINCT
                    m.new_id,
                    kc.id,
                    k.k_name,
                    CASE 
                        WHEN k.k_canc = 'c' THEN 'dismised'
                        WHEN k.k_out IS NULL OR k.k_out = '0000-00-00 00:00:00' OR k.k_out = 0 THEN 'available'
                        ELSE 'in_delivery'
                    END,
                    NOW(),
                    CASE WHEN k.k_canc = 'c' THEN NOW() ELSE NULL END
                FROM `$legacyDb`.keys_k k
                INNER JOIN _key_id_mapping m ON k.k_id = m.old_id
                INNER JOIN `$mainDb`.key_categories kc ON k.k_cat = kc.name
                WHERE m.is_deduplicated = 0  -- Solo vincitrici
                ON DUPLICATE KEY UPDATE id = VALUES(id)
            ");
            $keysMigrated = $stmt->rowCount();
            
            // 3d. Conta quante chiavi sono state deduplicate
            $stmt = $db->query("SELECT COUNT(*) as dedup FROM _key_id_mapping WHERE is_deduplicated = 1");
            $deduplicatedCount = $stmt->fetch()['dedup'];
            
            // 4. Migrazione movimenti - con mapping ID deduplicati
            // I movimenti delle chiavi deduplicate puntano alla chiave vincitrice
            $stmt = $db->query("
                INSERT INTO `$mainDb`.key_movements (key_id, user_id, action, notes, created_at)
                SELECT
                    m.new_id AS key_id,  -- Usa nuovo ID (solo movimenti con chiave valida)
                    (SELECT id FROM `$mainDb`.users WHERE username = log.log_user LIMIT 1),
                    'update',
                    CONCAT('[MIGRAZIONE] ', log.log_action),
                    log.log_date
                FROM keys_log log
                INNER JOIN _key_id_mapping m ON log.log_kid = m.old_id
                WHERE m.new_id IS NOT NULL  -- Solo movimenti per chiavi che esistono nel nuovo DB
            ");

            // 5. Movimenti per chiavi in consegna - con mapping ID deduplicati
            $stmt = $db->query("
                INSERT INTO `$mainDb`.key_movements (key_id, user_id, action, recipient_name, notes, created_at)
                SELECT
                    m.new_id AS key_id,  -- Usa nuovo ID (solo movimenti con chiave valida)
                    (SELECT id FROM `$mainDb`.users WHERE username = k.k_cons_from LIMIT 1),
                    'checkout',
                    k.k_cons_to,
                    'Movimento migrato da legacy',
                    k.k_out
                FROM keys_k k
                INNER JOIN _key_id_mapping m ON k.k_id = m.old_id
                WHERE m.new_id IS NOT NULL  -- Solo chiavi che esistono nel nuovo DB
                  AND k.k_out IS NOT NULL AND k.k_out != '0000-00-00 00:00:00' AND k.k_out != 0
            ");

            // 6. Log audit per deduplicazione
            if ($deduplicatedCount > 0) {
                $stmt = $db->query("
                    INSERT INTO `$mainDb`.audit_log (user_id, action, entity_type, entity_id, details, message, created_at)
                    SELECT
                        1,  -- admin
                        'deduplication',
                        'key',
                        m.kept_id,
                        JSON_OBJECT('deduplicated_ids', GROUP_CONCAT(m.old_id)),
                        CONCAT('Chiave deduplicata durante migrazione. Mantenuto ID ', m.kept_id),
                        NOW()
                    FROM _key_id_mapping m
                    WHERE m.is_deduplicated = 1
                    GROUP BY m.kept_id
                ");
            }

            // Torna al DB principale
            $db->exec("USE `" . DB_NAME . "`");

            $dedupMessage = $deduplicatedCount > 0 
                ? "$usersMigrated utenti, $keysMigrated chiavi importate, $deduplicatedCount chiavi deduplicate"
                : "$usersMigrated utenti, $keysMigrated chiavi importate";
            $success[] = "Migrazione dati completata: " . $dedupMessage;
            
            // Registra migrazione dati
            $stmt = $db->prepare("INSERT INTO migrations (migration, executed_at) VALUES (?, NOW())");
            $stmt->execute(['data_migration_legacy']);
            
        } catch (Exception $e) {
            $db->exec("USE `" . DB_NAME . "`");
            $errors[] = 'Errore migrazione dati: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'reset_admin_password') {
        try {
            $newHash = password_hash('admin', PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, force_password_change = 1 WHERE id = 1");
            $stmt->execute([$newHash]);
            $success[] = 'Password admin resettata a "admin"';
        } catch (Exception $e) {
            $errors[] = 'Errore reset password: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-database-gear me-2"></i>Gestione Migrazioni</h2>
            <p class="text-muted">Gestisci le migrazioni del database e l'importazione dati legacy</p>
        </div>
    </div>
    
    <!-- Alert -->
    <?php if (!empty($success)): ?>
        <?php foreach ($success as $msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $msg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Migrazioni eseguite -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-check-circle me-2"></i>Migrazioni Eseguite
                </div>
                <div class="card-body">
                    <?php if (empty($executedMigrations)): ?>
                        <p class="text-muted text-center">Nessuna migrazione eseguita</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($executedMigrations as $m): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($m['migration']) ?></code></td>
                                        <td><?= format_datetime($m['executed_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Migrazioni pendenti -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-clock me-2"></i>Migrazioni Pendenti
                </div>
                <div class="card-body">
                    <?php if (empty($pendingMigrations)): ?>
                        <p class="text-success text-center"><i class="bi bi-check-circle-fill me-2"></i>Tutte le migrazioni sono state eseguite</p>
                    <?php else: ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($pendingMigrations as $m): ?>
                                <li class="list-group-item">
                                    <code><?= htmlspecialchars($m['file']) ?></code>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <form method="POST" class="text-center">
                            <input type="hidden" name="action" value="run_pending">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirm_pending" name="confirm" required>
                                <label class="form-check-label" for="confirm_pending">
                                    Confermo esecuzione migrazioni pendenti
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-play-fill me-2"></i>Esegui Migrazioni
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Migrazione dati legacy -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-database me-2"></i>Migrazione Dati Legacy</span>
                    <span class="badge bg-<?= $legacyConnected ? 'success' : 'danger' ?>">
                        <?= $legacyConnected ? 'Connesso' : 'Non connesso' ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($legacyConnected): ?>
                        <p class="text-muted">
                            Database legacy trovato: <strong>chiavi_old</strong><br>
                            Tabelle disponibili: <?= implode(', ', $legacyTables) ?>
                        </p>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Attenzione:</strong> Questa operazione importerà i dati dal database legacy.
                            Assicurati di aver eseguito un backup prima di procedere.
                        </div>
                        
                        <form method="POST" class="text-center">
                            <input type="hidden" name="action" value="migrate_data">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirm_legacy" name="confirm" required>
                                <label class="form-check-label" for="confirm_legacy">
                                    Confermo migrazione dati da chiavi_old
                                </label>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-right-circle me-2"></i>Migra Dati Legacy
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">
                            Il database legacy <strong>chiavi_old</strong> non è stato trovato o non è accessibile.<br>
                            Per migrare i dati, assicurati che il database legacy esista sullo stesso server.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Utility -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-tools me-2"></i>Utility
                </div>
                <div class="card-body">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="reset_admin_password">
                        <button type="submit" class="btn btn-outline-danger" 
                                onclick="return confirm('Resettare la password di admin a \'admin\'?')">
                            <i class="bi bi-key me-2"></i>Reset Password Admin
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
