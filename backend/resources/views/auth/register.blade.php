{{-- Vista: Registro de usuario. Muestra el formulario de alta inicial de usuarios. --}}
@extends('layouts.blankLayout')

@section('title', 'Crear usuario')

@section('content')
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="card shadow-sm border-0" style="width: 100%; max-width: 430px;">
        <div class="card-body p-4 p-md-5">
            <h3 class="fw-bold mb-2">Crear usuario</h3>
            <p class="text-muted mb-4">La contraseña inicial será <strong>ERP123</strong>. Luego podrás cambiarla desde tu perfil.</p>

            <form method="POST" action="{{ url('/register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="name">Nombre</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label" for="email">Correo electrónico</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary w-100" type="submit">Crear usuario</button>
            </form>

            <p class="text-center mt-4 mb-0">¿Ya tenés usuario? <a href="{{ route('login') }}">Iniciá sesión</a></p>
        </div>
    </div>
</div>
@endsection
