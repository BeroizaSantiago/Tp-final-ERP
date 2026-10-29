<?php

namespace App\Http\Controllers\Api\Sales\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use Illuminate\Http\Request;

/**
 * Controlador para búsquedas y lookup de facturas.
 */
class InvoiceLookupController extends Controller
{
    public function byClient(Request $request, $clientId)
    {
        $query = Invoice::query()
            ->with(['client', 'items.variant.product'])
            ->where('client_id', $clientId);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('full_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('receipt_type_name', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get(['id', 'full_number', 'receipt_type_name', 'issue_date', 'total_amount', 'customer_name', 'client_id']);
    }
}