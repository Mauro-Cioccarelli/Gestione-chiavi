/**
 * Gestione Categorie - JavaScript
 */

let mergeTargetSelect = null;

document.addEventListener('DOMContentLoaded', function () {
    const tableElement = document.getElementById('categories-table');
    if (!tableElement) return;

    // Determina se l'utente può modificare (admin/god) o solo visualizzare (operator)
    const canEdit = window.CAN_EDIT_CATEGORIES !== false;

    // Inizializza Tabulator per categorie (richiede Tabulator 6 compatibile con fetch JSON array formattato da list.php)
    const table = new Tabulator(tableElement, {
        ajaxURL: window.APP_URL + "/ajax/categorie/list.php",
        dataLoader: false,
        pagination: true,
        paginationMode: "remote",
        filterMode: "remote",
        sortMode: "remote",
        layout: "fitColumns",
        paginationSize: 50,
        paginationSizeSelector: [20, 50, 100],
        selectableRows: canEdit,
        columns: [
            ...(canEdit ? [{
                formatter: "rowSelection",
                width: 40,
                hozAlign: "center",
                headerSort: false,
                frozen: true,
                headerFormatter: function() { return ''; }
            }] : []),
            {
                title: "ID",
                field: "id",
                width: 70,
                headerSort: true,
                headerHozAlign: "center",
                hozAlign: "center"
            },
            {
                title: "Nome Categoria",
                field: "name",
                minWidth: 200,
                headerSort: true
            },
            {
                title: "Descrizione",
                field: "description",
                minWidth: 250,
                headerSort: false,
                headerFilter: false
            },
            {
                title: "Tot. Chiavi",
                field: "keys_count",
                width: 130,
                headerSort: true,
                headerHozAlign: "center",
                hozAlign: "center",
                formatter: function (cell) {
                    const count = cell.getValue();
                    const data = cell.getRow().getData();
                    const stateClass = count > 0 ? "bg-info text-dark cursor-pointer category-keys-badge" : "bg-secondary";
                    const onClick = count > 0 ? `onclick="openCategoryKeys(${data.id}, '${escapeHtml(data.name)}')"` : '';

                    return `<span class="badge ${stateClass}" ${onClick} style="${count > 0 ? 'cursor:pointer;' : ''}" title="${count > 0 ? 'Clicca per vedere le chiavi' : ''}">${count}</span>`;
                }
            },
            {
                title: "Azioni",
                field: "actions",
                width: canEdit ? 140 : 0,
                visible: canEdit,
                headerSort: false,
                hozAlign: "center",
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    let html = '';

                    html += `<button class="btn btn-sm btn-outline-primary me-1"
                                onclick="openEditCategory(${data.id}, '${escapeHtml(data.name)}', '${escapeHtml(data.description || '')}')"
                                title="Modifica">
                                <i class="bi bi-pencil"></i>
                             </button>`;

                    html += `<button class="btn btn-sm btn-outline-danger"
                                onclick="deleteCategory(${data.id}, '${escapeHtml(data.name)}', ${parseInt(data.keys_count)})"
                                title="Elimina">
                                <i class="bi bi-trash"></i>
                             </button>`;

                    return html;
                }
            }
        ],
        locale: true,
        langs: {
            "it-it": {
                "pagination": {
                    "first": "Prima",
                    "prev": "Precedente",
                    "next": "Successiva",
                    "last": "Ultima",
                    "counter": {
                        "showing": "Mostra",
                        "of": "di",
                        "rows": "righe",
                        "all": "Tutte"
                    }
                },
                "data": {
                    "loading": "Caricamento...",
                    "error": "Errore nel caricamento"
                }
            }
        },
        initialSort: [
            { column: "name", dir: "asc" }
        ]
    });

    // Ricerca 
    table.on("tableBuilt", function () {
        let searchTimeout;
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const val = this.value;
                    if (val) {
                        table.setFilter([{ field: "search", type: "like", value: val }]);
                    } else {
                        table.clearFilter();
                    }
                }, 500);
            });
        }
    });

    // Refresh
    const refreshBtn = document.getElementById('btn-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            table.replaceData();
        });
    }

    // Gestione bottone Fusione in base a selezione (solo admin/god)
    const mergeBtn = document.getElementById('btn-merge');
    if (mergeBtn && canEdit) {
        table.on("rowSelectionChanged", function (data, rows) {
            if (data.length > 0) {
                mergeBtn.removeAttribute("disabled");
                mergeBtn.classList.remove("opacity-50");
            } else {
                mergeBtn.setAttribute("disabled", "disabled");
                mergeBtn.classList.add("opacity-50");
            }
        });

        mergeBtn.addEventListener('click', function () {
            const selectedData = table.getSelectedData();
            if (selectedData.length === 0) return;

            // Riempi lista visiva sorgenti
            const sourceList = document.getElementById('merge-source-list');
            const sourceIdsInput = document.getElementById('merge-source-ids');

            sourceList.innerHTML = '';
            let ids = [];

            selectedData.forEach(cat => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-warning';
                li.innerHTML = `<strong>ID: ${cat.id}</strong> - ${escapeHtml(cat.name)}`;
                sourceList.appendChild(li);
                ids.push(cat.id);
            });

            sourceIdsInput.value = ids.join(',');

            // Ricarica la select di destinazione con tutte le categorie
            reloadMergeTargetSelect();

            // Init o setup Tom Select se ancora non ci sta
            const targetSelectNode = document.getElementById('merge-target');
            if (targetSelectNode && !mergeTargetSelect) {
                mergeTargetSelect = new TomSelect(targetSelectNode, {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    }
                });
            }

            // Apri modal
            new bootstrap.Modal(document.getElementById('modalMergeCategory')).show();
        });
    }

    // Modal forms setup (solo admin/god)
    if (canEdit) {
        setupAjaxForm('form-new-category', 'modalNewCategory', '/ajax/categorie/create.php', () => { table.replaceData(); reloadCategorySelects(); });
        setupAjaxForm('form-edit-category', 'modalEditCategory', '/ajax/categorie/update.php', () => { table.replaceData(); reloadCategorySelects(); });
        setupAjaxForm('form-merge-category', 'modalMergeCategory', '/ajax/categorie/merge.php', () => { table.replaceData(); reloadCategorySelects(); });
    }
});

