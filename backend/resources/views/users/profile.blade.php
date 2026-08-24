{{-- Vista: Perfil de usuario. Muestra y permite modificar los datos del usuario autenticado. --}}
@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-1">Mi perfil</h3>
                    <p class="text-muted mb-4">{{ $user->name }} · {{ $user->email }}</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <h5 class="mb-3">Cambiar contraseña</h5>
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Contraseña actual</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Nueva contraseña</label>
                            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
