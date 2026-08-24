{{-- Vista: Motivos de ajuste de stock. Permite crear, editar y consultar los motivos de inventario. --}}
@extends('layouts.app')
@section('content')
<x-reason-maintenance title="Motivos de ajuste de stock" subtitle="Administración de los motivos utilizados en movimientos y ajustes de stock" endpoint="{{ url('/api/stock-adjustment-reasons') }}" singular="motivo" />
@endsection