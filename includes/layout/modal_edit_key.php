<?php
/**
 * Modal modifica chiave - include condiviso
 * Richiede: $categories (array di ['id', 'name'])
 */
?>
<!-- Modal Modifica Chiave -->
<div class="modal fade" id="modalEditKey" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-edit-key">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Modifica Chiave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="edit-key-id">

                    <div class="mb-3">
                        <label for="edit-category" class="form-label form-label-required">Categoria</label>
                        <select name="category_id" id="edit-category" class="form-select" required>
                            <option value="">Seleziona categoria...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit-identifier" class="form-label form-label-required">Identificativo</label>
                        <input type="text" name="identifier" id="edit-identifier" class="form-control"
                               placeholder="Es: Rossi Mario, Porta ingresso, ..." required maxlength="100">
                        <div class="form-text">Inserisci il proprietario o un identificativo della chiave</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if (has_role(ROLE_ADMIN) || has_role(ROLE_GOD) || has_role(ROLE_OPERATOR)): ?>
                        <button type="button" id="btn-delete-key" class="btn btn-danger me-auto"
                                onclick="deleteKeyFromModal()"
                                title="Elimina chiave">
                            <i class="bi bi-trash me-1"></i>Elimina
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Salva modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
