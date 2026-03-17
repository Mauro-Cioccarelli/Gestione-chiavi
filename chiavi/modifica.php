<?php
/**
 * Modifica Chiave
 */

define('APP_ROOT', true);
require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

// Solo admin e god possono modificare
if (!has_role(ROLE_ADMIN) && !has_role(ROLE_GOD)) {
    $_SESSION['flash_error'] = 'Accesso non consentito';
    header('Location: index.php');
    exit;
}

$pageTitle = 'Modifica Chiave';

// Ottieni ID chiave
$keyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($keyId < 1) {
    $_SESSION['flash_error'] = 'Chiave non specificata';
    header('Location: index.php');
    exit;
}

$db = db();

// Ottieni dati chiave
$stmt = $db->prepare("
    SELECT
        k.id,
        k.identifier,
        k.status,
        k.category_id,
        kc.name as category_name,
        k.created_at
    FROM `keys` k
    LEFT JOIN key_categories kc ON k.category_id = kc.id
    WHERE k.id = ? AND k.deleted_at IS NULL
");
$stmt->execute([$keyId]);
$key = $stmt->fetch();

if (!$key) {
    $_SESSION['flash_error'] = 'Chiave non trovata';
    header('Location: index.php');
    exit;
}

// Ottieni categorie per select
$categories = $db->query("SELECT id, name FROM key_categories WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/chiavi/index.php">Chiavi</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/chiavi/storia.php?id=<?= $keyId ?>">Storico</a></li>
            <li class="breadcrumb-item active">Modifica</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-pencil me-2"></i>Modifica Chiave
                </div>
                <div class="card-body">
                    <form id="form-edit-key">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" id="edit-key-id" value="<?= $key['id'] ?>">

                        <div class="mb-3">
                            <label for="edit-category" class="form-label form-label-required">Categoria</label>
                            <select name="category_id" id="edit-category" class="form-select" required>
                                <option value="">Seleziona categoria...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $key['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-identifier" class="form-label form-label-required">Identificativo</label>
                            <input type="text" name="identifier" id="edit-identifier" class="form-control"
                                   value="<?= htmlspecialchars($key['identifier']) ?>"
                                   placeholder="Es: Rossi Mario, Porta ingresso, ..." required maxlength="100">
                            <div class="form-text">Inserisci il proprietario o un identificativo della chiave</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <div>
                                <?php
                                $statusLabels = [
                                    'available' => '<span class="badge bg-success">Disponibile</span>',
                                    'in_delivery' => '<span class="badge bg-warning text-dark">In consegna</span>',
                                    'dismised' => '<span class="badge bg-danger">Dismessa</span>'
                                ];
                                echo $statusLabels[$key['status']] ?? $key['status'];
                                ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Creata il</label>
                            <div><?= format_datetime($key['created_at']) ?></div>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary" onclick="saveChanges()">
                        <i class="bi bi-check-lg me-1"></i>Salva modifiche
                    </button>
                    <a href="storia.php?id=<?= $keyId ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Annulla
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle me-2"></i>Zona Pericolosa
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        L'eliminazione è una soft delete: la chiave verrà nascosta ma potrà essere ripristinata.
                    </p>
                    <p class="small text-muted mb-3">
                        <strong>Nota:</strong> Non è possibile eliminare una chiave in consegna. Effettua prima il rientro.
                    </p>
                    <button type="button" class="btn btn-danger w-100" onclick="deleteKey()">
                        <i class="bi bi-trash me-2"></i>Elimina Chiave
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/chiavi.js"></script>
<script>
// Salva modifiche
function saveChanges() {
    const formData = new FormData(document.getElementById('form-edit-key'));

    showLoading();
    fetchJSON(window.APP_URL + '/ajax/chiavi/update.php', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.href = 'storia.php?id=<?= $keyId ?>';
            }, 1000);
        } else {
            showAlert('danger', data.error);
        }
    })
    .catch(err => showAlert('danger', err.message))
    .finally(() => hideLoading());
}

// Elimina chiave
function deleteKey() {
    if (!confirm(`Sei sicuro di voler eliminare la chiave "<?= htmlspecialchars($key['identifier'], ENT_QUOTES) ?>"?\n\nQuesta operazione è reversibile.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('id', <?= $keyId ?>);

    showLoading();
    fetchJSON(window.APP_URL + '/ajax/chiavi/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            showAlert('danger', data.error);
        }
    })
    .catch(err => showAlert('danger', err.message))
    .finally(() => hideLoading());
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
