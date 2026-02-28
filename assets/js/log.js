/**
 * Log Audit - JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    const tableElement = document.getElementById('log-table');

    if (!tableElement) return;

    // Inizializza Tabulator con remote pagination
    const table = new Tabulator(tableElement, {
        ajaxURL: window.APP_URL + "/ajax/log/list.php",
        dataLoader: false,
        pagination: true,
        paginationMode: "remote",
        filterMode: "remote",
        sortMode: "remote",
        layout: "fitColumns",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100],
        downloadConfig: {
            columnHeaders: true,
            columnGroups: false,
            rowGroups: false,
            columnOutput: function (column) {
                // Escludi colonna azioni dall'export
                return column.getField() !== 'actions';
            }
        },
        columns: [
            {
                title: "Data/Ora",
                field: "created_at",
                width: 180,
                headerSort: true,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    return `<div>${data.created_at_formatted}</div>
                            <small class="text-muted">${data.created_at_ago}</small>`;
                }
            },
            {
                title: "Utente",
                field: "user_name",
                width: 150,
                headerSort: true,
                formatter: function (cell) {
                    const value = cell.getValue();
                    return value
                        ? `<i class="bi bi-person me-1"></i>${value}`
                        : '<span class="text-muted">Sistema</span>';
                }
            },
            {
                title: "Azione",
                field: "action",
                width: 140,
                headerSort: true,
                formatter: function (cell) {
                    const action = cell.getValue();
                    const actionClass = getActionClass(action);
                    return `<span class="badge bg-${actionClass}">${action}</span>`;
                }
            },
            {
                title: "Entità",
                field: "entity_type",
                width: 120,
                headerSort: true,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    if (data.entity_type && data.entity_id) {
                        return `<span class="badge bg-info">${data.entity_type} #${data.entity_id}</span>`;
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            {
                title: "Dettagli",
                field: "message",
                minWidth: 250,
                headerSort: false,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    const action = data.action;
                    
                    // Se c'è un messaggio esplicito, usa quello
                    if (data.message) {
                        return escapeHtml(data.message);
                    }
                    
                    // Mostra dettagli in base al tipo di azione
                    if (data.details_decoded && typeof data.details_decoded === 'object') {
                        let html = '<small class="text-muted">';
                        const entries = Object.entries(data.details_decoded);
                        
                        entries.forEach(([k, v]) => {
                            // Salta movement_id (troppo tecnico)
                            if (k === 'movement_id') return;
                            
                            // Per user_deleted, mostra "Utente eliminato" invece di "deleted_user"
                            let label = k;
                            let value = v;
                            
                            if (k === 'deleted_user') {
                                label = 'Utente eliminato';
                                value = '<i class="bi bi-person me-1"></i>' + escapeHtml(v);
                            }
                            
                            // Per category_merged, mostra "Unita in" invece di "merged_into"
                            if (k === 'merged_into') {
                                label = 'Unita in';
                                value = '<i class="bi bi-folder me-1"></i>' + escapeHtml(v);
                            }
                            if (k === 'source_name') {
                                label = 'Categoria sorgente';
                                value = '<i class="bi bi-folder me-1"></i>' + escapeHtml(v);
                            }
                            
                            // Per category_updated, mostra formato "da → a"
                            if (typeof v === 'object' && v !== null && v.da !== undefined && v.a !== undefined) {
                                value = `<span class="text-danger">${escapeHtml(v.da)}</span> → <span class="text-success">${escapeHtml(v.a)}</span>`;
                            }
                            
                            const displayValue = typeof v === 'object' ? JSON.stringify(v) : String(v);
                            html += `<div><strong>${escapeHtml(label)}:</strong> ${value}</div>`;
                        });
                        
                        html += '</small>';
                        return html;
                    }
                    
                    // Per user_deleted senza dettagli, mostra ID
                    if (action === 'user_deleted' && data.entity_type === 'user' && data.entity_id) {
                        return '<small class="text-muted"><strong>Utente eliminato:</strong> ID #' + data.entity_id + '</small>';
                    }
                    
                    return '<span class="text-muted">-</span>';
                }
            },
            {
                title: "IP",
                field: "ip_address",
                width: 130,
                headerSort: true,
                formatter: function (cell) {
                    const value = cell.getValue();
                    return value ? `<code>${escapeHtml(value)}</code>` : 'N/A';
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
                },
                "download": {
                    "csv": "Scarica CSV",
                    "pdf": "Scarica PDF",
                    "xlsx": "Scarica Excel",
                    "copy": "Copia"
                }
            }
        },
        initialSort: [
            { column: "created_at", dir: "desc" }
        ],
        headerToolbar: [
            "download"
        ]
    });

    // Helper: classe badge per azione
    function getActionClass(action) {
        const classes = {
            'login_success': 'success',
            'checkin': 'success',
            'user_created': 'success',
            'category_created': 'success',
            'category_restored': 'success',
            'login_failed': 'warning',
            'checkout': 'warning',
            'logout': 'danger',
            'user_deleted': 'danger',
            'dismise': 'danger',
            'category_deleted': 'danger',
            'password_changed': 'info',
            'password_reset_requested': 'info',
            'create': 'info',
            'category_updated': 'info',
            'update': 'secondary',
            'category_merged': 'warning'
        };
        return classes[action] || 'secondary';
    }

    // Applica filtri personalizzati
    function applyCustomFilters() {
        let customFilters = [];

        const userIdSelect = document.getElementById('user_id');
        if (userIdSelect && userIdSelect.value) {
            customFilters.push({ field: "user_id", type: "=", value: userIdSelect.value });
        }

        const actionSelect = document.getElementById('action');
        if (actionSelect && actionSelect.value) {
            customFilters.push({ field: "action", type: "=", value: actionSelect.value });
        }

        const entitySelect = document.getElementById('entity_type');
        if (entitySelect && entitySelect.value) {
            customFilters.push({ field: "entity_type", type: "=", value: entitySelect.value });
        }

        const fromInput = document.getElementById('from_date');
        if (fromInput && fromInput.value) {
            customFilters.push({ field: "from_date", type: ">=", value: fromInput.value });
        }

        const toInput = document.getElementById('to_date');
        if (toInput && toInput.value) {
            customFilters.push({ field: "to_date", type: "<=", value: toInput.value });
        }

        table.setFilter(customFilters);
    }

    // Dopo che la tabella è costruita
    table.on("tableBuilt", function () {
        // Applica filtri dai parametri URL
        const urlParams = new URLSearchParams(window.location.search);
        let hasFilters = false;

        ['user_id', 'action', 'entity_type', 'from_date', 'to_date'].forEach(param => {
            const value = urlParams.get(param);
            if (value) {
                const el = document.getElementById(param);
                if (el) {
                    el.value = value;
                    hasFilters = true;
                }
            }
        });

        if (hasFilters) {
            applyCustomFilters();
        }
    });

    // Submit del form filtri
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            applyCustomFilters();
        });
    }

    // Pulsante Applica Filtri
    const applyBtn = document.getElementById('btn-apply-filters');
    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            applyCustomFilters();
        });
    }

    // Reset filtri
    const resetBtn = document.getElementById('btn-reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            document.getElementById('user_id').value = '';
            document.getElementById('action').value = '';
            document.getElementById('entity_type').value = '';
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
            applyCustomFilters();
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
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    if (text === null || text === undefined) return '';
    div.textContent = String(text);
    return div.innerHTML;
}
