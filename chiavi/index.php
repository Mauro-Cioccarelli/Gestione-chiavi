<?php
/**
 * Gestione Chiavi - Inventario
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$pageTitle = 'Inventario Chiavi';
$extraJs = [
    '/assets/js/voice/voice-core.js',
    '/assets/js/voice/voice-parser.js',
    '/assets/js/voice/voice-actions.js',
    '/assets/js/chiavi.js'
];

// Ottieni categorie per select
$db = db();
$categories = $db->query("SELECT id, name FROM key_categories WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-key me-2"></i><?= htmlspecialchars($pageTitle) ?>
                </h2>
                <?php if (has_role(ROLE_ADMIN) || has_role(ROLE_OPERATOR)): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewKey">
                    <i class="bi bi-plus-lg me-1"></i> Nuova Chiave
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtri e Tabella -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Filtri -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-5">
                            <label for="search-input" class="form-label visually-hidden">Cerca</label>
                            <input type="text" id="search-input" class="form-control"
                                   placeholder="Cerca per chiave o categoria...">
                        </div>
                        <div class="col-md-2">
                            <label for="status-filter" class="form-label visually-hidden">Stato</label>
                            <select id="status-filter" class="form-select">
                                <option value="">Tutti gli stati</option>
                                <option value="available">Disponibile</option>
                                <option value="in_delivery">In consegna</option>
                                <option value="dismised">Dismessa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" id="btn-voice-command" title="Voce">
                                <i class="bi bi-mic-fill me-2"></i> Voce
                            </button>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-secondary" id="btn-refresh" title="Aggiorna">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Stato riconoscimento vocale -->
                    <div class="mb-3" id="voice-status-container">
                        <div id="voice-status" class="alert alert-light py-2 mb-2" role="status">
                            Comandi vocali: "cerca [termine]" · "consegna [chiave] a [nome]" · "consegna chiave [chiave]" · "rientro [chiave]" · "annulla"
                        </div>
                        <div id="voice-recognized" class="small text-muted d-none">
                            Testo riconosciuto: <span id="voice-recognized-text"></span>
                        </div>
                    </div>

                    <!-- Tabulator -->
                    <div id="keys-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuova Chiave -->
<div class="modal fade" id="modalNewKey" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-new-key">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Nuova Chiave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="new-category" class="form-label form-label-required">Categoria</label>
                        <select name="category_id" id="new-category" class="form-select" required>
                            <option value="">Seleziona categoria...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new-identifier" class="form-label form-label-required">Identificativo</label>
                        <input type="text" name="identifier" id="new-identifier" class="form-control" 
                               placeholder="Es: Rossi Mario, Porta ingresso, ..." required maxlength="100">
                        <div class="form-text">Inserisci il proprietario o un identificativo della chiave</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Consegna -->
<div class="modal fade" id="modalCheckout" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-checkout">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-up me-2"></i>Consegna Chiave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="key_id" id="checkout-key-id">
                    
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" class="form-control" id="checkout-category-name" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chiave</label>
                        <input type="text" class="form-control" id="checkout-key-name" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="checkout-recipient" class="form-label form-label-required">Ricevente</label>
                        <input type="text" name="recipient_name" id="checkout-recipient" class="form-control" 
                               placeholder="Nome e cognome o ditta" required maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkout-notes" class="form-label">Note (opzionale)</label>
                        <textarea name="notes" id="checkout-notes" class="form-control" rows="2" 
                                  placeholder="Note aggiuntive..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-box-arrow-up me-1"></i>Consegna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rientro -->
<div class="modal fade" id="modalCheckin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-checkin">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Rientro Chiave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="key_id" id="checkin-key-id">
                    
                    <div class="mb-3">
                        <label class="form-label">Chiave</label>
                        <input type="text" class="form-control" id="checkin-key-name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkin-notes" class="form-label">Note (opzionale)</label>
                        <textarea name="notes" id="checkin-notes" class="form-control" rows="2" 
                                  placeholder="Stato chiave, osservazioni..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Registra Rientro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Imposta ruolo utente per JS
echo "<script>window.USER_ROLE = '" . (current_role() ?? '') . "';</script>";
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const voiceButton = document.getElementById('btn-voice-command');
    const statusBox = document.getElementById('voice-status');
    const recognizedWrapper = document.getElementById('voice-recognized');
    const recognizedText = document.getElementById('voice-recognized-text');

    if (!voiceButton || !statusBox || !window.VoiceCore || !window.parseVoiceCommand || !window.executeVoiceCommand) {
        return;
    }

    function updateStatus(message, tone) {
        statusBox.textContent = message;
        statusBox.className = 'alert py-2 mb-2';
        statusBox.classList.add(tone || 'alert-light');
    }

    const voice = new window.VoiceCore({
        lang: 'it-IT',
        onStart: function () {
            voiceButton.classList.remove('btn-outline-primary');
            voiceButton.classList.add('btn-danger');
            updateStatus('Sto ascoltando...', 'alert-danger');
        },
        onEnd: function () {
            voiceButton.classList.remove('btn-danger');
            voiceButton.classList.add('btn-outline-primary');
        },
        onResult: function (text) {
            recognizedText.textContent = text;
            recognizedWrapper.classList.remove('d-none');
            updateStatus('Comando ricevuto: elaborazione in corso...', 'alert-info');

            const command = window.parseVoiceCommand(text);
            if (!command) {
                updateStatus('Comando non riconosciuto. Prova con: consegna chiave a [nome], rientro chiave, annulla.', 'alert-warning');
                console.warn('[voice] Comando non riconosciuto:', text);
                return;
            }

            console.log('[voice] command:', command);
            updateStatus('Comando riconosciuto: ' + command.action, 'alert-success');
            window.executeVoiceCommand(command);
        },
        onError: function (errorType, message) {
            updateStatus(message, 'alert-warning');
            console.warn('[voice] error:', errorType, message);
        },
        onUnsupported: function () {
            voiceButton.disabled = true;
            updateStatus('Riconoscimento vocale non supportato da questo browser.', 'alert-secondary');
        }
    });

    function triggerVoice() {
        const started = voice.start();
        if (!started && !voice.isListening()) {
            updateStatus('Impossibile avviare il riconoscimento vocale.', 'alert-warning');
        }
    }

    voiceButton.addEventListener('click', triggerVoice);

    // Shortcut: Spacebar attiva il microfono solo se il focus non è su un campo interattivo
    document.addEventListener('keydown', function (e) {
        if (e.code !== 'Space') return;
        const tag = document.activeElement ? document.activeElement.tagName : '';
        if (['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(tag)) return;
        e.preventDefault();
        triggerVoice();
    });
});
</script>
<?php
include __DIR__ . '/../includes/layout/footer.php'; 
?>
