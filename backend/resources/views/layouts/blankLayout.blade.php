{{-- Layout: Página sin navegación. Define la estructura para pantallas que no muestran menú ni barra superior. --}}
@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset

@extends('layouts/commonMaster')

@section('layoutContent')
<!-- Content -->
@yield('content')
<!--/ Content -->
@endsection
