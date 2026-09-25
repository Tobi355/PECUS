// Reusable Components and Utility Functions

// Toast Notification System
class Toast {
    static show(message, type = 'info', duration = 3000) {
        // Remove any existing toast
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.role = 'alert';
        toast.ariaLive = 'assertive';
        toast.ariaAtomic = 'true';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="tooltip"></button>
            </div>
        `;

        // Add to body
        document.body.appendChild(toast);

        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();

        // Remove from DOM after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

// Modal Helper
class Modal {
    static show(content, options = {}) {
        // Remove any existing modal
        const existingModal = document.querySelector('.modal-backdrop');
        if (existingModal) {
            existingModal.remove();
        }
        const existingModalContent = document.querySelector('.modal');
        if (existingModalContent) {
            existingModalContent.remove();
        }

        // Create modal backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);

        // Create modal container
        const modalContainer = document.createElement('div');
        modalContainer.className = 'modal fade show';
        modalContainer.style.display = 'block';
        modalContainer.setAttribute('tabindex', '-1');
        modalContainer.innerHTML = `
            <div class="modal-dialog ${options.size || ''}">
                <div class="modal-content">
                    ${content}
                </div>
            </div>
        `;
        document.body.appendChild(modalContainer);

        // Add event listener for clicks outside modal to close
        modalContainer.addEventListener('click', (e) => {
            if (e.target === modalContainer) {
                Modal.hide();
            }
        });

        // Add escape key listener
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                Modal.hide();
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Return hide function
        return () => {
            Modal.hide();
            document.removeEventListener('keydown', escapeHandler);
        };
    }

    static hide() {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        const modal = document.querySelector('.modal');
        if (modal) {
            modal.remove();
        }
    }
}

// Form Helper
class FormHelper {
    static validateEmail(email) {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    static validateRequired(value) {
        return value && value.trim() !== '';
    }

    static showError(input, message) {
        const formGroup = input.closest('.form-group') || input.parentElement;
        // Remove existing error
        const existingError = formGroup.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        // Add error class
        input.classList.add('is-invalid');
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        formGroup.appendChild(errorDiv);
    }

    static clearError(input) {
        const formGroup = input.closest('.form-group') || input.parentElement;
        // Remove error class
        input.classList.remove('is-invalid');
        // Remove error message
        const error = formGroup.querySelector('.invalid-feedback');
        if (error) {
            error.remove();
        }
    }
}

// Utility Functions
class Utils {
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    static copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            Toast.show('Copiado al portapapeles', 'success');
        }).catch(err => {
            Toast.show('Error al copiar: ' + err, 'error');
        });
    }

    static downloadBlob(blob, filename) {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    }
}

// Export for use in other files
window.Toast = Toast;
window.Modal = Modal;
window.FormHelper = FormHelper;
window.Utils = Utils;