<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Sales\CreditNotes\CreditNoteController;
use App\Http\Controllers\Api\Sales\CreditNotes\CreditNoteReasonController;
use App\Http\Controllers\Api\Sales\Invoices\InvoiceController;
use App\Http\Controllers\Api\Sales\Vouchers\VoucherController;
use App\Http\Controllers\Api\Reports\Sales\ConsolidatedSalesReportController;
use App\Http\Controllers\Api\Reports\Sales\SalesCommissionReportController;
use App\Http\Controllers\Api\Reports\Sales\SalesListReportController;
use App\Http\Controllers\Api\Reports\Sales\DetailedSalesReportController;
use App\Http\Controllers\Api\Reports\Sales\SalesByDateAndCashReportController;
use App\Http\Controllers\Api\Reports\Products\ProductMarginReportController;
use App\Http\Controllers\Api\Reports\Products\ProductSalesRankingReportController;
use App\Http\Controllers\Api\Reports\Purchases\MiscExpensesReportController;
use App\Http\Controllers\Api\Reports\Purchases\PurchasesReportController;
use App\Http\Controllers\Api\Reports\Purchases\DetailedPurchasesReportController;
use App\Http\Controllers\Api\Reports\Clients\CustomerReportsController;
use App\Http\Controllers\Api\Reports\Suppliers\SupplierReportsController;
use App\Http\Controllers\Api\Reports\CompanyPosition\CompanyPositionReportController;
use App\Http\Controllers\Api\Reports\Stock\StockReportController;
use App\Http\Controllers\Api\Products\Catalog\ProductController;
use App\Http\Controllers\Api\Products\Catalog\ProductBulkImportController;
use App\Http\Controllers\Api\Products\Changes\ProductChangeController;
use App\Http\Controllers\Api\Stock\Adjustments\StockAdjustmentController;
use App\Http\Controllers\Api\Stock\Adjustments\StockAdjustmentReasonController;
use App\Http\Controllers\Api\Stock\StockLocationController;
use App\Http\Controllers\Api\Stock\Inventory\InventoryItemController;
use App\Http\Controllers\Api\Stock\Transfers\InternalTransferController;
use App\Http\Controllers\Api\Products\Masters\Brands\BrandController;
use App\Http\Controllers\Api\Products\Masters\Publishers\PublisherController;
use App\Http\Controllers\Api\Products\Masters\Collections\CollectionController;
use App\Http\Controllers\Api\Products\Masters\Categories\ProductCategoryController;
use App\Http\Controllers\Api\Products\Masters\Colors\ColorController;
use App\Http\Controllers\Api\Products\Masters\Models\ProductModelController;
use App\Http\Controllers\Api\Products\Masters\Sizes\SizeController;
use App\Http\Controllers\Api\Products\Masters\SizeTypes\SizeTypeController;
use App\Http\Controllers\Api\Products\Pricing\ProductBulkPriceUpdateController;
use App\Http\Controllers\Api\Products\Pricing\ProductPriceImportController;
use App\Http\Controllers\Api\Products\Pricing\ProductPriceUpdateController;
use App\Http\Controllers\Api\Products\Promotions\SalesPromotionController;
use App\Http\Controllers\Api\Products\Variants\ProductVariantController;
use App\Http\Controllers\Api\Purchases\ExpenseTypes\ExpenseTypeController;
use App\Http\Controllers\Api\Purchases\Invoices\PurchaseController;
use App\Http\Controllers\Api\Purchases\MiscExpenses\MiscExpenseController;
use App\Http\Controllers\Api\Purchases\Orders\PurchaseOrderController;
use App\Http\Controllers\Api\Purchases\Providers\ProviderController;
use App\Http\Controllers\Api\Purchases\Receipts\PurchaseReceiptController;
use App\Http\Controllers\Api\Finance\Cards\Coupons\CardCouponController;
use App\Http\Controllers\Api\Finance\Cards\Coupons\CardCouponReconciliationController;
use App\Http\Controllers\Api\Finance\Cards\Credit\CreditCardController;
use App\Http\Controllers\Api\Finance\CashBoxes\CashBoxController;
use App\Http\Controllers\Api\Finance\CashBoxes\CashConceptTypeController;
use App\Http\Controllers\Api\Finance\Sheets\Cash\CashSheetController;
use App\Http\Controllers\Api\Clients\Catalog\ClientController;
use App\Http\Controllers\Api\Security\UserController as SecurityUserController;
use App\Http\Controllers\Api\Security\RoleController;
use App\Http\Controllers\Api\Auth\SessionController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\DashboardController;

