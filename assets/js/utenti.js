/**
 * Gestione Utenti - JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    const tableElement = document.getElementById('users-table');

    if (!tableElement) return;

    // Determina se l'utente può modificare (admin/god) o solo visualizzare (operator)
    const canEdit = window.USER_ROLE === 'admin' || window.USER_ROLE === 'god';

    // Colonne della tabella
    const columns = [
        {
            title: "ID",
            field: "id",
            width: 70,
            headerSort: true,
            hozAlign: "center"
        },
        {
            title: "Username",
            field: "username",
            minWidth: 150,
            widthGrow: 1,
            headerSort: true,
        },
        {
            title: "Email",
            field: "email",
            minWidth: 150,
            widthGrow: 2,
            headerSort: true,
        },
        {
            title: "Ruolo",
            field: "role",
            minWidth: 120,
            headerSort: true,
            formatter: function (cell) {
                const role = cell.getValue();
                const labels = {
                    'operator': '<span class="badge bg-secondary">Operatore</span>',
                    'admin': '<span class="badge bg-primary">Admin</span>',
                    'god': '<span class="badge bg-danger">God</span>'
                };
                return labels[role] || role;
            },
            editor: function (cell) {
                // Solo god può modificare ruoli
                if (!hasRole(['god'])) return false;

                const editor = document.createElement("select");
                editor.innerHTML = `
                    <option value="operator">Operatore</option>
                    <option value="admin">Admin</option>
                    <option value="god">God</option>
                `;
                editor.value = cell.getValue();
                return editor;
            }
        },
        {
            title: "Stato",
            field: "deleted_at",
            width: 100,
            hozAlign: "center",
            formatter: function (cell) {
                const value = cell.getValue();
                if (value) {
                    return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Eliminato</span>';
                }
                return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Attivo</span>';
            }
        },
        {
            title: "Cambio PW",
            field: "force_password_change",
            minWidth: 120,
            hozAlign: "center",
            formatter: function (cell) {
                const value = cell.getValue();
                if (value) {
                    return '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle"></i> Forzato</span>';
                }
                return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> OK</span>';
            }
        },
        {
            title: "Ultimo Accesso",
            field: "last_login",
            minWidth: 150,
            formatter: function (cell) {
                const value = cell.getValue();
                return value ? formatDateTime(value) : '-';
            }
        }
    ];

    // Aggiungi colonna Azioni solo se l'utente può modificare
    if (canEdit) {
        columns.push({
            title: "Azioni",
            field: "actions",
            minWidth: 150,
            headerSort: false,
            hozAlign: "center",
            formatter: function (cell) {
                const data = cell.getRow().getData();
                const currentUserId = window.CURRENT_USER_ID || 0;

                let html = '';

                // Se eliminato, mostra pulsante Ripristina
                if (data.deleted_at) {
                    html += `<button class="btn btn-sm btn-outline-success me-1"
                                onclick="restoreUser(${data.id})"
                                title="Ripristina">
                                <i class="bi bi-arrow-counterclockwise"></i>
                             </button>`;
                } else {
                    // Modifica
                    html += `<button class="btn btn-sm btn-outline-primary me-1"
                                onclick="editUser(${data.id})"
                                title="Modifica">
                                <i class="bi bi-pencil"></i>
                             </button>`;

                    // Elimina (non se stesso)
                    if (data.id != currentUserId) {
                        html += `<button class="btn btn-sm btn-outline-danger"
                                    onclick="deleteUser(${data.id}, '${escapeHtml(data.username)}')"
                                    title="Elimina">
                                    <i class="bi bi-trash"></i>
                                 </button>`;
                    }
                }

                return html;
            }
        });
    }

    // Inizializza Tabulator
    const table = new Tabulator(tableElement, {
        ajaxURL: window.APP_URL + "/ajax/utenti/list.php",
        ajaxParams: {
            csrf_token: window.CSRF_TOKEN || ''
        },
        layout: "fitColumns",
        dataLoader: false,
        pagination: false,
        filterMode: "local",
        sortMode: "local",
        columns: columns,
        locale: true,
        langs: {
            "it-it": {
                "pagination": {
                    "first": "Prima",
                    "prev": "Precedente",
                    "next": "Successiva",
                    "last": "Ultima"
                }
            }
        },
        initialSort: [
            { column: "username", dir: "asc" }
        ]
    });

    // Ricerca
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const val = this.value;
                if (val) {
                    table.setFilter([
                        [
                            { field: "username", type: "like", value: val },
                            { field: "email", type: "like", value: val }
                        ]
                    ]);
                } else {
                    table.clearFilter();
                }
            }, 300);
        });
    }

    // Refresh
    const refreshBtn = document.getElementById('btn-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            table.replaceData();
        });
    }

    // Form nuovo utente
    const formNewUser = document.getElementById('form-new-user');
    if (formNewUser) {
        formNewUser.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            showLoading();

            fetchJSON(window.APP_URL + '/ajax/utenti/create.php', {
                method: 'POST',
                body: formData
            })
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalNewUser')).hide();
                        table.replaceData();
                        showAlert('success', data.message);
                        formNewUser.reset();
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
 * Modifica utente
 */
function editUser(userId) {
    // Carica dati utente e apri modal
    fetchJSON(window.APP_URL + '/ajax/utenti/get.php?id=' + userId)
        .then(data => {
            if (data.success) {
                const user = data.user;

                document.getElementById('edit-user-id').value = user.id;
                document.getElementById('edit-username').value = user.username;
                document.getElementById('edit-email').value = user.email;
                document.getElementById('edit-role').value = user.role;
                document.getElementById('edit-force-pw').checked = user.force_password_change;

                new bootstrap.Modal(document.getElementById('modalEditUser')).show();
            }
        })
        .catch(err => {
            showAlert('danger', 'Errore nel caricamento dati: ' + err.message);
        });
}

/**
 * Salva modifica utente
 */
function saveUser() {
    const formData = new FormData(document.getElementById('form-edit-user'));

    showLoading();

    fetchJSON(window.APP_URL + '/ajax/utenti/update.php', {
        method: 'POST',
        body: formData
    })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEditUser')).hide();
                const table = Tabulator.findTable('#users-table')[0];
                if (table) table.replaceData();
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
 * Elimina utente
 */
function deleteUser(userId, username) {
    if (!confirm(`Sei sicuro di voler eliminare l'utente "${username}"?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN || '');
    formData.append('id', userId);

    showLoading();

    fetchJSON(window.APP_URL + '/ajax/utenti/delete.php', {
        method: 'POST',
        body: formData
    })
        .then(data => {
            if (data.success) {
                const table = Tabulator.findTable('#users-table')[0];
                if (table) table.replaceData();
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
 * Ripristina utente eliminato
 */
function restoreUser(userId) {
    if (!confirm('Sei sicuro di voler ripristinare questo utente?')) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN || '');
    formData.append('id', userId);

    showLoading();

    fetchJSON(window.APP_URL + '/ajax/utenti/restore.php', {
        method: 'POST',
        body: formData
    })
        .then(data => {
            if (data.success) {
                const table = Tabulator.findTable('#users-table')[0];
                if (table) table.replaceData();
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
 * Formatta data
 */
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
