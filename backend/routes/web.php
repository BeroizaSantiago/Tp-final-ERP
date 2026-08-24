<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\InvoiceController;
use App\Models\Sales\Invoice;
use App\Models\Sales\ExchangeTicket;
use App\Models\Sales\Voucher;
use App\Services\Payments\VoucherService;
use App\Services\Printing\Code39Barcode;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use App\Models\Clients\Client;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\UserController;

Route::get('/media/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);

    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return $disk->response($path, null, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*')->name('media.public');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::view('/demo/reports/sales/consolidated', 'reports.sales.consolidated.index');
    Route::view('/demo/reports/sales/commissions', 'reports.sales.commissions.index');
    Route::view('/demo/reports/sales/list', 'reports.sales.list.index');
    Route::view('/demo/reports/sales/detailed', 'reports.sales.detailed.index');
    Route::view('/demo/reports/sales/by-date-cash', 'reports.sales.by-date-cash.index');
    Route::get('/demo/reports/products/margins/{grouping}', function (string $grouping) {
        abort_unless(in_array($grouping, ['product', 'category', 'brand'], true), 404);
        return view('reports.products.margins.index', compact('grouping'));
    });
    Route::view('/demo/reports/products/ranking', 'reports.products.ranking.index');
    Route::view('/demo/reports/purchases/misc-expenses', 'reports.purchases.misc-expenses.index');
    Route::view('/demo/reports/purchases/list', 'reports.purchases.list.index');
    Route::view('/demo/reports/purchases/detailed', 'reports.purchases.detailed.index');
    Route::view('/demo/reports/clients/list', 'reports.clients.list.index');
    Route::view('/demo/reports/clients/ranking', 'reports.clients.ranking.index');
    Route::view('/demo/reports/clients/debtors', 'reports.clients.debtors.index');
    Route::view('/demo/reports/suppliers/list', 'reports.suppliers.list.index');
    Route::view('/demo/reports/suppliers/ranking', 'reports.suppliers.ranking.index');
    Route::view('/demo/reports/suppliers/creditors', 'reports.suppliers.creditors.index');
    Route::get('/demo/reports/company-position/{type}', function (string $type) {
        abort_unless(in_array($type, ['economic', 'financial'], true), 404);
        return view('reports.company-position.index', compact('type'));
    });
    Route::view('/demo/reports/stock/movements','reports.stock.movements.index');
    Route::view('/demo/reports/stock/replenishment','reports.stock.replenishment.index');
    Route::view('/demo/reports/stock/sales-based','reports.stock.sales-based.index');
    Route::view('/demo/reports/stock/stock','reports.stock.tree.index');
    Route::view('/demo/security/users', 'security.users.index');
    Route::view('/demo/security/users/create', 'security.users.form');
    Route::get('/demo/security/users/{user}/edit',fn($user)=>view('security.users.form',['userId'=>$user]));
    Route::view('/demo/security/roles', 'security.roles.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [UserController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');

    /*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
| Pantalla principal de la aplicacion.
*/
    Route::view('/', 'dashboard');
    Route::view('/demo/dashboard', 'dashboard');
    Route::view('/demo/clients', 'clients.index');
    Route::view('/demo/clients/create', 'clients.create');

    Route::get('/demo/clients/{client}/edit', function ($client) {
        return view('clients.edit', ['clientId' => $client]);
    });

    /*
|--------------------------------------------------------------------------
| Facturas
|--------------------------------------------------------------------------
| Rutas CRUD generadas por Laravel para administrar facturas.
*/
    Route::resource('invoices', InvoiceController::class);

    /*
|--------------------------------------------------------------------------
| Productos
|--------------------------------------------------------------------------
| Vistas demo para listado, alta y datos complementarios de productos.
*/
    Route::view('/demo/products', 'products.catalog.index');
    Route::view('/demo/products/create', 'products.catalog.create');
    Route::view('/demo/products/import', 'products.catalog.import');
    Route::view('/demo/product-models', 'products.models.index');
    Route::view('/demo/size-types', 'products.size-types.index');
    Route::view('/demo/products/price-import', 'products.pricing.import');
    Route::view('/demo/products/bulk-price-update', 'products.pricing.bulk-update');
    Route::view('/demo/sales-promotions', 'products.promotions.index');
    Route::view('/demo/sales-promotions/create', 'products.promotions.form');
    Route::get('/demo/sales-promotions/{salesPromotion}/edit', fn ($salesPromotion) => view('products.promotions.form', ['promotionId'=>$salesPromotion]));
    Route::get('/demo/sales-promotions/{salesPromotion}', fn ($salesPromotion) => view('products.promotions.show', ['promotionId'=>$salesPromotion]));

    /*
|--------------------------------------------------------------------------
| Compras
|--------------------------------------------------------------------------
| Vistas demo relacionadas con compras, ordenes, remitos de proveedor y gastos.
*/

    Route::view('/demo/purchases', 'purchases.index');
    Route::view('/demo/purchases/create', 'purchases.create');

    Route::get('/demo/purchases/{purchase}', function ($purchase) {
        return view('purchases.show', ['purchaseId' => $purchase]);
    });
    Route::get('/demo/purchases/{purchase}/payment-method', function ($purchase) {
        return view('purchases.payment-method', ['purchaseId' => $purchase]);
    });
    Route::view('/demo/purchase-orders', 'purchases.purchase-orders.index');
    Route::view('/demo/purchase-orders/create', 'purchases.purchase-orders.create');
    Route::get('/demo/purchase-orders/{purchaseOrder}', function ($purchaseOrder) {
        return view('purchases.purchase-orders.show', ['purchaseOrderId' => $purchaseOrder]);
    });
    Route::view('/demo/providers', 'purchases.providers.index');
    Route::view('/demo/providers/create', 'purchases.providers.create');

    Route::get('/demo/providers/{provider}/edit', function ($provider) {
        return view('purchases.providers.edit', ['providerId' => $provider]);
    });

    Route::view('/demo/expense-types', 'purchases.expense-types.index');

    Route::view('/demo/misc-expenses', 'purchases.misc-expenses.index');
    Route::view('/demo/misc-expenses/create', 'purchases.misc-expenses.create');
    Route::view('/demo/misc-expenses/payment', 'purchases.misc-expenses.payment-method');

    Route::get('/demo/misc-expenses/{miscExpense}', function ($miscExpense) {
        return view('purchases.misc-expenses.show', ['miscExpenseId' => $miscExpense]);
    });


    /*
|--------------------------------------------------------------------------
| Ventas
|--------------------------------------------------------------------------
| Vistas demo para ventas y documentos comerciales asociados.
*/
    Route::view('/demo/sales', 'sales.invoices.index');
    Route::view('/demo/sales/create', 'sales.invoices.create');
    Route::view('/demo/vouchers', 'vouchers.index');
    Route::get('/demo/vouchers/{voucher}', fn (Voucher $voucher, VoucherService $service) => view('vouchers.show', [
        'voucher' => $service->releaseIfExpired($voucher)->load(['usedInvoice', 'reservedInvoice']),
    ]));
    Route::get('/demo/vouchers/{voucher}/print', function (Voucher $voucher, VoucherService $service, Code39Barcode $barcode) {
        $voucher = $service->releaseIfExpired($voucher);
        $validationUrl = url('/demo/vouchers/'.$voucher->id);
        $qrDataUri = (new SvgWriter())->write(new QrCode(data: $validationUrl, size: 220, margin: 10))->getDataUri();
        $barcodeSvg = $barcode->svg($voucher->code);
        return view('vouchers.print', compact('voucher', 'qrDataUri', 'barcodeSvg'));
    });
    Route::get('/demo/vouchers/{voucher}/download-image', function (Voucher $voucher, VoucherService $service, Code39Barcode $barcode) {
        $voucher = $service->releaseIfExpired($voucher);
        $validationUrl = url('/demo/vouchers/'.$voucher->id);
        $qrDataUri = (new PngWriter())->write(new QrCode(data: $validationUrl, size: 500, margin: 18))->getDataUri();
        $barcodeBits = $barcode->bits($voucher->code);
        return view('vouchers.download-image', compact('voucher', 'qrDataUri', 'barcodeBits'));
    });
    Route::view('/demo/sales/credit-notes', 'sales.credit-notes.index');
    Route::view('/demo/credit-note-reasons', 'sales.credit-notes.reasons');

    Route::view('/demo/sales/credit-notes/create', 'sales.credit-notes.create');

    Route::get('/demo/sales/credit-notes/{invoice}', function ($invoice) {
        return view('sales.credit-notes.show', ['invoiceId' => $invoice]);
    });



    Route::get('/demo/exchange-tickets/{ticket}/print', function (ExchangeTicket $ticket) {
        $ticket->load([
            'invoice.client',
            'items.invoiceItem',
        ]);

        $validationUrl = url('/demo/exchange-tickets/' . $ticket->id);

        $qrCode = new QrCode(
            data: $validationUrl,
            size: 220,
            margin: 10
        );

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        return view('tickets.exchange-ticket-print', [
            'ticket' => $ticket,
            'qrDataUri' => $result->getDataUri(),
        ]);
    });

    Route::get('/demo/exchange-tickets/{ticket}', function (ExchangeTicket $ticket) {
        return view('tickets.exchange-ticket-show', [
            'ticket' => $ticket->load([
                'invoice.client',
                'items.invoiceItem',
            ]),
        ]);
    });

    Route::post('/demo/exchange-tickets/{ticket}/use', function (
        Request $request,
        ExchangeTicket $ticket
    ) {
        if ($ticket->used) {
            return response()->json([
                'message' => 'Este ticket ya fue utilizado.',
            ], 422);
        }

        if (
            $ticket->expiration_date &&
            now()->startOfDay()->greaterThan($ticket->expiration_date->startOfDay())
        ) {
            return response()->json([
                'message' => 'Este ticket está vencido.',
            ], 422);
        }

        $ticket->update([
            'used' => true,
            'used_at' => now(),
            'used_invoice_id' => $request->input('used_invoice_id'),
        ]);

        return response()->json([
            'message' => 'Ticket utilizado correctamente.',
            'ticket' => $ticket->fresh(),
        ]);
    });
    /*
|--------------------------------------------------------------------------
| Stock
|--------------------------------------------------------------------------
| Vistas demo para inventario, ajustes y motivos de ajuste.
*/
    Route::view('/demo/stock/inventory', 'stock.inventory.index');
    Route::view('/demo/stock/adjustments', 'stock.adjustments.index');
    Route::get('/demo/stock/adjustments/{stockAdjustment}', function ($stockAdjustment) {
        return view('stock.adjustments.show', ['stockAdjustmentId' => $stockAdjustment]);
    })->name('stock-adjustments.show');
    Route::view('/demo/stock-adjustment-reasons', 'stock.adjustments.reasons');

    /*
|--------------------------------------------------------------------------
| Finanzas
|--------------------------------------------------------------------------
| Vistas demo de bancos, cuentas, movimientos, cajas, tarjetas y cotizaciones.
*/
    Route::view('/demo/finance/cash-sheets', 'finance.sheets.cash.index');
    Route::view('/demo/finance/cash-sheets/create', 'finance.sheets.cash.create');
    Route::view('/demo/finance/card-coupons', 'finance.cards.coupons.index');
    Route::view('/demo/finance/card-coupons/reconciliation', 'finance.cards.coupons.reconciliation');
    Route::get('/demo/finance/card-coupons/reconciliations/{reconciliation}', function ($reconciliation) {
        return view('finance.cards.coupons.reconciliation-show', ['reconciliationId' => $reconciliation]);
    });
    Route::view('/demo/finance/cash-boxes', 'finance.cash-boxes.index');
    Route::view('/demo/finance/cash-concept-types', 'finance.cash-boxes.concept-types');
    Route::view('/demo/finance/credit-cards', 'finance.cards.credit.index');
    Route::view('/demo/finance/credit-cards/create', 'finance.cards.credit.create');

    Route::get('/demo/finance/credit-cards/{creditCard}/edit', function ($creditCard) {
        return view('finance.cards.credit.edit', ['creditCardId' => $creditCard]);
    });

    Route::get('/demo/finance/cash-sheets/{cashSheet}', function ($cashSheet) {
        return view('finance.sheets.cash.show', ['cashSheetId' => $cashSheet]);
    });
    Route::view('/demo/finance/treasury-sheets', 'finance.sheets.treasury.index');

    Route::view('/demo/finance/cash-boxes/create', 'finance.cash-boxes.create');

    Route::get('/demo/finance/cash-boxes/{cashBox}/edit', function ($cashBox) {
        return view('finance.cash-boxes.edit', ['cashBoxId' => $cashBox]);
    });


    /*
|--------------------------------------------------------------------------
| Maestros
|--------------------------------------------------------------------------
| Vistas demo para tablas maestras usadas por productos y catalogos.
*/
    Route::view('/demo/brands', 'masters.brands');
    Route::view('/demo/colors', 'masters.colors');
    Route::view('/demo/sizes', 'masters.sizes');
    Route::view('/demo/product-categories', 'masters.product-categories');



    Route::redirect('/demo/brands/create', '/demo/brands');
    Route::redirect('/demo/product-categories/create', '/demo/product-categories');
    Route::redirect('/demo/colors/create', '/demo/colors');
    Route::redirect('/demo/product-models/create', '/demo/product-models');
    Route::redirect('/demo/size-types/create', '/demo/size-types');
    Route::redirect('/demo/sizes/create', '/demo/sizes');
    Route::view('/demo/stock/inventory/create', 'stock.inventory.create');

    Route::get('/demo/products/{product}/edit', function ($product) {
        return view('products.catalog.edit', ['productId' => $product]);
    });
    Route::get('/demo/products/{product}', function ($product) {
        return view('products.catalog.show', ['productId' => $product]);
    });
    Route::get('/demo/sales/{invoice}', function ($invoice) {
        return view('sales.invoices.show', ['invoiceId' => $invoice]);
    });

    Route::get('/demo/sales/{invoice}/ticket', function (Invoice $invoice) {
        $invoice->load([
            'client',
            'items',
            'payments.voucher',
        ]);

        $qrDataUri = null;

        return view('sales.invoices.ticket-print', compact('invoice', 'qrDataUri'));
    })->name('sales.invoices.ticket');



    Route::get('/demo/sales/{invoice}/payment-method', function ($invoice) {
        return view('sales.invoices.payment-method', ['invoiceId' => $invoice]);
    });

});
