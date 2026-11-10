<?php

namespace App\Services\Reports\Stock;

use App\Models\Products\Product;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Invoice;
use App\Models\Stock\InternalTransfer;
use App\Models\Stock\StockAdjustment;
use App\Models\Stock\StockAdjustmentReason;
use App\Models\Stock\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Arma el historial de movimientos desde la bitácora real de existencias. */
class StockMovementReportService
{
    public function generate(array $filters): array
    {
        $movements = $this->query($filters)->get();
        $references = $this->references($movements);

        $rows = $movements->map(function (StockMovement $movement) use ($references) {
            $inventory = $movement->inventoryItem;
            $reference = ($references[$movement->reference_type] ?? collect())->get($movement->reference_id);

            return [
                'date' => $movement->created_at?->format('d/m/Y H:i'),
                'operation' => $this->label($movement->movement_type),
                'reason' => $this->reason($movement, $reference),
                'document' => $this->document($movement, $reference),
                'product_code' => $inventory?->code ?: '-',
                'bar_code' => $inventory?->bar_code ?: '-',
                'product' => $inventory?->product_name ?: 'Producto eliminado',
                'quantity' => (float) $movement->quantity,
                'stock' => (float) $movement->stock_after,
                'user' => $this->user($movement, $reference),
            ];
        });

        return [
            'filters' => $filters,
            'rows' => $rows->all(),
            'summary' => [
                'income' => round((float) $rows->where('quantity', '>', 0)->sum('quantity'), 4),
                'expense' => round(abs((float) $rows->where('quantity', '<', 0)->sum('quantity')), 4),
                'net' => round((float) $rows->sum('quantity'), 4),
            ],
        ];
    }

    public function options(): array
    {
        $types = StockMovement::query()->distinct()->orderBy('movement_type')->pluck('movement_type');

        return [
            'products' => collect(),
            'branches' => \App\Models\Stock\InventoryItem::query()->whereNotNull('branch_name')->distinct()->orderBy('branch_name')->pluck('branch_name'),
            'warehouses' => \App\Models\Stock\InventoryItem::query()->whereNotNull('warehouse_name')->distinct()->orderBy('warehouse_name')->pluck('warehouse_name'),
            'modules' => ['sales' => 'Ventas', 'stock' => 'Stock', 'purchases' => 'Compras'],
            'operation_types' => $types->map(fn ($value) => ['value' => $value, 'label' => $this->label($value), 'module' => $this->module($value)])->values(),
            'reasons' => StockAdjustmentReason::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn ($row) => array_values($row));
    }

    private function references(Collection $movements): array
    {
        $ids = fn (string $type) => $movements->where('reference_type', $type)->pluck('reference_id')->filter();

        return [
            'stock_adjustment' => StockAdjustment::with('reason')->whereIn('id', $ids('stock_adjustment'))->get()->keyBy('id'),
            'purchase_receipt' => PurchaseReceipt::whereIn('id', $ids('purchase_receipt'))->get()->keyBy('id'),
            'purchase' => Purchase::whereIn('id', $ids('purchase'))->get()->keyBy('id'),
            'internal_transfer' => InternalTransfer::whereIn('id', $ids('internal_transfer'))->get()->keyBy('id'),
            'invoice' => Invoice::whereIn('id', $ids('invoice'))->get()->keyBy('id'),
            'book_return_purchase' => DB::table('book_return_purchases')->whereIn('id', $ids('book_return_purchase'))->get()->keyBy('id'),
            'book_return_sale' => DB::table('book_return_sales')->whereIn('id', $ids('book_return_sale'))->get()->keyBy('id'),
        ];
    }

