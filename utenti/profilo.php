<?php
/**
 * Profilo Utente
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$pageTitle = 'Il mio Profilo';

$db = db();
$userId = current_user_id();

// Ottieni dati utente
$user = get_user_by_id($userId);

if (!$user) {
    $_SESSION['flash_error'] = 'Utente non trovato';
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

// Ottieni ultimi accessi dall'audit log
$stmt = $db->prepare("
    SELECT created_at, ip_address 
    FROM audit_log 
    WHERE user_id = ? AND action = 'login_success'
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recentLogins = $stmt->fetchAll();

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($pageTitle) ?>
            </h2>
        </div>
    </div>

    <div class="row">
        <!-- Info Profilo -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2"></i>Dati Profilo
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Username</label>
                        <div class="fw-semibold">
                            <i class="bi bi-person me-1"></i>
                            <?= htmlspecialchars($user['username']) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <div class="fw-semibold">
                            <i class="bi bi-envelope me-1"></i>
                            <?= htmlspecialchars($user['email']) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Ruolo</label>
                        <div>
                            <?php
                            $roleLabels = [
                                'operator' => '<span class="badge bg-secondary">Operatore</span>',
                                'admin' => '<span class="badge bg-primary">Amministratore</span>',
                                'god' => '<span class="badge bg-danger">God</span>'
                            ];
                            echo $roleLabels[$user['role']] ?? $user['role'];
                            ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Ultimo Accesso</label>
                        <div class="fw-semibold">
                            <i class="bi bi-clock me-1"></i>
                            <?= format_datetime($user['last_login']) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Account Creato</label>
                        <div class="fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= format_datetime($user['created_at']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cambio Password -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <i class="bi bi-key me-2"></i>Cambio Password
                </div>
                <div class="card-body">
                    <form id="form-change-password">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="old_password" class="form-label form-label-required">
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
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key-fill me-2"></i>Cambia Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Ultimi Accessi -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Ultimi Accessi
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentLogins)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2">Nessun accesso registrato</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Data e Ora</th>
                                        <th>Indirizzo IP</th>
                                        <th>Relativo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLogins as $login): ?>
                                        <tr>
                                            <td><?= format_datetime($login['created_at']) ?></td>
                                            <td><code><?= htmlspecialchars($login['ip_address'] ?? 'N/A') ?></code></td>
                                            <td><?= format_time_ago($login['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('js/auth.js') ?>"></script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
