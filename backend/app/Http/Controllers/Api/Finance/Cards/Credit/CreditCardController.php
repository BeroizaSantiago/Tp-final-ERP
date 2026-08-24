<?php

namespace App\Http\Controllers\Api\Finance\Cards\Credit;

use App\Http\Controllers\Controller;
use App\Models\Finance\CreditCard;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de tarjetas de credito.
 *
 * Permite listar, consultar y registrar tarjetas con tipo, numero de comercio,
 * autorizaciones y estado de actividad.
 */
class CreditCardController extends Controller
{
    public function index()
    {
        return CreditCard::orderBy('name')->paginate(20);
    }

    public function show(CreditCard $creditCard)
    {
        return $creditCard;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'name' => ['required', 'string'],
            'credit_card_type' => ['nullable', 'string'],
            'trade_number' => ['nullable', 'string'],
            'authorizations' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return CreditCard::create($data);
    }

    public function update(Request $request, CreditCard $creditCard)
{
    $data = $request->validate([
        'name' => ['required', 'string'],
        'credit_card_type' => ['nullable', 'string'],
        'trade_number' => ['nullable', 'string'],
        'authorizations' => ['nullable', 'string'],
        'is_active' => ['nullable', 'boolean'],
    ]);

    $data['is_active'] = $request->boolean('is_active');

    $creditCard->update($data);

    return $creditCard->fresh();
}
}   