Route::get('/dashboard', DashboardController::class)->middleware('auth:sanctum');

// Autenticacion del cliente React mediante tokens de Sanctum.
Route::post('/login', [SessionController::class, 'store']);
Route::post('/register', [RegisterController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [SessionController::class, 'me']);
    Route::post('/logout', [SessionController::class, 'destroy']);
});

Route::get('/security/users', [SecurityUserController::class, 'index']);
Route::post('/security/users', [SecurityUserController::class, 'store']);
Route::get('/security/users/{user}', [SecurityUserController::class, 'show']);
Route::put('/security/users/{user}', [SecurityUserController::class, 'update']);
Route::post('/security/users/{user}/toggle', [SecurityUserController::class, 'toggle']);
Route::get('/security/roles', [RoleController::class, 'index']);
Route::post('/security/roles', [RoleController::class, 'store']);
Route::match(['put', 'patch'], '/security/roles/{role}', [RoleController::class, 'update']);
Route::delete('/security/roles/{role}', [RoleController::class, 'destroy']);


// Documentos comerciales de venta y sus variantes.
Route::post('/invoices', [InvoiceController::class, 'store']);
Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::post('/invoices/{invoice}/exchange-tickets', [InvoiceController::class, 'storeExchangeTickets']);
Route::get('/vouchers', [VoucherController::class, 'index']);
Route::post('/vouchers', [VoucherController::class, 'store']);
Route::get('/vouchers/lookup/{code}', [VoucherController::class, 'lookup']);
Route::get('/vouchers/{voucher}', [VoucherController::class, 'show']);
Route::put('/vouchers/{voucher}', [VoucherController::class, 'update']);
Route::post('/vouchers/{voucher}/toggle', [VoucherController::class, 'toggle']);
Route::post('/vouchers/{voucher}/consume', [VoucherController::class, 'consume']);

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/scan-preview', [ProductController::class, 'scanPreview']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::put('/products/{product}', [ProductController::class, 'update']);
Route::patch('/products/{product}/status', [ProductController::class, 'updateStatus']);

Route::post('/product-catalog-import/simulate', [ProductBulkImportController::class, 'simulate']);
Route::post('/product-catalog-import/analyze', [ProductBulkImportController::class, 'analyze']);
Route::post('/product-catalog-import/apply', [ProductBulkImportController::class, 'apply']);

Route::post(
    '/product-price-import/simulate',
    [ProductPriceImportController::class, 'simulate']
);

Route::post(
    '/product-price-import/analyze',
    [ProductPriceImportController::class, 'analyze']
);

Route::post(
    '/product-price-import/apply',
    [ProductPriceImportController::class, 'apply']
);

Route::post(
    '/product-bulk-price-update/simulate',
    [ProductBulkPriceUpdateController::class, 'simulate']
);

Route::post(
    '/product-bulk-price-update/apply',
    [ProductBulkPriceUpdateController::class, 'apply']
);


Route::get('/credit-notes', [CreditNoteController::class, 'creditNotes']);
Route::post('/credit-notes', [CreditNoteController::class, 'storeCreditNote']);
Route::get('/credit-notes/source-invoices', [CreditNoteController::class, 'sourceInvoices']);
Route::get('/credit-notes/source-invoices/{invoice}', [CreditNoteController::class, 'sourceInvoice']);
Route::get('/credit-notes/{invoice}', [CreditNoteController::class, 'show']);




// Cambios de productos, presupuestos y catalogos auxiliares de venta.
Route::get('/product-changes', [ProductChangeController::class, 'index']);
Route::post('/product-changes', [ProductChangeController::class, 'store']);
Route::get('/product-changes/{productChange}', [ProductChangeController::class, 'show']);


Route::get('/credit-note-reasons', [CreditNoteReasonController::class, 'index']);
Route::post('/credit-note-reasons', [CreditNoteReasonController::class, 'store']);
Route::match(['put', 'patch'], '/credit-note-reasons/{creditNoteReason}', [CreditNoteReasonController::class, 'update']);


