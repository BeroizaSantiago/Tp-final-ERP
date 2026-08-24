<?php

namespace App\Http\Controllers\Api\Stock\Transfers;

use App\Http\Controllers\Controller;
use App\Models\Stock\InternalTransfer;
use App\Models\Stock\InternalTransferItem;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestiona transferencias de existencias entre depositos.
 *
 * Registra la transferencia y sus productos dentro de una transaccion,
 * descuenta stock del origen, incrementa el destino y genera los movimientos
 * de inventario correspondientes para mantener la trazabilidad.
 */
class InternalTransferController extends Controller
{
    public function index()
    {
        return InternalTransfer::with('items.inventoryItem')
            ->latest()
            ->paginate(20);
    }

    public function show(InternalTransfer $internalTransfer)
    {
        return $internalTransfer->load('items.inventoryItem');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_warehouse' => ['required', 'string'],
            'to_warehouse' => ['required', 'string', 'different:from_warehouse'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data) {
            $transfer = InternalTransfer::create([
                'from_warehouse' => $data['from_warehouse'],
                'to_warehouse' => $data['to_warehouse'],
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $originItem = InventoryItem::findOrFail($item['inventory_item_id']);

                $qty = $item['quantity'];

                $originBefore = $originItem->current_stock;
                $originAfter = $originBefore - $qty;

                $originItem->update([
                    'current_stock' => $originAfter,
                ]);

                InternalTransferItem::create([
                    'internal_transfer_id' => $transfer->id,
                    'inventory_item_id' => $originItem->id,
                    'quantity' => $qty,
                ]);

                StockMovement::create([
                    'inventory_item_id' => $originItem->id,
                    'movement_type' => 'internal_transfer_out',
                    'quantity' => -$qty,
                    'stock_before' => $originBefore,
                    'stock_after' => $originAfter,
                    'reference_type' => 'internal_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => 'Salida hacia ' . $data['to_warehouse'],
                ]);

                $destinationItem = InventoryItem::firstOrCreate(
                    [
                        'product_external_id' => $originItem->product_external_id,
                        'product_variant_external_id' => $originItem->product_variant_external_id,
                        'warehouse_name' => $data['to_warehouse'],
                        'color_name' => $originItem->color_name,
                        'size_name' => $originItem->size_name,
                    ],
                    [
                        'code' => $originItem->code,
                        'bar_code' => $originItem->bar_code,
                        'reference_code' => $originItem->reference_code,
                        'company_configuration_code' => $originItem->company_configuration_code,
                        'product_name' => $originItem->product_name,
                        'branch_name' => $originItem->branch_name,
                        'min_stock' => $originItem->min_stock,
                        'reposition_stock' => $originItem->reposition_stock,
                        'current_stock' => 0,
                        'company_id' => $originItem->company_id,
                        'account_id' => $originItem->account_id,
                        'stock_batch' => $originItem->stock_batch,
                        'valued_item' => $originItem->valued_item,
                        'currency_id' => $originItem->currency_id,
                        'currency_symbol' => $originItem->currency_symbol,
                    ]
                );

                $destinationBefore = $destinationItem->current_stock;
                $destinationAfter = $destinationBefore + $qty;

                $destinationItem->update([
                    'current_stock' => $destinationAfter,
                ]);

                StockMovement::create([
                    'inventory_item_id' => $destinationItem->id,
                    'movement_type' => 'internal_transfer_in',
                    'quantity' => $qty,
                    'stock_before' => $destinationBefore,
                    'stock_after' => $destinationAfter,
                    'reference_type' => 'internal_transfer',
                    'reference_id' => $transfer->id,
                    'notes' => 'Ingreso desde ' . $data['from_warehouse'],
                ]);
            }

            return $transfer->load('items.inventoryItem');
        });
    }
}