// ============================================================================
// Azioni globali e Form Helpers
// ============================================================================

function setupAjaxForm(formId, modalId, endpoint, onSuccess) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        showLoading();

        fetchJSON(window.APP_URL + endpoint, {
            method: 'POST',
            body: formData
        })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                    showAlert('success', data.message);
                    form.reset();
                    if (onSuccess) onSuccess();
                } else {
                    showAlert('danger', data.error || 'Si è verificato un errore');
                }
            })
            .catch(err => {
                showAlert('danger', 'Errore di comunicazione: ' + err.message);
            })
            .finally(() => {
                hideLoading();
            });
    });
}

window.openEditCategory = function (id, name, description) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-description').value = description;
    new bootstrap.Modal(document.getElementById('modalEditCategory')).show();
};

window.deleteCategory = function (id, name, keysCount) {
    const warningKeys = keysCount > 0
        ? `\n\nATTENZIONE: questa categoria contiene ${keysCount} chiav${keysCount === 1 ? 'e' : 'i'} che verr${keysCount === 1 ? 'à' : 'anno'} eliminat${keysCount === 1 ? 'a' : 'e'} insieme alla categoria.`
        : '';
    if (confirm(`Sei sicuro di voler eliminare la categoria "${name}"?${warningKeys}\n\nQuesta azione non può essere annullata.`)) {
        showLoading();
        const formData = new FormData();
        formData.append('csrf_token', window.CSRF_TOKEN);
        formData.append('id', id);

        fetchJSON(window.APP_URL + '/ajax/categorie/delete.php', {
            method: 'POST',
            body: formData
        })
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    const table = Tabulator.findTable('#categories-table')[0];
                    if (table) table.replaceData();
                    reloadCategorySelects();
                } else {
                    showAlert('danger', data.error);
                }
            })
            .catch(err => showAlert('danger', err.message))
            .finally(() => hideLoading());
    }
};

window.openCategoryKeys = function (id, name) {
    document.getElementById('category-keys-title').textContent = name;

    const loadingEl = document.getElementById('category-keys-loading');
    const emptyEl = document.getElementById('category-keys-empty');
    const listEl = document.getElementById('category-keys-list');

    // Reset UI
    loadingEl.classList.remove('d-none');
    emptyEl.classList.add('d-none');
    listEl.innerHTML = '';

    const modal = new bootstrap.Modal(document.getElementById('modalCategoryKeys'));
    modal.show();

    // Fetch keys list
    fetchJSON(window.APP_URL + '/ajax/categorie/keys.php?id=' + id)
        .then(data => {
            loadingEl.classList.add('d-none');

            if (data.success && data.data) {
                if (data.data.length === 0) {
                    emptyEl.classList.remove('d-none');
                } else {
                    data.data.forEach(key => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';

                        // Status badge
                        let statusBadge = '';
                        if (key.deleted_at) {
                            statusBadge = '<span class="badge bg-danger">Dismessa</span>';
                        } else if (key.status === 'in_delivery') {
                            statusBadge = '<span class="badge bg-warning text-dark">In Consegna</span>';
                        } else {
                            statusBadge = '<span class="badge bg-success">Disponibile</span>';
                        }

                        li.innerHTML = `
                        <div class="${key.deleted_at ? 'text-decoration-line-through text-muted' : ''}">
                            <i class="bi bi-key me-2"></i><strong>${escapeHtml(key.identifier)}</strong>
                            <div class="small ms-4">ID: #${key.id}</div>
                        </div>
                        <div>
                            ${statusBadge}
                            <a href="${window.APP_URL}/chiavi/storia.php?id=${key.id}" class="btn btn-sm btn-outline-secondary ms-2" title="Vedi Storico" target="_blank">
                                <i class="bi bi-clock-history"></i>
                            </a>
                        </div>
                    `;
                        listEl.appendChild(li);
                    });
                }
            } else {
                showAlert('danger', data.error || 'Errore nel caricamento delle chiavi');
                modal.hide();
            }
        })
        .catch(err => {
            loadingEl.classList.add('d-none');
            showAlert('danger', 'Errore di comunicazione: ' + err.message);
            modal.hide();
        });
};

window.reloadCategorySelects = function () {
    // Non fa più nulla per i source, gestito tramite tabella (lo lascio per non rompere compatibilità on-success se fosse chiamata altrove)
};

window.reloadMergeTargetSelect = function () {
    fetchJSON(window.APP_URL + '/ajax/chiavi/categories.php')
        .then(data => {
            if (data.success && data.data) {
                const select = document.getElementById('merge-target');
                if (!select) return;

                if (mergeTargetSelect) {
                    mergeTargetSelect.clearOptions();

                    data.data.forEach(cat => {
                        mergeTargetSelect.addOption({ value: cat.id, text: cat.name });
                    });

                    mergeTargetSelect.refreshOptions(false);
                } else {
                    select.innerHTML = '<option value="">Seleziona...</option>';
                    data.data.forEach(cat => {
                        const optionElement = document.createElement('option');
                        optionElement.value = cat.id;
                        optionElement.textContent = cat.name;
                        select.appendChild(optionElement);
                    });
                }
            }
        })
        .catch(() => { });
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
