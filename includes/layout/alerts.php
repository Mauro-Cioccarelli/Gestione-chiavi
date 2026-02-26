<?php
/**
 * Layout - Flash messages / Alerts
 * 
 * Mostra messaggi flash da sessione:
 *   - flash_success
 *   - flash_error
 *   - flash_warning
 *   - flash_info
 */

// Prevenire accesso diretto
if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit('Accesso diretto non consentito');
}

// Raccogli tutti i messaggi
$messages = [];

if (isset($_SESSION['flash_success'])) {
    $messages[] = ['type' => 'success', 'message' => $_SESSION['flash_success']];
    unset($_SESSION['flash_success']);
}

if (isset($_SESSION['flash_error'])) {
    $messages[] = ['type' => 'danger', 'message' => $_SESSION['flash_error']];
    unset($_SESSION['flash_error']);
}

if (isset($_SESSION['flash_warning'])) {
    $messages[] = ['type' => 'warning', 'message' => $_SESSION['flash_warning']];
    unset($_SESSION['flash_warning']);
}

if (isset($_SESSION['flash_info'])) {
    $messages[] = ['type' => 'info', 'message' => $_SESSION['flash_info']];
    unset($_SESSION['flash_info']);
}

// Icone per tipo
$icons = [
    'success' => 'check-circle-fill',
    'danger' => 'exclamation-triangle-fill',
    'warning' => 'exclamation-circle-fill',
    'info' => 'info-circle-fill'
];
?>

<?php foreach ($messages as $msg): ?>
<div class="alert alert-<?= $msg['type'] ?> flash-message alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $icons[$msg['type']] ?> me-2"></i>
    <?= htmlspecialchars($msg['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endforeach; ?>

<!-- Script per auto-dismiss dopo 5 secondi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.flash-message').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
