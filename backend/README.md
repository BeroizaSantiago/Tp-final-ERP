# TP Final ERP

Aplicación ERP desarrollada con Laravel 13, PHP 8.3 o superior, MySQL y Vite.

## Requisitos

- XAMPP con MySQL activo.
- PHP 8.3 o superior con la extensión `pdo_mysql`.
- Composer 2.
- Node.js 22 y npm 11 (o versiones compatibles con el lockfile).

## Instalación local

Desde la raíz del proyecto, ejecutar en PowerShell:

```powershell
composer install --no-interaction --prefer-dist
npm ci
Copy-Item .env.example .env -ErrorAction SilentlyContinue
php artisan key:generate
php artisan migrate --force
npm run build
```

El `.env.example` está preparado para el MySQL local de XAMPP con estos valores predeterminados:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=tp_final_erp
DB_USERNAME=root
DB_PASSWORD=
```

Antes de ejecutar las migraciones, crear desde phpMyAdmin una base vacía llamada `tp_final_erp`. Si se utiliza otro nombre, modificar `DB_DATABASE` en `.env`. Laravel mantendrá sesiones y caché en archivos, la cola en modo síncrono y el correo en los logs.

## Ejecutar la aplicación

Para iniciar Laravel y Vite en terminales separadas:

```powershell
php artisan serve
npm run dev
```

Abrir `http://127.0.0.1:8000`.

También se puede iniciar el entorno de desarrollo completo (servidor, cola, logs y Vite) con:

```powershell
composer run dev
```

## Verificación

```powershell
php artisan route:list --except-vendor
php artisan test
npm run build
```

Para reconstruir la base durante el desarrollo se puede ejecutar `php artisan migrate:fresh`, pero este comando elimina todos sus registros y solo debe usarse sobre la base local nueva.
