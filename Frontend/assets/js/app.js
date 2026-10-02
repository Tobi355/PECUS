// Main Application Class
class PecusApp {
    constructor() {
        this.currentPage = document.body.dataset.page || 'dashboard';
        this.mockData = this.loadMockData();
        this.state = {
            user: this.mockData.user,
            dashboard: null,
            sources: this.mockData.sources,
            categories: this.mockData.categories,
            movements: this.mockData.movements,
            receipts: [],
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
        const [user, dashboard, sources, categories, movements, receipts] = await Promise.all([
            PecusApi.getUser().catch(() => null),
            PecusApi.getDashboard().catch(() => null),
            PecusApi.getSources().catch(() => []),
            PecusApi.getCategories().catch(() => []),
            PecusApi.getMovements().catch(() => []),
            PecusApi.getReceipts().catch(() => []),
        ]);

        this.state.user = user || this.mockData.user;
        this.state.dashboard = dashboard || {
            summary: { available: 0, income: 0, expenses: 0, balance: 0 },
            recent_movements: [],
            sources: []
        };
        this.state.sources = Array.isArray(sources) && sources.length ? sources : this.mockData.sources;
        this.state.categories = Array.isArray(categories) && categories.length ? categories : this.mockData.categories;
        this.state.movements = Array.isArray(movements) && movements.length ? movements : this.mockData.movements;
        this.state.receipts = Array.isArray(receipts) ? receipts : [];

        this.loadSidebar();
    }

    getDisplayUser() {
        const user = this.state.user || this.mockData.user || { name: 'Usuario', email: 'usuario@pecus.app' };
        return {
            name: user.name || 'Usuario',
            surname: user.surname || '',
            email: user.email || 'usuario@pecus.app',
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
                    const processedReceipt = await PecusApi.processReceipt(uploadedReceipt.id);
                    Toast?.show('Comprobante cargado y procesado correctamente.', 'success');
                    await this.loadProtectedData();
                    this.renderPage('receipts');
                    console.log('Processed receipt:', processedReceipt);
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
    }

    loadMockData() {
        return {
            user: {
                name: 'Juan',
                surname: 'Pérez',
                email: 'juan.perez@email.com',
                balance: 250000
            },
            sources: [
                {
                    id: 1,
                    name: 'Cuenta Sueldo',
                    type: 'BANK',
                    icon: 'building-2',
                    balance: 150000,
                    currency: 'ARS'
                },
                {
                    id: 2,
                    name: 'Tarjeta Visa',
                    type: 'CREDIT_CARD',
                    icon: 'credit-card',
                    balance: -45000,
                    currency: 'ARS'
                },
                {
                    id: 3,
                    name: 'Mercado Pago',
                    type: 'WALLET',
                    icon: 'wallet',
                    balance: 30000,
                    currency: 'ARS'
                },
                {
                    id: 4,
                    name: 'Efectivo',
                    type: 'CASH',
                    icon: 'banknote',
                    balance: 15000,
                    currency: 'ARS'
                }
            ],
            movements: [
                {
                    id: 1,
                    type: 'INCOME',
                    sourceId: 1,
                    categoryId: 1,
                    amount: 80000,
                    description: 'Sueldo mensual',
                    date: '2026-09-20',
                    status: 'CONFIRMED'
                },
                {
                    id: 2,
                    type: 'EXPENSE',
                    sourceId: 2,
                    categoryId: 2,
                    amount: 12500,
                    description: 'Supermercado',
                    date: '2026-09-21',
                    status: 'CONFIRMED'
                },
                {
                    id: 3,
                    type: 'EXPENSE',
                    sourceId: 3,
                    categoryId: 3,
                    amount: 2800,
                    description: 'Subte y colectivo',
                    date: '2026-09-21',
                    status: 'CONFIRMED'
                },
                {
                    id: 4,
                    type: 'EXPENSE',
                    sourceId: 1,
                    categoryId: 4,
                    amount: 5600,
                    description: 'Netflix subscription',
                    date: '2026-09-20',
                    status: 'CONFIRMED'
                },
                {
                    id: 5,
                    type: 'INCOME',
                    sourceId: 3,
                    categoryId: 5,
                    amount: 5000,
                    description: 'Freelance diseño',
                    date: '2026-09-19',
                    status: 'CONFIRMED'
                }
            ],
            categories: [
                {
                    id: 1,
                    name: 'Sueldo',
                    type: 'INCOME',
                    icon: 'banknote',
                    color: '#28a745'
                },
                {
                    id: 2,
                    name: 'Alimentación',
                    type: 'EXPENSE',
                    icon: 'utensils',
                    color: '#dc3545'
                },
                {
                    id: 3,
                    name: 'Transporte',
                    type: 'EXPENSE',
                    icon: 'car',
                    color: '#ffc107'
                },
                {
                    id: 4,
                    name: 'Entretenimiento',
                    type: 'EXPENSE',
                    icon: 'clapperboard',
                    color: '#17a2b8'
                },
                {
                    id: 5,
                    name: 'Freelance',
                    type: 'INCOME',
                    icon: 'briefcase',
                    color: '#20c997'
                }
            ],
            notifications: [
                {
                    id: 1,
                    type: 'success',
                    title: 'Comprobante procesado',
                    message: 'El comprobante del supermercado ha sido procesado correctamente.',
                    time: 'Hoy, 10:30',
                    read: false
                },
                {
                    id: 2,
                    type: 'warning',
                    title: 'Presupuesto próximo al límite',
                    message: 'Has usado el 80% de tu presupuesto de alimentación.',
                    time: 'Ayer, 18:45',
                    read: false
                },
                {
                    id: 3,
                    type: 'info',
                    title: 'Progreso de meta',
                    message: 'Has alcanzado el 60% de tu meta de vacaciones.',
                    time: 'Ayer, 14:20',
                    read: true
                }
            ]
        };
    }

    // Page Content Methods
    getDashboardContent() {
        const summary = this.getDashboardSummary();
        const recentMovements = (this.state.dashboard?.recent_movements?.length ? this.state.dashboard.recent_movements : this.state.movements).slice(0, 6);

        return `
            <div class="container">
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Dashboard</h1>
                    <div class="dashboard-period-selector">
                        <button class="period-btn active" data-period="month">Mes</button>
                        <button class="period-btn" data-period="week">Semana</button>
                        <button class="period-btn" data-period="year">Año</button>
                        <button class="period-btn" data-period="custom">Personalizado</button>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title text-uppercase text-muted">Dinero disponible</h6>
                                    <h3 class="card-text mb-0">$${this.formatNumber(summary.available)}</h3>
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
                                    <h3 class="card-text mb-0">$${this.formatNumber(summary.income)}</h3>
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
                                    <h3 class="card-text mb-0">$${this.formatNumber(summary.expenses)}</h3>
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
                                    <h3 class="card-text mb-0">$${this.formatNumber(summary.balance)}</h3>
                                </div>
                                <div class="icon-bg-info">
                                    <i class="icon-lg" data-lucide="scale"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                            <div class="card-header">
                                <h5 class="card-title mb-0">Movimientos recientes</h5>
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
                                            ${recentMovements.map((mov) => {
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
                                                            ${mov.type === 'INCOME' ? '+' : '-'}$${this.formatNumber(mov.amount || 0)}
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
                                <canvas id="expensesChart" height="150"></canvas>
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
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Movimientos</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="movements-filter">
                            <input type="text" class="form-control filter-input" placeholder="Buscar movimientos...">
                            <select class="form-select filter-select">
                                <option value="all">Todas las categorías</option>
                                ${this.state.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                            </select>
                            <select class="form-select filter-select">
                                <option value="all">Todos los tipos</option>
                                <option value="INCOME">Ingresos</option>
                                <option value="EXPENSE">Gastos</option>
                                <option value="INTERNAL_TRANSFER">Transferencias internas</option>
                                <option value="EXTERNAL_TRANSFER">Transferencias externas</option>
                                <option value="REFUND">Reintegros</option>
                            </select>
                            <button class="btn btn-outline filter-btn">Filtrar</button>
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${movements.map((mov) => {
                                                const sourceName = mov.source?.name || this.getSourceById(Number(mov.source_id || mov.sourceId))?.name || 'Desconocido';
                                                const category = mov.category || this.getCategoryById(Number(mov.category_id || mov.categoryId));
                                                const categoryName = category?.name || 'Sin categoría';
                                                return `
                                                    <tr>
                                                        <td>${this.formatDate(mov.operation_date || mov.date)}</td>
                                                        <td>${mov.description || 'Sin descripción'}</td>
                                                        <td>${sourceName}</td>
                                                        <td><span class="badge" style="background:${category?.color || '#6c757d'};color:white;">${categoryName}</span></td>
                                                        <td><span class="badge bg-${mov.type === 'INCOME' ? 'success' : mov.type === 'EXPENSE' ? 'danger' : 'info'}">${this.getMovementTypeLabel(mov.type)}</span></td>
                                                        <td class="text-end ${mov.type === 'INCOME' ? 'text-success' : 'text-error'}">${mov.type === 'INCOME' ? '+' : '-'}$${this.formatNumber(mov.amount || 0)}</td>
                                                    </tr>
                                                `;
                                            }).join('') || '<tr><td colspan="6" class="text-center py-4">No hay movimientos para mostrar.</td></tr>'}
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

    getReceiptsContent() {
        const receipts = this.state.receipts || [];
        const latestProcessed = receipts.find((receipt) => receipt.status === 'PROCESSED' || receipt.status === 'CONFIRMED') || receipts[0];
        const extracted = latestProcessed?.extracted_data || {};

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

                ${latestProcessed && Object.keys(extracted).length ? `
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Revisar datos extraídos</h5>
                                </div>
                                <div class="card-body">
                                    <form id="receipt-review-form" data-receipt-id="${latestProcessed.id}">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Monto</label>
                                                <input id="review-amount" class="form-control" value="${extracted.amount || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Fecha</label>
                                                <input id="review-date" type="date" class="form-control" value="${extracted.date || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Comercio</label>
                                                <input id="review-merchant" class="form-control" value="${extracted.merchant || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Moneda</label>
                                                <input id="review-currency" class="form-control" value="${extracted.currency || 'ARS'}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Descripción</label>
                                                <input id="review-description" class="form-control" value="${extracted.description || ''}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tipo</label>
                                                <select id="review-operation-type" class="form-select">
                                                    <option value="EXPENSE" ${extracted.operation_type === 'EXPENSE' ? 'selected' : ''}>Gasto</option>
                                                    <option value="INCOME" ${extracted.operation_type === 'INCOME' ? 'selected' : ''}>Ingreso</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Fuente</label>
                                                <select id="review-source" class="form-select">
                                                    ${this.state.sources.map((source) => `<option value="${source.id}" ${Number(extracted.source_id) === Number(source.id) ? 'selected' : ''}>${source.name}</option>`).join('')}
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Categoría</label>
                                                <select id="review-category" class="form-select">
                                                    ${this.state.categories.map((category) => `<option value="${category.id}" ${Number(extracted.category_id) === Number(category.id) ? 'selected' : ''}>${category.name}</option>`).join('')}
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="submit" class="btn btn-primary">Guardar revisión</button>
                                            <button type="button" class="btn btn-success" id="receipt-confirm-button" data-receipt-id="${latestProcessed.id}">Confirmar comprobante</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <div class="row">
                    <div class="col-12">
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
                                                <th>Confianza</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${receipts.length ? receipts.map((receipt) => `
                                                <tr>
                                                    <td>${this.formatDate(receipt.created_at || new Date().toISOString())}</td>
                                                    <td>${receipt.extracted_data?.merchant || receipt.file_name || 'Sin nombre'}</td>
                                                    <td>$${this.formatNumber(Number(receipt.extracted_data?.amount || 0))}</td>
                                                    <td><span class="badge bg-${receipt.status === 'CONFIRMED' ? 'success' : receipt.status === 'PROCESSED' ? 'warning' : 'secondary'}">${receipt.status}</span></td>
                                                    <td>${receipt.confidence || 'HIGH'}</td>
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
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Fuentes</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-outline">
                            <i class="me-2" data-lucide="plus"></i> Nueva fuente
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis fuentes</h5>
                            </div>
                            <div class="card-body">
                                ${this.state.sources.map((source) => {
                                    const currentBalance = source.current_balance ?? source.initial_balance ?? 0;
                                    return `
                                        <div class="source-card">
                                            <div class="source-info">
                                                <div class="source-icon">
                                                    <i data-lucide="${source.type === 'BANK' ? 'building-2' : source.type === 'CREDIT_CARD' ? 'credit-card' : source.type === 'CASH' ? 'banknote' : 'wallet'}"></i>
                                                </div>
                                                <div class="source-details">
                                                    <h6 class="source-name">${source.name}</h6>
                                                    <small class="source-type">${this.getSourceTypeLabel(source.type)}</small>
                                                </div>
                                            </div>
                                            <div class="source-balance ${currentBalance >= 0 ? 'text-success' : 'text-error'}">
                                                $${this.formatNumber(Math.abs(currentBalance))}
                                                ${currentBalance >= 0 ? '' : '(deuda)'}
                                            </div>
                                            <div class="source-actions">
                                                <button class="btn btn-outline btn-sm" title="Editar"><i data-lucide="pencil"></i></button>
                                                <button class="btn btn-outline btn-sm" title="Ver movimientos"><i data-lucide="arrow-left-right"></i></button>
                                            </div>
                                        </div>
                                    `;
                                }).join('') || '<div class="text-center py-4">No hay fuentes cargadas.</div>'}
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
                <div class="dashboard-header mb-4">
                    <h1 class="dashboard-title">Categorías</h1>
                </div>

                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-outline">
                            <i class="me-2" data-lucide="plus"></i> Nueva categoría
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis categorías</h5>
                            </div>
                            <div class="card-body">
                                <div class="category-grid">
                                    ${this.state.categories.map((category) => `
                                        <div class="category-card">
                                            <div class="category-icon" style="background:${category.color || '#6c757d'};">
                                                <i data-lucide="${category.icon || 'tag'}"></i>
                                            </div>
                                            <h6 class="category-name">${category.name}</h6>
                                            <small class="category-type">${category.type === 'INCOME' ? 'Ingreso' : 'Gasto'}</small>
                                        </div>
                                    `).join('') || '<div class="col-12 text-center py-4">No hay categorías cargadas.</div>'}
                                </div>
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
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Evolución financiera</h3>
                            <canvas id="evolutionChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Ingresos vs Gastos</h3>
                            <canvas id="incomeExpenseChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Distribución de gastos</h3>
                            <canvas id="expensesPieChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="analysis-chart-container">
                            <h3 class="chart-title">Gastos por fuente</h3>
                            <canvas id="sourceChart" height="200"></canvas>
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

                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-outline">
                            <i class="me-2" data-lucide="plus"></i> Nueva meta
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis metas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="goal-card">
                                            <div class="goal-header">
                                                <h6 class="goal-title">Viaje a Bariloche</h6>
                                            </div>
                                            <div class="goal-body">
                                                <div class="goal-progress-label">
                                                    <span>Objetivo: $2,000,000</span>
                                                    <span>Ahorrado: $850,000</span>
                                                </div>
                                                <div class="goal-progress-bar">
                                                    <div class="goal-progress-fill" style="width: 42.5%;"></div>
                                                </div>
                                                <div class="goal-progress-label">
                                                    <span>Progreso: 42.5%</span>
                                                    <span>Fecha objetivo: Dic 2026</span>
                                                </div>
                                            </div>
                                            <div class="goal-footer">
                                                <button class="btn btn-outline w-100">
                                                    <i class="me-2" data-lucide="pencil"></i> Editar meta
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="goal-card">
                                            <div class="goal-header">
                                                <h6 class="goal-title">Emergency Fund</h6>
                                            </div>
                                            <div class="goal-body">
                                                <div class="goal-progress-label">
                                                    <span>Objetivo: $500,000</span>
                                                    <span>Ahorrado: $200,000</span>
                                                </div>
                                                <div class="goal-progress-bar">
                                                    <div class="goal-progress-fill" style="width: 40%;"></div>
                                                </div>
                                                <div class="goal-progress-label">
                                                    <span>Progreso: 40%</span>
                                                    <span>Fecha objetivo: Jun 2027</span>
                                                </div>
                                            </div>
                                            <div class="goal-footer">
                                                <button class="btn btn-outline w-100">
                                                    <i class="me-2" data-lucide="pencil"></i> Editar meta
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <button class="btn btn-primary">
                            <i class="me-2" data-lucide="plus"></i> Nueva meta
                        </button>
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

                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-outline">
                            <i class="me-2" data-lucide="plus"></i> Nuevo presupuesto
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis presupuestos</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="budget-card">
                                            <div class="budget-header">
                                                <h6 class="budget-title">Alimentación</h6>
                                            </div>
                                            <div class="budget-body">
                                                <div class="budget-details">
                                                    <span>Límite: $30,000</span>
                                                    <span>Gastado: $24,000</span>
                                                </div>
                                                <div class="budget-progress-bar">
                                                    <div class="budget-progress-fill" style="width: 80%;"></div>
                                                </div>
                                                <div class="budget-details">
                                                    <span>Disponible: $6,000</span>
                                                    <span>Utilizado: 80%</span>
                                                </div>
                                            </div>
                                            <div class="budget-footer">
                                                <button class="btn btn-outline w-100">
                                                    <i class="me-2" data-lucide="pencil"></i> Editar presupuesto
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="budget-card">
                                            <div class="budget-header">
                                                <h6 class="budget-title">Transporte</h6>
                                            </div>
                                            <div class="budget-body">
                                                <div class="budget-details">
                                                    <span>Límite: $15,000</span>
                                                    <span>Gastado: $8,500</span>
                                                </div>
                                                <div class="budget-progress-bar">
                                                    <div class="budget-progress-fill" style="width: 56.7%;"></div>
                                                </div>
                                                <div class="budget-details">
                                                    <span>Disponible: $6,500</span>
                                                    <span>Utilizado: 56.7%</span>
                                                </div>
                                            </div>
                                            <div class="budget-footer">
                                                <button class="btn btn-outline w-100">
                                                    <i class="me-2" data-lucide="pencil"></i> Editar presupuesto
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <button class="btn btn-primary">
                            <i class="me-2" data-lucide="plus"></i> Nuevo presupuesto
                        </button>
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
                                <div class="assistant-chat" id="chat-messages">
                                    <div class="chat-message assistant">
                                        <div class="chat-avatar">
                                            <i data-lucide="bot"></i>
                                        </div>
                                        <div class="chat-content">
                                            <p class="chat-text mb-0">¡Hola! Soy tu asistente financiero PECUS. ¿En qué puedo ayudarte hoy?</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="chat-input-area">
                                    <input type="text" class="form-control chat-input" placeholder="Escribe tu mensaje..." id="chat-input">
                                    <button class="btn btn-primary chat-send-btn" id="chat-send-btn">
                                        <i data-lucide="send"></i>
                                    </button>
                                </div>
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

                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-outline">
                            <i class="me-2" data-lucide="toggle-left"></i> Marcar todo como leído
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Mis notificaciones</h5>
                            </div>
                            <div class="card-body">
                                ${this.mockData.notifications.map(notif => `
                                    <div class="notification-item ${notif.read ? 'read' : 'unread'}">
                                        <div class="notification-icon">
                                            <i data-lucide="${notif.type === 'success' ? 'check-circle' : notif.type === 'warning' ? 'triangle-alert' : 'info'}"></i>
                                        </div>
                                        <div class="notification-content">
                                            <h6 class="notification-title">${notif.title}</h6>
                                            <p class="notification-message">${notif.message}</p>
                                            <div class="notification-time">${notif.time}</p>
                                            <div class="notification-actions">
                                                <button class="btn btn-outline btn-sm">${notif.read ? 'Marcar como no leído' : 'Marcar como leído'}</button>
                                                <button class="btn btn-outline btn-sm">Eliminar</button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
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
                                    <span>${this.mockData.user.name} ${this.mockData.user.surname}</span>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Email</span>
                                    <span>${this.mockData.user.email}</span>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Teléfono</span>
                                    <span>+54 9 11 1234-5678</span>
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
                                        <input type="checkbox" id="push-notifications" checked>
                                        <label class="settings-slider" for="push-notifications"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Notificaciones por email</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="email-notifications" checked>
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
                                        <input type="checkbox" id="auto-sync" checked>
                                        <label class="settings-slider" for="auto-sync"></label>
                                    </div>
                                </div>
                                <div class="settings-item">
                                    <span class="settings-label">Copias de seguridad</span>
                                    <div class="settings-toggle">
                                        <input type="checkbox" id="backups" checked>
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
        const summary = this.state.dashboard?.summary || {};
        return {
            available: Number(summary.available ?? summary.balance ?? 0),
            income: Number(summary.income ?? 0),
            expenses: Number(summary.expenses ?? 0),
            balance: Number(summary.balance ?? 0),
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
        return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(num || 0));
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