// Main Application Class
class PecusApp {
    constructor() {
        this.currentPage = document.body.dataset.page || 'dashboard';
        this.state = {
            user: null,
            dashboard: null,
            sources: [],
            categories: [],
            movements: [],
            receipts: [],
            errors: {},
        };
        this.init();
    }

    async init() {
        if (window.lucide) {
            window.lucide.createIcons();
        }

        if (document.getElementById('login-form')) {
            return;
        }

        if (document.getElementById('register-form')) {
            return;
        }

        if (!document.getElementById('sidebar-placeholder')) {
            return;
        }

        if (!PecusApi || !PecusApi.getToken()) {
            window.location.href = 'login.html';
            return;
        }

        try {
            this.loadSidebar();
            await this.loadProtectedData();
            this.renderPage(this.currentPage);
        } catch (error) {
            console.error('Session init error:', error);
            if (PecusApi) {
                PecusApi.clearToken();
            }
            Toast?.show(error.message || 'No se pudo cargar tu sesión.', 'error');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1200);
        }
    }

    async loadProtectedData() {
        const requests = await Promise.allSettled([
            PecusApi.getUser(), PecusApi.getDashboard(), PecusApi.getSources(),
            PecusApi.getCategories(), PecusApi.getMovements(), PecusApi.getReceipts(),
        ]);
        const [user, dashboard, sources, categories, movements, receipts] = requests.map((result) =>
            result.status === 'fulfilled' ? result.value : null);
        this.state.errors = Object.fromEntries(requests.flatMap((result, index) =>
            result.status === 'rejected' ? [[['user', 'dashboard', 'sources', 'categories', 'movements', 'receipts'][index], result.reason?.message || 'No se pudieron cargar los datos.']] : []));
        this.state.user = user;
        this.state.dashboard = dashboard;
        this.state.sources = Array.isArray(sources) ? sources : [];
        this.state.categories = Array.isArray(categories) ? categories : [];
        this.state.movements = Array.isArray(movements) ? movements : [];
        this.state.receipts = Array.isArray(receipts) ? receipts : [];

        this.loadSidebar();
    }

    getDisplayUser() {
        const user = this.state.user || {};
        return {
            name: user.name || '',
            surname: user.surname || '',
            email: user.email || '',
        };
    }

    loadSidebar() {
        const sidebarPlaceholder = document.getElementById('sidebar-placeholder');
        if (!sidebarPlaceholder) {
            return;
        }

        const profile = this.getDisplayUser();
        sidebarPlaceholder.innerHTML = this.getSidebarHTML(profile);
        this.setupSidebarEvents();
    }

    renderPage(pageName) {
        const mainContentPlaceholder = document.getElementById('main-content-placeholder');
        if (!mainContentPlaceholder) {
            return;
        }

        mainContentPlaceholder.innerHTML = this.getPageContent(pageName);
        this.setupPageSpecificEvents(pageName);
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    getSidebarHTML(profile) {
        const items = [
            { page: 'dashboard', icon: 'home', label: 'Dashboard' },
            { page: 'movements', icon: 'arrow-left-right', label: 'Movimientos' },
            { page: 'receipts', icon: 'receipt', label: 'Comprobantes' },
            { page: 'sources', icon: 'building-2', label: 'Fuentes' },
            { page: 'categories', icon: 'tag', label: 'Categorías' },
            { page: 'analysis', icon: 'bar-chart-3', label: 'Análisis' },
            { page: 'goals', icon: 'target', label: 'Metas' },
            { page: 'budgets', icon: 'wallet', label: 'Presupuestos' },
            { page: 'assistant', icon: 'bot', label: 'Asistente financiero' },
            { page: 'notifications', icon: 'bell', label: 'Notificaciones' },
            { page: 'settings', icon: 'settings', label: 'Configuración' },
        ];

        const navItems = items.map(item => `
            <li class="nav-item">
                <a class="nav-link ${this.currentPage === item.page ? 'active' : ''}" href="${item.page}.html" data-tooltip="${item.label}">
                    <i class="icon" data-lucide="${item.icon}"></i>
                    <span class="nav-text">${item.label}</span>
                </a>
            </li>`).join('');

        return `
            <button class="sidebar-mobile-toggle" id="sidebar-mobile-toggle" aria-label="Abrir menú">
                <i data-lucide="menu"></i>
            </button>
            <aside class="sidebar collapsed" id="sidebar">
                <a class="sidebar-logo" href="dashboard.html">
                    <span class="logo-text">PECUS</span>
                </a>
                <nav class="sidebar-nav" id="sidebar-nav">
                    <ul class="nav flex-column">
                        ${navItems}
                    </ul>
                </nav>
                <div class="sidebar-footer">
                    <a class="sidebar-profile" href="settings.html">
                        <i class="icon" data-lucide="user-circle"></i>
                        <div class="profile-info">
                            <span class="profile-name">${profile.name} ${profile.surname}</span>
                            <small class="profile-email">${profile.email}</small>
                        </div>
                    </a>
                </div>
            </aside>
            <div class="sidebar-overlay" id="sidebar-overlay"></div>
        `;
    }

    setupSidebarEvents() {
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const mobileToggle = document.getElementById('sidebar-mobile-toggle');

        // Navigation links use real hrefs (each page is its own HTML file),
        // so no click interception is needed here — the browser navigates.

        // Mobile: open/close sidebar as an off-canvas panel
        const openMobileSidebar = () => {
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.add('active');
        };
        const closeMobileSidebar = () => {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        };

        mobileToggle.addEventListener('click', openMobileSidebar);
        sidebarOverlay.addEventListener('click', closeMobileSidebar);

        // Desktop: expand on hover, collapse on mouse leave
        sidebar.addEventListener('mouseenter', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('collapsed');
            }
        });
        sidebar.addEventListener('mouseleave', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.add('collapsed');
            }
        });
    }

    getPageContent(pageName) {
        switch (pageName) {
            case 'dashboard':
                return this.getDashboardContent();
            case 'movements':
                return this.getMovementsContent();
            case 'receipts':
                return this.getReceiptsContent();
            case 'sources':
                return this.getSourcesContent();
            case 'categories':
                return this.getCategoriesContent();
            case 'analysis':
                return this.getAnalysisContent();
            case 'goals':
                return this.getGoalsContent();
            case 'budgets':
                return this.getBudgetsContent();
            case 'assistant':
                return this.getAssistantContent();
            case 'notifications':
                return this.getNotificationsContent();
            case 'settings':
                return this.getSettingsContent();
            default:
                return '<div class="container"><h1>Página no encontrada</h1></div>';
        }
    }

    setupPageSpecificEvents(pageName) {
        if (pageName === 'receipts') {
            this.setupReceiptsPageEvents();
        }
        if (pageName === 'sources') {
            this.setupSourcesPageEvents();
        }
        if (pageName === 'categories') {
            this.setupCategoriesPageEvents();
        }
        if (pageName === 'movements') {
            this.setupMovementsPageEvents();
        }
        if (pageName === 'dashboard') {
            this.setupDashboardPageEvents();
        }
    }

    setupSourcesPageEvents() {
        // Create source form (inline)
        const createForm = document.getElementById('source-create-form');
        if (createForm) {
            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = createForm.querySelector('button[type="submit"]');
                button.disabled = true;
                try {
                    await PecusApi.createSource({
                        name: document.getElementById('source-name').value.trim(),
                        type: document.getElementById('source-type').value,
                        currency: document.getElementById('source-currency').value.trim().toUpperCase(),
                        initial_balance: Number(document.getElementById('source-initial-balance').value),
                        status: 'ACTIVE',
                    });
                    Toast?.show('Fuente guardada.', 'success');
                    await this.loadProtectedData();
                    this.renderPage('sources');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo guardar la fuente.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Create source modal form
        const createModalForm = document.getElementById('source-create-modal-form');
        if (createModalForm) {
            createModalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = createModalForm.querySelector('button[type="submit"]');
                button.disabled = true;
                try {
                    await PecusApi.createSource({
                        name: document.getElementById('modal-source-name').value.trim(),
                        type: document.getElementById('modal-source-type').value,
                        currency: document.getElementById('modal-source-currency').value.trim().toUpperCase(),
                        initial_balance: Number(document.getElementById('modal-source-initial-balance').value),
                        status: 'ACTIVE',
                    });
                    Toast?.show('Fuente creada.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('source-create-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('sources');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo crear la fuente.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Edit source modal form
        const editModalForm = document.getElementById('source-edit-modal-form');
        if (editModalForm) {
            editModalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = editModalForm.querySelector('button[type="submit"]');
                const sourceId = document.getElementById('modal-source-edit-id').value;
                button.disabled = true;
                try {
                    await PecusApi.updateSource(sourceId, {
                        name: document.getElementById('modal-source-edit-name').value.trim(),
                        type: document.getElementById('modal-source-edit-type').value,
                        currency: document.getElementById('modal-source-edit-currency').value.trim().toUpperCase(),
                        initial_balance: Number(document.getElementById('modal-source-edit-initial-balance').value),
                    });
                    Toast?.show('Fuente actualizada.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('source-edit-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('sources');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo actualizar la fuente.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Delete source confirm
        const deleteConfirmBtn = document.getElementById('source-delete-confirm-btn');
        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', async () => {
                const sourceId = deleteConfirmBtn.dataset.sourceId;
                deleteConfirmBtn.disabled = true;
                deleteConfirmBtn.textContent = 'Eliminando...';
                try {
                    await PecusApi.deleteSource(sourceId);
                    Toast?.show('Fuente eliminada.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('source-delete-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('sources');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo eliminar la fuente.', 'error');
                } finally {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.textContent = 'Eliminar';
                }
            });
        }

        // Edit/delete buttons
        document.querySelectorAll('[data-source-edit]').forEach((button) => {
            button.addEventListener('click', () => this.openSourceEditModal(button.dataset.sourceEdit));
        });

        document.querySelectorAll('[data-source-delete]').forEach((button) => {
            button.addEventListener('click', () => this.confirmSourceDelete(button.dataset.sourceDelete));
        });
    }

    setupReceiptsPageEvents() {
        const uploadForm = document.getElementById('receipt-upload-form');
        if (uploadForm) {
            uploadForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const fileInput = document.getElementById('receipt-file');
                const file = fileInput?.files?.[0];

                if (!file) {
                    Toast?.show('Seleccioná un comprobante para subir.', 'error');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                const isAllowedType = allowedTypes.includes(file.type) || ['jpg', 'jpeg', 'png', 'pdf'].includes(file.name.split('.').pop().toLowerCase());
                const maxSize = 10 * 1024 * 1024;

                if (!isAllowedType) {
                    Toast?.show('Formato no válido. Usá JPG, JPEG, PNG o PDF.', 'error');
                    return;
                }

                if (file.size > maxSize) {
                    Toast?.show('El archivo supera el límite de 10MB.', 'error');
                    return;
                }

                const submitButton = uploadForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Subiendo...';

                try {
                    const uploadedReceipt = await PecusApi.uploadReceipt(file);
                    Toast?.show('Comprobante guardado. Completá los datos manualmente para crear un movimiento.', 'success');
                    await this.loadProtectedData();
                    this.renderPage('receipts');
                } catch (error) {
                    console.error(error);
                    Toast?.show(error.message || 'No se pudo subir el comprobante.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="me-2" data-lucide="upload"></i> Subir comprobante';
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                }
            });
        }

        const reviewForm = document.getElementById('receipt-review-form');
        if (reviewForm) {
            reviewForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const receiptId = reviewForm.dataset.receiptId;
                const payload = {
                    amount: Number(document.getElementById('review-amount').value),
                    date: document.getElementById('review-date').value,
                    merchant: document.getElementById('review-merchant').value,
                    description: document.getElementById('review-description').value,
                    operation_type: document.getElementById('review-operation-type').value,
                    currency: document.getElementById('review-currency').value,
                    source_id: Number(document.getElementById('review-source').value),
                    category_id: Number(document.getElementById('review-category').value),
                };

                const submitButton = reviewForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Guardando...';

                try {
                    await PecusApi.reviewReceipt(receiptId, payload);
                    Toast?.show('Revisión guardada correctamente.', 'success');
                    await this.loadProtectedData();
                    this.renderPage('receipts');
                } catch (error) {
                    console.error(error);
                    Toast?.show(error.message || 'No se pudo guardar la revisión.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Guardar revisión';
                }
            });
        }

        const confirmButton = document.getElementById('receipt-confirm-button');
        if (confirmButton) {
            confirmButton.addEventListener('click', async () => {
                const receiptId = confirmButton.dataset.receiptId;
                confirmButton.disabled = true;
                confirmButton.textContent = 'Confirmando...';

                try {
                    const result = await PecusApi.confirmReceipt(receiptId);
                    Toast?.show('Comprobante confirmado y movimiento creado.', 'success');
                    console.log('Receipt confirmed:', result);
                    await this.loadProtectedData();
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    Toast?.show(error.message || 'No se pudo confirmar el comprobante.', 'error');
                    confirmButton.disabled = false;
                    confirmButton.textContent = 'Confirmar comprobante';
                }
            });
        }

        document.querySelectorAll('[data-receipt-file]').forEach((button) => {
            button.addEventListener('click', async () => {
                button.disabled = true;
                try {
                    await PecusApi.openReceiptFile(button.dataset.receiptFile);
                } catch (error) {
                    Toast?.show(error.message, 'error');
                } finally {
                    button.disabled = false;
                }
            });
        });
    }

    setupCategoriesPageEvents() {
        // Create category form (inline)
        const createForm = document.getElementById('category-create-form');
        if (createForm) {
            createForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = createForm.querySelector('button[type="submit"]');
                button.disabled = true;
                button.textContent = 'Guardando...';
                try {
                    await PecusApi.createCategory({
                        name: document.getElementById('category-name').value.trim(),
                        type: document.getElementById('category-type').value,
                        icon: document.getElementById('category-icon').value.trim() || 'tag',
                        color: document.getElementById('category-color').value.trim() || '#6c757d',
                    });
                    Toast?.show('Categoría creada.', 'success');
                    await this.loadProtectedData();
                    this.renderPage('categories');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo crear la categoría.', 'error');
                } finally {
                    button.disabled = false;
                    button.textContent = 'Guardar categoría';
                }
            });
        }

        // Create category modal form
        const createModalForm = document.getElementById('category-create-modal-form');
        if (createModalForm) {
            createModalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = createModalForm.querySelector('button[type="submit"]');
                button.disabled = true;
                try {
                    await PecusApi.createCategory({
                        name: document.getElementById('modal-category-name').value.trim(),
                        type: document.getElementById('modal-category-type').value,
                        icon: document.getElementById('modal-category-icon').value.trim() || 'tag',
                        color: document.getElementById('modal-category-color').value.trim() || '#6c757d',
                    });
                    Toast?.show('Categoría creada.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('category-create-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('categories');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo crear la categoría.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Edit category modal form
        const editModalForm = document.getElementById('category-edit-modal-form');
        if (editModalForm) {
            editModalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = editModalForm.querySelector('button[type="submit"]');
                const categoryId = document.getElementById('modal-category-edit-id').value;
                button.disabled = true;
                try {
                    await PecusApi.updateCategory(categoryId, {
                        name: document.getElementById('modal-category-edit-name').value.trim(),
                        type: document.getElementById('modal-category-edit-type').value,
                        icon: document.getElementById('modal-category-edit-icon').value.trim() || 'tag',
                        color: document.getElementById('modal-category-edit-color').value.trim() || '#6c757d',
                    });
                    Toast?.show('Categoría actualizada.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('category-edit-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('categories');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo actualizar la categoría.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Delete category confirm
        const deleteConfirmBtn = document.getElementById('category-delete-confirm-btn');
        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', async () => {
                const categoryId = deleteConfirmBtn.dataset.categoryId;
                deleteConfirmBtn.disabled = true;
                deleteConfirmBtn.textContent = 'Eliminando...';
                try {
                    await PecusApi.deleteCategory(categoryId);
                    Toast?.show('Categoría eliminada.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('category-delete-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('categories');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo eliminar la categoría.', 'error');
                } finally {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.textContent = 'Eliminar';
                }
            });
        }

        // Edit/delete buttons
        document.querySelectorAll('[data-category-edit]').forEach((button) => {
            button.addEventListener('click', () => this.openCategoryEditModal(button.dataset.categoryEdit));
        });

        document.querySelectorAll('[data-category-delete]').forEach((button) => {
            button.addEventListener('click', () => this.confirmCategoryDelete(button.dataset.categoryDelete));
        });
    }

    setupMovementsPageEvents() {
        const filterForm = document.getElementById('movements-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await this.applyMovementFilters();
            });
        }

        const clearBtn = document.getElementById('movements-clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', async () => {
                document.getElementById('movements-filter-form')?.reset();
                await this.applyMovementFilters();
            });
        }

        const createBtn = document.getElementById('movement-create-btn');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.openMovementCreateModal());
        }

        // Create movement modal form
        const createModalForm = document.getElementById('movement-create-form');
        if (createModalForm) {
            createModalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = createModalForm.querySelector('button[type="submit"]');
                button.disabled = true;
                try {
                    const formData = new FormData(createModalForm);
                    const payload = {
                        description: formData.get('description'),
                        type: formData.get('type'),
                        amount: Number(formData.get('amount')),
                        currency: formData.get('currency'),
                        operation_date: formData.get('operation_date'),
                        source_id: Number(formData.get('source_id')),
                        category_id: Number(formData.get('category_id')),
                        notes: formData.get('notes') || '',
                    };
                    await PecusApi.createMovement(payload);
                    Toast?.show('Movimiento creado.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('movement-create-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('movements');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo crear el movimiento.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Edit movement modal form
        const editModalForm = document.getElementById('movement-edit-form');
        if (editModalForm) {
            editModalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = editModalForm.querySelector('button[type="submit"]');
                const movementId = document.getElementById('movement-edit-id').value;
                button.disabled = true;
                try {
                    const formData = new FormData(editModalForm);
                    const payload = {
                        description: formData.get('description'),
                        type: formData.get('type'),
                        amount: Number(formData.get('amount')),
                        currency: formData.get('currency'),
                        operation_date: formData.get('operation_date'),
                        source_id: Number(formData.get('source_id')),
                        category_id: Number(formData.get('category_id')),
                        notes: formData.get('notes') || '',
                    };
                    await PecusApi.updateMovement(movementId, payload);
                    Toast?.show('Movimiento actualizado.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('movement-edit-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('movements');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo actualizar el movimiento.', 'error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        // Delete movement confirm
        const deleteConfirmBtn = document.getElementById('movement-delete-confirm-btn');
        if (deleteConfirmBtn) {
            deleteConfirmBtn.addEventListener('click', async () => {
                const movementId = deleteConfirmBtn.dataset.movementId;
                deleteConfirmBtn.disabled = true;
                deleteConfirmBtn.textContent = 'Eliminando...';
                try {
                    await PecusApi.deleteMovement(movementId);
                    Toast?.show('Movimiento eliminado.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('movement-delete-modal'))?.hide();
                    await this.loadProtectedData();
                    this.renderPage('movements');
                } catch (error) {
                    Toast?.show(error.message || 'No se pudo eliminar el movimiento.', 'error');
                } finally {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.textContent = 'Eliminar';
                }
            });
        }

        // Edit/delete buttons
        document.querySelectorAll('[data-movement-edit]').forEach((button) => {
            button.addEventListener('click', () => this.openMovementEditModal(button.dataset.movementEdit));
        });

        document.querySelectorAll('[data-movement-delete]').forEach((button) => {
            button.addEventListener('click', () => this.confirmMovementDelete(button.dataset.movementDelete));
        });
    }

    setupDashboardPageEvents() {
        document.querySelectorAll('.period-btn').forEach((button) => {
            button.addEventListener('click', async () => {
                document.querySelectorAll('.period-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const period = button.dataset.period;
                await this.loadDashboardData(period);
            });
        });

        const customForm = document.getElementById('dashboard-custom-period-form');
        if (customForm) {
            customForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const startDate = document.getElementById('dashboard-start-date').value;
                const endDate = document.getElementById('dashboard-end-date').value;
                if (startDate && endDate) {
                    await this.loadDashboardData('custom', startDate, endDate);
                }
            });
        }
    }

    async loadDashboardData(period = 'month', startDate = null, endDate = null) {
        try {
            const params = { period };
            if (startDate && endDate) {
                params.start_date = startDate;
                params.end_date = endDate;
            }
            const data = await PecusApi.getDashboard(params);
            this.state.dashboard = data;
            this.renderPage('dashboard');
        } catch (error) {
            Toast?.show(error.message || 'No se pudo cargar el dashboard.', 'error');
        }
    }

    async applyMovementFilters() {
        const form = document.getElementById('movements-filter-form');
        if (!form) return;

        const formData = new FormData(form);
        const params = {};
        for (const [key, value] of formData.entries()) {
            if (value) params[key] = value;
        }

        try {
            const movements = await PecusApi.getMovements(params);
            this.state.movements = Array.isArray(movements) ? movements : [];
            this.renderPage('movements');
        } catch (error) {
            Toast?.show(error.message || 'No se pudieron filtrar los movimientos.', 'error');
        }
    }

    // Modal Handler Methods
    openCategoryEditModal(categoryId) {
        const category = this.getCategoryById(Number(categoryId));
        if (!category) return;

        document.getElementById('modal-category-edit-id').value = category.id;
        document.getElementById('modal-category-edit-name').value = category.name;
        document.getElementById('modal-category-edit-type').value = category.type;
        document.getElementById('modal-category-edit-icon').value = category.icon || 'tag';
        document.getElementById('modal-category-edit-color').value = category.color || '#6c757d';

        const modal = new bootstrap.Modal(document.getElementById('category-edit-modal'));
        modal.show();
    }

    confirmCategoryDelete(categoryId) {
        const category = this.getCategoryById(Number(categoryId));
        if (!category) return;

        document.getElementById('category-delete-preview').textContent = `Categoría: ${category.name} (${category.type === 'INCOME' ? 'Ingreso' : 'Gasto'})`;
        document.getElementById('category-delete-confirm-btn').dataset.categoryId = categoryId;

        const modal = new bootstrap.Modal(document.getElementById('category-delete-modal'));
        modal.show();
    }

    openMovementCreateModal() {
        // Reset form
        document.getElementById('movement-create-form').reset();
        document.getElementById('movement-date').valueAsDate = new Date();
        document.getElementById('movement-currency').value = 'ARS';

        // The modal is triggered by data-bs-toggle in the button
    }

    openMovementEditModal(movementId) {
        const movement = (this.state.movements || []).find(m => Number(m.id) === Number(movementId));
        if (!movement) return;

        document.getElementById('movement-edit-id').value = movement.id;
        document.getElementById('movement-edit-description').value = movement.description || '';
        document.getElementById('movement-edit-type').value = movement.type;
        document.getElementById('movement-edit-amount').value = movement.amount;
        document.getElementById('movement-edit-currency').value = movement.currency || 'ARS';
        document.getElementById('movement-edit-date').value = movement.operation_date || movement.date || '';
        document.getElementById('movement-edit-source').value = movement.source_id || movement.sourceId || '';
        document.getElementById('movement-edit-category').value = movement.category_id || movement.categoryId || '';
        document.getElementById('movement-edit-notes').value = movement.notes || '';

        const modal = new bootstrap.Modal(document.getElementById('movement-edit-modal'));
        modal.show();
    }

    confirmMovementDelete(movementId) {
        const movement = (this.state.movements || []).find(m => Number(m.id) === Number(movementId));
        if (!movement) return;

        const category = this.getCategoryById(Number(movement.category_id || movement.categoryId));
        const categoryName = category?.name || 'Sin categoría';
        const sourceName = this.getSourceById(Number(movement.source_id || movement.sourceId))?.name || 'Desconocido';

        document.getElementById('movement-delete-preview').textContent = `${movement.description || 'Sin descripción'} - ${sourceName} - ${categoryName} - ${movement.type === 'INCOME' ? '+' : '-'}$${this.formatNumber(movement.amount)}`;
        document.getElementById('movement-delete-confirm-btn').dataset.movementId = movementId;

        const modal = new bootstrap.Modal(document.getElementById('movement-delete-modal'));
        modal.show();
    }

    openSourceEditModal(sourceId) {
        const source = this.getSourceById(Number(sourceId));
        if (!source) return;

        document.getElementById('modal-source-edit-id').value = source.id;
        document.getElementById('modal-source-edit-name').value = source.name;
        document.getElementById('modal-source-edit-type').value = source.type;
        document.getElementById('modal-source-edit-currency').value = source.currency || 'ARS';
        document.getElementById('modal-source-edit-initial-balance').value = source.initial_balance || 0;

        const modal = new bootstrap.Modal(document.getElementById('source-edit-modal'));
        modal.show();
    }

    confirmSourceDelete(sourceId) {
        const source = this.getSourceById(Number(sourceId));
        if (!source) return;

        document.getElementById('source-delete-preview').textContent = `${source.name} (${this.getSourceTypeLabel(source.type)})`;
        document.getElementById('source-delete-confirm-btn').dataset.sourceId = sourceId;

        const modal = new bootstrap.Modal(document.getElementById('source-delete-modal'));
        modal.show();
    }

    // Page Content Methods
    getDashboardContent() {
        const summary = this.getDashboardSummary();
        const recentMovements = (this.state.dashboard?.recent_movements || []).slice(0, 6);
        const categoryDistribution = this.state.dashboard?.category_distribution || [];
        const summaryError = this.state.errors.dashboard || (summary ? null : 'El resumen no está disponible.');

        return `
            <div class="container">
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Dashboard</h1>
                    <div class="dashboard-period-selector">
                        <button class="period-btn ${this.state.dashboard?.period_info?.period === 'month' ? 'active' : ''}" data-period="month">Mes</button>
                        <button class="period-btn ${this.state.dashboard?.period_info?.period === 'week' ? 'active' : ''}" data-period="week">Semana</button>
                        <button class="period-btn ${this.state.dashboard?.period_info?.period === 'year' ? 'active' : ''}" data-period="year">Año</button>
                        <button class="period-btn ${this.state.dashboard?.period_info?.period === 'custom' ? 'active' : ''}" data-period="custom">Personalizado</button>
                    </div>
                </div>

                ${this.state.dashboard?.period_info?.period === 'custom' ? `
                <div class="card mb-4" id="dashboard-custom-period-form">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-5">
                                <label for="dashboard-start-date" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="dashboard-start-date" value="${this.state.dashboard?.period_info?.start_date || ''}">
                            </div>
                            <div class="col-md-5">
                                <label for="dashboard-end-date" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="dashboard-end-date" value="${this.state.dashboard?.period_info?.end_date || ''}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Aplicar</button>
                            </div>
                        </form>
                    </div>
                </div>
                ` : ''}

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-uppercase text-muted">Dinero disponible</h6>
                                    <h3 class="card-text mb-0">${summaryError ? '—' : `$${this.formatNumber(summary.available)}`}</h3>
                                </div>
                                <div class="icon-bg-primary">
                                    <i class="icon-lg" data-lucide="wallet"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-uppercase text-muted">Ingresos</h6>
                                    <h3 class="card-text mb-0">${summaryError ? '—' : `$${this.formatNumber(summary.income)}`}</h3>
                                </div>
                                <div class="icon-bg-success">
                                    <i class="icon-lg" data-lucide="trending-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-uppercase text-muted">Gastos</h6>
                                    <h3 class="card-text mb-0">${summaryError ? '—' : `$${this.formatNumber(summary.expenses)}`}</h3>
                                </div>
                                <div class="icon-bg-error">
                                    <i class="icon-lg" data-lucide="trending-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-uppercase text-muted">Balance</h6>
                                    <h3 class="card-text mb-0">${summaryError ? '—' : `$${this.formatNumber(summary.balance)}`}</h3>
                                </div>
                                <div class="icon-bg-info">
                                    <i class="icon-lg" data-lucide="scale"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                ${summaryError ? `<div class="alert alert-danger" role="alert">No se pudo cargar el resumen: ${summaryError}</div>` : ''}

                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="section-title mb-3">Acciones rápidas</h2>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="movements.html" class="btn btn-primary w-100">
                                    <i class="me-2" data-lucide="plus"></i> Nuevo movimiento
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="receipts.html" class="btn btn-outline w-100">
                                    <i class="me-2" data-lucide="upload"></i> Cargar comprobante
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="sources.html" class="btn btn-outline w-100">
                                    <i class="me-2" data-lucide="building-2"></i> Nueva fuente
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="receipts.html" class="btn btn-outline w-100">
                                    <i class="me-2" data-lucide="list-checks"></i> Revisar pendientes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Movimientos recientes</h5>
                                <a href="movements.html" class="btn btn-outline btn-sm">Ver todos</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Descripción</th>
                                                <th>Fuente</th>
                                                <th>Categoría</th>
                                                <th>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${this.state.errors.dashboard ? `<tr><td colspan="5" class="text-center py-4 text-danger">No se pudieron cargar los movimientos recientes.</td></tr>` : recentMovements.map((mov) => {
                                                const sourceName = mov.source?.name || this.getSourceById(Number(mov.source_id || mov.sourceId))?.name || 'Desconocido';
                                                const category = mov.category || this.getCategoryById(Number(mov.category_id || mov.categoryId));
                                                const categoryName = category?.name || 'Sin categoría';
                                                const categoryColor = category?.color || '#6c757d';
                                                return `
                                                    <tr>
                                                        <td>${this.formatDate(mov.operation_date || mov.date)}</td>
                                                        <td>${mov.description || 'Sin descripción'}</td>
                                                        <td>${sourceName}</td>
                                                        <td>
                                                            <span class="badge" style="background:${categoryColor};color:white;">${categoryName}</span>
                                                        </td>
                                                        <td class="text-end ${mov.type === 'INCOME' ? 'text-success' : 'text-error'}">
                                                            ${mov.type === 'INCOME' ? '+' : '-'}$${this.formatNumber(mov.amount)}
                                                        </td>
                                                    </tr>
                                                `;
                                            }).join('') || '<tr><td colspan="5" class="text-center py-4">No hay movimientos para mostrar.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Gastos por categoría</h5>
                            </div>
                            <div class="card-body">
                                ${categoryDistribution.length ? categoryDistribution.map(cat => `
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="badge me-2" style="background:${cat.color || '#6c757d'};color:white;">${cat.category}</span>
                                        </div>
                                        <div class="text-end">
                                            <div>$${this.formatNumber(cat.amount)}</div>
                                            <small class="text-muted">${cat.percentage}%</small>
                                        </div>
                                    </div>
                                `).join('') : '<p class="text-muted mb-0">No hay datos de gastos para este período.</p>'}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Saldos por fuente</h5>
                            </div>
                            <div class="card-body">
                                ${(this.state.dashboard?.sources || []).length ? (this.state.dashboard?.sources || []).map(source => `
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                        <div>
                                            <i class="me-2" data-lucide="${source.type === 'BANK' ? 'building-2' : source.type === 'CREDIT_CARD' ? 'credit-card' : source.type === 'CASH' ? 'banknote' : 'wallet'}"></i>
                                            <span>${source.name}</span>
                                        </div>
                                        <div class="text-end ${(source.current_balance ?? source.initial_balance ?? 0) >= 0 ? 'text-success' : 'text-error'}">
                                            $${this.formatNumber(Math.abs(source.current_balance ?? source.initial_balance ?? 0))}
                                        </div>
                                    </div>
                                `).join('') : '<p class="text-muted mb-0">No hay fuentes configuradas.</p>'}
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Resumen del período</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="text-success">+$${this.formatNumber(summary?.income || 0)}</div>
                                        <small class="text-muted">Ingresos</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-error">-$${this.formatNumber(summary?.expenses || 0)}</div>
                                        <small class="text-muted">Gastos</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="${(summary?.balance || 0) >= 0 ? 'text-success' : 'text-error'}">$${this.formatNumber(summary?.balance || 0)}</div>
                                        <small class="text-muted">Balance</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getMovementsContent() {
        const movements = this.state.movements || [];

        return `
            <div class="container">
                <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="dashboard-title mb-0">Movimientos</h1>
                    <button type="button" class="btn btn-primary" id="movement-create-btn" data-bs-toggle="modal" data-bs-target="#movement-create-modal">
                        <i class="me-2" data-lucide="plus"></i> Nuevo movimiento
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="movements-filter-form" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="movement-search" class="form-label">Buscar</label>
                                        <input type="text" class="form-control" id="movement-search" name="search" placeholder="Descripción...">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="movement-category" class="form-label">Categoría</label>
                                        <select class="form-select" id="movement-category" name="category_id">
                                            <option value="">Todas</option>
                                            ${this.state.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="movement-type" class="form-label">Tipo</label>
                                        <select class="form-select" id="movement-type" name="type">
                                            <option value="">Todos</option>
                                            <option value="INCOME">Ingresos</option>
                                            <option value="EXPENSE">Gastos</option>
                                            <option value="INTERNAL_TRANSFER">Transferencias internas</option>
                                            <option value="EXTERNAL_TRANSFER">Transferencias externas</option>
                                            <option value="REFUND">Reintegros</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="movement-source" class="form-label">Fuente</label>
                                        <select class="form-select" id="movement-source" name="source_id">
                                            <option value="">Todas</option>
                                            ${this.state.sources.map(src => `<option value="${src.id}">${src.name}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label for="movement-date-from" class="form-label">Desde</label>
                                        <input type="date" class="form-control" id="movement-date-from" name="date_from">
                                    </div>
                                    <div class="col-md-1">
                                        <label for="movement-date-to" class="form-label">Hasta</label>
                                        <input type="date" class="form-control" id="movement-date-to" name="date_to">
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="me-2" data-lucide="filter"></i> Filtrar
                                            </button>
                                            <button type="button" class="btn btn-outline" id="movements-clear-filters">
                                                <i class="me-2" data-lucide="x"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Lista de movimientos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Descripción</th>
                                                <th>Fuente</th>
                                                <th>Categoría</th>
                                                <th>Tipo</th>
                                                <th>Monto</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${this.state.errors.movements ? `<tr><td colspan="7" class="text-center py-4 text-danger">No se pudieron cargar los movimientos.</td></tr>` : movements.map((mov) => {
                                                const sourceName = mov.source?.name || this.getSourceById(Number(mov.source_id || mov.sourceId))?.name || 'Desconocido';
                                                const category = mov.category || this.getCategoryById(Number(mov.category_id || mov.categoryId));
                                                const categoryName = category?.name || 'Sin categoría';
                                                const categoryColor = category?.color || '#6c757d';
                                                return `
                                                    <tr>
                                                        <td>${this.formatDate(mov.operation_date || mov.date)}</td>
                                                        <td>${mov.description || 'Sin descripción'}</td>
                                                        <td>${sourceName}</td>
                                                        <td><span class="badge" style="background:${categoryColor};color:white;">${categoryName}</span></td>
                                                        <td><span class="badge bg-${mov.type === 'INCOME' ? 'success' : mov.type === 'EXPENSE' ? 'danger' : 'info'}">${this.getMovementTypeLabel(mov.type)}</span></td>
                                                        <td class="text-end ${mov.type === 'INCOME' ? 'text-success' : 'text-error'}">${mov.type === 'INCOME' ? '+' : '-'}$${this.formatNumber(mov.amount)}</td>
                                                        <td class="text-end">
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-outline" data-movement-edit="${mov.id}" title="Editar">
                                                                    <i data-lucide="edit-2"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline text-error" data-movement-delete="${mov.id}" title="Eliminar">
                                                                    <i data-lucide="trash-2"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                `;
                                            }).join('') || '<tr><td colspan="7" class="text-center py-4">No hay movimientos para mostrar.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Movement Modal -->
                <div class="modal fade" id="movement-create-modal" tabindex="-1" aria-labelledby="movement-create-modal-label" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="movement-create-modal-label">Nuevo movimiento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="movement-create-form">
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="movement-description" class="form-label">Descripción</label>
                                            <input type="text" class="form-control" id="movement-description" name="description" required maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="movement-type-create" class="form-label">Tipo</label>
                                            <select class="form-select" id="movement-type-create" name="type" required>
                                                <option value="">Seleccioná un tipo</option>
                                                <option value="INCOME">Ingreso</option>
                                                <option value="EXPENSE">Gasto</option>
                                                <option value="INTERNAL_TRANSFER">Transferencia interna</option>
                                                <option value="EXTERNAL_TRANSFER">Transferencia externa</option>
                                                <option value="REFUND">Reintegro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-amount" class="form-label">Monto</label>
                                            <input type="number" class="form-control" id="movement-amount" name="amount" min="0.01" step="0.01" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-currency" class="form-label">Moneda</label>
                                            <input type="text" class="form-control" id="movement-currency" name="currency" maxlength="3" placeholder="ARS" required value="ARS">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-date" class="form-label">Fecha</label>
                                            <input type="date" class="form-control" id="movement-date" name="operation_date" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-source" class="form-label">Fuente</label>
                                            <select class="form-select" id="movement-source" name="source_id" required>
                                                <option value="">Seleccioná una fuente</option>
                                                ${this.state.sources.map(src => `<option value="${src.id}">${src.name}</option>`).join('')}
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-category" class="form-label">Categoría</label>
                                            <select class="form-select" id="movement-category" name="category_id" required>
                                                <option value="">Seleccioná una categoría</option>
                                                ${this.state.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="movement-notes" class="form-label">Notas (opcional)</label>
                                            <textarea class="form-control" id="movement-notes" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar movimiento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Movement Modal -->
                <div class="modal fade" id="movement-edit-modal" tabindex="-1" aria-labelledby="movement-edit-modal-label" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="movement-edit-modal-label">Editar movimiento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="movement-edit-form">
                                <div class="modal-body">
                                    <input type="hidden" id="movement-edit-id" name="id">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="movement-edit-description" class="form-label">Descripción</label>
                                            <input type="text" class="form-control" id="movement-edit-description" name="description" required maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="movement-edit-type" class="form-label">Tipo</label>
                                            <select class="form-select" id="movement-edit-type" name="type" required>
                                                <option value="INCOME">Ingreso</option>
                                                <option value="EXPENSE">Gasto</option>
                                                <option value="INTERNAL_TRANSFER">Transferencia interna</option>
                                                <option value="EXTERNAL_TRANSFER">Transferencia externa</option>
                                                <option value="REFUND">Reintegro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-edit-amount" class="form-label">Monto</label>
                                            <input type="number" class="form-control" id="movement-edit-amount" name="amount" min="0.01" step="0.01" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-edit-currency" class="form-label">Moneda</label>
                                            <input type="text" class="form-control" id="movement-edit-currency" name="currency" maxlength="3" placeholder="ARS" required value="ARS">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-edit-date" class="form-label">Fecha</label>
                                            <input type="date" class="form-control" id="movement-edit-date" name="operation_date" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-edit-source" class="form-label">Fuente</label>
                                            <select class="form-select" id="movement-edit-source" name="source_id" required>
                                                <option value="">Seleccioná una fuente</option>
                                                ${this.state.sources.map(src => `<option value="${src.id}">${src.name}</option>`).join('')}
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="movement-edit-category" class="form-label">Categoría</label>
                                            <select class="form-select" id="movement-edit-category" name="category_id" required>
                                                <option value="">Seleccioná una categoría</option>
                                                ${this.state.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="movement-edit-notes" class="form-label">Notas (opcional)</label>
                                            <textarea class="form-control" id="movement-edit-notes" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Movement Confirmation Modal -->
                <div class="modal fade" id="movement-delete-modal" tabindex="-1" aria-labelledby="movement-delete-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="movement-delete-modal-label">Eliminar movimiento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que querés eliminar este movimiento? Esta acción no se puede deshacer.</p>
                                <p class="text-muted" id="movement-delete-preview"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-error" id="movement-delete-confirm-btn">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getReceiptsContent() {
        const receipts = this.state.receipts || [];
        const pendingReceipt = receipts.find((receipt) => receipt.status !== 'CONFIRMED');
        const legacyProcessed = ['PROCESSING', 'PROCESSED'].includes(pendingReceipt?.status);
        const extracted = legacyProcessed ? {} : (pendingReceipt?.extracted_data || {});

        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Comprobantes</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="receipt-upload-form">
                                    <div class="mb-3">
                                        <label for="receipt-file" class="form-label">Seleccionar comprobante</label>
                                        <input class="form-control" type="file" id="receipt-file" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="form-text">Formatos permitidos: JPG, PNG, PDF. Máximo 10MB.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="me-2" data-lucide="upload"></i> Subir comprobante
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                ${pendingReceipt ? `
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Completar datos manualmente</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">PECUS guardó el archivo, pero todavía no extrae información. Revisá el comprobante y cargá sus datos.</p>
                                    <button type="button" class="btn btn-outline btn-sm mb-3" data-receipt-file="${pendingReceipt.id}">Abrir ${pendingReceipt.file_name}</button>
                                    <form id="receipt-review-form" data-receipt-id="${pendingReceipt.id}">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Monto</label>
                                                <input id="review-amount" type="number" min="0.01" step="0.01" required class="form-control" value="${extracted.amount ?? ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Fecha</label>
                                                <input id="review-date" type="date" required class="form-control" value="${extracted.date || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Comercio</label>
                                                <input id="review-merchant" class="form-control" value="${extracted.merchant || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Moneda</label>
                                                <input id="review-currency" maxlength="3" required class="form-control" placeholder="Ej.: ARS" value="${extracted.currency || ''}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Descripción</label>
                                                <input id="review-description" class="form-control" value="${extracted.description || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tipo</label>
                                                <select id="review-operation-type" class="form-select" required>
                                                    <option value="" ${extracted.operation_type ? '' : 'selected'}>Seleccioná un tipo</option>
                                                    <option value="EXPENSE" ${extracted.operation_type === 'EXPENSE' ? 'selected' : ''}>Gasto</option>
                                                    <option value="INCOME" ${extracted.operation_type === 'INCOME' ? 'selected' : ''}>Ingreso</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Fuente</label>
                                                <select id="review-source" class="form-select" required>
                                                    <option value="">Seleccioná una fuente</option>
                                                    ${this.state.sources.map((source) => `<option value="${source.id}" ${Number(extracted.source_id) === Number(source.id) ? 'selected' : ''}>${source.name}</option>`).join('')}
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Categoría</label>
                                                <select id="review-category" class="form-select" required>
                                                    <option value="">Seleccioná una categoría</option>
                                                    ${this.state.categories.map((category) => `<option value="${category.id}" ${Number(extracted.category_id) === Number(category.id) ? 'selected' : ''}>${category.name}</option>`).join('')}
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="submit" class="btn btn-primary">Guardar revisión</button>
                                            ${pendingReceipt.status === 'REQUIRES_REVIEW' ? `<button type="button" class="btn btn-success" id="receipt-confirm-button" data-receipt-id="${pendingReceipt.id}">Crear movimiento</button>` : ''}
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <div class="row">
                    <div class="col-12">
                        ${this.state.errors.receipts ? `<div class="alert alert-danger">No se pudieron cargar los comprobantes: ${this.state.errors.receipts}</div>` : ''}
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Comprobantes recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Comercio</th>
                                                <th>Monto</th>
                                                <th>Estado</th>
                                                <th>Archivo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${receipts.length ? receipts.map((receipt) => `
                                                <tr>
                                                    <td>${this.formatDate(receipt.created_at)}</td>
                                                    <td>${receipt.extracted_data?.merchant || receipt.file_name || 'Sin nombre'}</td>
                                                    <td>${receipt.extracted_data?.amount ? `$${this.formatNumber(Number(receipt.extracted_data.amount))}` : 'Pendiente de carga manual'}</td>
                                                    <td><span class="badge bg-${receipt.status === 'CONFIRMED' ? 'success' : 'secondary'}">${receipt.status === 'CONFIRMED' ? 'Movimiento creado' : receipt.status === 'REQUIRES_REVIEW' ? 'Revisión manual completa' : ['PROCESSING', 'PROCESSED'].includes(receipt.status) ? 'Requiere nueva revisión manual' : 'Archivo recibido'}</span></td>
                                                    <td><button type="button" class="btn btn-outline btn-sm" data-receipt-file="${receipt.id}">Abrir archivo</button></td>
                                                </tr>
                                            `).join('') : '<tr><td colspan="5" class="text-center py-4">Todavía no hay comprobantes cargados.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getSourcesContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="dashboard-title mb-0">Fuentes</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#source-create-modal">
                        <i class="me-2" data-lucide="plus"></i> Nueva fuente
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Agregar fuente</h5>
                            </div>
                            <div class="card-body">
                                <form id="source-create-form" class="row g-3">
                                    <div class="col-md-3"><label for="source-name" class="form-label">Nombre</label><input id="source-name" class="form-control" name="name" required maxlength="255"></div>
                                    <div class="col-md-3"><label for="source-type" class="form-label">Tipo</label><select id="source-type" class="form-select" name="type" required><option value="">Seleccioná un tipo</option><option value="BANK">Banco</option><option value="CREDIT_CARD">Tarjeta de crédito</option><option value="WALLET">Billetera virtual</option><option value="CASH">Efectivo</option><option value="OTHER">Otro</option></select></div>
                                    <div class="col-md-2"><label for="source-currency" class="form-label">Moneda</label><input id="source-currency" class="form-control" name="currency" maxlength="3" placeholder="ARS" required value="ARS"></div>
                                    <div class="col-md-2"><label for="source-initial-balance" class="form-label">Saldo inicial</label><input id="source-initial-balance" type="number" class="form-control" name="initial_balance" min="0" step="0.01" required></div>
                                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Guardar fuente</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis fuentes</h5>
                            </div>
                            <div class="card-body">
                                ${this.state.errors.sources ? `<div class="alert alert-danger">No se pudieron cargar las fuentes: ${this.state.errors.sources}</div>` : this.state.sources.map((source) => {
                                    const currentBalance = source.current_balance ?? source.initial_balance ?? 0;
                                    return `
                                        <div class="source-card d-flex justify-content-between align-items-center p-3 border rounded mb-3">
                                            <div class="source-info d-flex align-items-center">
                                                <div class="source-icon me-3">
                                                    <i data-lucide="${source.type === 'BANK' ? 'building-2' : source.type === 'CREDIT_CARD' ? 'credit-card' : source.type === 'CASH' ? 'banknote' : 'wallet'}"></i>
                                                </div>
                                                <div class="source-details">
                                                    <h6 class="source-name mb-1">${source.name}</h6>
                                                    <small class="source-type badge bg-secondary">${this.getSourceTypeLabel(source.type)}</small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="source-balance text-end ${currentBalance >= 0 ? 'text-success' : 'text-error'} me-3" style="min-width: 120px;">
                                                    $${this.formatNumber(Math.abs(currentBalance))}
                                                    ${currentBalance >= 0 ? '' : '(deuda)'}
                                                </div>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline" data-source-edit="${source.id}" title="Editar">
                                                        <i data-lucide="edit-2"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline text-error" data-source-delete="${source.id}" title="Eliminar">
                                                        <i data-lucide="trash-2"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }).join('') || '<div class="text-center py-4">No hay fuentes cargadas.</div>'}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Source Modal -->
                <div class="modal fade" id="source-create-modal" tabindex="-1" aria-labelledby="source-create-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="source-create-modal-label">Nueva fuente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="source-create-modal-form">
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="modal-source-name" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="modal-source-name" name="name" required maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="modal-source-type" class="form-label">Tipo</label>
                                            <select class="form-select" id="modal-source-type" name="type" required>
                                                <option value="">Seleccioná un tipo</option>
                                                <option value="BANK">Banco</option>
                                                <option value="CREDIT_CARD">Tarjeta de crédito</option>
                                                <option value="WALLET">Billetera virtual</option>
                                                <option value="CASH">Efectivo</option>
                                                <option value="OTHER">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-source-currency" class="form-label">Moneda</label>
                                            <input type="text" class="form-control" id="modal-source-currency" name="currency" maxlength="3" placeholder="ARS" required value="ARS">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-source-initial-balance" class="form-label">Saldo inicial</label>
                                            <input type="number" class="form-control" id="modal-source-initial-balance" name="initial_balance" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar fuente</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Source Modal -->
                <div class="modal fade" id="source-edit-modal" tabindex="-1" aria-labelledby="source-edit-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="source-edit-modal-label">Editar fuente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="source-edit-modal-form">
                                <div class="modal-body">
                                    <input type="hidden" id="modal-source-edit-id" name="id">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="modal-source-edit-name" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="modal-source-edit-name" name="name" required maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="modal-source-edit-type" class="form-label">Tipo</label>
                                            <select class="form-select" id="modal-source-edit-type" name="type" required>
                                                <option value="BANK">Banco</option>
                                                <option value="CREDIT_CARD">Tarjeta de crédito</option>
                                                <option value="WALLET">Billetera virtual</option>
                                                <option value="CASH">Efectivo</option>
                                                <option value="OTHER">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-source-edit-currency" class="form-label">Moneda</label>
                                            <input type="text" class="form-control" id="modal-source-edit-currency" name="currency" maxlength="3" placeholder="ARS" required value="ARS">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-source-edit-initial-balance" class="form-label">Saldo inicial</label>
                                            <input type="number" class="form-control" id="modal-source-edit-initial-balance" name="initial_balance" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Source Confirmation Modal -->
                <div class="modal fade" id="source-delete-modal" tabindex="-1" aria-labelledby="source-delete-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="source-delete-modal-label">Eliminar fuente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que querés eliminar esta fuente? Esta acción no se puede deshacer.</p>
                                <p class="text-muted" id="source-delete-preview"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-error" id="source-delete-confirm-btn">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getCategoriesContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="dashboard-title mb-0">Categorías</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#category-create-modal">
                        <i class="me-2" data-lucide="plus"></i> Nueva categoría
                    </button>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Crear nueva categoría</h5>
                            </div>
                            <div class="card-body">
                                <form id="category-create-form" class="row g-3">
                                    <div class="col-md-4">
                                        <label for="category-name" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="category-name" name="name" required maxlength="255">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="category-type" class="form-label">Tipo</label>
                                        <select class="form-select" id="category-type" name="type" required>
                                            <option value="">Seleccioná un tipo</option>
                                            <option value="EXPENSE">Gasto</option>
                                            <option value="INCOME">Ingreso</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="category-icon" class="form-label">Icono</label>
                                        <input type="text" class="form-control" id="category-icon" name="icon" placeholder="tag" value="tag">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="category-color" class="form-label">Color</label>
                                        <input type="color" class="form-control form-control-color" id="category-color" name="color" value="#6c757d" title="Elegí un color">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis categorías</h5>
                            </div>
                            <div class="card-body">
                                ${this.state.errors.categories ? `<div class="alert alert-danger">No se pudieron cargar las categorías: ${this.state.errors.categories}</div>` : this.state.categories.map((category) => `
                                    <div class="category-card d-flex justify-content-between align-items-center p-3 border rounded mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="category-icon me-3" style="background:${category.color || '#6c757d'};">
                                                <i data-lucide="${category.icon || 'tag'}"></i>
                                            </div>
                                            <div>
                                                <h6 class="category-name mb-1">${category.name}</h6>
                                                <small class="category-type badge bg-${category.type === 'INCOME' ? 'success' : 'danger'}">${category.type === 'INCOME' ? 'Ingreso' : 'Gasto'}</small>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline" data-category-edit="${category.id}" title="Editar">
                                                <i data-lucide="edit-2"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline text-error" data-category-delete="${category.id}" title="Eliminar">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                `).join('') || '<div class="text-center py-4">No hay categorías cargadas.</div>'}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Category Modal -->
                <div class="modal fade" id="category-create-modal" tabindex="-1" aria-labelledby="category-create-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="category-create-modal-label">Nueva categoría</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="category-create-modal-form">
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="modal-category-name" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="modal-category-name" name="name" required maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="modal-category-type" class="form-label">Tipo</label>
                                            <select class="form-select" id="modal-category-type" name="type" required>
                                                <option value="">Seleccioná un tipo</option>
                                                <option value="EXPENSE">Gasto</option>
                                                <option value="INCOME">Ingreso</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-category-icon" class="form-label">Icono</label>
                                            <input type="text" class="form-control" id="modal-category-icon" name="icon" placeholder="tag" value="tag">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-category-color" class="form-label">Color</label>
                                            <input type="color" class="form-control form-control-color" id="modal-category-color" name="color" value="#6c757d" title="Elegí un color">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar categoría</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Category Modal -->
                <div class="modal fade" id="category-edit-modal" tabindex="-1" aria-labelledby="category-edit-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="category-edit-modal-label">Editar categoría</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="category-edit-modal-form">
                                <div class="modal-body">
                                    <input type="hidden" id="modal-category-edit-id" name="id">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="modal-category-edit-name" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="modal-category-edit-name" name="name" required maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="modal-category-edit-type" class="form-label">Tipo</label>
                                            <select class="form-select" id="modal-category-edit-type" name="type" required>
                                                <option value="EXPENSE">Gasto</option>
                                                <option value="INCOME">Ingreso</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-category-edit-icon" class="form-label">Icono</label>
                                            <input type="text" class="form-control" id="modal-category-edit-icon" name="icon" placeholder="tag" value="tag">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="modal-category-edit-color" class="form-label">Color</label>
                                            <input type="color" class="form-control form-control-color" id="modal-category-edit-color" name="color" value="#6c757d" title="Elegí un color">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Category Confirmation Modal -->
                <div class="modal fade" id="category-delete-modal" tabindex="-1" aria-labelledby="category-delete-modal-label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="category-delete-modal-label">Eliminar categoría</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que querés eliminar esta categoría? Esta acción no se puede deshacer.</p>
                                <p class="text-muted" id="category-delete-preview"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-error" id="category-delete-confirm-btn">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getAnalysisContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Análisis</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="analysis-chart-container"><h3 class="chart-title">Evolución financiera</h3><p class="text-muted mb-0">Todavía no hay análisis disponible para mostrar.</p></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Ingresos vs Gastos</h3>
                            <p class="text-muted mb-0">Sin datos de análisis disponibles.</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Distribución de gastos</h3>
                            <p class="text-muted mb-0">Sin datos de análisis disponibles.</p>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Gastos por fuente</h3>
                            <p class="text-muted mb-0">Sin datos de análisis disponibles.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getGoalsContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Metas</h1>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis metas</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-0">Todavía no hay metas registradas. Este módulo aún no está conectado a una API.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        `;
    }

    getBudgetsContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Presupuestos</h1>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis presupuestos</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-0">Todavía no hay presupuestos registrados. Este módulo aún no está conectado a una API.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        `;
    }

    getAssistantContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Asistente financiero</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Chat con tu asistente</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-0">El asistente financiero todavía no está implementado.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getNotificationsContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Notificaciones</h1>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis notificaciones</h5>
                            </div>
                            <div class="card-body">
                <p class="text-muted mb-0">El módulo de notificaciones todavía no está conectado a la API.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getSettingsContent() {
        return `
            <div class="container">
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Configuración</h1>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="settings-section">
                            <div class="settings-header">
                                <h6 class="settings-title">Perfil</h6>
                            </div>
                            <div class="settings-body">
                                <div class="settings-item">
                                    <span class="settings-label">Nombre</span>
                                    <span>${this.state.user ? `${this.state.user.name || ''} ${this.state.user.surname || ''}`.trim() : 'No disponible'}</span>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Email</span>
                                    <span>${this.state.user?.email || 'No disponible'}</span>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Teléfono</span>
                                    <span>No disponible</span>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Foto de perfil</span>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <i class="icon-lg" data-lucide="user-circle"></i>
                                        </div>
                                        <button class="btn btn-outline btn-sm">Cambiar foto</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="settings-section">
                            <div class="settings-header">
                                <h6 class="settings-title">Preferencias</h6>
                            </div>
                            <div class="settings-body">
                                <div class="settings-item">
                                    <span class="settings-label">Notificaciones push</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="push-notifications" disabled>
                                        <label class="settings-slider" for="push-notifications"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Notificaciones por email</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="email-notifications" disabled>
                                        <label class="settings-slider" for="email-notifications"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Modo oscuro</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="dark-mode">
                                        <label class="settings-slider" for="dark-mode"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Moneda por defecto</span>
                                    <select class="form-select form-select-sm">
                                        <option value="ARS" selected>ARS (Peso argentino)</option>
                                        <option value="USD">USD (Dólar estadounidense)</option>
                                        <option value="EUR">EUR (Euro)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="settings-section">
                            <div class="settings-header">
                                <h6 class="settings-title">Configuración general</h6>
                            </div>
                            <div class="settings-body">
                                <div class="settings-item">
                                    <span class="settings-label">Inicio de sesión automático</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="auto-login">
                                        <label class="settings-slider" for="auto-login"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Sincronización automática</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="auto-sync" disabled>
                                        <label class="settings-slider" for="auto-sync"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Copias de seguridad</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="backups" disabled>
                                        <label class="settings-slider" for="backups"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Versión de la app</span>
                                    <span>1.0.0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    getDashboardSummary() {
        const summary = this.state.dashboard?.summary;
        if (!summary || !['available', 'balance', 'income', 'expenses'].every((key) => Number.isFinite(Number(summary[key])))) {
            return null;
        }
        return {
            available: Number(summary.available ?? summary.balance),
            income: Number(summary.income),
            expenses: Number(summary.expenses),
            balance: Number(summary.balance),
        };
    }

    // Helper Methods
    getSourceById(id) {
        const targetId = Number(id);
        return (this.state.sources || []).find(source => Number(source.id) === targetId) || null;
    }

    getCategoryById(id) {
        const targetId = Number(id);
        return (this.state.categories || []).find(cat => Number(cat.id) === targetId) || null;
    }

    getMovementTypeLabel(type) {
        const labels = {
            'INCOME': 'Ingreso',
            'EXPENSE': 'Gasto',
            'INTERNAL_TRANSFER': 'Transferencia interna',
            'EXTERNAL_TRANSFER': 'Transferencia externa',
            'REFUND': 'Reintegro'
        };
        return labels[type] || type;
    }

    getSourceTypeLabel(type) {
        const labels = {
            'BANK': 'Banco',
            'CREDIT_CARD': 'Tarjeta de crédito',
            'WALLET': 'Billetera virtual',
            'CASH': 'Efectivo',
            'OTHER': 'Otro'
        };
        return labels[type] || type;
    }

    formatNumber(num) {
        const value = Number(num);
        if (!Number.isFinite(value)) {
            return '—';
        }
        return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
    }

    formatDate(dateString) {
        if (!dateString) {
            return 'Sin fecha';
        }

        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) {
            return 'Sin fecha';
        }

        return new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pecusApp = new PecusApp();
});
