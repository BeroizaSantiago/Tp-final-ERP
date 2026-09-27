# TP Final ERP

El repositorio contiene:

- `backend/`: aplicación Laravel. Sus APIs y reglas de negocio siguen siendo la
  fuente de datos; las vistas Blade quedan sólo como referencia estética.
- `frontend/`: aplicación React. Es la interfaz del ERP y el punto de entrada.

Por ahora, el sistema funcional se levanta desde `backend/` usando Blade. El login
de `frontend/` es la primera vista migrada a React.

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

`frontend/` es la aplicación web del ERP. **Todas las vistas se generan desde React**:
la SPA nunca navega hacia el backend ni cambia de puerto, ni siquiera al iniciar o
cerrar sesión. Del backend sólo se consumen **datos** mediante su API.

Por ahora están implementados el login, el alta de usuario, el catálogo de libros y
el CRUD de los catálogos maestros, todo con la estética de la vista Blade
`auth/login.blade.php` como referencia. Las vistas Blade de
`backend/resources/views` se conservan **sólo como referencia estética** y no se
utilizan.

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

> El backend debe estar levantado (`php artisan serve`) para poder iniciar sesión.

### Configuración del frontend

El frontend se conecta al backend mediante variables de entorno. Copiar el ejemplo
si el backend escucha en otro host o puerto:

```
Copy-Item .env.example .env
```

```.env
VITE_API_URL=http://127.0.0.1:8000/api
```

`VITE_API_URL` es el único dato obligatorio: apunta a la API, no a las vistas.

### Rutas

| Ruta               | Acceso     | Pantalla                                    |
| ------------------ | ---------- | ------------------------------------------- |
| `/`                | —          | redirige a `/libros`                        |
| `/login`           | sin sesión | login (replica `auth/login.blade.php`)      |
| `/register`        | sin sesión | alta de usuario (mismo layout que el login) |
| `/libros`          | con sesión | listado con búsqueda, filtros y paginación  |
| `/libros/nuevo`    | con sesión | alta de libro                               |
| `/libros/:id`      | con sesión | administración del libro                    |
| `/libros/:id/editar` | con sesión | edición del libro                         |
| `/maestros/*`      | con sesión | CRUD de los catálogos maestros              |
| `/dashboard`       | —          | retired, redirige a `/libros`               |
| cualquier otra    | —          | 404 dentro de la SPA                        |

No hay dashboard: el catálogo de libros es la pantalla de inicio y **todo el menú
cuelga del desplegable "Productos"** (libros, cargar libro y los catálogos maestros).

La sesión se restaura al recargar: el token de Sanctum se valida contra
`GET /api/user`, y mientras se valida se muestra un loader en vez de expulsar al
usuario al login.

## Módulo de libros

En el ERP los libros se cargan sobre el catálogo de productos: la API, la base y
el dominio siguen llamándose *producto* (`/api/products`, tabla `products`), y sólo
cambia la etiqueta que ve el usuario.

La SPA implementa las cuatro pantallas de la sección de productos, consumiendo
`Api\Products\Catalog\ProductController`:

- **Listado** (`/libros`): portada, ISBN, categoría, marca, precio final A, stock,
  estado y depósito; búsqueda con rebote, filtros por categoría / marca / estado y
  paginación del backend. Las acciones por fila son botones cuadrados de sólo ícono
  con `title` y `aria-label`, para que la tabla no requiera scroll horizontal.
- **Detalle** (`/libros/:id`): datos de edición, sinopsis, notas, listas de precio,
  variantes y depósitos, más editar y habilitar / dar de baja.
- **Alta y edición** (`/libros/nuevo`, `/libros/:id/editar`): formulario por
  secciones con datos de edición, clasificación (desde los maestros), precios con
  cálculo automático de impuestos, stock y portadas.
- **Maestros** (`/maestros/*`): CRUD genérico sobre categorías, marcas, editoriales,
  modelos, colecciones y colores. Los selects del formulario de libros leen de estos
  mismos catálogos. Los talles y los tipos de talle quedaron afuera: son de
  indumentaria y no aplican a los libros, aunque los endpoints sigan existiendo en el
  backend.

### Editoriales y colecciones

Para el catálogo de libros hacen falta dos catálogos que el ERP no tenía. Se
agregaron con una migración (`2026_08_22_100000_create_publishers_and_collections_tables`)
que crea:

- `publishers` (editoriales): nombre, código externo, país, sitio web y estado.
- `collections` (colecciones): nombre, código externo, editorial a la que pertenece,
  orden y estado.
- `publisher_id` y `collection_id` en `products`, para poder elegirlos en el
  formulario del libro.

