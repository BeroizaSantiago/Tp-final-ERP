<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Sales\Budgets\BudgetController;
use App\Http\Controllers\Api\Sales\CreditNotes\CreditNoteController;
use App\Http\Controllers\Api\Sales\CreditNotes\CreditNoteReasonController;
use App\Http\Controllers\Api\Sales\CustomerNotes\CustomerNoteController;
use App\Http\Controllers\Api\Sales\DebitNotes\DebitNoteController;
use App\Http\Controllers\Api\Sales\DeliveryNotes\DeliveryNoteController;
use App\Http\Controllers\Api\Sales\Invoices\InvoiceController;
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
use App\Http\Controllers\Api\Reports\Banks\BankCheckReportsController;
use App\Http\Controllers\Api\Reports\CompanyPosition\CompanyPositionReportController;
use App\Http\Controllers\Api\Reports\Stock\StockReportController;
use App\Http\Controllers\Api\Products\Catalog\ProductController;
use App\Http\Controllers\Api\Products\Changes\ProductChangeController;
use App\Http\Controllers\Api\Stock\Adjustments\StockAdjustmentController;
use App\Http\Controllers\Api\Stock\Adjustments\StockAdjustmentReasonController;
use App\Http\Controllers\Api\Stock\Inventory\InventoryItemController;
use App\Http\Controllers\Api\Stock\Transfers\InternalTransferController;
use App\Http\Controllers\Api\Products\Masters\Brands\BrandController;
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
use App\Http\Controllers\Api\Purchases\ProviderDeliveryNotes\ProviderDeliveryNoteController;
use App\Http\Controllers\Api\Purchases\Providers\ProviderController;
use App\Http\Controllers\Api\Purchases\Providers\ProviderCurrentAccountController;
use App\Http\Controllers\Api\Purchases\PaymentOrders\ProviderPaymentOrderController;
use App\Http\Controllers\Api\Purchases\Receipts\PurchaseReceiptController;
use App\Http\Controllers\Api\Finance\BankAccounts\BankAccountController;
use App\Http\Controllers\Api\Finance\BankMovements\BankMovementController;
use App\Http\Controllers\Api\Finance\Banks\BankBranchController;
use App\Http\Controllers\Api\Finance\Banks\BankConceptTypeController;
use App\Http\Controllers\Api\Finance\Banks\BankController;
use App\Http\Controllers\Api\Finance\Cards\Coupons\CardCouponController;
use App\Http\Controllers\Api\Finance\Cards\Coupons\CardCouponReconciliationController;
use App\Http\Controllers\Api\Finance\Cards\Credit\CreditCardController;
use App\Http\Controllers\Api\Finance\CashBoxes\CashBoxController;
use App\Http\Controllers\Api\Finance\CashBoxes\CashConceptTypeController;
use App\Http\Controllers\Api\Finance\Checkbooks\CheckbookController;
use App\Http\Controllers\Api\Finance\ExchangeRates\ExchangeRateController;
use App\Http\Controllers\Api\Finance\Sheets\Cash\CashSheetController;
use App\Http\Controllers\Api\Finance\ThirdPartyChecks\ThirdPartyCheckController;
use App\Services\Arca\ArcaClient;
use App\Models\Sales\Invoice;
use App\Services\InvoiceFiscalService;
use App\Http\Controllers\Api\Clients\Catalog\ClientController;
use App\Http\Controllers\Api\Clients\CurrentAccount\CustomerCurrentAccountController;
use App\Http\Controllers\Api\Clients\Receipts\CustomerReceiptController;
use App\Http\Controllers\Api\Taxes\VatSalesBookController;
use App\Http\Controllers\Api\Taxes\VatPurchasesBookController;
use App\Http\Controllers\Api\Taxes\TaxRetentionController;
use App\Http\Controllers\Api\Taxes\TaxPerceptionController;
use App\Http\Controllers\Api\Security\UserController as SecurityUserController;
use App\Http\Controllers\Api\Security\RoleController;

