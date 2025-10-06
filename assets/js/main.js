/**
 * Tour Operations Management System - Main JavaScript
 * Handles UI interactions, form validation, and performance optimizations
 */

// Global configuration
const APP_CONFIG = window.APP_CONFIG || {};
const BASE_URL = APP_CONFIG.baseUrl || '';
const CSRF_TOKEN = APP_CONFIG.csrfToken || '';

// Utility functions
const utils = {
    // Show loading spinner
    showLoading: () => {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.classList.remove('hidden');
        }
    },

    // Hide loading spinner
    hideLoading: () => {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.classList.add('hidden');
        }
    },

    // Show toast notification
    showToast: (message, type = 'info', duration = 5000) => {
        const toastContainer = document.getElementById('toast-container') || utils.createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${utils.getToastIcon(type)} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: duration
        });

        bsToast.show();

        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    },

    // Get toast icon based on type
    getToastIcon: (type) => {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    // Create toast container if it doesn't exist
    createToastContainer: () => {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    },

    // Format currency
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    },

    // Validate email
    validateEmail: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Debounce function
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // AJAX request helper
    ajax: async (url, options = {}) => {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            credentials: 'same-origin'
        };

        const config = { ...defaultOptions, ...options };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            utils.showLoading();
            const response = await fetch(url, config);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            return await response.text();
        } catch (error) {
            console.error('AJAX request failed:', error);
            utils.showToast('Request failed. Please try again.', 'error');
            throw error; // Re-throw the error for further handling
        } finally {
            utils.hideLoading();
        }
    },

    // Generic See More initializer
    initSeeMore: () => {
        // Any table or container with data-see-more will get client-side reveal
        const targets = document.querySelectorAll('[data-see-more]');
        targets.forEach((el) => {
            const batch = parseInt(el.getAttribute('data-see-more') || '20', 10);
            let items = [];
            // Support tables (use tbody rows) and generic containers (use direct children)
            if (el.tagName === 'TABLE') {
                const tbody = el.tBodies && el.tBodies[0];
                if (!tbody) return;
                items = Array.from(tbody.querySelectorAll('tr'));
            } else {
                items = Array.from(el.children);
            }
            if (!items.length || items.length <= batch) return;

            // Hide beyond batch
            items.forEach((node, idx) => { if (idx >= batch) node.style.display = 'none'; });

            // Insert button after the element
            const btnWrap = document.createElement('div');
            btnWrap.className = 'text-center mt-3 mb-2';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-primary btn-sm rounded-pill px-4';
            btn.textContent = 'See more';
            btnWrap.appendChild(btn);
            el.parentNode.insertBefore(btnWrap, el.nextSibling);

            let shown = batch;
            btn.addEventListener('click', () => {
                const next = shown + batch;
                for (let i = shown; i < Math.min(next, items.length); i++) {
                    items[i].style.display = '';
                }
                shown = Math.min(next, items.length);
                if (shown >= items.length) {
                    btnWrap.remove();
                }
            });
        });
    }
};

// Form validation
const formValidator = {
    // Validate booking form
    validateBookingForm: (form) => {
        const errors = [];

        // Required fields
        const requiredFields = ['guest_name', 'guest_email', 'travel_date', 'pax'];
        requiredFields.forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input || !input.value.trim()) {
                errors.push(`${field.replace('_', ' ')} is required`);
            }
        });

        // Email validation
        const emailInput = form.querySelector('[name="guest_email"]');
        if (emailInput && emailInput.value && !utils.validateEmail(emailInput.value)) {
            errors.push('Please enter a valid email address');
        }

        // Travel date validation
        const dateInput = form.querySelector('[name="travel_date"]');
        if (dateInput && dateInput.value) {
            const selectedDate = new Date(dateInput.value);
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 0, 0, 0);

            if (selectedDate < tomorrow) {
                errors.push('Travel date must be at least tomorrow');
            }
        }

        // Pax validation
        const paxInput = form.querySelector('[name="pax"]');
        if (paxInput && paxInput.value) {
            const pax = parseInt(paxInput.value);
            if (pax < 1 || pax > 50) {
                errors.push('Number of passengers must be between 1 and 50');
            }
        }

        return errors;
    },

    // Show form errors
    showErrors: (form, errors) => {
        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Remove previous error messages
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.remove();
        });

        // Show new errors
        errors.forEach(error => {
            utils.showToast(error, 'error');
        });
    }
};

// UI enhancements
const uiEnhancer = {
    // Initialize all UI enhancements
    init: () => {
        uiEnhancer.initAutoHideAlerts();
        uiEnhancer.initFormValidation();
        uiEnhancer.initCardInteractions();
        uiEnhancer.initTableInteractions();
        uiEnhancer.initModalEnhancements();
        uiEnhancer.initSearchFunctionality();
        uiEnhancer.initPaginationLoading();
        uiEnhancer.initSeeMore();
        uiEnhancer.initNavbarScroll();
        uiEnhancer.initDarkMode();
        uiEnhancer.initFlatpickr();
        uiEnhancer.initLazyLoading();
        uiEnhancer.initPerformanceOptimizations();
    },

    // Auto-hide alerts
    initAutoHideAlerts: () => {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    },

    // Form validation
    initFormValidation: () => {
        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', (e) => {
                const errors = formValidator.validateBookingForm(bookingForm);
                if (errors.length > 0) {
                    e.preventDefault();
                    formValidator.showErrors(bookingForm, errors);
                }
            });
        }
    },

    // Flatpickr initialization
    initFlatpickr: () => {
        const dateInputs = document.querySelectorAll('input[type="date"], .flatpickr-input');
        dateInputs.forEach(input => {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(input, {
                    minDate: 'tomorrow',
                    dateFormat: 'Y-m-d',
                    theme: 'material_blue',
                    disableMobile: true,
                    allowInput: true,
                    clickOpens: true,
                    locale: {
                        firstDayOfWeek: 1
                    }
                });
            } else {
                console.warn('Flatpickr library is not loaded.');
            }
        });
    }
};

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    utils.hideLoading();
    uiEnhancer.init();
});