/**
 * Gestione Chiavi - JavaScript
 */

// Riferimento alla tabella (accessibile globalmente)
window.keysTable = null;

document.addEventListener('DOMContentLoaded', function () {
    const tableElement = document.getElementById('keys-table');

    if (!tableElement) return;

    // Inizializza Tabulator con remote pagination
    const table = new Tabulator(tableElement, {
        ajaxURL: window.APP_URL + "/ajax/chiavi/list.php",
        dataLoader: false,
        pagination: true,
        paginationMode: "remote",
        filterMode: "remote",
        sortMode: "remote",
        layout: "fitColumns",
        paginationSize: 20,
        paginationSizeSelector: [10, 20, 50, 100],
        dataLoaded: function() {
            window.keysTable = table;
        },
        columns: [
            {
                title: "ID",
                field: "id",
                width: 70,
                headerSort: true,
                headerHozAlign: "center",
                hozAlign: "center"
            },
            {
                title: "Categoria",
                field: "category_name",
                minWidth: 150,
                headerSort: true,
            },
            {
                title: "Chiave",
                field: "identifier",
                minWidth: 200,
                headerSort: true,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    return `<a href="${window.APP_URL}/chiavi/storia.php?id=${data.id}" class="text-decoration-none">
                        <i class="bi bi-key me-1"></i>${cell.getValue()}
                    </a>`;
                }
            },
            {
                title: "Stato",
                field: "status",
                widthGrow: 1.5,
                headerSort: true,
                formatter: function (cell) {
                    const status = cell.getValue();
                    const data = cell.getRow().getData();
                    const labels = {
                        'available': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Disponibile</span>',
                        'in_delivery': '<span class="badge bg-warning text-dark" data-bs-toggle="tooltip" title="' + (data.recipient_name || '...') + '"><i class="bi bi-box-arrow-up me-1"></i>In consegna <span class="opacity-75" style="font-size: 0.85em">(' + (data.recipient_name || '...') + ')</span></span>',
                        'dismised': '<span class="badge bg-danger"><i class="bi bi-trash me-1"></i>Dismessa</span>'
                    };
                    return labels[status] || status;
                }
            },
            {
                title: "Ricevente",
                field: "recipient_name",
                minWidth: 150,
                visible: false,
                formatter: function (cell) {
                    const value = cell.getValue();
                    return value ? `<i class="bi bi-person me-1"></i>${value}` : '-';
                }
            },
            {
                title: "Data Consegna",
                field: "checkout_date",
                width: 130,
                visible: false
            },
            {
                title: "Azioni",
                field: "actions",
                width: 180,
                headerSort: false,
                hozAlign: "center",
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    let html = '';

                    if (data.status === 'available') {
                        html += `<button class="btn btn-sm btn-warning me-1"
                                    onclick="openCheckout(${data.id}, '${escapeHtml(data.identifier)}', '${escapeHtml(data.category_name || '')}')"
                                    title="Consegna">
                                    <i class="bi bi-box-arrow-up"></i>
                                 </button>`;
                    } else if (data.status === 'in_delivery') {
                        html += `<button class="btn btn-sm btn-success me-1"
                                    onclick="openCheckin(${data.id}, '${escapeHtml(data.identifier)}', '${escapeHtml(data.category_name || '')}')"
                                    title="Rientro">
                                    <i class="bi bi-box-arrow-in-down"></i>
                                 </button>`;
                    }

                    html += `<button class="btn btn-sm btn-info me-1" 
                                onclick="viewHistory(${data.id})"
                                title="Storico">
                                <i class="bi bi-clock-history"></i>
                             </button>`;

                    if (hasRole(['admin', 'god', 'operator'])) {
                        html += `<button class="btn btn-sm btn-outline-secondary"
                                    onclick="editKey(${data.id})"
                                    title="Modifica">
                                    <i class="bi bi-pencil"></i>
                                 </button>`;
                    }

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
            { column: "id", dir: "desc" }
        ]
    });


    function applyCustomFilters() {
        let customFilters = [];

        const searchInput = document.getElementById('search-input');
        if (searchInput && searchInput.value) {
            customFilters.push({ field: "search", type: "like", value: searchInput.value });
        }

        const statusFilter = document.getElementById('status-filter');
        if (statusFilter && statusFilter.value) {
            customFilters.push({ field: "status", type: "=", value: statusFilter.value });
        }

        table.setFilter(customFilters);
    }

    // Ricerca e filtri ritardati fino a dopo la costruzione della tabella
    table.on("tableBuilt", function () {
        let searchTimeout;
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyCustomFilters();
                }, 500);
            });
        }

        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function () {
                applyCustomFilters();
            });

            // Check URL params per filtro stato
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            if (statusParam && ['available', 'in_delivery', 'dismised'].includes(statusParam)) {
                statusFilter.value = statusParam;
                applyCustomFilters();
            }
        }
    });

    // Inizializza tooltip dopo il caricamento dei dati
    table.on("dataLoaded", function () {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Refresh: pulisce i filtri e ricarica
    const refreshBtn = document.getElementById('btn-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            const searchInput = document.getElementById('search-input');
            if (searchInput) searchInput.value = '';
            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) statusFilter.value = '';
            applyCustomFilters();
        });
    }

    // Form nuova chiave
    const formNewKey = document.getElementById('form-new-key');
    let pendingRestoreId = null;

    if (formNewKey) {
        formNewKey.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Assicura che il token CSRF sia presente
            if (!formData.has('csrf_token') && window.CSRF_TOKEN) {
                formData.append('csrf_token', window.CSRF_TOKEN);
            }

            showLoading();

            fetch(window.APP_URL + '/ajax/chiavi/create.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalNewKey')).hide();
                        table.replaceData();
                        showAlert('success', data.message);
                        formNewKey.reset();
                    } else if (data.confirm_required && data.confirm_type === 'restore') {
                        // Chiave dismessa esistente - chiedi conferma
                        pendingRestoreId = data.existing_id;
                        hideLoading();

                        if (confirm(data.message + '\n\nID chiave: ' + data.existing_id)) {
                            // Utente ha confermato - chiama endpoint ripristino
                            showLoading();
                            const restoreData = new FormData();
                            restoreData.append('csrf_token', window.CSRF_TOKEN);
                            restoreData.append('id', data.existing_id);

                            fetchJSON(window.APP_URL + '/ajax/chiavi/restore.php', {
                                method: 'POST',
                                body: restoreData
                            })
                                .then(restoreResult => {
                                    if (restoreResult.success) {
                                        bootstrap.Modal.getInstance(document.getElementById('modalNewKey')).hide();
                                        table.replaceData();
                                        showAlert('warning', '<i class="bi bi-arrow-clockwise me-2"></i>' + restoreResult.message, 5000, true);
                                        formNewKey.reset();
                                    } else {
                                        showAlert('danger', restoreResult.error);
                                    }
                                })
                                .catch(err => {
                                    showAlert('danger', 'Errore nel ripristino: ' + err.message);
                                })
                                .finally(() => {
                                    hideLoading();
                                    pendingRestoreId = null;
                                });
                        } else {
                            // Utente ha annullato
                            pendingRestoreId = null;
                        }
                    } else {
                        // Se la chiave esiste già (attiva), mostro il link per andare alla scheda
                        if (data.existing_id && data.existing_type === 'active') {
                            showAlert('info', 'La chiave esiste già. <a href="' + window.APP_URL + '/chiavi/storia.php?id=' + data.existing_id + '" class="alert-link">Vai alla scheda</a>', 5000, true);
                        } else {
                            showAlert('danger', data.error);
                        }
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

    // Form checkout
    const formCheckout = document.getElementById('form-checkout');
    if (formCheckout) {
        formCheckout.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            showLoading();

            fetchJSON(window.APP_URL + '/ajax/chiavi/checkout.php', {
                method: 'POST',
                body: formData
            })
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalCheckout')).hide();
                        table.replaceData();
                        showAlert('success', data.message);
                        formCheckout.reset();
                    } else {
                        showAlert('danger', data.error);
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

    // Form checkin
    const formCheckin = document.getElementById('form-checkin');
    if (formCheckin) {
        formCheckin.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            showLoading();

            fetchJSON(window.APP_URL + '/ajax/chiavi/checkin.php', {
                method: 'POST',
                body: formData
            })
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalCheckin')).hide();
                        table.replaceData();
                        showAlert('success', data.message);
                        formCheckin.reset();
                    } else {
                        showAlert('danger', data.error);
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

    // Form edit key
    const formEditKey = document.getElementById('form-edit-key');
    if (formEditKey) {
        formEditKey.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            showLoading();

            fetchJSON(window.APP_URL + '/ajax/chiavi/update.php', {
                method: 'POST',
                body: formData
            })
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalEditKey')).hide();
                        table.replaceData();
                        showAlert('success', data.message);
                        formEditKey.reset();
                    } else {
                        showAlert('danger', data.error);
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
});

// ============================================================================
// Funzioni globali
// ============================================================================

/**
 * Apre modal consegna
 */
function openCheckout(keyId, keyName, categoryName) {
    document.getElementById('checkout-key-id').value = keyId;
    document.getElementById('checkout-key-name').value = keyName;
    const catField = document.getElementById('checkout-category-name');
    if (catField) catField.value = categoryName || '';
    document.getElementById('checkout-recipient').value = '';
    document.getElementById('checkout-recipient').focus();
    new bootstrap.Modal(document.getElementById('modalCheckout')).show();
}

/**
 * Apre modal rientro
 */
function openCheckin(keyId, keyName, categoryName) {
    document.getElementById('checkin-key-id').value = keyId;
    document.getElementById('checkin-key-name').value = keyName;
    const catField = document.getElementById('checkin-category-name');
    if (catField) catField.value = categoryName || '';
    new bootstrap.Modal(document.getElementById('modalCheckin')).show();
}

/**
 * Visualizza storico
 */
function viewHistory(keyId) {
    window.location.href = window.APP_URL + '/chiavi/storia.php?id=' + keyId;
}

/**
 * Modifica chiave
 */
function editKey(keyId, categoryName, identifier) {
    fetchJSON(window.APP_URL + '/ajax/chiavi/list.php?id=' + keyId)
        .then(data => {
            if (data && data.data && data.data.length > 0) {
                const key = data.data[0];
                document.getElementById('edit-key-id').value = key.id;
                document.getElementById('edit-category').value = key.category_id;
                document.getElementById('edit-identifier').value = key.identifier;
                new bootstrap.Modal(document.getElementById('modalEditKey')).show();
            } else {
                showAlert('danger', 'Chiave non trovata');
            }
        })
        .catch(err => {
            showAlert('danger', 'Errore nel caricamento dei dati: ' + err.message);
        });
}

/**
 * Elimina chiave (soft delete)
 */
function deleteKey(keyId, identifier) {
    if (!confirm(`Sei sicuro di voler eliminare la chiave "${identifier}"?\n\nQuesta operazione è reversibile.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('id', keyId);

    showLoading();

    fetchJSON(window.APP_URL + '/ajax/chiavi/delete.php', {
        method: 'POST',
        body: formData
    })
        .then(data => {
            if (data.success) {
                if (window.keysTable) {
                    window.keysTable.replaceData();
                }
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.error);
            }
        })
        .catch(err => {
            showAlert('danger', 'Errore di comunicazione: ' + err.message);
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Elimina chiave dal modal di modifica
 */
function deleteKeyFromModal() {
    const keyId = document.getElementById('edit-key-id')?.value;
    const identifier = document.getElementById('edit-identifier')?.value;

    if (!keyId) {
        showAlert('danger', 'ID chiave non valido');
        return;
    }

    if (!confirm(`Sei sicuro di voler eliminare la chiave "${identifier}"?\n\nQuesta operazione è reversibile.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN);
    formData.append('id', keyId);

    showLoading();

    fetchJSON(window.APP_URL + '/ajax/chiavi/delete.php', {
        method: 'POST',
        body: formData
    })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEditKey')).hide();
                if (window.keysTable) {
                    window.keysTable.replaceData();
                }
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.error);
            }
        })
        .catch(err => {
            showAlert('danger', 'Errore di comunicazione: ' + err.message);
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Verifica ruolo utente
 */
function hasRole(roles) {
    // Questa funzione dovrebbe ottenere il ruolo da una variabile globale
    // impostata nel layout
    const userRole = window.USER_ROLE || '';
    const roleHierarchy = {
        'operator': 1,
        'admin': 2,
        'god': 3
    };

    const userLevel = roleHierarchy[userRole] || 0;
    return roles.some(role => userLevel >= (roleHierarchy[role] || 0));
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
