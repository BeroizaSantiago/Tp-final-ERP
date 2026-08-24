<?php

namespace App\Http\Controllers\Api\Clients\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Clients\Client;
use Illuminate\Http\Request;

/**
 * Controlador de Cliente.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Cliente del ERP.
 */
class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($request->boolean('lookup') && mb_strlen($search) < 3) {
            return Client::query()->whereRaw('1 = 0')->paginate(20);
        }

        return Client::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                    ->orWhere('fantasy_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);
    }

    public function show(Client $client)
    {
        return $client;
    }

    public function defaultConsumer()
    {
        return Client::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['consumidor final'])
                    ->orWhereRaw('LOWER(vat_classification) = ?', ['consumidor final']);
            })
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN LOWER(name) = 'consumidor final' THEN 0 ELSE 1 END")
            ->firstOrFail();
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_wholesaler'] = $request->boolean('is_wholesaler');
        $data['use_credit_invoice'] = $request->boolean('use_credit_invoice');

        return response()->json(Client::create($data), 201);
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validateData($request);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_wholesaler'] = $request->boolean('is_wholesaler');
        $data['use_credit_invoice'] = $request->boolean('use_credit_invoice');

        $client->update($data);

        return response()->json($client->fresh());
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'external_id' => ['nullable'],
            'code' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'birth_date' => ['nullable', 'date'],
            'document_type' => ['nullable', 'string'],
            'document_number' => ['nullable', 'string'],
            'vat_classification' => ['nullable', 'string'],
            'gross_income_classification' => ['nullable', 'string'],
            'fantasy_name' => ['nullable', 'string'],
            'state_tax_number' => ['nullable', 'string'],
            'payment_condition' => ['nullable', 'string'],
            'payment_condition_detail' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'price_type' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric'],
            'referred' => ['nullable', 'string'],
            'seller' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'last_payment_date' => ['nullable', 'date'],
            'related_provider' => ['nullable', 'string'],
            'is_wholesaler' => ['nullable', 'boolean'],
            'use_credit_invoice' => ['nullable', 'boolean'],
            'first_phone' => ['nullable', 'string'],
            'second_phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'address_number' => ['nullable', 'string'],
            'apartment' => ['nullable', 'string'],
            'neighborhood' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'branch_origin' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