Route::get('/stock-adjustment-reasons', [StockAdjustmentReasonController::class, 'index']);
Route::post('/stock-adjustment-reasons', [StockAdjustmentReasonController::class, 'store']);
Route::get('/stock-adjustment-reasons/{stockAdjustmentReason}', [StockAdjustmentReasonController::class, 'show']);
Route::match(['put', 'patch'], '/stock-adjustment-reasons/{stockAdjustmentReason}', [StockAdjustmentReasonController::class, 'update']);
Route::delete('/stock-adjustment-reasons/{stockAdjustmentReason}', [StockAdjustmentReasonController::class, 'destroy']);

Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
Route::get('/stock-adjustments-options', [StockAdjustmentController::class, 'options']);
Route::get('/stock-adjustments-current-stock', [StockAdjustmentController::class, 'currentStock']);
Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show']);
Route::get('/stock-locations', [StockLocationController::class, 'index']);

Route::get('/inventory-items', [InventoryItemController::class, 'index']);
Route::post('/inventory-items', [InventoryItemController::class, 'store']);
Route::get('/inventory-items/{inventoryItem}', [InventoryItemController::class, 'show']);

Route::get('/internal-transfers', [InternalTransferController::class, 'index']);
Route::post('/internal-transfers', [InternalTransferController::class, 'store']);
Route::get('/internal-transfers/{internalTransfer}', [InternalTransferController::class, 'show']);

// Catalogos de productos, variantes y promociones.
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::post('/product-categories', [ProductCategoryController::class, 'store']);
Route::get('/product-categories/{productCategory}', [ProductCategoryController::class, 'show']);
Route::match(['put', 'patch'], '/product-categories/{productCategory}', [ProductCategoryController::class, 'update']);
Route::delete('/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy']);

Route::get('/sizes', [SizeController::class, 'index']);
Route::post('/sizes', [SizeController::class, 'store']);
Route::get('/sizes/{size}', [SizeController::class, 'show']);
Route::match(['put', 'patch'], '/sizes/{size}', [SizeController::class, 'update']);
Route::delete('/sizes/{size}', [SizeController::class, 'destroy']);
Route::get('/size-types', [SizeTypeController::class, 'index']);
Route::post('/size-types', [SizeTypeController::class, 'store']);
Route::get('/size-types/{sizeType}', [SizeTypeController::class, 'show']);
Route::match(['put', 'patch'], '/size-types/{sizeType}', [SizeTypeController::class, 'update']);
Route::delete('/size-types/{sizeType}', [SizeTypeController::class, 'destroy']);

Route::get('/colors', [ColorController::class, 'index']);
Route::post('/colors', [ColorController::class, 'store']);
Route::get('/colors/{color}', [ColorController::class, 'show']);
Route::match(['put', 'patch'], '/colors/{color}', [ColorController::class, 'update']);
Route::delete('/colors/{color}', [ColorController::class, 'destroy']);

Route::get('/brands', [BrandController::class, 'index']);
Route::post('/brands', [BrandController::class, 'store']);
Route::get('/brands/{brand}', [BrandController::class, 'show']);
Route::match(['put', 'patch'], '/brands/{brand}', [BrandController::class, 'update']);
Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);

// Editoriales y colecciones: catalogos propios del catalogo de libros.
Route::get('/publishers', [PublisherController::class, 'index']);
Route::post('/publishers', [PublisherController::class, 'store']);
Route::get('/publishers/{publisher}', [PublisherController::class, 'show']);
Route::match(['put', 'patch'], '/publishers/{publisher}', [PublisherController::class, 'update']);
Route::delete('/publishers/{publisher}', [PublisherController::class, 'destroy']);

Route::get('/collections', [CollectionController::class, 'index']);
Route::post('/collections', [CollectionController::class, 'store']);
Route::get('/collections/{collection}', [CollectionController::class, 'show']);
Route::match(['put', 'patch'], '/collections/{collection}', [CollectionController::class, 'update']);
Route::delete('/collections/{collection}', [CollectionController::class, 'destroy']);

