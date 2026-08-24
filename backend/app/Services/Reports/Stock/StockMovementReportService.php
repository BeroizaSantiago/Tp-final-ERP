<?php

namespace App\Services\Reports\Stock;

use App\Models\Products\Product;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Stock\InternalTransfer;
use App\Models\Stock\StockAdjustment;
use App\Models\Stock\StockAdjustmentReason;
use App\Models\Stock\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Arma el historial de movimientos desde la bitácora real de existencias. */
class StockMovementReportService
{
    public function generate(array $f): array
    {
        $movements = $this->query($f)->get();
        $adjustments = StockAdjustment::with('reason')->whereIn('id', $movements->where('reference_type', 'stock_adjustment')->pluck('reference_id')->filter())->get()->keyBy('id');
        $receipts = PurchaseReceipt::whereIn('id', $movements->where('reference_type', 'purchase_receipt')->pluck('reference_id')->filter())->get()->keyBy('id');
        $transfers = InternalTransfer::whereIn('id', $movements->where('reference_type', 'internal_transfer')->pluck('reference_id')->filter())->get()->keyBy('id');
        $rows = $movements->map(function ($m) use ($adjustments, $receipts, $transfers) {
            $i = $m->inventoryItem;
            $ref = match ($m->reference_type) {
                'stock_adjustment' => $adjustments->get($m->reference_id),
                'purchase_receipt' => $receipts->get($m->reference_id),
                'internal_transfer' => $transfers->get($m->reference_id),
                default => null
            };
            return ['date' => $m->created_at?->format('d/m/Y H:i'), 'operation' => $this->label($m->movement_type), 'reason' => $m->reference_type === 'stock_adjustment' ? ($ref?->reason?->name ?: $ref?->adjustment_stock_reason ?: $m->notes ?: '-') : '-', 'document' => $this->document($m, $ref), 'product_code' => $i?->code ?: '-', 'bar_code' => $i?->bar_code ?: '-', 'product' => $i?->product_name ?: 'Producto eliminado', 'quantity' => (float)$m->quantity, 'stock' => (float)$m->stock_after, 'user' => $ref?->created_by ?: 'Sistema'];
        });
        return ['filters' => $f, 'rows' => $rows->all(), 'summary' => ['income' => round((float)$rows->where('quantity', '>', 0)->sum('quantity'), 4), 'expense' => round(abs((float)$rows->where('quantity', '<', 0)->sum('quantity')), 4), 'net' => round((float)$rows->sum('quantity'), 4)]];
    }
    public function options(): array
    {
        $types = StockMovement::query()->distinct()->orderBy('movement_type')->pluck('movement_type');
        return ['products' => collect(), 'branches' => \App\Models\Stock\InventoryItem::query()->whereNotNull('branch_name')->distinct()->orderBy('branch_name')->pluck('branch_name'), 'warehouses' => \App\Models\Stock\InventoryItem::query()->whereNotNull('warehouse_name')->distinct()->orderBy('warehouse_name')->pluck('warehouse_name'), 'modules' => ['sales' => 'Ventas', 'stock' => 'Stock', 'purchases' => 'Compras'], 'operation_types' => $types->map(fn($v) => ['value' => $v, 'label' => $this->label($v), 'module' => $this->module($v)])->values(), 'reasons' => StockAdjustmentReason::query()->orderBy('name')->get(['id', 'name'])];
    }
    public function exportRows(array $r): Collection
    {
        return collect($r['rows'])->map(fn($x) => array_values($x));
    }
    private function query(array $f): Builder
    {
        $q = StockMovement::query()->with('inventoryItem')->whereDate('created_at', '>=', $f['date_from'])->whereDate('created_at', '<=', $f['date_to'])->when($f['name'] ?? null, fn($q, $v) => $q->whereHas('inventoryItem', fn($i) => $i->where('product_name', 'like', '%' . $v . '%')))->when($f['branches'] ?? null, fn($q, $v) => $q->whereHas('inventoryItem', fn($i) => $i->whereIn('branch_name', $v)))->when($f['warehouses'] ?? null, fn($q, $v) => $q->whereHas('inventoryItem', fn($i) => $i->whereIn('warehouse_name', $v)))->when($f['operation_types'] ?? null, fn($q, $v) => $q->whereIn('movement_type', $v))->when($f['modules'] ?? null, fn($q, $v) => $q->whereIn('movement_type', $this->typesForModules($v)))->when($f['reason_ids'] ?? null, fn($q, $v) => $q->where('reference_type', 'stock_adjustment')->whereIn('reference_id', StockAdjustment::whereIn('reason_id', $v)->pluck('id')));
        if ($ids = $f['product_ids'] ?? null) {
            $products = Product::whereIn('id', $ids)->get();
            $q->whereHas('inventoryItem', fn($i) => $i->whereIn('product_id', $ids)->orWhereIn('product_external_id', $products->pluck('external_id')->filter())->orWhereIn('code', $products->pluck('code')->filter())->orWhereIn('product_name', $products->pluck('name')));
        }
        return $q->orderBy('created_at')->orderBy('id');
    }
    private function typesForModules(array $v): array
    {
        return StockMovement::query()->distinct()->pluck('movement_type')->filter(fn($t) => in_array($this->module($t), $v, true))->all();
    }
    private function module(string $t): string
    {
        return match (true) {
            str_contains($t, 'sale'), str_contains($t, 'credit_note'), str_contains($t, 'remito') => 'sales',
            str_contains($t, 'purchase') => 'purchases',
            default => 'stock'
        };
    }
    private function label(string $t): string
    {
        return match ($t) {
            'sale' => 'Venta',
            'credit_note' => 'Nota de crédito de venta',
            'purchase' => 'Compra',
            'purchase_credit_note' => 'Nota de crédito de compra',
            'internal_transfer_in' => 'Transferencia - ingreso',
            'internal_transfer_out' => 'Transferencia - egreso',
            'adjustment' => 'Ajuste',
            default => ucfirst(str_replace('_', ' ', $t))
        };
    }
    private function document($m, $r): string
    {
        return match ($m->reference_type) {
            'stock_adjustment' => ($r?->number ? 'Ajuste ' . $r->number : 'Ajuste #' . ($m->reference_id ?: $m->id)),
            'purchase_receipt' => ($r?->receipt_number ?: 'Compra #' . $m->reference_id),
            'internal_transfer' => 'Transferencia #' . $m->reference_id,
            default => ($m->reference_type ? ucfirst(str_replace('_', ' ', $m->reference_type)) . ' #' . $m->reference_id : 'Movimiento #' . $m->id)
        };
    }
}
