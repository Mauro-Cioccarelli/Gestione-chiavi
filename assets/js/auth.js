/**
 * Autenticazione - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // =========================================================================
    // Login form
    // =========================================================================
    
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Accesso...';
            
            fetchJSON(window.APP_URL + '/ajax/auth/login.php', {
                method: 'POST',
                body: formData
            })
            .then(data => {
                if (data.success) {
                    // Redirect
                    window.location.href = data.redirect || window.APP_URL + '/dashboard.php';
                } else {
                    showAlert('danger', data.error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                showAlert('danger', 'Errore di comunicazione: ' + err.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
    
    // =========================================================================
    // Cambio password
    // =========================================================================
    
    const changePasswordForm = document.getElementById('form-change-password');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Validazione client-side
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword !== confirmPassword) {
                showAlert('danger', 'Le password non coincidono');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('danger', 'La password deve essere di almeno 6 caratteri');
                return;
            }
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Elaborazione...';
            
            fetchJSON(window.APP_URL + '/ajax/auth/change-password.php', {
                method: 'POST',
                body: formData
            })
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    
                    // Reset form
                    this.reset();
                    
                    // Se cambio forzato, redirect a dashboard
                    const forceParam = new URLSearchParams(window.location.search).get('force');
                    if (forceParam === '1') {
                        setTimeout(() => {
                            window.location.href = window.APP_URL + '/dashboard.php';
                        }, 1500);
                    }
                } else {
                    showAlert('danger', data.error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                showAlert('danger', 'Errore di comunicazione: ' + err.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
    
    // =========================================================================
    // Recupera password
    // =========================================================================
    
    const resetPasswordForm = document.getElementById('form-reset-password');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const resultDiv = document.getElementById('reset-result');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Elaborazione...';
            
            fetchJSON(window.APP_URL + '/ajax/auth/reset-password.php', {
                method: 'POST',
                body: formData
            })
            .then(data => {
                if (data.success) {
                    if (resultDiv) {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                ${data.message}
                            </div>
                        `;
                        
                        // Solo sviluppo - mostra token
                        if (data.token && data.username) {
                            resultDiv.innerHTML += `
                                <div class="alert alert-info mt-2">
                                    <strong>Solo sviluppo:</strong><br>
                                    Username: ${data.username}<br>
                                    Token: ${data.token}<br>
                                    <a href="${window.APP_URL}/utenti/reset-password-confirm.php?token=${data.token}" 
                                       class="btn btn-sm btn-primary mt-2">
                                        Vai al reset
                                    </a>
                                </div>
                            `;
                        }
                    }
                    this.reset();
                } else {
                    showAlert('danger', data.error);
                }
            })
            .catch(err => {
                showAlert('danger', 'Errore di comunicazione: ' + err.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
    
    // =========================================================================
    // Toggle visibilità password
    // =========================================================================
    
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
    
    // =========================================================================
    // Auto-hide alert dopo submit
    // =========================================================================
    
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