    private function query(array $filters): Builder
    {
        $query = StockMovement::query()->with('inventoryItem')
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to'])
            ->when($filters['name'] ?? null, fn ($query, $name) => $query->whereHas('inventoryItem', fn ($inventory) => $inventory->where('product_name', 'like', '%'.$name.'%')))
            ->when($filters['branches'] ?? null, fn ($query, $values) => $query->whereHas('inventoryItem', fn ($inventory) => $inventory->whereIn('branch_name', $values)))
            ->when($filters['warehouses'] ?? null, fn ($query, $values) => $query->whereHas('inventoryItem', fn ($inventory) => $inventory->whereIn('warehouse_name', $values)))
            ->when($filters['operation_types'] ?? null, fn ($query, $values) => $query->whereIn('movement_type', $values))
            ->when($filters['modules'] ?? null, fn ($query, $values) => $query->whereIn('movement_type', $this->typesForModules($values)))
            ->when($filters['reason_ids'] ?? null, fn ($query, $values) => $query->where('reference_type', 'stock_adjustment')->whereIn('reference_id', StockAdjustment::whereIn('reason_id', $values)->pluck('id')));

        if ($productIds = $filters['product_ids'] ?? null) {
            $products = Product::whereIn('id', $productIds)->get();
            $query->whereHas('inventoryItem', fn ($inventory) => $inventory
                ->whereIn('product_id', $productIds)
                ->orWhereIn('product_external_id', $products->pluck('external_id')->filter())
                ->orWhereIn('code', $products->pluck('code')->filter())
                ->orWhereIn('product_name', $products->pluck('name')));
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function typesForModules(array $modules): array
    {
        return StockMovement::query()->distinct()->pluck('movement_type')->filter(fn ($type) => in_array($this->module($type), $modules, true))->all();
    }

    private function module(string $type): string
    {
        return match (true) {
            str_contains($type, 'sale'), str_contains($type, 'customer_return'), str_contains($type, 'credit_note'), str_contains($type, 'remito') => 'sales',
            str_contains($type, 'purchase'), str_contains($type, 'supplier_return') => 'purchases',
            default => 'stock',
        };
    }

    private function label(string $type): string
    {
        return match ($type) {
            'sale' => 'Venta',
            'credit_note' => 'Nota de crédito de venta',
            'purchase' => 'Compra',
            'purchase_credit_note' => 'Nota de crédito de compra',
            'customer_return' => 'Devolución de cliente',
            'supplier_return' => 'Devolución a proveedor',
            'internal_transfer_in' => 'Transferencia - ingreso',
            'internal_transfer_out' => 'Transferencia - egreso',
            'adjustment' => 'Ajuste',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function reason(StockMovement $movement, mixed $reference): string
    {
        return match ($movement->reference_type) {
            'stock_adjustment' => $reference?->reason?->name ?: $reference?->adjustment_stock_reason ?: $movement->notes ?: '-',
            'book_return_purchase', 'book_return_sale' => $reference?->reason ?: $movement->notes ?: '-',
            default => $this->translatedNotes($movement->notes) ?: '-',
        };
    }

    private function translatedNotes(?string $notes): ?string
    {
        return $notes ? preg_replace('/^Purchase\s+/', 'Compra ', $notes) : null;
    }

    private function user(StockMovement $movement, mixed $reference): string
    {
        return $movement->reference_type === 'invoice'
            ? ($reference?->user_name ?: $reference?->seller_full_name ?: 'Sistema')
            : (data_get($reference, 'created_by') ?: 'Sistema');
    }

    private function document(StockMovement $movement, mixed $reference): string
    {
        return match ($movement->reference_type) {
            'stock_adjustment' => $reference?->number ? 'Ajuste '.$reference->number : 'Ajuste #'.($movement->reference_id ?: $movement->id),
            'purchase_receipt' => $reference?->receipt_number ?: 'Compra #'.$movement->reference_id,
            'purchase' => $reference?->full_number ? 'Compra '.$reference->full_number : 'Compra #'.$movement->reference_id,
            'internal_transfer' => 'Transferencia #'.$movement->reference_id,
            'invoice' => $reference?->display_number ? 'Venta '.$reference->display_number : 'Venta #'.$movement->reference_id,
            'book_return_purchase' => 'Devolución a proveedor #'.$movement->reference_id,
            'book_return_sale' => 'Devolución de cliente #'.$movement->reference_id,
            default => $movement->reference_type ? ucfirst(str_replace('_', ' ', $movement->reference_type)).' #'.$movement->reference_id : 'Movimiento #'.$movement->id,
        };
    }
}
