# PECUS - Guía rápida para ejecutar el proyecto

## 1) Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MariaDB/MySQL
- Git

## 2) Clonar y preparar el proyecto

```bash
git clone <url-del-repo>
cd Pecus
```

## 3) Backend (Laravel)

```bash
cd Backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `Backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pecus
DB_USERNAME=root
DB_PASSWORD=
```

Luego crea la base de datos y corre las migraciones:

```bash
php artisan migrate
```

Importante: la base de datos queda limpia por defecto. No se crean usuarios ni registros de demo al inicializarla, para que puedas exportarla sin datos de prueba.

Inicia el backend:

```bash
php artisan serve
```

La API quedará disponible en:

- http://localhost:8000/api

Registrate desde la pantalla de registro. Cada cuenta nueva recibe categorías iniciales; no se crean cuentas ni movimientos de demostración.

## 4) Frontend

Abre una nueva terminal y ejecuta:

```bash
cd Frontend
python -m http.server 8080
```

Luego abre en el navegador:

- http://localhost:8080

## 5) Flujo inicial

1. Registrate e inicia sesión.
2. Crea una fuente financiera.
3. Ve a Comprobantes y sube una imagen o PDF.
4. PECUS guardará el archivo, sin extraer datos automáticamente.
5. Completa manualmente los datos y selecciona una fuente y categoría.
6. Confirma para crear el movimiento.
8. Verifica el movimiento generado en Movimientos.
9. Vuelve al Dashboard y valida que cambió el balance.
10. Recarga la página para comprobar persistencia.

## 6) Verificación de funcionamiento

### Backend

```bash
cd Backend
php artisan test
```

### Frontend

- Abrir http://localhost:8080/pages/login.html
- Inicio de sesión exitoso
- Redirección a Dashboard
- Carga de datos reales desde Laravel
- Upload de comprobante real
- Confirmación del comprobante
- Movimiento creado y visible en Movimientos

## 7) Solución de errores comunes

### CORS

Si el navegador muestra error CORS:

- asegurate de que el backend esté corriendo en puerto 8000
- asegurate de que el frontend esté en 8080
- revisa que la API esté respondiendo con `Access-Control-Allow-Origin` permitido para `http://localhost:8080`

### Login falla

- verifica que la cuenta se haya registrado correctamente
- revisa que el backend esté levantado
- prueba con:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tu@email.com","password":"tu-contraseña"}'
```

### No se abre el frontend

- usa `python -m http.server 8080` desde la carpeta `Frontend`
- no abras los HTML directamente desde el sistema de archivos si estás usando fetch a la API

## 8) Estado esperado

El estado esperado de las funcionalidades conectadas es:

- el backend responde en localhost:8000
- el frontend se abre en localhost:8080
- el login funciona
- aparecen datos reales del Dashboard
- un comprobante real puede guardarse y completarse manualmente antes de confirmarlo
- se crea el movimiento asociado
- metas, presupuestos, análisis y notificaciones siguen pendientes de endpoints funcionales
