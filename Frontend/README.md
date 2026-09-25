# PECUS Frontend

Este es el frontend de la aplicación PECUS, construido con HTML, CSS, JavaScript vanilla y Bootstrap.

## Estructura del proyecto

```
Frontend/
├── index.html
├── pages/
│   ├── login.html
│   ├── register.html
│   ├── onboarding.html
│   ├── dashboard.html
│   ├── movements.html
│   ├── receipts.html
│   ├── sources.html
│   ├── categories.html
│   ├── analysis.html
│   ├── goals.html
│   ├── budgets.html
│   ├── assistant.html
│   ├── notifications.html
│   └── settings.html
├── assets/
│   ├── css/
│   │   ├── variables.css
│   │   ├── global.css
│   │   ├── components.css
│   │   └── pages.css
│   ├── js/
│   │   ├── app.js
│   │   └── components.js
│   ├── fonts/
│   └── images/
└── components/
```

## Características implementadas

### Design System
- Variables CSS con los colores oficiales de PECUS:
  - Primario oscuro: #0A3B25
  - Primario: #2A6151
  - Neutro: #B2B7AA
  - Fondo: #FCF7F0
  - Acento: #D8C2A4
- Tipografías definidas (con fallbacks):
  - Principal: Gopadel Medium
  - Secundaria: Souvenir Std Light Italic
- Espaciado, bordes, sombras y transiciones consistentes

### Componentes reutilizables
- Botones (primario, secundario, outline)
- Tarjetas con efectos hover
- Badges para estados y categorías
- Inputs y selects stylizados
- Tablas responsivas
- Alertas informativas
- Modales personalizados
- Tooltips
- Indicadores de carga
- Estados vacíos
- Barras de progreso

### Layout y navegación
- Sidebar vertical minimalista (según especificación)
  - Estado retraído por defecto con solo iconos
  - Expansión al hover (desktop) o click (mobile)
  - Tooltips en estado retraído
  - Logo PECUS en la parte superior
  - Navegación agrupada por funcionalidad
  - Perfil y configuración en la parte inferior
  - Overlay para mobile

### Páginas implementadas
- **Público**: Login, Registro, Onboarding
- **Aplicación**: Dashboard, Movimientos, Comprobantes, Fuentes, Categorías, Análisis, Metas, Presupuestos, Asistente, Notificaciones, Configuración

### Funcionalidades del dashboard
- Resumen financiero (dinero disponible, ingresos, gastos, balance)
- Selector de período (Hoy, Semana, Mes, Año, Personalizado)
- Movimientos recientes
- Gráficos de gastos por categoría
- Acciones rápidas (Nuevo movimiento, Cargar comprobante, Nueva fuente, Revisar pendientes)

### Datos mock
- Implementados en app.js con información coherente y realista
- Incluyen usuario, fuentes, movimientos, categorías y notificaciones
- Diseñados para ser fácilmente reemplazados por llamadas API futuras

## Tecnologías utilizadas
- HTML5
- CSS3 (con variables CSS)
- JavaScript ES6
- Bootstrap 5.3.0 (solo para componentes y utilidades, no para diseño)
- Boxicons (como alternativa temporal a Lucide Icons hasta que se añadan las fuentes locales)

## Próximos pasos
1. Integrar Lucide Icons una vez que se agreguen las fuentes locales
2. Implementar lógica de negocio en los componentes JavaScript
3. Añadir validaciones de formulario completas
4. Implementar gráficos con una librería ligera (Chart.js o similar)
5. Mejorar el responsive y las animaciones
6. Preparar la capa de servicios para conectar con el API REST

## Cómo probar
1. Abrir `index.html` en cualquier navegador moderno
2. Navegar por las diferentes páginas usando la sidebar
3. Probar el comportamiento responsive reduciendo el tamaño de la ventana
4. Interactuar con los formularios y componentes