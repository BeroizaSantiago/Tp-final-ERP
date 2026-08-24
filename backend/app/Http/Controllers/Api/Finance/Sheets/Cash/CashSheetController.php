<?php

namespace App\Http\Controllers\Api\Finance\Sheets\Cash;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashBox;
use App\Models\Finance\CashSheet;
use App\Models\Finance\CashSheetMovement;
use Illuminate\Http\Request;
use App\Models\Finance\CashCurrency;
use Illuminate\Support\Facades\DB;
use App\Services\Finance\TreasuryService;
use Illuminate\Validation\Rule;

/**
 * Gestiona planillas de caja.
 *
 * Registra aperturas y cierres con caja, cajero, punto de venta, sucursal y
 * deposito, y ofrece una consulta especifica para planillas de tesoreria.
 */
class CashSheetController extends Controller
{
    public function __construct(private readonly TreasuryService $treasuryService) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = min(100, max(10, (int) $request->query('per_page', 20)));

        return CashSheet::with([
            'cashBox',
            'user',
        ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('cash_box_name', 'like', "%{$search}%")
                        ->orWhere('cashier_name', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhere('status_name', 'like', "%{$search}%")
                        ->orWhere('branch_name', 'like', "%{$search}%")
                        ->orWhereHas('cashBox', fn ($cashBox) => $cashBox->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('opening_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function show(CashSheet $cashSheet)
    {
        return $cashSheet->load([
            'cashBox',
            'user',
            'closedByUser',
            'reopenedByUser',
            'movements.invoice',
            'movements.invoicePayment',
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Debés iniciar sesión para abrir una caja.',
            ], 401);
        }

        $data = $request->validate([
            'cash_box_id' => [
                'required',
                'exists:cash_boxes,id',
            ],

            'number' => [
                'nullable',
                'integer',
            ],

            'pos_name' => [
                'required',
                'string',
                Rule::in([
                    str_pad((string) config('arca.pto_vta', 4), 4, '0', STR_PAD_LEFT),
                    '0099',
                ]),
            ],

            'opening_date' => [
                'nullable',
                'date',
            ],

            'opening_cash_amount' => ['nullable', 'numeric', 'min:0'],

            'opening_observation' => [
                'nullable',
                'string',
            ],

            'branch_name' => [
                'nullable',
                'string',
            ],

            'warehouse_name' => [
                'nullable',
                'string',
            ],
        ]);

        $existingSheet = CashSheet::query()
            ->where('user_id', auth()->id())
            ->where('status_name', 'Abierta')
            ->whereNull('closing_date')
            ->first();

        if ($existingSheet) {
            return response()->json([
                'message' => 'Ya tenés una planilla de caja abierta.',
                'cash_sheet' => $existingSheet,
            ], 422);
        }

        $cashBox = CashBox::findOrFail(
            $data['cash_box_id']
        );

        $cashBox = $this->treasuryService->normalizeBranch(
            $cashBox,
            $data['branch_name'] ?? null
        );

        if ($cashBox->cashSheets()->where('status_name', 'Abierta')->whereNull('closing_date')->exists()) {
            return response()->json([
                'message' => 'Esta caja ya tiene una planilla abierta.',
            ], 422);
        }

        $this->treasuryService->ensureTreasuryAllowsOpening($cashBox);

        $nextNumber = (
            CashSheet::max('number') ?? 0
        ) + 1;

        $sheet = CashSheet::create([
            'cash_box_id' => $cashBox->id,
            'user_id' => auth()->id(),

            'cash_box_name' => $cashBox->name,

            'number' => $data['number']
                ?? $nextNumber,

            'pos_name' => $data['pos_name']
                ?? str_pad((string) config('arca.pto_vta', 4), 4, '0', STR_PAD_LEFT),

            'cashier_name' => auth()->user()->name
                ?? auth()->user()->email
                ?? 'Usuario',

            'opening_date' => $data['opening_date']
                ?? now(),

            'opening_cash_amount' => $data['opening_cash_amount'] ?? 0,

            'closing_date' => null,

            'status_name' => 'Abierta',

            'opening_observation' =>
            $data['opening_observation']
                ?? null,

            'closing_observation' => null,

            'branch_name' => $data['branch_name']
                ?? $cashBox->branch_name
                ?? 'SUCURSAL',

            'warehouse_name' =>
            $data['warehouse_name']
                ?? 'DEPÓSITO RIOS LORENA BEATRIZ',
        ]);

        $cashBox->update([
            'status_description' => 'Abierta',
        ]);

        return response()->json(
            $sheet->load([
                'cashBox',
                'user',
            ]),
            201
        );
    }


    public function treasurySheets(Request $request)
    {
        $treasuryBoxes = CashBox::query()->where('box_type_name', 'TESORERIA')->where('is_active', true)->orderBy('branch_name')->get();
        $selectedBox = $treasuryBoxes->firstWhere('id', (int) $request->query('cash_box_id')) ?? $treasuryBoxes->first();

        if (!$selectedBox) {
            return response()->json(['treasuries' => [], 'treasury' => null, 'summary' => ['current_balance'=>0,'total_income'=>0,'total_expense'=>0,'closures_received'=>0,'total_sales'=>0], 'closures' => []]);
        }

        $sheet = $selectedBox->cashSheets()->where('status_name', 'Abierta')->whereNull('closing_date')->latest('opening_date')->first()
            ?? $selectedBox->cashSheets()->latest('opening_date')->first();

        $movements = $sheet ? $sheet->activeMovements()->get() : collect();
        $reversedMovementIds = $movements->pluck('reversal_of_movement_id')->filter()->map(fn ($id) => (int) $id);
        $effectiveMovements = $movements->reject(fn ($movement) =>
            $movement->reversal_of_movement_id !== null
            || $reversedMovementIds->contains((int) $movement->id)
        );
        $income = (float) $effectiveMovements->where('movement_type', 'income')->sum('amount');
        $expense = (float) $effectiveMovements->where('movement_type', 'expense')->sum('amount');
        $closures = $sheet ? $sheet->activeMovements()->whereNotNull('origin_cash_sheet_id')
            ->with(['originCashBox', 'originCashSheet.closedByUser', 'user'])->latest()->get() : collect();
        $totalSales = $closures->isEmpty() ? 0 : CashSheetMovement::query()
            ->whereIn('cash_sheet_id', $closures->pluck('origin_cash_sheet_id')->filter()->unique())
            ->where('status', 'active')
            ->whereNotNull('invoice_id')
            ->distinct()
            ->count('invoice_id');

        return response()->json([
            'treasuries' => $treasuryBoxes,
            'treasury' => $sheet?->load('cashBox', 'user'),
            'summary' => [
                'current_balance' => round((float) ($sheet?->opening_cash_amount ?? 0) + $income - $expense, 2),
                'total_income' => round($income, 2),
                'total_expense' => round($expense, 2),
                'closures_received' => $closures->count(),
                'total_sales' => $totalSales,
            ],
            'closures' => $closures,
        ]);
    }
    public function close(Request $request, CashSheet $cashSheet)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Debés iniciar sesión para cerrar la caja.',
            ], 401);
        }

        if ($cashSheet->status_name !== 'Abierta' || $cashSheet->closing_date) {
            return response()->json([
                'message' => 'La planilla ya se encuentra cerrada.',
            ], 422);
        }

        if (
            $cashSheet->user_id &&
            (int) $cashSheet->user_id !== (int) auth()->id()
        ) {
            return response()->json([
                'message' => 'Esta planilla pertenece a otro cajero.',
            ], 403);
        }

        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'counted_credit_card' => ['nullable', 'numeric', 'min:0'],
            'counted_debit_card' => ['nullable', 'numeric', 'min:0'],
            'counted_transfer' => ['nullable', 'numeric', 'min:0'],
            'counted_checks' => ['nullable', 'numeric', 'min:0'],
            'leave_in_cash' => ['nullable', 'numeric', 'min:0'],
            'closing_observation' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($cashSheet, $data) {
            $cashSheet->loadMissing('cashBox');
            $this->treasuryService->ensureTreasuryCanClose($cashSheet);
            $summary = $this->calculateClosingSummary($cashSheet);

            $countedCash = (float) $data['counted_cash'];
            $countedCredit = (float) ($data['counted_credit_card'] ?? 0);
            $countedDebit = (float) ($data['counted_debit_card'] ?? 0);
            $countedTransfer = (float) ($data['counted_transfer'] ?? 0);
            $countedChecks = (float) ($data['counted_checks'] ?? 0);
            $leaveInCash = (float) ($data['leave_in_cash'] ?? 0);

            if ($leaveInCash > $countedCash) {
                return response()->json([
                    'message' => 'El fondo que dejás en caja no puede superar el efectivo contado.',
                ], 422);
            }

            $countedTotal =
                $countedCash +
                $countedCredit +
                $countedDebit +
                $countedTransfer +
                $countedChecks;

            $difference = round(
                $countedTotal - (float) $summary['theoretical_total'],
                2
            );

            if (
                abs($difference) >= 0.01 &&
                empty(trim((string) ($data['closing_observation'] ?? '')))
            ) {
                return response()->json([
                    'message' => 'Existe una diferencia de caja. Debés ingresar una observación para poder cerrar.',
                    'difference' => $difference,
                ], 422);
            }

            $cashSheet->update([
                'theoretical_cash' => $summary['theoretical_cash'],
                'counted_cash' => $countedCash,

                'theoretical_credit_card' => $summary['theoretical_credit_card'],
                'counted_credit_card' => $countedCredit,

                'theoretical_debit_card' => $summary['theoretical_debit_card'],
                'counted_debit_card' => $countedDebit,

                'theoretical_transfer' => $summary['theoretical_transfer'],
                'counted_transfer' => $countedTransfer,

                'theoretical_checks' => $summary['theoretical_checks'],
                'counted_checks' => $countedChecks,

                'theoretical_total' => $summary['theoretical_total'],
                'counted_total' => $countedTotal,

                'closing_difference' => $difference,
                'leave_in_cash' => $leaveInCash,

                'closing_observation' => $data['closing_observation'] ?? null,
                'closing_date' => now(),
                'closed_by' => auth()->id(),
                'status_name' => 'Cerrada',

                'reopened_at' => null,
                'reopened_by' => null,
                'reopening_reason' => null,
            ]);

            $cashSheet->cashBox?->update([
                'status_description' => 'Cerrada',
                'last_closing_cash_form_id' => $cashSheet->id,
            ]);

            if ($cashSheet->cashBox && !$this->treasuryService->isTreasury($cashSheet->cashBox)) {
                $this->treasuryService->receiveCashClosure($cashSheet, $countedTotal, $leaveInCash);
            }

            CashCurrency::query()
                ->where('cash_box_id', $cashSheet->cash_box_id)
                ->where(function ($query) {
                    $query->where('currency_name', 'Pesos')
                        ->orWhere('currency_name', 'ARS');
                })
                ->update([
                    'last_closing_balance' => $leaveInCash,
                    'last_is_manual' => true,
                ]);

            $sales = $cashSheet->activeMovements()
                ->with('invoice')
                ->whereNotNull('invoice_id')
                ->get()
                ->pluck('invoice')
                ->filter()
                ->unique('id')
                ->values()
                ->map(function ($invoice) use ($cashSheet) {
                    return [
                        'id' => $invoice->id,
                        'issue_date' => optional($invoice->issue_date)?->toISOString(),
                        'receipt_type_name' => $invoice->receipt_type_name,
                        'second_number' => $invoice->second_number,
                        'full_number' => $invoice->full_number
                            ?: 'Venta #' . $invoice->id,
                        'client_id' => $invoice->client_id,
                        'customer_name' => $invoice->customer_name,
                        'currency_name' => $invoice->currency_name,
                        'total_amount' => (float) $invoice->total_amount,
                        'status_name' => $invoice->status_name,
                        'status_id' => $invoice->status_id,
                        'service_channel_id' => $invoice->service_channel_id,
                        'service_channel' => $invoice->service_channel,
                        'point_of_sale_name' => $cashSheet->pos_name,
                    ];
                });

            return response()->json([
                'message' => 'La caja fue cerrada correctamente.',

                'cash_sheet' => $cashSheet->fresh()->load([
                    'cashBox',
                    'user',
                    'closedByUser',
                ]),

                'closing_summary' => [
                    'theoretical_cash' => $summary['theoretical_cash'],
                    'theoretical_credit_card' => $summary['theoretical_credit_card'],
                    'theoretical_debit_card' => $summary['theoretical_debit_card'],
                    'theoretical_transfer' => $summary['theoretical_transfer'],
                    'theoretical_checks' => $summary['theoretical_checks'],
                    'theoretical_total' => $summary['theoretical_total'],
                    'counted_total' => $countedTotal,
                    'difference' => $difference,
                    'leave_in_cash' => $leaveInCash,
                ],

                'sales' => $sales,
            ]);
        });
    }

    public function reopen(Request $request, CashSheet $cashSheet)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Debés iniciar sesión para reabrir la caja.',
            ], 401);
        }

        if ($cashSheet->status_name !== 'Cerrada') {
            return response()->json([
                'message' => 'La planilla no se encuentra cerrada.',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $otherOpenSheet = CashSheet::query()
            ->where('user_id', $cashSheet->user_id)
            ->where('status_name', 'Abierta')
            ->whereNull('closing_date')
            ->where('id', '!=', $cashSheet->id)
            ->exists();

        if ($otherOpenSheet) {
            return response()->json([
                'message' => 'El cajero ya tiene otra planilla abierta.',
            ], 422);
        }

        return DB::transaction(function () use ($cashSheet, $data) {
            $cashSheet->update([
                'status_name' => 'Abierta',
                'closing_date' => null,
                'reopened_at' => now(),
                'reopened_by' => auth()->id(),
                'reopening_reason' => $data['reason'],
                'closed_by' => null,
            ]);

            $cashSheet->cashBox?->update([
                'status_description' => 'Abierta',
            ]);

            return response()->json([
                'message' => 'La caja fue reabierta correctamente.',
                'cash_sheet' => $cashSheet->fresh()->load([
                    'cashBox',
                    'user',
                    'reopenedByUser',
                    'movements.invoice',
                    'movements.invoicePayment',
                ]),
            ]);
        });
    }

    private function calculateClosingSummary(CashSheet $cashSheet): array
    {
        $movements = $cashSheet->activeMovements()->get();

        $netByMethod = function (array $methods) use ($movements): float {
            return round(
                $movements
                    ->filter(
                        fn($movement) =>
                        in_array($movement->payment_method, $methods, true)
                    )
                    ->sum(function ($movement) {
                        $amount = (float) $movement->amount;

                        return $movement->movement_type === 'expense'
                            ? -$amount
                            : $amount;
                    }),
                2
            );
        };

        $cashMovements = $netByMethod(['cash']);

        $theoreticalCash = round(
            (float) $cashSheet->opening_cash_amount + $cashMovements,
            2
        );

        $credit = $netByMethod(['credit_card']);
        $debit = $netByMethod(['debit_card']);
        $transfer = $netByMethod(['transfer']);

        $checks = $netByMethod([
            'check',
            'cheque',
            'third_party_check',
            'echeq',
        ]);

        return [
            'opening_cash_amount' => round(
                (float) $cashSheet->opening_cash_amount,
                2
            ),

            'theoretical_cash' => $theoreticalCash,
            'theoretical_credit_card' => $credit,
            'theoretical_debit_card' => $debit,
            'theoretical_transfer' => $transfer,
            'theoretical_checks' => $checks,

            'theoretical_total' => round(
                $theoreticalCash +
                    $credit +
                    $debit +
                    $transfer +
                    $checks,
                2
            ),
        ];
    }
}
