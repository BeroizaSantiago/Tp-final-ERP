<?php

declare(strict_types=1);

/**
 * Agrega documentación de propósito a controllers, models y vistas del ERP.
 * Es idempotente: respeta cualquier encabezado descriptivo ya existente.
 */

$root = dirname(__DIR__);

$terms = [
    'Adjustment' => 'Ajuste', 'Adjustments' => 'Ajustes', 'Application' => 'Aplicación',
    'Bank' => 'Banco', 'Banks' => 'Bancos', 'Branch' => 'Sucursal', 'Branches' => 'Sucursales',
    'Brand' => 'Marca', 'Budget' => 'Presupuesto', 'Card' => 'Tarjeta', 'Cash' => 'Caja',
    'Category' => 'Categoría', 'Checkbook' => 'Chequera', 'Client' => 'Cliente',
    'Color' => 'Color', 'Concept' => 'Concepto', 'Coupon' => 'Cupón', 'Credit' => 'Crédito',
    'Current' => 'Corriente', 'Customer' => 'Cliente', 'Debit' => 'Débito',
    'Delivery' => 'Remito', 'Document' => 'Documento', 'Exchange' => 'Cambio',
    'Expense' => 'Gasto', 'Inventory' => 'Inventario', 'Invoice' => 'Factura',
    'Item' => 'Ítem', 'Misc' => 'Vario', 'Movement' => 'Movimiento', 'Note' => 'Nota',
    'Order' => 'Orden', 'Payment' => 'Pago', 'Perception' => 'Percepción',
    'Price' => 'Precio', 'Product' => 'Producto', 'Promotion' => 'Promoción',
    'Provider' => 'Proveedor', 'Purchase' => 'Compra', 'Reason' => 'Motivo',
    'Receipt' => 'Recibo', 'Reconciliation' => 'Conciliación', 'Retention' => 'Retención',
    'Role' => 'Rol', 'Sale' => 'Venta', 'Sales' => 'Ventas', 'Security' => 'Seguridad',
    'Sheet' => 'Planilla', 'Size' => 'Talle', 'Stock' => 'Stock', 'Tax' => 'Impuesto',
    'Ticket' => 'Ticket', 'Treasury' => 'Tesorería', 'Type' => 'Tipo', 'User' => 'Usuario',
    'Variant' => 'Variante', 'Vat' => 'IVA', 'Warehouse' => 'Depósito',
];

$entities = [
    'bank-accounts' => 'Cuentas Bancarias', 'bank-movements' => 'Movimientos Bancarios',
    'banks' => 'Bancos', 'brands' => 'Marcas', 'budgets' => 'Presupuestos',
    'cash-boxes' => 'Cajas', 'cash-sheets' => 'Planillas de Caja', 'checkbooks' => 'Chequeras',
    'clients' => 'Clientes', 'colors' => 'Colores', 'concept-types' => 'Tipos de Concepto',
    'coupons' => 'Cupones de Tarjeta', 'credit-cards' => 'Tarjetas de Crédito',
    'credit' => 'Tarjetas de Crédito', 'credit-notes' => 'Notas de Crédito', 'current-account' => 'Cuenta Corriente',
    'customer-current-account' => 'Cuenta Corriente de Clientes', 'customer-notes' => 'Notas de Pedido',
    'customer-receipts' => 'Recibos de Clientes', 'debit-notes' => 'Notas de Débito',
    'delivery-notes' => 'Remitos', 'exchange-rates' => 'Tipos de Cambio',
    'expense-types' => 'Tipos de Gasto', 'invoices' => 'Facturas de Venta',
    'inventory' => 'Inventario', 'misc-expenses' => 'Gastos Varios', 'models' => 'Modelos de Producto',
    'payment-orders' => 'Órdenes de Pago', 'perceptions' => 'Percepciones',
    'catalog' => 'Productos', 'pricing' => 'Precios de Productos',
    'product-categories' => 'Categorías de Producto', 'products' => 'Productos',
    'promotions' => 'Promociones de Venta', 'provider-current-account' => 'Cuenta Corriente de Proveedores',
    'provider-delivery-notes' => 'Remitos de Proveedores', 'provider-payment-orders' => 'Órdenes de Pago',
    'providers' => 'Proveedores', 'purchase-orders' => 'Órdenes de Compra', 'purchases' => 'Compras',
    'reasons' => 'Motivos', 'retentions' => 'Retenciones', 'roles' => 'Roles',
    'cash' => 'Planillas de Caja', 'shared' => 'Planillas de Caja',
    'operations' => 'Operaciones con Cheques de Terceros', 'security' => 'Seguridad',
    'sizes' => 'Talles', 'size-types' => 'Tipos de Talle',
    'stock' => 'Stock', 'taxes' => 'Impuestos', 'third-party-checks' => 'Cheques de Terceros',
    'treasury' => 'Tesorería', 'users' => 'Usuarios', 'vat-purchases-book' => 'Libro IVA Compras',
    'vat-sales-book' => 'Libro IVA Ventas',
];

