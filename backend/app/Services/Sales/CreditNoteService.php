<?php

namespace App\Services\Sales;

use App\Models\Products\ProductVariant;
use App\Models\Sales\CreditNoteReason;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\StockMovement;
use App\Models\Stock\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditNoteService
{
    public function create(array $data): Invoice
    {
        $creditNote = DB::transaction(function () use ($data) {
            if ($existing = Invoice::query()->where('credit_note_idempotency_key', $data['idempotency_key'])->first()) {
                return $existing->load($this->relations());
            }

            $original = Invoice::query()->lockForUpdate()->with(['items.variant.product', 'client'])->findOrFail($data['related_invoice_id']);
            $this->validateOriginal($original);
            $reason = CreditNoteReason::query()->where('is_active', true)->findOrFail($data['credit_note_reason_id']);
            $requested = collect($data['items'])->keyBy(fn ($item) => (int) $item['original_invoice_item_id']);
            $calculated = [];

            foreach ($requested as $originalItemId => $requestItem) {
                $item = $original->items->firstWhere('id', $originalItemId);
                if (! $item) $this->fail('items', 'Uno de los productos no pertenece a la factura seleccionada.');
                $quantity = round((float) $requestItem['quantity'], 4);
                $remaining = $this->remainingQuantity($item);
                if ($quantity <= 0 || $quantity > $remaining + 0.0001) {
                    $this->fail("items.{$originalItemId}", "La cantidad de {$item->product_name} supera el máximo acreditable ({$remaining}).");
                }

                $gross = $quantity * (float) $item->unit_price_with_taxes;
                $total = round($gross * (1 - ((float) $item->discount_percentage / 100)), 4);
                $taxRate = (float) $item->tax_aliquot_percentage;
                $net = $taxRate > 0 ? round($total / (1 + $taxRate / 100), 4) : $total;
                $calculated[] = compact('item', 'quantity', 'remaining', 'total', 'net') + ['tax' => round($total - $net, 4)];
            }
            if ($calculated === []) $this->fail('items', 'Seleccioná al menos un producto para acreditar.');

            $taxed = round(collect($calculated)->sum('net'), 4);
            $tax = round(collect($calculated)->sum('tax'), 4);
            $total = round(collect($calculated)->sum('total'), 2);
            $letter = in_array($original->letter, ['A', 'B', 'C'], true) ? $original->letter : 'C';

            $creditNote = Invoice::create([
                'client_id' => $original->client_id,
                'customer_external_id' => $original->customer_external_id,
                'customer_name' => $original->customer_name,
                'customer_email' => $original->customer_email,
                'customer_phone_number' => $original->customer_phone_number,
                'issue_date' => $data['issue_date'],
                'payment_due_date' => $data['issue_date'],
                'receipt_types_prefix' => 'NC',
                'receipt_type_name' => "Nota de Crédito {$letter}",
                'letter' => $letter,
                'first_number' => $original->first_number,
                'mode' => $original->mode,
                'currency_name' => $original->currency_name ?: 'Pesos',
                'taxed_amount' => $taxed,
                'tax_amount' => $tax,
                'non_taxed_amount' => 0,
                'exempt_amount' => 0,
                'total_amount' => $total,
                'balance' => 0,
                'status_name' => $original->is_internal_receipt ? 'Emitida' : 'Pend. autorización',
                'status_id' => 1,
                'related_invoice_id' => $original->id,
                'related_document_full_name' => $original->display_number,
                'credit_note_reason_id' => $reason->id,
                'credit_note_reason_name' => $reason->name,
                'credit_note_idempotency_key' => $data['idempotency_key'],
                'warehouse_id' => $original->warehouse_id,
                'seller_full_name' => auth()->user()?->name,
                'user_name' => auth()->user()?->username ?: auth()->user()?->email,
                'payment_condition_name' => 'Crédito por devolución',
                'arca_status' => $original->is_internal_receipt ? 'NOT_APPLICABLE' : 'PENDING',
                'printed' => false,
            ]);
            $creditNote->update(['full_number' => 'NC-'.str_pad((string) $creditNote->id, 8, '0', STR_PAD_LEFT)]);

            foreach ($calculated as $line) {
                $source = $line['item'];
                $creditItem = $source->replicate();
                $creditItem->invoice_id = $creditNote->id;
                $creditItem->original_invoice_item_id = $source->id;
                $creditItem->quantity = $line['quantity'];
                $creditItem->pending = 0;
                $creditItem->subtotal_amount = $line['net'];
                $creditItem->tax_amount = $line['tax'];
                $creditItem->total_amount = $line['total'];
                $creditItem->created_at = now();
                $creditItem->updated_at = now();
                $creditItem->save();
            }

            return $creditNote->fresh()->load($this->relations());
        }, 3);

        if ($creditNote->is_internal_receipt) {
            $this->applyStock($creditNote);
        }

        return $creditNote->fresh()->load($this->relations());
    }

    public function applyStock(Invoice $creditNote): Invoice
    {
        return DB::transaction(function () use ($creditNote) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($creditNote->id);
            if ($locked->credit_note_stock_applied_at) {
                return $locked->load($this->relations());
            }

            $locked->load(['items', 'originalInvoice']);
            $original = $locked->originalInvoice;
            if (! $original) {
                $this->fail('related_invoice_id', 'La nota de crédito no tiene una factura relacionada.');
            }

            if (! $locked->is_internal_receipt && ! $locked->arca_cae) {
                $this->fail('arca_status', 'La nota de crédito debe estar autorizada por ARCA antes de devolver stock.');
            }

            foreach ($locked->items as $item) {
                $this->returnStock($original, $locked, $item, (float) $item->quantity);
            }

            $locked->update(['credit_note_stock_applied_at' => now()]);
            $credited = (float) $original->creditNotes()
                ->whereNotIn('status_name', ['Anulada', 'Rechazada', 'Pend. autorización'])
                ->sum('total_amount');

            if ($credited + 0.005 >= (float) $original->total_amount) {
                $original->update(['status_name' => 'Acreditada completamente']);
            }

            return $locked->fresh()->load($this->relations());
        }, 3);
    }

    public function remainingQuantity(InvoiceItem $item): float
    {
        $credited = (float) InvoiceItem::query()
            ->where('original_invoice_item_id', $item->id)
            ->whereHas('invoice', fn ($query) => $query->where('receipt_types_prefix', 'NC')->whereNotIn('status_name', ['Anulada', 'Rechazada']))
            ->sum('quantity');
        return max(0, round(abs((float) $item->quantity) - $credited, 4));
    }

    private function returnStock(Invoice $original, Invoice $creditNote, InvoiceItem $item, float $quantity): void
    {
        if (! $original->stock_applied_at || ! $item->product_variant_id) return;
        $variant = ProductVariant::query()->with('product')->lockForUpdate()->findOrFail($item->product_variant_id);
        $inventory = $this->inventoryItem($item, $original);
        if (! $inventory) {
            $this->fail('items', 'No se encontró el depósito original de '.$item->product_name.'. No se modificó el stock.');
        }
        $before = (float) $variant->current_stock;
        $after = $before + $quantity;
        $variant->update(['current_stock' => $after, 'available_stock' => $after]);
        app(StockService::class)->recalculateProductStock($variant->product);

        $locationBefore = (float) $inventory->current_stock;
        $inventory->update(['current_stock' => $locationBefore + $quantity]);
        StockMovement::create([
            'inventory_item_id' => $inventory->id,
            'movement_type' => 'credit_note',
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => 'invoice',
            'reference_id' => $creditNote->id,
            'notes' => 'Devolución de '.$original->display_number,
        ]);
    }

    private function inventoryItem(InvoiceItem $item, Invoice $invoice): ?InventoryItem
    {
        $warehouseId = $item->warehouse_id ?: $invoice->warehouse_id;
        $warehouse = $warehouseId ? Warehouse::with('branch')->find($warehouseId) : null;
        return InventoryItem::query()->where('product_variant_id', $item->product_variant_id)
            ->when($warehouse, fn ($query) => $query->where('warehouse_name', $warehouse->name)->where('branch_name', $warehouse->branch?->name))
            ->orderBy('id')->lockForUpdate()->first();
    }

    private function validateOriginal(Invoice $invoice): void
    {
        if ($invoice->receipt_types_prefix !== 'FV' && $invoice->receipt_types_prefix !== null) $this->fail('related_invoice_id', 'El comprobante seleccionado no es una factura.');
        if (! $invoice->stock_applied_at) $this->fail('related_invoice_id', 'La factura todavía no fue finalizada.');
        if (in_array($invoice->status_name, ['Anulada', 'Acreditada completamente'], true)) $this->fail('related_invoice_id', 'La factura ya fue acreditada completamente.');
    }

    private function relations(): array
    {
        return ['client', 'items.variant.size', 'items.variant.color', 'items.originalInvoiceItem', 'originalInvoice', 'creditNoteReason'];
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
