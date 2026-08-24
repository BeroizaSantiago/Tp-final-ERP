<?php

namespace App\Http\Controllers\Api\Sales\CreditNotes;

use App\Http\Controllers\Api\Sales\Shared\SalesDocumentController;
use App\Models\Sales\Invoice;
use App\Services\Sales\CreditNoteFiscalService;
use App\Services\Sales\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CreditNoteController extends SalesDocumentController
{
    public function creditNotes(Request $request)
    {
        return Invoice::query()->with(['client', 'originalInvoice', 'creditNoteReason'])
            ->where('receipt_types_prefix', 'NC')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('full_number', 'like', '%'.$request->search.'%')
                ->orWhere('customer_name', 'like', '%'.$request->search.'%')
                ->orWhere('status_name', 'like', '%'.$request->search.'%')))
            ->latest('issue_date')->latest('id')->paginate(20);
    }

    public function storeCreditNote(Request $request, CreditNoteService $service)
    {
        $data = $request->validate([
            'related_invoice_id' => ['required', 'exists:invoices,id'],
            'issue_date' => ['required', 'date'],
            'credit_note_reason_id' => ['required', 'exists:credit_note_reasons,id'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.original_invoice_item_id' => ['required', 'integer', 'exists:invoice_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        return response()->json($service->create($data), 201);
    }

    public function sourceInvoices(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'per_page' => ['nullable', 'integer', 'between:5,50']]);
        return Invoice::query()->whereNotNull('stock_applied_at')
            ->where(fn ($query) => $query->where('receipt_types_prefix', 'FV')->orWhereNull('receipt_types_prefix'))
            ->whereNotIn('status_name', ['Anulada', 'Acreditada completamente'])
            ->when($data['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('full_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%")))
            ->latest('issue_date')->latest('id')->paginate($data['per_page'] ?? 20)
            ->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'name' => ($invoice->display_number ?: 'Venta #'.$invoice->id).' · '.$invoice->customer_name,
                'full_number' => $invoice->display_number,
                'customer_name' => $invoice->customer_name,
            ]);
    }

    public function sourceInvoice(Invoice $invoice, CreditNoteService $service)
    {
        abort_unless(($invoice->receipt_types_prefix === 'FV' || $invoice->receipt_types_prefix === null) && $invoice->stock_applied_at, 422, 'La factura no está disponible para acreditar.');
        $invoice->load(['client', 'items.variant.size', 'items.variant.color']);
        $invoice->items->each(fn ($item) => $item->setAttribute('remaining_credit_quantity', $service->remainingQuantity($item)));
        return $invoice;
    }

    public function show(Invoice $invoice)
    {
        abort_unless($invoice->receipt_types_prefix === 'NC', 404);
        return $invoice->load(['client', 'items.variant.size', 'items.variant.color', 'items.originalInvoiceItem', 'originalInvoice', 'creditNoteReason']);
    }

}