$viewOverrides = [
    '_partials/macros.blade.php' => ['Componente', 'Identidad visual del sistema', 'Renderiza el logotipo reutilizado por los layouts.'],
    'components/action-button.blade.php' => ['Componente', 'Botón de acción', 'Renderiza botones reutilizables con estilos semánticos uniformes.'],
    'components/api-pagination.blade.php' => ['Componente', 'Paginación de listados', 'Renderiza los controles reutilizables de paginación.'],
    'components/api-pagination-script.blade.php' => ['Componente', 'Lógica de paginación', 'Conecta los controles de paginación con los endpoints del ERP.'],
    'components/tax-book-actions.blade.php' => ['Componente', 'Acciones de libros impositivos', 'Renderiza las acciones compartidas de exportación fiscal.'],
    'layouts/app.blade.php' => ['Layout', 'Aplicación principal', 'Define el layout base utilizado por las pantallas autenticadas.'],
    'layouts/blankLayout.blade.php' => ['Layout', 'Página sin navegación', 'Define la estructura para pantallas que no muestran menú ni barra superior.'],
    'layouts/commonMaster.blade.php' => ['Layout', 'Documento HTML principal', 'Define metadatos, estilos y scripts comunes de toda la aplicación.'],
    'layouts/contentNavbarLayout.blade.php' => ['Layout', 'Contenido con barra de navegación', 'Organiza el menú lateral, la barra superior y el contenido principal.'],
    'layouts/sections/footer/footer.blade.php' => ['Componente', 'Pie de página', 'Muestra el pie de página compartido del sistema.'],
    'layouts/sections/menu/submenu.blade.php' => ['Componente', 'Submenú lateral', 'Renderiza las opciones anidadas del menú.'],
    'layouts/sections/menu/verticalMenu.blade.php' => ['Componente', 'Menú lateral', 'Renderiza la navegación principal vertical del ERP.'],
    'layouts/sections/navbar/navbar.blade.php' => ['Componente', 'Barra superior', 'Define el contenedor de la barra de navegación superior.'],
    'layouts/sections/navbar/navbar-partial.blade.php' => ['Componente', 'Contenido de la barra superior', 'Muestra búsqueda, accesos rápidos, tema y usuario activo.'],
    'layouts/sections/scripts.blade.php' => ['Componente', 'Scripts globales', 'Carga los scripts comunes al final de las páginas.'],
    'layouts/sections/scriptsIncludes.blade.php' => ['Componente', 'Configuración inicial', 'Carga las utilidades y la configuración temprana del template.'],
    'layouts/sections/styles.blade.php' => ['Componente', 'Estilos globales', 'Carga fuentes, estilos del template y estilos propios del ERP.'],
    'auth/login.blade.php' => ['Vista', 'Inicio de sesión', 'Muestra el formulario de acceso de usuarios.'],
    'auth/register.blade.php' => ['Vista', 'Registro de usuario', 'Muestra el formulario de alta inicial de usuarios.'],
    'dashboard.blade.php' => ['Vista', 'Panel principal', 'Muestra los accesos y resúmenes principales del ERP.'],
    'content/authentications/auth-forgot-password-basic.blade.php' => ['Vista', 'Recuperación de contraseña', 'Muestra el formulario de recuperación de acceso.'],
    'content/authentications/auth-login-basic.blade.php' => ['Vista', 'Inicio de sesión básico', 'Muestra el formulario alternativo de autenticación.'],
    'content/authentications/auth-register-basic.blade.php' => ['Vista', 'Registro básico', 'Muestra el formulario alternativo de registro.'],
    'content/cards/cards-basic.blade.php' => ['Vista', 'Ejemplos de tarjetas', 'Muestra variantes visuales de tarjetas del template.'],
    'content/dashboard/dashboards-analytics.blade.php' => ['Vista', 'Panel analítico', 'Muestra indicadores, accesos y resúmenes operativos del ERP.'],
    'content/extended-ui/extended-ui-perfect-scrollbar.blade.php' => ['Vista', 'Ejemplo de barra de desplazamiento', 'Muestra el comportamiento visual de desplazamiento personalizado.'],
    'content/extended-ui/extended-ui-text-divider.blade.php' => ['Vista', 'Ejemplo de separadores de texto', 'Muestra variantes visuales de separadores.'],
    'content/form-elements/forms-basic-inputs.blade.php' => ['Vista', 'Ejemplo de campos básicos', 'Muestra controles básicos de formulario del template.'],
    'content/form-elements/forms-input-groups.blade.php' => ['Vista', 'Ejemplo de grupos de campos', 'Muestra campos de formulario agrupados.'],
    'content/form-layout/form-layouts-horizontal.blade.php' => ['Vista', 'Ejemplo de formulario horizontal', 'Muestra la distribución horizontal de formularios.'],
    'content/form-layout/form-layouts-vertical.blade.php' => ['Vista', 'Ejemplo de formulario vertical', 'Muestra la distribución vertical de formularios.'],
    'content/icons/icons-ri.blade.php' => ['Vista', 'Catálogo de iconos', 'Muestra los iconos Remix disponibles en el template.'],
    'tickets/exchange-ticket-print.blade.php' => ['Vista', 'Impresión de ticket de cambio', 'Genera el formato imprimible del ticket de cambio.'],
    'tickets/exchange-ticket-show.blade.php' => ['Vista', 'Detalle del ticket de cambio', 'Muestra los datos y productos asociados al ticket.'],
    'users/profile.blade.php' => ['Vista', 'Perfil de usuario', 'Muestra y permite modificar los datos del usuario autenticado.'],
];

