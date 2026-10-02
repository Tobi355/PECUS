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

Luego crea la base de datos y corre las migraciones con seeders:

```bash
php artisan migrate:fresh --seed
```

Inicia el backend:

```bash
php artisan serve --host 0.0.0.0 --port 8000
```

La API quedará disponible en:

- http://localhost:8000/api

Credenciales demo:

- Email: juan.perez@email.com
- Password: password

## 4) Frontend

Abre una nueva terminal y ejecuta:

```bash
cd ../Frontend
python -m http.server 8080
```

Luego abre en el navegador:

- http://localhost:8080

## 5) Flujo demo recomendado

1. Inicia sesión con el usuario demo.
2. Revisa el Dashboard con datos reales del backend.
3. Ve a Comprobantes.
4. Sube una imagen real (.jpg, .jpeg, .png, .pdf).
5. Procesa el comprobante.
6. Revisa y edita los datos extraídos.
7. Confirma el comprobante.
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

- verifica que exista el usuario demo en la base de datos
- revisa que el backend esté levantado
- prueba con:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"juan.perez@email.com","password":"password"}'
```

### No se abre el frontend

- usa `python -m http.server 8080` desde la carpeta `Frontend`
- no abras los HTML directamente desde el sistema de archivos si estás usando fetch a la API

## 8) Estado esperado

La demo queda funcional cuando:

- el backend responde en localhost:8000
- el frontend se abre en localhost:8080
- el login funciona
- aparecen datos reales del Dashboard
- un comprobante real puede subirse y confirmarse
- se crea el movimiento asociado
- la data persiste luego de recargar la página