Route::get('/taxes/vat-sales-book/export', [VatSalesBookController::class, 'export']);
Route::get('/taxes/vat-sales-book', [VatSalesBookController::class, 'index']);
Route::get('/taxes/vat-purchases-book/export', [VatPurchasesBookController::class, 'export']);
Route::get('/taxes/vat-purchases-book', [VatPurchasesBookController::class, 'index']);
Route::get('/taxes/retentions/export', [TaxRetentionController::class, 'export']);
Route::get('/taxes/retentions', [TaxRetentionController::class, 'index']);
Route::get('/taxes/perceptions/export/excel', [TaxPerceptionController::class, 'exportExcel']);
Route::get('/taxes/perceptions/export/pdf', [TaxPerceptionController::class, 'exportPdf']);
Route::get('/taxes/perceptions', [TaxPerceptionController::class, 'index']);
Route::get('/security/users', [SecurityUserController::class, 'index']);
Route::post('/security/users', [SecurityUserController::class, 'store']);
Route::get('/security/users/{user}', [SecurityUserController::class, 'show']);
Route::put('/security/users/{user}', [SecurityUserController::class, 'update']);
Route::post('/security/users/{user}/toggle', [SecurityUserController::class, 'toggle']);
Route::apiResource('/security/roles', RoleController::class)->except(['show']);

// Documentos comerciales de venta y sus variantes.
Route::post('/invoices', [InvoiceController::class, 'store']);
Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::post('/invoices/{invoice}/exchange-tickets', [InvoiceController::class, 'storeExchangeTickets']);

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::put('/products/{product}', [ProductController::class, 'update']);

