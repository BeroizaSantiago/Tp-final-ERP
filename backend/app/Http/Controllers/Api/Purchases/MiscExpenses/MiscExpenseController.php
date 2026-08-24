<?php

namespace App\Http\Controllers\Api\Purchases\MiscExpenses;

use App\Http\Controllers\Controller;
use App\Models\Purchases\ExpenseType;
use App\Models\Purchases\MiscExpense;
use App\Models\Purchases\Provider;
use Illuminate\Http\Request;
use App\Models\Purchases\MiscExpensePayment;

/**
 * Gestiona gastos varios cargados por comprobante.
 *
 * Permite asociarlos opcionalmente a proveedor y tipo de gasto, calcula el
 * total a partir de importes netos, descuentos, recargos, impuestos y
 * percepciones, y devuelve el registro con sus relaciones.
 */
class MiscExpenseController extends Controller
{
    public function index()
    {
        return MiscExpense::with('provider', 'expenseType')
            ->latest('issue_date')
            ->paginate(20);
    }

    public function show(MiscExpense $miscExpense)
    {
       return $miscExpense->load('provider', 'expenseType', 'payments');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['nullable', 'exists:providers,id'],
            'expense_type_id' => ['nullable', 'exists:expense_types,id'],

            'issue_date' => ['required', 'date'],
            'receipt_type_name' => ['nullable', 'string'],
            'receipt_number' => ['nullable', 'string'],

            'branch_name' => ['nullable', 'string'],

            'net_amount' => ['nullable', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'surcharge_amount' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'exempt_amount' => ['nullable', 'numeric'],
            'non_taxed_amount' => ['nullable', 'numeric'],
            'perception_amount' => ['nullable', 'numeric'],

            'notes' => ['nullable', 'string'],
        ]);

        $provider = !empty($data['provider_id'])
            ? Provider::find($data['provider_id'])
            : null;

        $net = $data['net_amount'] ?? 0;
        $discount = $data['discount_amount'] ?? 0;
        $surcharge = $data['surcharge_amount'] ?? 0;
        $tax = $data['tax_amount'] ?? 0;
        $exempt = $data['exempt_amount'] ?? 0;
        $nonTaxed = $data['non_taxed_amount'] ?? 0;
        $perception = $data['perception_amount'] ?? 0;

        $total = $net - $discount + $surcharge + $tax + $exempt + $nonTaxed + $perception;

        $expense = MiscExpense::create([
            'provider_id' => $provider?->id,
            'expense_type_id' => $data['expense_type_id'] ?? null,

            'issue_date' => $data['issue_date'],
            'receipt_type_name' => $data['receipt_type_name'] ?? null,
            'receipt_number' => $data['receipt_number'] ?? null,

            'provider_name' => $provider?->name,
            'branch_name' => $data['branch_name'] ?? 'SUCURSAL',

            'net_amount' => $net,
            'discount_amount' => $discount,
            'surcharge_amount' => $surcharge,
            'tax_amount' => $tax,
            'exempt_amount' => $exempt,
            'non_taxed_amount' => $nonTaxed,
            'perception_amount' => $perception,
            'total_amount' => $total,

            'status_name' => 'Registrado',
            'status_id' => 1,
            'created_by' => 'system',

            'notes' => $data['notes'] ?? null,
        ]);

        return $expense->load('provider', 'expenseType');
    }

    public function storePayment(Request $request, MiscExpense $miscExpense)
{
    $data = $request->validate([
        'payment_method' => ['required', 'string'],
        'amount' => ['required', 'numeric', 'min:0.01'],
        'discount_amount' => ['nullable', 'numeric', 'min:0'],
        'surcharge_amount' => ['nullable', 'numeric', 'min:0'],
        'bank_name' => ['nullable', 'string'],
        'reference' => ['nullable', 'string'],
        'notes' => ['nullable', 'string'],
    ]);

    $amount = (float) $data['amount'];
    $discount = (float) ($data['discount_amount'] ?? 0);
    $surcharge = (float) ($data['surcharge_amount'] ?? 0);

    $totalPaid = $amount - $discount + $surcharge;

    $miscExpense->payments()->create([
        'payment_method' => $data['payment_method'],
        'amount' => $amount,
        'discount_amount' => $discount,
        'surcharge_amount' => $surcharge,
        'total_paid' => $totalPaid,
        'bank_name' => $data['bank_name'] ?? null,
        'reference' => $data['reference'] ?? null,
        'notes' => $data['notes'] ?? null,
    ]);

    return $miscExpense->load('provider', 'expenseType', 'payments');
}
}
