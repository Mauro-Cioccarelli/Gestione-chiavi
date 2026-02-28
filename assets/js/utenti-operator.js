/**
 * Gestione Utenti - JavaScript per Operatori (sola lettura)
 */

document.addEventListener('DOMContentLoaded', function () {
    const tableElement = document.getElementById('users-table');

    if (!tableElement) return;

    // Inizializza Tabulator
    const table = new Tabulator(tableElement, {
        ajaxURL: window.APP_URL + "/ajax/utenti/list-operator.php",
        ajaxParams: {
            csrf_token: window.CSRF_TOKEN || ''
        },
        dataLoader: false,
        pagination: false,
        filterMode: "local",
        sortMode: "local",
        columns: [
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
                headerSort: true,
            },
            {
                title: "Email",
                field: "email",
                minWidth: 200,
                headerSort: true,
            },
            {
                title: "Ruolo",
                field: "role",
                width: 120,
                headerSort: true,
                formatter: function (cell) {
                    const role = cell.getValue();
                    const labels = {
                        'operator': '<span class="badge bg-secondary">Operatore</span>',
                        'admin': '<span class="badge bg-primary">Admin</span>',
                        'god': '<span class="badge bg-danger">God</span>'
                    };
                    return labels[role] || role;
                }
            },
            {
                title: "Cambio PW",
                field: "force_password_change",
                width: 100,
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
                width: 150,
                formatter: function (cell) {
                    const value = cell.getValue();
                    return value ? formatDateTime(value) : '-';
                }
            }
            // Nessuna colonna Azioni per operatori (sola lettura)
        ],
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
});

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
