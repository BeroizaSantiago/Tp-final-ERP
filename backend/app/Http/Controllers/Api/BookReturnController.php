<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Sales\Shared\SalesDocumentController;
use App\Models\BookReturn;
use App\Services\BookReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class BookReturnController extends SalesDocumentController
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'return_type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'between:5,100'],
        ]);

        $query = BookReturn::query();

        if (!empty($data['search'])) {
            $query->where(function ($inner) use ($data) {
                $inner->where('id', $data['search'])
                    ->orWhere('reason', 'like', '%' . $data['search'] . '%')
                    ->orWhere('condition', 'like', '%' . $data['search'] . '%');
            });
        }

        if (!empty($data['return_type'])) {
            $query->where('return_type', $data['return_type']);
        }

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return $query->latest('return_date')->paginate($data['per_page'] ?? 20);
    }

    public function store(Request $request, BookReturnService $service)
    {
        $data = $request->validate([
            'return_type' => ['required', 'string', 'in:client,supplier'],
            'returnable_type' => ['required', 'string'],
            'returnable_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['nullable', 'string'],
            'reason' => ['required', 'string'],
            'client_id' => ['required_if:return_type,client', 'exists:clients,id'],
            'provider_id' => ['required_if:return_type,supplier', 'exists:providers,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        return response()->json($service->create($data), 201);
    }

    public function show($bookReturn)
    {
        return $bookReturn->load([
            'returnable',
            'client',
            'provider',
            'invoice',
            'purchase',
            'status',
        ]);
    }

    public function updateStatus(Request $request, BookReturn $bookReturn)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected,completed'],
        ]);

        $bookReturn->update($data);

        return response()->json($bookReturn->fresh());
    }
}