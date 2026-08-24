<?php

namespace App\Http\Controllers\Api\Stock\Adjustments;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockAdjustment;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $service)
    {
    }

    public function index()
    {
        return StockAdjustment::with('reason')->latest()->paginate(20);
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $stockAdjustment->load([
            'reason',
            'movements.inventoryItem.product',
            'movements.inventoryItem.variant.size',
            'movements.inventoryItem.variant.color',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_name' => ['required', 'string', 'max:255'],
            'warehouse_name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'reason_id' => ['nullable', 'exists:stock_adjustment_reasons,id'],
            'adjust_by_variant' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
                'required_if:adjust_by_variant,true',
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $userName = $user ? ($user->name ?? $user->username ?? $user->email) : null;
        $adjustment = $this->service->create($data, $userName);

        return response()->json([
            'message' => 'Stock ajustado correctamente.',
            'adjustment' => $adjustment,
        ], 201);
    }

    public function options()
    {
        return response()->json($this->service->locations());
    }

    public function currentStock(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'branch_name' => ['required', 'string', 'max:255'],
            'warehouse_name' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'current_stock' => $this->service->currentStock(
                $data['product_id'],
                $data['product_variant_id'] ?? null,
                $data['branch_name'],
                $data['warehouse_name']
            ),
        ]);
    }
}
