<?php

namespace App\Http\Controllers;

use App\Models\Sales\Invoice;
use Illuminate\Http\Request;
use App\Models\Clients\Client;

/**
 * Gestiona las pantallas web relacionadas con facturas.
 *
 * Prepara el formulario de alta con los clientes activos y muestra el detalle
 * de una factura junto con sus items y el cliente asociado. El resto de las
 * operaciones CRUD se encuentra pendiente de implementacion.
 */
class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
public function create()
{
    $clients = Client::where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('invoices.create', compact('clients'));
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
public function show(Invoice $invoice)
{
    $invoice->load('items', 'client');

    return view('invoices.show', compact('invoice'));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
