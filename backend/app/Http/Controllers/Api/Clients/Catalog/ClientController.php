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
            return Client::query()->whereRaw('1 = 0')->paginate($this->perPage($request));
        }

        return Client::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('fantasy_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            // Filtro de estado para el ABM: se listan los dados de baja aparte
            // de los activos, en lugar de esconderlos del listado.
            ->when($request->query('is_active') !== null && $request->query('is_active') !== '', function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));
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
        $data = $request->validate([
            'external_id' => ['nullable'],
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'document_type' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'vat_classification' => ['nullable', 'string', 'max:255'],
            'gross_income_classification' => ['nullable', 'string', 'max:255'],
            'fantasy_name' => ['nullable', 'string', 'max:255'],
            'state_tax_number' => ['nullable', 'string', 'max:255'],
            'payment_condition' => ['nullable', 'string', 'max:255'],
            'payment_condition_detail' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:255'],
            'price_type' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'referred' => ['nullable', 'string', 'max:255'],
            'seller' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'last_payment_date' => ['nullable', 'date'],
            'related_provider' => ['nullable', 'string', 'max:255'],
            'is_wholesaler' => ['nullable', 'boolean'],
            'use_credit_invoice' => ['nullable', 'boolean'],
            'first_phone' => ['nullable', 'string', 'max:255'],
            'second_phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'branch_origin' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Ingresá el nombre o razón social del cliente.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.email' => 'El correo electrónico no es válido.',
            'document_number.max' => 'El número de documento no puede superar los 255 caracteres.',
            'birth_date.date' => 'La fecha de nacimiento no es válida.',
            'last_payment_date.date' => 'La fecha del último pago no es válida.',
            'credit_limit.numeric' => 'El límite de crédito debe ser un número.',
            'credit_limit.min' => 'El límite de crédito no puede ser negativo.',
            'discount.numeric' => 'El descuento debe ser un número.',
            'discount.min' => 'El descuento no puede ser negativo.',
        ]);

        /*
         * La columna `discount` es NOT NULL DEFAULT 0 y el middleware
         * `ConvertEmptyStringsToNull` convierte los campos vacíos en null: sin
         * este default el insert fallaría con un error de integridad.
         */
        $data['discount'] = $data['discount'] ?? 0;

        return $data;
    }

    /**
     * Tamaño de página del listado, acotado para no pedir paginas gigantes.
     */
    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        return max(1, min(100, $perPage));
    }
}
