# TP Final ERP

El repositorio contiene:

- `backend/`: aplicación Laravel y vistas Blade actuales.
- `frontend/`: base de React para las futuras vistas.

Por ahora, el sistema funcional se levanta desde `backend/` usando Blade.

## Requisitos

Antes de comenzar, tener instalado:

- XAMPP con MySQL y phpMyAdmin.
- PHP 8.3 o superior con `pdo_mysql`.
- Composer 2.
- Node.js y npm.

## Pasos para levantar el proyecto

### Paso 1: iniciar MySQL y crear la base de datos

Abrir el panel de XAMPP y presionar **Start** en MySQL.

Abrir phpMyAdmin y crear una base vacía con estos datos:

```
Nombre: tp_final_erp
Codificación: utf8mb4
```

No es necesario crear tablas manualmente.

### Paso 2: abrir una terminal en el proyecto

Abrir PowerShell en la carpeta raíz del repositorio y entrar al backend:

```
cd Tp-final-ERP\backend
```

Todos los comandos de los pasos siguientes deben ejecutarse dentro de `backend/`.

### Paso 3: instalar las dependencias de PHP

En la terminal ejecutar:

```
composer install --no-interaction --prefer-dist
```

### Paso 4: instalar las dependencias de JavaScript

En la misma terminal ejecutar:

```
npm ci
```

### Paso 5: crear el archivo de configuración

Si `backend/.env` todavía no existe, ejecutar:

```
Copy-Item .env.example .env
```

La conexión local debe quedar así:

```.env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=tp_final_erp
DB_USERNAME=root
DB_PASSWORD=
```

El puerto `3307` corresponde al MySQL de XAMPP configurado en esta computadora. Si XAMPP utiliza otro puerto, modificar `DB_PORT` en `.env`.

### Paso 6: generar la clave de Laravel

En la terminal ejecutar:

```
php artisan key:generate
php artisan config:clear
```

### Paso 7: ejecutar las migraciones

Con MySQL iniciado y la base `tp_final_erp` creada, ejecutar:

```
php artisan migrate
```

Crea todas las tablas necesarias. También crea el cliente operativo `Consumidor Final`.

Para comprobar las migraciones:

```
php artisan migrate:status
```

### Paso 8: compilar los estilos y scripts de Blade (para ver el sistema, despues se reemplaza con el front nuevo)

En la terminal ejecutar:

```
npm run build
```

### Paso 9: levantar Laravel

En la terminal ejecutar:

```
php artisan serve
```

Cuando aparezca el mensaje indicando que el servidor está activo, abrir:

```text
http://127.0.0.1:8000
```

La primera vez se puede ingresar a `http://127.0.0.1:8000/register` para crear un usuario.


## Frontend React

React ya está instalado en `frontend/`, pero todavía no contiene las vistas definitivas.

Para levantarlo, abrir otra terminal desde la raíz y ejecutar:

```
cd frontend   
npm install
npm run dev
```

Luego abrir:

```text
http://127.0.0.1:5173
```

## Verificar el proyecto

Para comprobar el backend, entrar a `backend/` y ejecutar:

```
php artisan route:list --except-vendor
php artisan test
npm run build
```

Para comprobar React, entrar a `frontend/` y ejecutar:

```
npm run build
```

## Comandos importantes

Limpiar la configuración almacenada por Laravel:

```
php artisan config:clear
```

Limpiar las vistas Blade compiladas:

```
php artisan view:clear
```

Ejecutar migraciones nuevas:

```
php artisan migrate
```

Reconstruir completamente la base local:

```
php artisan migrate:fresh
```

> `migrate:fresh` elimina todas las tablas y registros. No ejecutarlo si hay información que se quiera conservar.
