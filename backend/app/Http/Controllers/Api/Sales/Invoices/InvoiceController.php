<?php

namespace App\Http\Controllers\Api\Sales\Invoices;

use App\Http\Controllers\Api\Sales\Shared\SalesDocumentController;
use App\Models\Finance\CardCoupon;
use App\Models\Clients\Client;
use App\Models\Clients\CustomerReceipt;
use App\Models\Clients\CustomerReceiptApplication;
use App\Models\Clients\CustomerReceiptPayment;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePayment;
use App\Models\Sales\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Finance\CashSheet;
use App\Models\Finance\CashSheetMovement;
use App\Services\InvoiceFiscalService;
use App\Services\Payments\VoucherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Controlador de Factura.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Factura del ERP.
 */
class InvoiceController extends SalesDocumentController
{
    public function index(Request $request)
    {
        $query = Invoice::query()
            ->with([
                'client',
                'items.variant.size',
                'items.variant.color',
            ])
            ->where(function ($q) {
                $q->where('receipt_types_prefix', 'FV')
                    ->orWhereNull('receipt_types_prefix');
            });

        // Los registros sin aplicación de stock son borradores de la pantalla
        // de pago, no facturas finalizadas.
        $query->whereNotNull('stock_applied_at');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('full_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('receipt_type_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        return $query
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        // Al pasar a formas de pago sólo se crea un borrador. El stock se
        // confirma de forma atómica cuando el usuario finaliza la venta.
        $request->merge(['affects_stock' => false]);
        $request->merge([
            'receipt_type_name' => 'Comprobante interno',
            'letter' => '-',
            'first_number' => '0099',
            'mode' => 'internal',
        ]);

        return $this->storeDocument($request);
    }

    public function finalize(Invoice $invoice)
    {
        $lock = Cache::lock('invoice-finalize-'.$invoice->id, 60);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'La venta ya se está finalizando. Esperá unos segundos.',
            ], 409);
        }

        try {
            $invoice->refresh()->load(['payments', 'client']);


            $appliedPayments = $invoice->payments->filter(fn (InvoicePayment $payment) =>
                $payment->status === 'approved' || (! $payment->provider && ! $payment->status)
            );

            $paid = (float) $appliedPayments->sum(fn (InvoicePayment $payment) =>
                $payment->payment_method === 'current_account'
                    ? (float) $payment->amount
                    : (float) $payment->total_paid
            );

            if ($appliedPayments->isEmpty() || (float) $invoice->total_amount - $paid > 0.005) {
                return response()->json([
                    'message' => 'La factura debe tener el importe completo asignado antes de finalizar.',
                ], 422);
            }

            if (true) {
                $invoice->update([
                    'mode' => 'internal',
                    'receipt_type_name' => 'Comprobante interno',
                    'letter' => '-',
                    'first_number' => '0099',
                    'full_number' => $invoice->full_number
                        ?: '0099-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT),
                    'arca_status' => 'NOT_APPLICABLE',
                    'arca_result' => 'Comprobante interno',
                    'arca_response' => null,
                ]);

                $this->applyInvoiceStock($invoice);
                $this->consumeInvoiceVouchers($invoice);

                return response()->json([
                    'message' => 'Venta finalizada como comprobante interno. ',
                    'invoice' => $invoice->fresh()->load(['payments', 'items', 'client']),
                ]);
            }

        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?: 'No se pudo aplicar el stock de la venta.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo finalizar la venta local.',
            ], 502);
        } finally {
            $lock->release();
        }
    }

    public function discardDraft(Invoice $invoice)
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->arca_cae || $invoice->stock_applied_at || $invoice->payments()->exists()) {
                return response()->json([
                    'message' => 'No se puede volver a editar porque la venta ya tiene pagos o fue finalizada.',
                ], 422);
            }

            $invoice->items()->delete();
            $invoice->delete();

            return response()->json(['message' => 'Borrador descartado.']);
        });
    }

    public function destroyVoucherPayment(Invoice $invoice, InvoicePayment $payment, VoucherService $service)
    {
        if ((int) $payment->invoice_id !== (int) $invoice->id || $payment->payment_method !== 'voucher' || ! $payment->voucher_id) {
            return response()->json(['message' => 'El pago con voucher no pertenece a esta venta.'], 404);
        }
        if ($invoice->stock_applied_at || $invoice->arca_cae) {
            return response()->json(['message' => 'No se puede quitar un voucher de una venta finalizada.'], 422);
        }

        $voucher = Voucher::query()->findOrFail($payment->voucher_id);
        $service->release($voucher, $invoice);

        return response()->json([
            'message' => 'Voucher liberado correctamente.',
            'invoice' => $invoice->fresh()->load(['payments.voucher', 'items', 'client']),
        ]);
    }

    private function applyInvoiceStock(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($lockedInvoice->stock_applied_at) {
                return [];
            }

            $lockedInvoice->load('items');
            $affectedProductIds = [];
            foreach ($lockedInvoice->items as $item) {
                // Los artículos genéricos ZZ/00/0000 no representan una
                // unidad inventariable y, por definición, no mueven stock.
                if (! $item->product_variant_id) {
                    continue;
                }

                $variant = \App\Models\Products\ProductVariant::query()
                    ->lockForUpdate()
                    ->findOrFail($item->product_variant_id);
                $quantity = (float) $item->quantity;

                if ((float) $variant->current_stock < $quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'stock' => "Stock insuficiente para {$item->product_name}.",
                    ]);
                }

                $remaining = (float) $variant->current_stock - $quantity;
                $variant->update([
                    'current_stock' => $remaining,
                    'available_stock' => $remaining,
                ]);
                app(\App\Services\StockService::class)->recalculateProductStock($variant->product);
                $affectedProductIds[] = (int) $variant->product_id;
            }

            $lockedInvoice->update(['stock_applied_at' => now()]);
            return array_values(array_unique($affectedProductIds));
        });

        // La venta ya quedó confirmada y su stock persistido. La actualización
        // externa es posterior para que una caída de WooCommerce nunca revierta
        // ni duplique la factura local.
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['card', 'cash', 'transfer', 'check'])],
            'amount' => ['required', 'numeric', 'min:0.01'],

            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'surcharge_amount' => ['nullable', 'numeric', 'min:0'],

            'card_name' => ['nullable', 'string'],
            'card_plan' => ['nullable', 'string'],
            'card_surcharge_percentage' => ['nullable', 'numeric', 'min:0'],

            'coupon_number' => ['nullable', 'string'],
            'lot_number' => ['nullable', 'string'],
            'last_digits_card' => ['nullable', 'string'],
            'trade_number' => ['nullable', 'string'],

            'bank_name' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'payment_due_date' => ['nullable', 'date'],
            'voucher_code' => ['nullable', 'string', 'max:40'],
        ]);

        // Asegurar que los pagos con tarjeta de débito tengan plan = 1 por defecto
        if (isset($data['payment_method']) && $data['payment_method'] === 'debit_card') {
            if (empty($data['card_plan'])) {
                $data['card_plan'] = '1';
            }

            if (!isset($data['card_surcharge_percentage'])) {
                $data['card_surcharge_percentage'] = 0;
            }
        }

        return DB::transaction(function () use ($data, $invoice) {

            // Evita cobros duplicados por doble clic o solicitudes simultáneas.
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $alreadyApplied = (float) $invoice->payments()
                ->get()
                ->sum(fn (InvoicePayment $payment) => $payment->payment_method === 'current_account'
                    ? (float) $payment->amount
                    : (float) $payment->total_paid);

            if (max(0, (float) $invoice->total_amount - $alreadyApplied) <= 0.005) {
                return response()->json([
                    'message' => 'La factura ya está completamente pagada.',
                ], 422);
            }

            $amount = (float) $data['amount'];

            $voucher = null;
            if ($data['payment_method'] === 'voucher') {
                if (empty($data['voucher_code'])) {
                    throw ValidationException::withMessages(['voucher_code' => 'Ingresá o escaneá el código del voucher.']);
                }

                $voucher = app(VoucherService::class)->reserve($data['voucher_code'], $invoice);
                $amount = $voucher->applicableAmount((float) $invoice->total_amount);
                $pendingBalance = max(0, (float) $invoice->total_amount - $alreadyApplied);
                if ($amount - $pendingBalance > 0.005) {
                    throw ValidationException::withMessages([
                        'voucher_code' => 'El importe del voucher supera el saldo pendiente de la venta.',
                    ]);
                }
            }

            $discount = (float) ($data['discount_amount'] ?? 0);

            $surcharge = (float) ($data['surcharge_amount'] ?? 0);

            $totalPaid = $amount - $discount + $surcharge;

            if ($data['payment_method'] === 'current_account') {
                $dueDate = $data['payment_due_date'] ?? now()->addDays(30)->toDateString();

                $realCollected = (float) $invoice->payments()
                    ->where('payment_method', '!=', 'current_account')
                    ->sum('total_paid');

                $currentRealBalance = max(
                    0,
                    (float) $invoice->total_amount - $realCollected
                );

                if ($amount > $currentRealBalance) {
                    return response()->json([
                        'message' => 'El importe enviado a cuenta corriente supera el saldo pendiente.',
                    ], 422);
                }

                /*
                * Se registra para que aparezca en el resumen de formas de pago,
                * pero total_paid queda en cero porque no ingresó dinero.
                */
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_method' => 'current_account',
                    'amount' => $amount,
                    'discount_amount' => 0,
                    'surcharge_amount' => 0,
                    'total_paid' => 0,
                    'card_name' => null,
                    'card_plan' => null,
                    'card_surcharge_percentage' => 0,
                    'bank_name' => null,
                    'reference' => null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $invoice->update([
                    'payment_condition_name' => 'Cuenta Corriente',
                    'payment_due_date' => $dueDate,

                    /*
                    * La deuda real es lo que todavía no ingresó mediante
                    * efectivo, tarjeta, transferencia, etc.
                    */
                    'balance' => $currentRealBalance,
                    'current_account_amount' => $amount,

                    'status_name' => 'Pend. Cobro',
                    'status_id' => 6,
                    'is_credit' => true,
                ]);

                return $invoice->fresh()->load(
                    'payments',
                    'items',
                    'client'
                );
            }

            if (!auth()->check()) {
                return response()->json([
                    'message' => 'Debés iniciar sesión para registrar el cobro.',
                ], 401);
            }

            $openCashSheet = CashSheet::query()
                ->where('user_id', auth()->id())
                ->where('status_name', 'Abierta')
                ->whereNull('closing_date')
                ->latest('opening_date')
                ->first();

            if (!$openCashSheet) {
                return response()->json([
                    'message' => 'No tenés una caja abierta. Abrí una planilla de caja antes de registrar la venta.',
                ], 422);
            }
            /*
        |--------------------------------------------------------------------------
        | Pago de factura
        |--------------------------------------------------------------------------
        */

            $payment = InvoicePayment::create([

                'invoice_id' => $invoice->id,
                'voucher_id' => $voucher?->id,

                'payment_method' => $data['payment_method'],

                'amount' => $amount,

                'discount_amount' => $discount,

                'surcharge_amount' => $surcharge,

                'total_paid' => $totalPaid,

                'card_name' => $data['card_name'] ?? null,

                'card_plan' => $data['card_plan'] ?? null,

                'card_surcharge_percentage' => $data['card_surcharge_percentage'] ?? 0,

                'bank_name' => $data['bank_name'] ?? null,

                'reference' => $data['reference'] ?? null,

                'notes' => $data['notes'] ?? null,

            ]);

            $cashMovement = CashSheetMovement::create([
                'cash_sheet_id' => $openCashSheet->id,
                'cash_box_id' => $openCashSheet->cash_box_id,

                'invoice_id' => $invoice->id,
                'invoice_payment_id' => $payment->id,

                'user_id' => auth()->id(),

                'movement_type' => 'income',

                'payment_method' =>
                $data['payment_method'],

                'affects_cash_balance' =>
                $data['payment_method'] === 'cash',

                'amount' => $totalPaid,

                'document_number' =>
                $invoice->full_number
                    ?? 'Venta #' . $invoice->id,

                'customer_name' =>
                $invoice->customer_name,

                'reference' =>
                $data['reference']
                    ?? $data['coupon_number']
                    ?? null,

                'description' =>
                'Cobro de venta '
                    . (
                        $invoice->full_number
                        ?? '#' . $invoice->id
                    ),

                'status' => 'active',
            ]);

            /*
        |--------------------------------------------------------------------------
        | Cupón tarjeta
        |--------------------------------------------------------------------------
        */

            if (in_array($data['payment_method'], ['credit_card', 'debit_card'])) {

                CardCoupon::create([

                    'invoice_id' => $invoice->id,

                    'credit_card' => $data['card_name'] ?? null,

                    'last_digits_card' => $data['last_digits_card'] ?? null,

                    'credit_card_plan' => $data['card_plan'] ?? null,

                    'lot_number' => $data['lot_number'] ?? null,

                    'coupon_number' => $data['coupon_number'] ?? ('AUTO-' . $payment->id),

                    'instalments' => is_numeric($data['card_plan'] ?? null)
                        ? (int)$data['card_plan']
                        : 1,

                    'coupon_status' => 'Pendiente',

                    'coupon_amount' => $totalPaid,

                    'commission' => 0,

                    'charge_amount' => $surcharge,

                    'creation_date' => now(),

                    'expected_date' => now(),

                    'credit_card_type' => $data['payment_method'] === 'credit_card'
                        ? 'Crédito'
                        : 'Débito',

                    'receipt_number' => $invoice->full_number,

                    'customer_name' => $invoice->customer_name,

                    'trade_number' => $data['trade_number'] ?? null,

                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Actualizar saldo factura
        |--------------------------------------------------------------------------
        */

            $totalCollected = $invoice
                ->payments()
                ->sum('total_paid');

            $balance = max(
                0,
                $invoice->total_amount - $totalCollected
            );

            $hasPendingCreditCardSettlement = $invoice->cardCoupons()
                ->whereIn('credit_card_type', ['Crédito', 'Crédito', 'Credito'])
                ->where('coupon_status', '!=', 'Conciliado')
                ->exists();

            $isCollected = $balance <= 0 && ! $hasPendingCreditCardSettlement;

            $invoice->update([

                'balance' => $balance,

                'status_name' => $isCollected
                    ? 'Cobrada'
                    : 'Pend. Cobro',

                'status_id' => $isCollected
                    ? 2
                    : 6,

            ]);

            /*
        |--------------------------------------------------------------------------
        | Recibo Cuenta Corriente
        |--------------------------------------------------------------------------
        */

            if ($invoice->client_id) {

                $receipt = CustomerReceipt::create([

                    'client_id' => $invoice->client_id,

                    'number' => 'RC-' .
                        now()->format('Y') .
                        '-' .
                        str_pad(
                            CustomerReceipt::max('id') + 1,
                            8,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'receipt_date' => today(),

                    'total_amount' => $amount,

                    'discount_amount' => $discount,

                    'surcharge_amount' => $surcharge,

                    'net_amount' => $totalPaid,

                    'notes' => $data['notes'] ?? null,

                ]);

                $receiptPayment = CustomerReceiptPayment::create([

                    'customer_receipt_id' => $receipt->id,

                    'payment_method' => $data['payment_method'],

                    'amount' => $totalPaid,

                    'card_name' => $data['card_name'] ?? null,

                    'card_plan' => $data['card_plan'] ?? null,

                    'bank_name' => $data['bank_name'] ?? null,

                    'reference' => $data['reference'] ?? null,

                    'notes' => $data['notes'] ?? null,

                ]);

                CustomerReceiptApplication::create([

                    'customer_receipt_id' => $receipt->id,

                    'invoice_id' => $invoice->id,

                    'amount' => $totalPaid,

                ]);

                $cashMovement->update([
                    'customer_receipt_id' => $receipt->id,
                    'customer_receipt_payment_id' => $receiptPayment->id,
                ]);

                $invoice->client()->update([

                    'last_payment_date' => now(),

                ]);
            }

            return $invoice
                ->fresh()
                ->load(
                    'payments',
                    'items',
                    'client'
                );
        });
    }

    private function consumeInvoiceVouchers(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $payments = InvoicePayment::query()
                ->where('invoice_id', $invoice->id)
                ->where('payment_method', 'voucher')
                ->whereNotNull('voucher_id')
                ->get();

            foreach ($payments as $payment) {
                $voucher = Voucher::query()->findOrFail($payment->voucher_id);
                app(VoucherService::class)->consume($voucher, $invoice);
            }
        });
    }

    public function customerCurrentAccount(Client $client)
    {
        /*
     * Solamente documentos enviados realmente a Cuenta Corriente.
     * Se excluyen remitos, presupuestos y notas de pedido.
     * Tambien se toman ventas antiguas que ya tienen el pago current_account
     * aunque hayan quedado sin prefijo por mass assignment.
     */
        $currentAccountInvoices = Invoice::query()
            ->with('payments')
            ->where('client_id', $client->id)
            ->where(function ($query) {
                $query->where('payment_condition_name', 'Cuenta Corriente')
                    ->orWhereHas('payments', function ($paymentQuery) {
                        $paymentQuery->where('payment_method', 'current_account');
                    });
            })
            ->where(function ($query) {
                $query->whereIn('receipt_types_prefix', ['FV', 'ND'])
                    ->orWhereNull('receipt_types_prefix');
            })
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        /*
     * Comprobantes que todavía tienen deuda.
     */
        $pendingInvoices = $currentAccountInvoices
            ->filter(fn(Invoice $invoice) => (float) $invoice->balance > 0)
            ->values();

        $balance = (float) $pendingInvoices->sum('balance');

        $today = now()->startOfDay();

        $expired = (float) $pendingInvoices
            ->filter(function (Invoice $invoice) use ($today) {
                if (!$invoice->payment_due_date) {
                    return false;
                }

                return \Illuminate\Support\Carbon::parse(
                    $invoice->payment_due_date
                )->startOfDay()->lt($today);
            })
            ->sum('balance');

        $future = max(0, $balance - $expired);

        /*
     * Solo recibos aplicados a documentos de Cuenta Corriente.
     * No se mezclan cobros de ventas normales de contado.
     */
        $receipts = CustomerReceipt::query()
            ->with([
                'payments',
                'applications.invoice',
            ])
            ->where('client_id', $client->id)
            ->whereHas('applications.invoice', function ($query) {
                $query->where(
                    'payment_condition_name',
                    'Cuenta Corriente'
                );
            })
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->get();

        $movements = collect();

        foreach ($currentAccountInvoices as $invoice) {
            $movements->push([
                'date' => optional($invoice->issue_date)->format('Y-m-d'),
                'type' => $invoice->receipt_type_name ?? 'Factura',
                'document' => $invoice->full_number
                    ?: 'Venta #' . $invoice->id,
                'debit' => (float) (
                    $invoice->current_account_amount > 0
                    ? $invoice->current_account_amount
                    : $invoice->total_amount
                ),
                'credit' => 0,
                'invoice_id' => $invoice->id,
            ]);
        }

        foreach ($receipts as $receipt) {
            $movements->push([
                'date' => optional($receipt->receipt_date)->format('Y-m-d'),
                'type' => 'Recibo de cobro',
                'document' => $receipt->number,
                'debit' => 0,
                'credit' => (float) $receipt->net_amount,
                'receipt_id' => $receipt->id,
            ]);
        }

        $movements = $movements
            ->sortBy([
                ['date', 'asc'],
                ['type', 'asc'],
            ])
            ->values();

        /*
     * Saldo acumulado del movimiento.
     */
        $runningBalance = 0;

        $movements = $movements->map(function (array $movement) use (&$runningBalance) {
            $runningBalance +=
                (float) $movement['debit']
                - (float) $movement['credit'];

            $movement['balance'] = max(0, $runningBalance);

            return $movement;
        });

        $creditLimit = (float) ($client->credit_limit ?? 0);

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'document_number' => $client->document_number,
            ],

            'summary' => [
                'balance' => round($balance, 2),
                'expired_debt' => round($expired, 2),
                'future_debt' => round($future, 2),
                'credit_limit' => round($creditLimit, 2),
                'available_credit' => round(
                    max(0, $creditLimit - $balance),
                    2
                ),
                'last_payment_date' => $client->last_payment_date
                    ? $client->last_payment_date->format('Y-m-d')
                    : null,
            ],

            'pending_invoices' => $pendingInvoices
                ->map(function (Invoice $invoice) {
                    return [
                        'id' => $invoice->id,
                        'full_number' => $invoice->full_number
                            ?: 'Venta #' . $invoice->id,
                        'receipt_type_name' => $invoice->receipt_type_name,
                        'issue_date' => optional(
                            $invoice->issue_date
                        )->format('Y-m-d'),
                        'payment_due_date' => $invoice->payment_due_date
                            ? \Illuminate\Support\Carbon::parse(
                                $invoice->payment_due_date
                            )->format('Y-m-d')
                            : null,
                        'total_amount' => (float) $invoice->total_amount,
                        'current_account_amount' => (float) (
                            $invoice->current_account_amount ?? 0
                        ),
                        'balance' => (float) $invoice->balance,
                        'status_name' => $invoice->status_name,
                    ];
                })
                ->values(),

            'receipts' => $receipts,
            'movements' => $movements,
        ]);
    }
}
