# PECUS Development Plan

Based on user priorities from pasted content:

## Prioridad A: núcleo financiero confiable
- [x] Creación, visualización, edición y eliminación lógica de movimientos
- [x] Ingresos y gastos manuales
- [x] Validaciones coherentes de importes, fechas, categorías y fuentes
- [x] Estados pendientes y confirmados
- [x] Filtros por período, tipo, fuente, categoría
- [x] Coherencia de datos después de editar/eliminar movimientos
- [x] Cálculo correcto de saldos por fuente
- [x] Cálculo del balance general, ingresos y gastos
- [x] Tratamiento consistente de las transferencias internas
- [x] Representación correcta de transferencias externas y reembolsos
- [x] Protección de los datos de cada usuario
- [x] Prevención de operaciones duplicadas y estados inconsistentes

## Prioridad B: fuentes y categorías
- [x] Crear, listar, ver, editar y eliminar lógicamente fuentes
- [x] Tipos de fuente y monedas admitidas
- [x] Saldo inicial y saldo actual correctamente diferenciados
- [x] Relaciones correctas con los movimientos
- [x] Validación de pertenencia al usuario
- [x] Manejo coherente cuando una fuente tiene movimientos asociados
- [x] Categorías de ingresos y gastos
- [x] Crear, listar, editar y eliminar lógicamente
- [x] Categorías predeterminadas creadas durante el registro
- [x] Protección de las categorías predeterminadas cuando corresponda

## Prioridad C: comprobantes sin IA todavía
- [x] Subir un archivo real
- [x] Validar formato, tamaño y tipo de archivo
- [x] Guardarlo de forma segura
- [x] Registrar sus metadatos y estado
- [x] Permitir visualizar o descargar el archivo únicamente a su propietario
- [x] Permitir cargar o editar manualmente los datos financieros cuando corresponda
- [x] Permitir revisar los datos antes de confirmar
- [x] Crear el movimiento asociado sin duplicarlo
- [x] Mantener coherencia entre comprobante y movimiento
- [x] Mostrar estados y errores comprensibles
- [x] Corregir rutas o métodos de la API que impidan utilizar el flujo de comprobantes

## Prioridad D: dashboard financiero
- [x] Balance general
- [x] Ingresos y gastos del período
- [x] Saldos por fuente
- [x] Movimientos recientes
- [x] Distribución por categoría
- [x] Selector de período y filtros
- [x] Estados vacíos cuando el usuario todavía no tiene movimientos
- [x] Actualización de los indicadores después de crear, editar o eliminar información

## Prioridad E: integración real del frontend
- [x] Movimientos
- [x] Fuentes
- [x] Categorías
- [x] Comprobantes
- [x] Dashboard
- [x] Formularios, modales, filtros, búsquedas y acciones
- [x] Estados de carga, errores, confirmaciones y listas vacías
- [x] Navegación y autenticación

## Prioridad F: robustez e integración
- [x] Transacciones de base de datos para operaciones financieras que deben ser atómicas
- [x] Validaciones del lado del servidor
- [x] Autorización y aislamiento de datos por usuario
- [x] Manejo de errores de API
- [x] Relaciones y eliminación lógica coherentes
- [x] Prevención de confirmaciones duplicadas
- [x] Registro de usuario y creación de categorías predeterminadas de forma consistente
- [x] Consultas e índices razonables para las operaciones que realmente lo necesiten