function spanishClassName(string $class, array $terms): string
{
    $base = preg_replace('/(Controller|Service)$/', '', $class);
    preg_match_all('/[A-Z][a-z0-9]*|IVA|VAT/', (string) $base, $matches);
    return implode(' ', array_map(
        fn (string $word) => $terms[$word] ?? ($word === 'VAT' ? 'IVA' : $word),
        $matches[0]
    ));
}

function files(string $directory, string $suffix): array
{
    $result = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
            $result[] = $file->getPathname();
        }
    }
    return $result;
}

function documentPhp(string $file, string $kind, array $terms): bool
{
    $content = file_get_contents($file);
    if ($content === false || !preg_match('/^(final\s+|abstract\s+)?class\s+(\w+)/m', $content, $match, PREG_OFFSET_CAPTURE)) {
        return false;
    }

    $offset = $match[0][1];
    $before = substr($content, max(0, $offset - 600), min(600, $offset));
    if (preg_match('/\/\*\*[\s\S]*?\*\/\s*$/', $before)) {
        return false;
    }

    $class = $match[2][0];
    $name = spanishClassName($class, $terms);
    $description = $kind === 'controller'
        ? "Controlador de {$name}.\n *\n * Coordina las solicitudes, validaciones y respuestas del módulo {$name} del ERP."
        : "Modelo de {$name}.\n *\n * Representa la información persistida y las relaciones de {$name} dentro del ERP.";
    $doc = "/**\n * {$description}\n */\n";
    file_put_contents($file, substr($content, 0, $offset) . $doc . substr($content, $offset));
    return true;
}

