<?php

namespace App\Http\Controllers\Api\Purchases\Providers;

use App\Http\Controllers\Controller;
use App\Models\Purchases\Provider;
use Illuminate\Http\Request;

/**
 * Controlador de Proveedor.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Proveedor del ERP.
 */
class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($request->boolean('lookup') && mb_strlen($search) < 3) {
            return Provider::query()->whereRaw('1 = 0')->paginate(20);
        }

        return Provider::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                    ->orWhere('fantasy_name', 'like', "%{$search}%")
                    ->orWhere('identification_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);
    }

    public function show(Provider $provider)
    {
        return $provider;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'code' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'fantasy_name' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string'],
            'identification_number' => ['nullable', 'string'],
            'vat_classification' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'province_name' => ['nullable', 'string'],
            'gross_income_number' => ['nullable', 'string'],
            'primary_phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $provider = Provider::create($data);

        return response()->json($provider, 201);
    }

    public function update(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'code' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'fantasy_name' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string'],
            'identification_number' => ['nullable', 'string'],
            'vat_classification' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'province_name' => ['nullable', 'string'],
            'gross_income_number' => ['nullable', 'string'],
            'primary_phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $provider->update($data);

        return response()->json($provider->fresh());
    }
}