Route::post(
    '/product-price-import/simulate',
    [ProductPriceImportController::class, 'simulate']
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

Route::get('/delivery-notes', [DeliveryNoteController::class, 'deliveryNotes']);
Route::post('/delivery-notes', [DeliveryNoteController::class, 'storeDeliveryNote']);
Route::get('/delivery-notes/{invoice}', [DeliveryNoteController::class, 'show']);

Route::get('/credit-notes', [CreditNoteController::class, 'creditNotes']);
Route::post('/credit-notes', [CreditNoteController::class, 'storeCreditNote']);
Route::get('/credit-notes/{invoice}', [CreditNoteController::class, 'show']);


Route::get('/debit-notes', [DebitNoteController::class, 'debitNotes']);
Route::post('/debit-notes', [DebitNoteController::class, 'storeDebitNote']);
Route::get('/debit-notes/{invoice}', [DebitNoteController::class, 'show']);

Route::get('/customer-notes', [CustomerNoteController::class, 'customerNotes']);
Route::post('/customer-notes', [CustomerNoteController::class, 'storeCustomerNote']);
Route::get('/customer-notes/{invoice}', [CustomerNoteController::class, 'show']);


// Cambios de productos, presupuestos y catalogos auxiliares de venta.
Route::get('/product-changes', [ProductChangeController::class, 'index']);
Route::post('/product-changes', [ProductChangeController::class, 'store']);
Route::get('/product-changes/{productChange}', [ProductChangeController::class, 'show']);

Route::get('/budgets', [BudgetController::class, 'budgets']);
Route::post('/budgets', [BudgetController::class, 'storeBudget']);
Route::get('/budgets/{invoice}', [BudgetController::class, 'show']);

Route::apiResource('credit-note-reasons', CreditNoteReasonController::class);


Route::apiResource('stock-adjustment-reasons', StockAdjustmentReasonController::class);

Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index']);
Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store']);
Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show']);

Route::get('/inventory-items', [InventoryItemController::class, 'index']);
Route::post('/inventory-items', [InventoryItemController::class, 'store']);
Route::get('/inventory-items/{inventoryItem}', [InventoryItemController::class, 'show']);

Route::get('/internal-transfers', [InternalTransferController::class, 'index']);
Route::post('/internal-transfers', [InternalTransferController::class, 'store']);
Route::get('/internal-transfers/{internalTransfer}', [InternalTransferController::class, 'show']);

// Catalogos de productos, variantes y promociones.
Route::apiResource('product-categories', ProductCategoryController::class);

Route::apiResource('sizes', SizeController::class);
Route::apiResource('size-types', SizeTypeController::class);

Route::apiResource('colors', ColorController::class);

Route::apiResource('brands', BrandController::class);

Route::apiResource('product-models', ProductModelController::class);

Route::post('/sales-promotions/compatible', [SalesPromotionController::class, 'compatible']);
Route::get('/invoices/{invoice}/compatible-promotions', [SalesPromotionController::class, 'compatibleInvoice']);
Route::post('/invoices/{invoice}/promotions/{salesPromotion}/apply', [SalesPromotionController::class, 'apply']);
Route::post('/sales-promotions/{salesPromotion}/toggle', [SalesPromotionController::class, 'toggle']);
Route::apiResource('sales-promotions', SalesPromotionController::class)->except('destroy')->parameters(['sales-promotions'=>'salesPromotion']);

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


Route::get('/provider-delivery-notes',[ProviderDeliveryNoteController::class, 'index']);

Route::post('/provider-delivery-notes', [ProviderDeliveryNoteController::class, 'store' ]);

Route::get('/provider-delivery-notes/{providerDeliveryNote}',[ProviderDeliveryNoteController::class, 'show']);

Route::get('/providers', [ProviderController::class, 'index']);
Route::post('/providers', [ProviderController::class, 'store']);
Route::get('/providers/{provider}', [ProviderController::class, 'show']);
Route::put('/providers/{provider}', [ProviderController::class, 'update']);

// Tipos de gasto y carga de gastos varios.
Route::apiResource('expense-types', ExpenseTypeController::class);

Route::get('/misc-expenses', [MiscExpenseController::class, 'index']);
Route::post('/misc-expenses', [MiscExpenseController::class, 'store']);
Route::get('/misc-expenses/{miscExpense}', [MiscExpenseController::class, 'show']);
Route::post('/misc-expenses/{miscExpense}/payments', [MiscExpenseController::class, 'storePayment']);


Route::apiResource('cash-concept-types',CashConceptTypeController::class);

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

Route::get('/banks', [BankController::class, 'index']);
Route::post('/banks', [BankController::class, 'store']);
Route::get('/banks/{bank}', [BankController::class, 'show']);
Route::put('/banks/{bank}', [BankController::class, 'update']);

Route::get('/bank-branches', [BankBranchController::class, 'index']);
Route::post('/bank-branches', [BankBranchController::class, 'store']);
Route::get('/bank-branches/{bankBranch}', [BankBranchController::class, 'show']);
Route::put('/bank-branches/{bankBranch}', [BankBranchController::class, 'update']);

Route::get('/bank-concept-types', [BankConceptTypeController::class, 'index']);
Route::post('/bank-concept-types', [BankConceptTypeController::class, 'store']);
Route::get('/bank-concept-types/{bankConceptType}', [BankConceptTypeController::class, 'show']);
Route::put('/bank-concept-types/{bankConceptType}', [BankConceptTypeController::class, 'update']);
Route::delete('/bank-concept-types/{bankConceptType}', [BankConceptTypeController::class, 'destroy']);

Route::get('/bank-accounts', [BankAccountController::class, 'index']);
Route::post('/bank-accounts', [BankAccountController::class, 'store']);
Route::get('/bank-accounts/{bankAccount}', [BankAccountController::class, 'show']);
Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update']);

Route::get('/checkbooks', [CheckbookController::class, 'index']);
Route::post('/checkbooks', [CheckbookController::class, 'store']);
Route::get('/checkbooks/{checkbook}', [CheckbookController::class, 'show']);

Route::get('/third-party-checks', [ThirdPartyCheckController::class, 'index']);
Route::post('/third-party-checks', [ThirdPartyCheckController::class, 'store']);
Route::get('/third-party-checks/{thirdPartyCheck}', [ThirdPartyCheckController::class, 'show']);
Route::post('/third-party-checks/{thirdPartyCheck}/operation', [ThirdPartyCheckController::class, 'operation']);

Route::get('/bank-movements', [BankMovementController::class, 'index']);
Route::post('/bank-movements', [BankMovementController::class, 'store']);
Route::get('/bank-movements/{bankMovement}', [BankMovementController::class, 'show']);
Route::post('/bank-movements/{bankMovement}/reconcile', [BankMovementController::class, 'reconcile']);
Route::put('/bank-movements/{bankMovement}', [BankMovementController::class, 'update']);

Route::apiResource('exchange-rates', ExchangeRateController::class);

Route::get('/clients', [ClientController::class, 'index']);
Route::post('/clients', [ClientController::class, 'store']);
Route::get('/clients/{client}', [ClientController::class, 'show']);
Route::put('/clients/{client}', [ClientController::class, 'update']);

Route::get('/product-variants', [ProductVariantController::class, 'index']);
Route::post('/product-variants', [ProductVariantController::class, 'store']);
Route::get('/product-variants/{productVariant}', [ProductVariantController::class, 'show']);


// Rutas de prueba para el servicio ARCA.
Route::get('/arca/dummy', function () {
    $arca = new ArcaClient(config('arca'));

    return response()->json([
        'result' => $arca->dummy(),
    ]);
});

Route::get('/arca/last-voucher', function () {
    $arca = new \App\Services\Arca\ArcaClient(config('arca'));

    return response()->json([
        'result' => $arca->getLastVoucher(
            (int) config('arca.pto_vta'),
            11 // Factura C
        ),
    ]);
});
Route::post('/invoices/{invoice}/authorize-arca', [InvoiceController::class, 'authorizeArca']);



Route::post('/invoices/{invoice}/authorize', function (
    Invoice $invoice,
    InvoiceFiscalService $service
) {
    return response()->json(
        $service->authorize($invoice)
    );
});


Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment']);

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
Route::middleware('auth:sanctum')->prefix('reports/banks')->controller(BankCheckReportsController::class)->group(function () {
    Route::get('/checks-to-cover', 'checksToCover');
    Route::get('/checks-to-cover/options', 'checksToCoverOptions');
    Route::get('/checks-to-cover/pdf', 'checksToCoverPdf');
    Route::get('/checks-to-cover/export', 'checksToCoverExport');
    Route::get('/third-party-checks', 'thirdParty');
    Route::get('/third-party-checks/options', 'thirdPartyOptions');
    Route::get('/third-party-checks/pdf', 'thirdPartyPdf');
    Route::get('/third-party-checks/export', 'thirdPartyExport');
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

// Cuentas corrientes de clientes y aplicaciones de recibos.
Route::prefix('customer-current-account')->group(function () {

    Route::get('/{client}', [InvoiceController::class,'customerCurrentAccount']);
    Route::post('/{client}/receipts', [CustomerCurrentAccountController::class, 'storeReceipt']);
});

Route::get(
    '/customer-current-account/{client}',
    [InvoiceController::class, 'customerCurrentAccount']
);

Route::prefix('provider-current-account')->group(function () {
    Route::get('/{provider}', [ProviderCurrentAccountController::class, 'show']);
    Route::post('/{provider}/payments', [ProviderCurrentAccountController::class, 'storePayment']);

});

Route::prefix('provider-payment-orders')->group(function () {
    Route::get('/', [ProviderPaymentOrderController::class, 'index']);
    Route::get('/export', [ProviderPaymentOrderController::class, 'export']);
    Route::post('/', [ProviderPaymentOrderController::class, 'store']);
    Route::get('/{providerPaymentOrder}', [ProviderPaymentOrderController::class, 'show']);
    Route::post('/{providerPaymentOrder}/cancel', [ProviderPaymentOrderController::class, 'cancel']);
});

Route::get(
    '/customer-receipts',
    [CustomerReceiptController::class, 'index']
);

Route::get(
    '/customer-receipts/export',
    [CustomerReceiptController::class, 'export']
);

Route::get(
    '/customer-receipts/{customerReceipt}',
    [CustomerReceiptController::class, 'show']
);

Route::post(
    '/customer-receipts/{customerReceipt}/cancel',
    [CustomerReceiptController::class, 'cancel']
);