Route::get('/product-models', [ProductModelController::class, 'index']);
Route::post('/product-models', [ProductModelController::class, 'store']);
Route::get('/product-models/{productModel}', [ProductModelController::class, 'show']);
Route::match(['put', 'patch'], '/product-models/{productModel}', [ProductModelController::class, 'update']);
Route::delete('/product-models/{productModel}', [ProductModelController::class, 'destroy']);

Route::post('/sales-promotions/compatible', [SalesPromotionController::class, 'compatible']);
Route::get('/invoices/{invoice}/compatible-promotions', [SalesPromotionController::class, 'compatibleInvoice']);
Route::post('/invoices/{invoice}/promotions/{salesPromotion}/apply', [SalesPromotionController::class, 'apply']);
Route::post('/sales-promotions/{salesPromotion}/toggle', [SalesPromotionController::class, 'toggle']);
Route::get('/sales-promotions', [SalesPromotionController::class, 'index']);
Route::post('/sales-promotions', [SalesPromotionController::class, 'store']);
Route::get('/sales-promotions/{salesPromotion}', [SalesPromotionController::class, 'show']);
Route::match(['put', 'patch'], '/sales-promotions/{salesPromotion}', [SalesPromotionController::class, 'update']);

Route::post('/product-price-updates/simulate', [ProductPriceUpdateController::class, 'simulate']);
Route::post('/product-price-updates/apply', [ProductPriceUpdateController::class, 'apply']);

// Compras a proveedores y comprobantes asociados.


Route::get('/purchases', [PurchaseController::class, 'index']);
Route::post('/purchases', [PurchaseController::class, 'store']);
Route::get('/purchases/{purchase}', [PurchaseController::class, 'show']);

Route::get('/purchase-receipts', [PurchaseReceiptController::class, 'index']);
Route::post('/purchase-receipts', [PurchaseReceiptController::class, 'store']);
Route::get('/purchase-receipts/{purchaseReceipt}', [PurchaseReceiptController::class, 'show']);

Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
Route::get('/demo/purchase-orders/{purchaseOrder}', function ($purchaseOrder) {
    return view('purchases.purchase-orders.show', ['purchaseOrderId' => $purchaseOrder]);
});
Route::post('/purchases/{purchase}/payments', [PurchaseController::class, 'storePayment']);



Route::get('/providers', [ProviderController::class, 'index']);
Route::post('/providers', [ProviderController::class, 'store']);
Route::get('/providers/{provider}', [ProviderController::class, 'show']);
Route::put('/providers/{provider}', [ProviderController::class, 'update']);

// Tipos de gasto y carga de gastos varios.
Route::get('/expense-types', [ExpenseTypeController::class, 'index']);
Route::post('/expense-types', [ExpenseTypeController::class, 'store']);
Route::match(['put', 'patch'], '/expense-types/{expenseType}', [ExpenseTypeController::class, 'update']);
Route::delete('/expense-types/{expenseType}', [ExpenseTypeController::class, 'destroy']);

Route::get('/misc-expenses', [MiscExpenseController::class, 'index']);
Route::post('/misc-expenses', [MiscExpenseController::class, 'store']);
Route::get('/misc-expenses/{miscExpense}', [MiscExpenseController::class, 'show']);
Route::post('/misc-expenses/{miscExpense}/payments', [MiscExpenseController::class, 'storePayment']);


Route::get('/cash-concept-types', [CashConceptTypeController::class, 'index']);
Route::post('/cash-concept-types', [CashConceptTypeController::class, 'store']);
Route::get('/cash-concept-types/{cashConceptType}', [CashConceptTypeController::class, 'show']);
Route::match(['put', 'patch'], '/cash-concept-types/{cashConceptType}', [CashConceptTypeController::class, 'update']);
Route::delete('/cash-concept-types/{cashConceptType}', [CashConceptTypeController::class, 'destroy']);

Route::get('/cash-boxes', [CashBoxController::class, 'index']);
Route::post('/cash-boxes', [CashBoxController::class, 'store']);
Route::get('/cash-boxes/{cashBox}', [CashBoxController::class, 'show']);
Route::put('/cash-boxes/{cashBox}', [CashBoxController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cash-sheets', [CashSheetController::class, 'index']);
    Route::post('/cash-sheets', [CashSheetController::class, 'store']);
    Route::get('/cash-sheets/{cashSheet}', [CashSheetController::class, 'show']);
    Route::post('/cash-sheets/{cashSheet}/close', [CashSheetController::class, 'close']);
    Route::post('/cash-sheets/{cashSheet}/reopen', [CashSheetController::class, 'reopen']);
    Route::get('/treasury-sheets', [CashSheetController::class, 'treasurySheets']);
});

