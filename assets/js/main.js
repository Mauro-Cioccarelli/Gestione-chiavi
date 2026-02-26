/**
 * Main JavaScript - Funzioni comuni
 */

// ============================================================================
// Utility functions
// ============================================================================

/**
 * Mostra alert
 */
function showAlert(type, message, duration = 5000) {
    const container = document.querySelector('.main-content') || document.body;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show flash-message`;
    alert.innerHTML = `
        <i class="bi bi-${getAlertIcon(type)} me-2"></i>
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.insertBefore(alert, container.firstChild);
    
    // Auto-dismiss
    if (duration > 0) {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, duration);
    }
}

/**
 * Ottieni icona per tipo alert
 */
function getAlertIcon(type) {
    const icons = {
        success: 'check-circle-fill',
        danger: 'exclamation-triangle-fill',
        warning: 'exclamation-circle-fill',
        info: 'info-circle-fill'
    };
    return icons[type] || 'info-circle-fill';
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Fetch con gestione errori e CSRF
 */
async function fetchWithCSRF(url, options = {}) {
    const defaultOptions = {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
        }
    };
    
    // Aggiungi CSRF token al body per POST/PUT
    if (options.method === 'POST' || options.method === 'PUT') {
        if (!options.body) {
            options.body = new FormData();
        }
        if (options.body instanceof FormData) {
            options.body.set('csrf_token', window.CSRF_TOKEN || '');
        }
    }
    
    const mergedOptions = {
        ...defaultOptions,
        ...options
    };
    
    try {
        const response = await fetch(url, mergedOptions);
        
        // Gestisci redirect su 401
        if (response.status === 401) {
            const data = await response.json().catch(() => ({}));
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
        }
        
        return response;
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

/**
 * Fetch JSON con gestione errori
 */
async function fetchJSON(url, options = {}) {
    const response = await fetchWithCSRF(url, options);
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.error || data.message || 'Errore di comunicazione');
    }
    
    return data;
}

/**
 * Conferma azione
 */
function confirmAction(message = 'Sei sicuro di voler procedere?') {
    return new Promise((resolve) => {
        if (confirm(message)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

/**
 * Loading overlay
 */
function showLoading() {
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Caricamento...</span>
                </div>
                <p class="mt-2 text-muted">Caricamento...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.classList.remove('hidden');
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

// ============================================================================
// Sidebar mobile toggle
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('main-wrapper');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            
            // Crea overlay per mobile
            let overlay = document.getElementById('sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'sidebar-overlay';
                overlay.className = 'sidebar-overlay';
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
                document.body.appendChild(overlay);
            }
            overlay.classList.toggle('show');
        });
    }
    
    // Chiudi sidebar al click su link mobile
    sidebar?.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                sidebar.classList.remove('show');
                document.getElementById('sidebar-overlay')?.classList.remove('show');
            }
        });
    });
});

// ============================================================================
// Form validation
// ============================================================================

/**
 * Validazione form client-side
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Aggiungi messaggio errore se non presente
            if (!field.nextElementSibling?.classList.contains('invalid-feedback')) {
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Campo obbligatorio';
                field.parentNode.appendChild(feedback);
            }
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// ============================================================================
// Auto-dismiss alerts
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        const dismiss = alert.querySelector('[data-bs-dismiss="alert"]');
        if (dismiss) {
            dismiss.addEventListener('click', function() {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }
    });
});

// ============================================================================
// Export functions
// ============================================================================

window.showAlert = showAlert;
window.fetchWithCSRF = fetchWithCSRF;
window.fetchJSON = fetchJSON;
window.confirmAction = confirmAction;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.validateForm = validateForm;
