{{-- Vista: Motivos de nota de crédito. Permite crear, editar y consultar motivos comerciales. --}}
@extends('layouts.app')
@section('content')
<x-reason-maintenance title="Motivos de nota de crédito" subtitle="Administración de los motivos disponibles para emitir notas de crédito" endpoint="{{ url('/api/credit-note-reasons') }}" singular="motivo" />
@endsection