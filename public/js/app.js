/**
 * Shared front-end helpers: AJAX (Fetch API), toasts, confirmation dialogs,
 * form error display and small utilities used by every module.
 */
const App = (function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let confirmResolver = null;

    async function request(url, method = 'GET', payload = null) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        };

        const options = {
            method,
            headers,
            credentials: 'same-origin',
        };

        if (payload !== null) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }

        const response = await fetch(url, options);
        let data = {};

        try {
            data = await response.json();
        } catch (e) {
            data = {};
        }

        if (!response.ok) {
            const error = new Error(data.message || 'Something went wrong. Please try again.');
            error.status = response.status;
            error.data = data;
            throw error;
        }

        return data;
    }

    function toast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            return;
        }

        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-octagon-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill',
        };

        const colors = {
            success: 'text-success',
            danger: 'text-danger',
            warning: 'text-warning',
            info: 'text-primary',
        };

        const wrapper = document.createElement('div');
        wrapper.className = `toast align-items-center toast-${type}`;
        wrapper.setAttribute('role', 'alert');
        wrapper.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icons[type] || icons.info} ${colors[type] || colors.info}"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;

        container.appendChild(wrapper);
        const instance = bootstrap.Toast.getOrCreateInstance(wrapper, { delay: 3500 });
        instance.show();
        wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
    }

    function confirmAction(message, options = {}) {
        const modalElement = document.getElementById('confirmModal');
        if (!modalElement) {
            return Promise.resolve(window.confirm(message));
        }

        const {
            title = 'Please confirm',
            confirmText = 'Yes, continue',
            danger = true,
        } = options;

        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalBody').textContent = message;

        const button = document.getElementById('confirmModalButton');
        button.textContent = confirmText;
        button.className = `btn ${danger ? 'btn-danger' : 'btn-teal'}`;

        bootstrap.Modal.getOrCreateInstance(modalElement).show();

        return new Promise((resolve) => {
            confirmResolver = resolve;
        });
    }

    function clearFormErrors(form) {
        form.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));
        form.querySelectorAll('.js-error').forEach((element) => element.remove());
    }

    function showFormErrors(form, errors = {}) {
        let shown = 0;

        Object.entries(errors).forEach(([field, messages]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input) {
                return;
            }

            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback js-error d-block';
            feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
            const container = input.closest('.mb-3, .mb-2, .col-md-6, .col-md-4, .col-12, .col-md-3') || input.parentElement;
            container?.appendChild(feedback);
            shown++;
        });

        const firstMessage = Object.values(errors)[0];

        if (shown === 0 && firstMessage) {
            toast(Array.isArray(firstMessage) ? firstMessage[0] : firstMessage, 'danger');
        }
    }

    function setLoading(button, loading, loadingText = 'Saving...') {
        if (!button) {
            return;
        }

        if (loading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
        } else {
            button.disabled = false;
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }
        }
    }

    function debounce(callback, wait = 400) {
        let timer = null;

        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => callback.apply(this, args), wait);
        };
    }

    function searchParams(form) {
        const params = new URLSearchParams();

        new FormData(form).forEach((value, key) => {
            if (value !== null && value !== '') {
                params.set(key, value);
            }
        });

        return params;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function formatMoney(value) {
        const number = Number(value || 0);

        return number.toLocaleString('en-PK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    const get = (url) => request(url, 'GET');
    const post = (url, payload) => request(url, 'POST', payload);
    const put = (url, payload) => request(url, 'PUT', payload);
    const patch = (url, payload) => request(url, 'PATCH', payload);
    const del = (url) => request(url, 'DELETE');

    document.addEventListener('DOMContentLoaded', () => {
        const modalElement = document.getElementById('confirmModal');
        const confirmButton = document.getElementById('confirmModalButton');

        confirmButton?.addEventListener('click', () => {
            bootstrap.Modal.getOrCreateInstance(modalElement).hide();
            if (confirmResolver) {
                confirmResolver(true);
                confirmResolver = null;
            }
        });

        modalElement?.addEventListener('hidden.bs.modal', () => {
            if (confirmResolver) {
                confirmResolver(false);
                confirmResolver = null;
            }
        });

        const sidebarToggle = document.getElementById('sidebarToggle');
        const backdrop = document.querySelector('.sidebar-backdrop');

        sidebarToggle?.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
        backdrop?.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });

    return {
        get,
        post,
        put,
        patch,
        delete: del,
        toast,
        confirm: confirmAction,
        clearFormErrors,
        showFormErrors,
        setLoading,
        debounce,
        searchParams,
        escapeHtml,
        formatMoney,
    };
})();

window.App = App;
