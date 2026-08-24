{{-- Vista: Nuevo registro de Facturas de Venta. Muestra el formulario para crear un registro de Facturas de Venta. --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Ventas</h3>
        <button class="btn btn-success">✓ Continuar</button>
    </div>

    <div class="card mb-3">
        <div class="card-body row g-3 align-items-end">

            <div class="col-md-3">
                <label>Cliente *</label>
                <select name="client_id" class="form-control">
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-1 text-center">
                <label>Factura</label>
                <h1>B</h1>
            </div>

            <div class="col-md-2">
                <label>Pto. Vta. *</label>
                <input type="text" class="form-control" value="0005">
            </div>

            <div class="col-md-2">
                <label>Fecha Emisión *</label>
                <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-2">
                <label>Vencimiento *</label>
                <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>

            <div class="col-md-2">
                <label>Vendedor</label>
                <input type="text" class="form-control" placeholder="Ingrese 3 caracteres">
            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>PRODUCTOS</strong>
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Talle</th>
                        <th>Color</th>
                        <th>Cant.</th>
                        <th>P. Unit. c/IVA</th>
                        <th>% Desc</th>
                        <th>% IVA</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><input class="form-control" placeholder="Código"></td>
                        <td><input class="form-control" placeholder="Agregar Producto"></td>
                        <td><input class="form-control"></td>
                        <td><input class="form-control"></td>
                        <td><input class="form-control" value="1"></td>
                        <td><input class="form-control" value="0"></td>
                        <td><input class="form-control" value="0"></td>
                        <td>
                            <select class="form-control">
                                <option>21</option>
                                <option>10.5</option>
                                <option>0</option>
                            </select>
                        </td>
                        <td><input class="form-control" value="0"></td>
                    </tr>
                </tbody>
            </table>

            <p>Total Items: 0</p>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body d-flex justify-content-between">
            <div>GRAVADO<br><strong>$ 0</strong></div>
            <div>NO GRAVADO<br><strong>$ 0</strong></div>
            <div>EXENTO<br><strong>$ 0</strong></div>
            <div>DESCUENTO S/IVA<br><strong>$ 0</strong></div>
            <div>ENVÍO<br><strong>$ 0</strong></div>
            <div>IVA<br><strong>$ 0</strong></div>
            <div>TOTAL<br><strong>$ 0</strong></div>
        </div>
    </div>

</div>
@endsection