function viewEntity(string $file, array $entities): string
{
    $segments = preg_split('~[\\\\/]~', $file);
    $segments = array_values(array_filter($segments, fn ($part) => !in_array($part, [
        'resources', 'views', 'index.blade.php', 'create.blade.php', 'edit.blade.php', 'show.blade.php',
        'payment-method.blade.php', 'app.blade.php', 'commonMaster.blade.php',
    ], true)));

    for ($i = count($segments) - 1; $i >= 0; $i--) {
        $key = str_replace('.blade.php', '', $segments[$i]);
        if (isset($entities[$key])) return $entities[$key];
    }

    $base = str_replace(['.blade.php', '-', '_'], ['', ' ', ' '], basename($file));
    return mb_convert_case($base, MB_CASE_TITLE, 'UTF-8');
}

function documentView(string $file, string $root, array $entities, array $overrides): bool
{
    $content = file_get_contents($file);
    if ($content === false) return false;

    $original = $content;
    $content = preg_replace('/^\s*\{\{--\s*(Vista|Componente|Layout):[^\n]*--\}\}\R?/u', '', $content);

    $relative = str_replace('\\', '/', substr($file, strlen($root . '/resources/views/') ));
    if (isset($overrides[$relative])) {
        [$kind, $title, $description] = $overrides[$relative];
        $header = "{{-- {$kind}: {$title}. {$description} --}}\n";
        $updated = $header . $content;
        if ($updated !== $original) file_put_contents($file, $updated);
        return $updated !== $original;
    }

    $base = basename($file, '.blade.php');
    $entity = viewEntity($file, $entities);
    [$title, $description] = match ($base) {
        'index' => ["Listado de {$entity}", "Muestra la consulta principal y las acciones disponibles de {$entity}."],
        'create' => ["Nuevo registro de {$entity}", "Muestra el formulario para crear un registro de {$entity}."],
        'edit' => ["Edición de {$entity}", "Muestra el formulario para modificar un registro de {$entity}."],
        'show' => ["Detalle de {$entity}", "Muestra la información completa de un registro de {$entity}."],
        'payment-method' => ["Formas de pago de {$entity}", "Permite registrar y consultar los medios de pago de {$entity}."],
        default => ["{$entity}", "Muestra la pantalla o componente funcional correspondiente a {$entity}."],
    };

    $header = "{{-- Vista: {$title}. {$description} --}}\n";
    $updated = $header . $content;
    if ($updated !== $original) file_put_contents($file, $updated);
    return $updated !== $original;
}

$counts = ['controllers' => 0, 'models' => 0, 'views' => 0];
foreach (files($root . '/app/Http/Controllers', '.php') as $file) {
    $counts['controllers'] += documentPhp($file, 'controller', $terms) ? 1 : 0;
}
foreach (files($root . '/app/Models', '.php') as $file) {
    $counts['models'] += documentPhp($file, 'model', $terms) ? 1 : 0;
}
foreach (files($root . '/resources/views', '.blade.php') as $file) {
    $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
    if (preg_match('~^resources/views/(content/(user-interface|tables|layouts-example|pages)/|welcome\.blade\.php)~', $relative)) continue;
    $counts['views'] += documentView($file, $root, $entities, $viewOverrides) ? 1 : 0;
}

echo json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