Route::get('/card-coupons', [CardCouponController::class, 'index']);
Route::post('/card-coupons', [CardCouponController::class, 'store']);
Route::get('/card-coupons/{cardCoupon}', [CardCouponController::class, 'show']);
Route::put('/card-coupons/{cardCoupon}', [CardCouponController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/card-coupon-reconciliations', [CardCouponReconciliationController::class, 'index']);
    Route::get('/card-coupon-reconciliations/pending-coupons', [CardCouponReconciliationController::class, 'pendingCoupons']);
    Route::get('/card-coupon-reconciliations/options', [CardCouponReconciliationController::class, 'options']);
    Route::post('/card-coupon-reconciliations', [CardCouponReconciliationController::class, 'store']);
    Route::get('/card-coupon-reconciliations/{cardCouponReconciliation}', [CardCouponReconciliationController::class, 'show']);
});

Route::get('/credit-cards', [CreditCardController::class, 'index']);
Route::post('/credit-cards', [CreditCardController::class, 'store']);
Route::get('/credit-cards/{creditCard}', [CreditCardController::class, 'show']);
Route::put('/credit-cards/{creditCard}', [CreditCardController::class, 'update']);


Route::get('/clients/default-consumer', [ClientController::class, 'defaultConsumer']);
Route::get('/clients', [ClientController::class, 'index']);
Route::post('/clients', [ClientController::class, 'store']);
Route::get('/clients/{client}', [ClientController::class, 'show']);
Route::put('/clients/{client}', [ClientController::class, 'update']);

Route::get('/product-variants', [ProductVariantController::class, 'index']);
Route::post('/product-variants', [ProductVariantController::class, 'store']);
Route::get('/product-variants/{productVariant}', [ProductVariantController::class, 'show']);


Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment']);
Route::delete('/invoices/{invoice}/payments/{payment}/voucher', [InvoiceController::class, 'destroyVoucherPayment']);
Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize']);
Route::delete('/invoices/{invoice}/draft', [InvoiceController::class, 'discardDraft']);

// Reporte de Ventas Consolidado: una única consulta alimenta pantalla y exportaciones.
Route::middleware('auth:sanctum')->prefix('reports/sales/consolidated')->group(function () {
    Route::get('/', [ConsolidatedSalesReportController::class, 'index']);
    Route::get('/options', [ConsolidatedSalesReportController::class, 'options']);
    Route::get('/pdf', [ConsolidatedSalesReportController::class, 'pdf']);
    Route::get('/export', [ConsolidatedSalesReportController::class, 'export']);
});

// Listado de Comisiones: PDF y planillas comparten el mismo calculo del servicio.
Route::middleware('auth:sanctum')->prefix('reports/sales/commissions')->group(function () {
    Route::get('/', [SalesCommissionReportController::class, 'index']);
    Route::get('/options', [SalesCommissionReportController::class, 'options']);
    Route::get('/pdf', [SalesCommissionReportController::class, 'pdf']);
    Route::get('/export', [SalesCommissionReportController::class, 'export']);
});

Route::middleware('auth:sanctum')->prefix('reports/sales/list')->group(function () {
    Route::get('/', [SalesListReportController::class, 'index']);
    Route::get('/options', [SalesListReportController::class, 'options']);
    Route::get('/pdf', [SalesListReportController::class, 'pdf']);
    Route::get('/export', [SalesListReportController::class, 'export']);
});

Route::middleware('auth:sanctum')->prefix('reports/sales/detailed')->group(function () {
    Route::get('/', [DetailedSalesReportController::class, 'index']);
    Route::get('/options', [DetailedSalesReportController::class, 'options']);
    Route::get('/pdf', [DetailedSalesReportController::class, 'pdf']);
    Route::get('/export', [DetailedSalesReportController::class, 'export']);
});