Sus endpoints siguen el mismo patrón que el resto de los maestros
(`/api/publishers` y `/api/collections`, con `?lookup=1` para los selects). Quedan
distintos de marcas y modelos a propósito: la marca es el sello comercial, la
editorial es quien edita, el modelo es la edición y la colección es la serie.

### Campos con default en los maestros

Las tablas de maestros declaran columnas `NOT NULL DEFAULT 0` (por ejemplo
`web_order`) y el middleware `ConvertEmptyStringsToNull` de Laravel convierte los
campos vacíos en `null` antes de validar. Si ese `null` llegaba al `insert`, MySQL
cortaba la consulta con un error de integridad y el guardado fallaba.

Se corrige en los dos lados:

- El frontend manda `0` en los campos numéricos vacíos, y `null` sólo en los
  selects (que sí son claves foráneas nullable).
- El backend completa con su default los campos nulos mediante el trait
  `AppliesCatalogDefaults`, para que ningún cliente pueda provocar el error 500.

### Menú lateral

Todo cuelga de un desplegable **Productos** que contiene, en orden: Libros, Cargar
libro, Categorías, Marcas, Editoriales, Modelos, Colecciones y Colores. El estado
(abierto o plegado) se recuerda en `localStorage`, y el grupo se reabre solo
cuando la ruta actual es una de sus páginas, para no dejar al usuario en una
pantalla que no puede ver en el menú.

### Stock y portadas

Dos problemas que impedían guardar libros con portada y actualizar el stock:

- **Multipart y booleanos.** Al subir una imagen la petición va como multipart, y
  ahí todos los valores viajan como texto. La regla `boolean` de Laravel sólo
  acepta `true`, `false`, `1`, `0`, `"1"` y `"0"`, así que mandar `has_variants`
  como `"false"` devolvía 422 y el producto no se guardaba. El frontend ahora manda
  `1` y omite el `false`, y el backend normaliza `"true"` / `"false"` para tolerar
  cualquier cliente.
- **Stock desactualizado.** El alta inicializaba el stock de la variante, pero la
  edición no: `current_stock` se guardaba en el producto mientras `available_stock`
  y la variante quedaban con el valor anterior, y el detalle mostraba un stock
  distinto al del listado. `update()` ahora sincroniza la variante y recalcula.

Las imágenes se sirven por la ruta `/media/{path}` del backend, que devuelve el
archivo desde el disco `public`.

### Endpoints ampliados

Para que el módulo sea utilizable se completaron tres huecos de la API, **sin
tocar la lógica de negocio**:

- `GET /api/products` acepta `category_id`, `brand_id`, `is_active` y `per_page`,
  y ahora carga `inventoryItems` (la columna Depósito siempre salía vacía).
- `POST /api/products` y `PUT /api/products/{id}` aceptan `description`, `notes`,
  `web_title`, `min_stock` y `reposition_stock`, que ya existían en la tabla
  `products` pero no se aceptaban. `description` es la sinopsis del libro.
- Mensajes de validación en español en los endpoints de productos.

`PUT /api/products/{id}` exige `name`: no admite actualizaciones parciales, por eso
el formulario siempre manda el registro completo. El catálogo **no tiene endpoint
de borrado** (no existe `DELETE /api/products`), por lo que la baja de un libro se
hace inhabilitándolo.

### Íconos

Los 45 iconos de la interfaz se copiaron de la colección Remix Icon que ya usa el
backend, para no sumar una dependencia de iconos. Para regenerarlos:
`node tools/extract-icons.cjs`.

### Autenticación

El login usa **tokens de Sanctum** (`Authorization: Bearer <token>`), guardados en
`localStorage` bajo la clave `erp.token`.

Endpoints en `backend/routes/api.php`:

| Método | Ruta          | Descripción                                      |
| ------ | ------------- | ------------------------------------------------ |
| `POST` | `/api/login`  | Valida credenciales y devuelve `token` + `user`.  |
| `POST` | `/api/register` | Crea el usuario, inicia sesión y devuelve `token` + `user` + `initial_password`. |
| `GET`  | `/api/user`   | Devuelve el usuario del token vigente.           |
| `POST` | `/api/logout` | Revoca el token vigente.                         |

`/api/login` y `/api/register` replican las reglas del ERP: el login acepta
**usuario o correo** en el mismo campo y exige `is_active = 1`; el alta asigna el rol
**Vendedor**, deriva el `username` del correo (`prefijo-id`) y usa la contraseña
inicial de `RegisteredUserController::DEFAULT_PASSWORD` (`ERP123`), que la SPA muestra
una sola vez en el dashboard.

### Comandos

```
npm run dev      # servidor de desarrollo en http://127.0.0.1:5173
npm run build    # build de producción en dist/
npm run preview  # previsualiza el build
npm test         # smoke test de rutas y flujo de login (jsdom)
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
