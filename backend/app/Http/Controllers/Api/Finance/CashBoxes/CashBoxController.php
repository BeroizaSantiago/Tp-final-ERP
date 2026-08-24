<?php

namespace App\Http\Controllers\Api\Finance\CashBoxes;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashBox;
use App\Models\Finance\CashCurrency;
use App\Models\Finance\CashPointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona cajas y su configuracion operativa.
 *
 * Crea la caja junto con sus monedas habilitadas y puntos de venta dentro de
 * una transaccion, y permite consultarla con esas relaciones.
 */
class CashBoxController extends Controller
{
    public function index()
    {
        return CashBox::with('currencies', 'pointOfSales')
            ->orderBy('name')
            ->paginate(20);
    }

    public function show(CashBox $cashBox)
    {
        return $cashBox->load('currencies', 'pointOfSales');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'code' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'branch_id' => ['nullable'],
            'warehouse_id' => ['nullable'],
            'box_type_id' => ['nullable'],
            'status_id' => ['nullable'],
            'treasury_id' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
            'last_closing_cash_form_id' => ['nullable'],
            'status_description' => ['nullable', 'string'],

            'currencies' => ['nullable', 'array'],
            'currencies.*.external_id' => ['nullable'],
            'currencies.*.currency_id' => ['nullable'],
            'currencies.*.currency_name' => ['nullable', 'string'],
            'currencies.*.gl_account_id' => ['nullable'],
            'currencies.*.last_closing_balance' => ['nullable', 'numeric'],
            'currencies.*.last_is_manual' => ['nullable', 'boolean'],
            'currencies.*.is_active' => ['nullable', 'boolean'],
            'currencies.*.deleted' => ['nullable', 'boolean'],

            'point_of_sales' => ['nullable', 'array'],
            'point_of_sales.*.external_id' => ['nullable'],
            'point_of_sales.*.cash_point_of_sale_id' => ['nullable'],
            'point_of_sales.*.number' => ['nullable', 'string'],
            'point_of_sales.*.is_manual' => ['nullable', 'boolean'],
            'point_of_sales.*.pos_type_id' => ['nullable'],
            'box_type_name' => ['nullable', 'string'],
            'branch_name' => ['nullable', 'string'],
        ]);

        $this->ensureUniqueTreasury($data);

        return DB::transaction(function () use ($data) {
            $cashBox = CashBox::create([
                'external_id' => $data['external_id'] ?? null,
                'code' => $data['code'] ?? null,
                'name' => $data['name'],
                'branch_id' => $data['branch_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'box_type_id' => $data['box_type_id'] ?? null,
                'status_id' => $data['status_id'] ?? 10,
                'treasury_id' => $data['treasury_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'last_closing_cash_form_id' => $data['last_closing_cash_form_id'] ?? null,
                'status_description' => $data['status_description'] ?? 'Cerrada',
                'box_type_name' => $data['box_type_name'] ?? null,
                'branch_name' => $data['branch_name'] ?? null,
            ]);

            foreach ($data['currencies'] ?? [] as $currency) {
                CashCurrency::create([
                    'cash_box_id' => $cashBox->id,
                    'external_id' => $currency['external_id'] ?? null,
                    'currency_id' => $currency['currency_id'] ?? null,
                    'currency_name' => $currency['currency_name'] ?? 'Pesos',
                    'gl_account_id' => $currency['gl_account_id'] ?? null,
                    'last_closing_balance' => $currency['last_closing_balance'] ?? 0,
                    'last_is_manual' => $currency['last_is_manual'] ?? false,
                    'is_active' => $currency['is_active'] ?? true,
                    'deleted' => $currency['deleted'] ?? false,
                ]);
            }

            foreach ($data['point_of_sales'] ?? [] as $pos) {
                CashPointOfSale::create([
                    'cash_box_id' => $cashBox->id,
                    'external_id' => $pos['external_id'] ?? null,
                    'cash_point_of_sale_id' => $pos['cash_point_of_sale_id'] ?? null,
                    'number' => $pos['number'] ?? null,
                    'is_manual' => $pos['is_manual'] ?? false,
                    'pos_type_id' => $pos['pos_type_id'] ?? null,
                ]);
            }

            return $cashBox->load('currencies', 'pointOfSales');
        });
    }

    public function update(Request $request, CashBox $cashBox)
{
    $data = $request->validate([
        'code' => ['nullable', 'string'],
        'name' => ['required', 'string'],
        'branch_name' => ['nullable', 'string'],
        'box_type_name' => ['nullable', 'string'],
        'status_description' => ['nullable', 'string'],
        'is_active' => ['nullable', 'boolean'],

        'currencies' => ['nullable', 'array'],
        'currencies.*.currency_name' => ['nullable', 'string'],
        'currencies.*.last_closing_balance' => ['nullable', 'numeric'],
        'currencies.*.is_active' => ['nullable', 'boolean'],

        'point_of_sales' => ['nullable', 'array'],
        'point_of_sales.*.number' => ['nullable', 'string'],
        'point_of_sales.*.is_manual' => ['nullable', 'boolean'],
    ]);

    $this->ensureUniqueTreasury($data, $cashBox);

    return DB::transaction(function () use ($cashBox, $data) {
        $cashBox->update([
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'branch_name' => $data['branch_name'] ?? null,
            'box_type_name' => $data['box_type_name'] ?? 'CAJA',
            'status_description' => $data['status_description'] ?? 'Cerrada',
            'is_active' => $data['is_active'] ?? true,
        ]);

        $cashBox->currencies()->delete();
        $cashBox->pointOfSales()->delete();

        foreach ($data['currencies'] ?? [] as $currency) {
            CashCurrency::create([
                'cash_box_id' => $cashBox->id,
                'currency_name' => $currency['currency_name'] ?? 'Pesos',
                'last_closing_balance' => $currency['last_closing_balance'] ?? 0,
                'is_active' => $currency['is_active'] ?? true,
                'last_is_manual' => false,
                'deleted' => false,
            ]);
        }

        foreach ($data['point_of_sales'] ?? [] as $pos) {
            CashPointOfSale::create([
                'cash_box_id' => $cashBox->id,
                'number' => $pos['number'] ?? null,
                'is_manual' => $pos['is_manual'] ?? false,
            ]);
        }

        return $cashBox->load('currencies', 'pointOfSales');
    });
}

private function ensureUniqueTreasury(array $data, ?CashBox $except = null): void
{
    if (strtoupper((string) ($data['box_type_name'] ?? '')) !== 'TESORERIA') return;

    $query = CashBox::query()->where('box_type_name', 'TESORERIA');
    if ($except) $query->where('id', '!=', $except->id);

    if (!empty($data['branch_id'])) $query->where('branch_id', $data['branch_id']);
    else $query->where('branch_name', $data['branch_name'] ?? null);

    if ($query->exists()) {
        throw ValidationException::withMessages(['box_type_name' => 'Ya existe una Tesorería para esta sucursal.']);
    }
}
}
