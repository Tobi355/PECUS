const PECUS_API_BASE_URL = window.PECUS_API_BASE_URL || 'http://localhost:8000/api';

class PecusApiClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }

    getToken() {
        return localStorage.getItem('pecus_token');
    }

    setToken(token) {
        if (!token) return;
        localStorage.setItem('pecus_token', token);
    }

    clearToken() {
        localStorage.removeItem('pecus_token');
        localStorage.removeItem('pecus_user');
    }

    getUserData() {
        const raw = localStorage.getItem('pecus_user');
        if (!raw) return null;

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error('Invalid stored user', error);
            return null;
        }
    }

    saveUserData(user) {
        if (!user) return;
        localStorage.setItem('pecus_user', JSON.stringify(user));
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const defaultHeaders = {};
        const token = this.getToken();

        if (!(options.body instanceof FormData)) {
            defaultHeaders['Content-Type'] = 'application/json';
            defaultHeaders.Accept = 'application/json';
        }

        if (token && !options.skipAuth) {
            defaultHeaders.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(url, {
            ...options,
            headers: {
                ...defaultHeaders,
                ...(options.headers || {}),
            },
        });

        if (response.status === 401) {
            this.clearToken();
            throw new Error('Tu sesión expiró. Iniciá sesión nuevamente.');
        }

        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json') ? await response.json() : await response.text();

        if (!response.ok) {
            const message = typeof payload === 'object' && payload
                ? payload.message || payload.error || 'Hubo un problema al comunicarnos con PECUS.'
                : 'Hubo un problema al comunicarnos con PECUS.';

            if (Array.isArray(payload?.errors)) {
                throw new Error(payload.errors.join(', '));
            }

            if (payload?.errors && typeof payload.errors === 'object') {
                const firstError = Object.values(payload.errors).flat()[0];
                if (firstError) {
                    throw new Error(firstError);
                }
            }

            throw new Error(message);
        }

        return payload;
    }

    async login(email, password) {
        const data = await this.request('/login', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        });

        if (data?.token) {
            this.setToken(data.token);
            this.saveUserData(data.user);
        }

        return data;
    }

    async register(payload) {
        const data = await this.request('/register', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        if (data?.token) {
            this.setToken(data.token);
            this.saveUserData(data.user);
        }

        return data;
    }

    async logout() {
        try {
            await this.request('/logout', { method: 'POST' });
        } finally {
            this.clearToken();
        }
    }

    async getUser() {
        return this.request('/user');
    }

    async getDashboard() {
        return this.request('/dashboard');
    }

    async getSources() {
        return this.request('/sources');
    }

    async getCategories() {
        return this.request('/categories');
    }

    async getMovements() {
        return this.request('/movements');
    }

    async getReceipts() {
        return this.request('/receipts');
    }

    async uploadReceipt(file) {
        const formData = new FormData();
        formData.append('file', file);

        return this.request('/receipts', {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json' },
        });
    }

    async processReceipt(receiptId) {
        return this.request(`/receipts/${receiptId}/process`, {
            method: 'POST',
        });
    }

    async reviewReceipt(receiptId, payload) {
        return this.request(`/receipts/${receiptId}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });
    }

    async confirmReceipt(receiptId) {
        return this.request(`/receipts/${receiptId}/confirm`, {
            method: 'POST',
        });
    }
}

window.PecusApi = new PecusApiClient(PECUS_API_BASE_URL);

window.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = loginForm.querySelector('button[type="submit"]');
            const email = document.getElementById('login-email')?.value?.trim();
            const password = document.getElementById('login-password')?.value;

            if (!email || !password) {
                Toast?.show('Ingresá tu email y contraseña.', 'error');
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Ingresando...';

            try {
                await window.PecusApi.login(email, password);
                window.location.href = 'dashboard.html';
            } catch (error) {
                console.error(error);
                Toast?.show(error.message || 'No se pudo iniciar sesión.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Iniciar sesión';
            }
        });
    }

    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const name = document.getElementById('register-name')?.value?.trim();
            const surname = document.getElementById('register-surname')?.value?.trim();
            const email = document.getElementById('register-email')?.value?.trim();
            const password = document.getElementById('register-password')?.value;
            const confirmPassword = document.getElementById('register-confirm-password')?.value;

            if (!name || !surname || !email || !password || !confirmPassword) {
                Toast?.show('Completá todos los campos.', 'error');
                return;
            }

            if (password !== confirmPassword) {
                Toast?.show('Las contraseñas no coinciden.', 'error');
                return;
            }

            const submitButton = registerForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Creando cuenta...';

            try {
                await window.PecusApi.register({ name, surname, email, password, password_confirmation: confirmPassword });
                window.location.href = 'dashboard.html';
            } catch (error) {
                console.error(error);
                Toast?.show(error.message || 'No se pudo crear la cuenta.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Crear cuenta';
            }
        });
    }
});