Route::middleware('auth:sanctum')->prefix('reports/sales/by-date-cash')->group(function () {
    Route::get('/', [SalesByDateAndCashReportController::class, 'index']);
    Route::get('/options', [SalesByDateAndCashReportController::class, 'options']);
    Route::get('/pdf', [SalesByDateAndCashReportController::class, 'pdf']);
    Route::get('/export', [SalesByDateAndCashReportController::class, 'export']);
});

Route::middleware('auth:sanctum')->prefix('reports/products')->group(function () {
    Route::get('/margins/options', [ProductMarginReportController::class, 'options']);
    Route::get('/margins/{grouping}', [ProductMarginReportController::class, 'index']);
    Route::get('/margins/{grouping}/pdf', [ProductMarginReportController::class, 'pdf']);
    Route::get('/margins/{grouping}/export', [ProductMarginReportController::class, 'export']);
    Route::get('/ranking/options', [ProductSalesRankingReportController::class, 'options']);
    Route::get('/ranking', [ProductSalesRankingReportController::class, 'index']);
    Route::get('/ranking/pdf', [ProductSalesRankingReportController::class, 'pdf']);
    Route::get('/ranking/export', [ProductSalesRankingReportController::class, 'export']);
});

Route::middleware('auth:sanctum')->prefix('reports/purchases')->group(function () {
    foreach (['misc-expenses' => MiscExpensesReportController::class, 'list' => PurchasesReportController::class, 'detailed' => DetailedPurchasesReportController::class] as $path => $controller) {
        Route::get("/$path", [$controller, 'index']);
        Route::get("/$path/options", [$controller, 'options']);
        Route::get("/$path/pdf", [$controller, 'pdf']);
        Route::get("/$path/export", [$controller, 'export']);
    }
});

Route::middleware('auth:sanctum')->prefix('reports/clients')->controller(CustomerReportsController::class)->group(function () {
    Route::get('/list', 'list'); Route::get('/list/options', 'listOptions'); Route::get('/list/pdf', 'listPdf'); Route::get('/list/export', 'listExport');
    Route::get('/ranking', 'ranking'); Route::get('/ranking/pdf', 'rankingPdf'); Route::get('/ranking/export', 'rankingExport');
    Route::get('/debtors', 'debtors'); Route::get('/debtors/options', 'debtorOptions'); Route::get('/debtors/pdf', 'debtorsPdf'); Route::get('/debtors/export', 'debtorsExport');
});
Route::middleware('auth:sanctum')->prefix('reports/suppliers')->controller(SupplierReportsController::class)->group(function () {
    Route::get('/list', 'list'); Route::get('/list/pdf', 'listPdf'); Route::get('/list/export', 'listExport');
    Route::get('/ranking', 'ranking'); Route::get('/ranking/pdf', 'rankingPdf'); Route::get('/ranking/export', 'rankingExport');
    Route::get('/creditors', 'creditors'); Route::get('/creditors/options', 'creditorOptions'); Route::get('/creditors/pdf', 'creditorsPdf'); Route::get('/creditors/export', 'creditorsExport');
});
Route::middleware('auth:sanctum')->prefix('reports/company-position')->controller(CompanyPositionReportController::class)->group(function () {
    Route::get('/options', 'options');
    Route::get('/economic', 'economic'); Route::get('/economic/pdf', 'economicPdf'); Route::get('/economic/export', 'economicExport');
    Route::get('/financial', 'financial'); Route::get('/financial/pdf', 'financialPdf'); Route::get('/financial/export', 'financialExport');
});
Route::middleware('auth:sanctum')->prefix('reports/stock')->controller(StockReportController::class)->group(function () {
    Route::get('/movements/options','movementOptions'); Route::get('/movements','movements'); Route::get('/movements/pdf','movementsPdf'); Route::get('/movements/export','movementsExport');
    Route::get('/replenishment/options','replenishmentOptions'); Route::get('/replenishment','replenishment'); Route::get('/replenishment/pdf','replenishmentPdf'); Route::get('/replenishment/export','replenishmentExport');
    Route::get('/sales-based/options','stockBySalesOptions'); Route::get('/sales-based/export','stockBySalesExport'); Route::get('/sales-based','stockBySales');
    Route::get('/tree/options','stockOptions'); Route::get('/tree/export','stockExport'); Route::get('/tree','stock');
});

