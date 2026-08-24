{{-- Vista: Inicio de sesión. --}}
@extends('layouts.blankLayout')

@section('title', 'Iniciar sesión')

@section('content')
<style>
    .erp-login-page {
        min-height: 100vh;
        background: var(--bs-body-bg);
    }
    .erp-login-visual {
        position: relative;
        min-height: 100vh;
        overflow: hidden;
        background: #f4f3fb;
    }
    .erp-login-visual::before,
    .erp-login-visual::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgb(115 103 240 / 8%);
    }
    .erp-login-visual::before { width: 28rem; height: 28rem; top: -14rem; left: -10rem; }
    .erp-login-visual::after { width: 22rem; height: 22rem; right: -8rem; bottom: -12rem; }
    .erp-login-brand {
        position: absolute;
        z-index: 2;
        top: 2rem;
        left: 2.5rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        color: var(--bs-heading-color);
        font-size: 1.35rem;
        font-weight: 700;
    }
    .erp-login-brand svg { width: 2rem; height: 2rem; }
    .erp-login-hero {
        width: min(88%, 62rem);
        max-height: 82vh;
        object-fit: contain;
        border-radius: 1.5rem;
        filter: drop-shadow(0 1.25rem 2.25rem rgb(67 53 133 / 10%));
    }
    .erp-login-form-panel {
        position: relative;
        min-height: 100vh;
        padding: 3rem clamp(1.5rem, 5vw, 6rem);
        background: var(--bs-paper-bg, #fff);
    }
    .erp-login-form { width: 100%; max-width: 28rem; }
    .erp-login-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: 1rem;
        padding: .4rem .75rem;
        border-radius: 2rem;
        background: rgb(115 103 240 / 10%);
        color: #7367f0;
        font-size: .78rem;
        font-weight: 600;
    }
    .erp-login-title { color: var(--bs-heading-color); font-size: clamp(1.75rem, 3vw, 2.3rem); }
    .erp-login-input {
        min-height: 3.35rem;
        border-radius: .65rem;
        padding-inline: 1rem;
    }
    .erp-password-field { position: relative; }
    .erp-password-field .erp-login-input { padding-right: 3.4rem; }
    .erp-password-toggle {
        position: absolute;
        top: 50%;
        right: .7rem;
        display: grid;
        width: 2.25rem;
        height: 2.25rem;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: var(--bs-secondary-color);
        transform: translateY(-50%);
    }
    .erp-password-toggle:hover,
    .erp-password-toggle:focus-visible { background: rgb(115 103 240 / 10%); color: #7367f0; outline: none; }
    .erp-password-toggle svg { width: 1.2rem; height: 1.2rem; }
    .erp-login-submit {
        min-height: 3.25rem;
        border-radius: .65rem;
        font-weight: 600;
        box-shadow: 0 .5rem 1.15rem rgb(115 103 240 / 25%);
    }
    .erp-login-theme {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        display: grid;
        width: 2.65rem;
        height: 2.65rem;
        place-items: center;
        border: 1px solid var(--bs-border-color);
        border-radius: 50%;
        background: var(--bs-paper-bg, #fff);
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .erp-login-visual { background: #242238; }
    [data-bs-theme="dark"] .erp-login-hero { opacity: .9; filter: brightness(.82) saturate(.9) drop-shadow(0 1.25rem 2rem rgb(0 0 0 / 22%)); }
    [data-bs-theme="dark"] .erp-login-kicker { background: rgb(195 167 255 / 13%); color: #c3a7ff; }
    [data-bs-theme="dark"] .erp-password-toggle:hover { background: rgb(195 167 255 / 12%); color: #c3a7ff; }
    @media (max-width: 991.98px) {
        .erp-login-form-panel { padding-block: 5rem 2.5rem; }
        .erp-login-mobile-brand { display: flex !important; }
    }
</style>

<main class="erp-login-page container-fluid p-0">
    <div class="row g-0 min-vh-100">
        <section class="col-lg-7 d-none d-lg-flex align-items-center justify-content-center erp-login-visual" aria-hidden="true">
            <div class="erp-login-brand">
                
                <span>ORBY</span>
            </div>
            <img class="erp-login-hero" src="{{ asset('assets/img/auth/erp-login-hero.png') }}" alt="">
        </section>

        <section class="col-lg-5 d-flex align-items-center justify-content-center erp-login-form-panel">
            <button class="erp-login-theme" id="loginThemeToggle" type="button" aria-label="Cambiar tema" title="Cambiar tema">
                <i class="ri-moon-clear-line fs-5"></i>
            </button>

            <div class="erp-login-form">
                <div class="erp-login-mobile-brand d-none align-items-center gap-2 mb-5 fw-bold fs-4">
                    @include('_partials.macros', ['width' => '30', 'height' => '30'])
                    <span>Gestión ERP</span>
                </div>

                <span class="erp-login-kicker"><i class="ri-shield-check-line"></i> Acceso seguro</span>
                <h1 class="erp-login-title fw-bold mb-2">¡Bienvenido nuevamente!</h1>
                <p class="text-muted mb-5">Ingresá a tu cuenta para continuar gestionando tu negocio.</p>

                <form method="POST" action="{{ url('/login') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-medium" for="email">Usuario o correo electrónico</label>
                        <input class="form-control erp-login-input @error('email') is-invalid @enderror"
                            id="email" name="email" type="text" value="{{ old('email') }}"
                            placeholder="Ingresá tu usuario o correo" autocomplete="username" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium" for="password">Contraseña</label>
                        <div class="erp-password-field">
                            <input class="form-control erp-login-input" id="password" name="password" type="password"
                                placeholder="Ingresá tu contraseña" autocomplete="current-password" required>
                            <button class="erp-password-toggle" id="passwordToggle" type="button"
                                aria-label="Mostrar contraseña" title="Mostrar contraseña">
                                <svg id="passwordEyeOff" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                    <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5.5 0 9 5 9 5a16.8 16.8 0 0 1-2.1 2.7"></path>
                                    <path d="M6.6 6.6C4.4 8.1 3 10 3 10s3.5 5 9 5c1.2 0 2.3-.2 3.3-.6"></path>
                                </svg>
                                <svg id="passwordEye" class="d-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="2.5"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                        <label class="form-check-label" for="remember">Mantener la sesión iniciada</label>
                    </div>

                    <button class="btn btn-primary erp-login-submit w-100" type="submit">
                        Iniciar sesión <i class="ri-arrow-right-line ms-1"></i>
                    </button>
                </form>

                <p class="text-center text-muted mt-4 mb-0">
                    ¿Todavía no tenés usuario?
                    <a class="fw-semibold" href="{{ route('register') }}">Crear usuario</a>
                </p>
            </div>
        </section>
    </div>
</main>

<script>
    document.getElementById('passwordToggle').addEventListener('click', event => {
        const input = document.getElementById('password');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        document.getElementById('passwordEyeOff').classList.toggle('d-none', !visible);
        document.getElementById('passwordEye').classList.toggle('d-none', visible);
        event.currentTarget.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        event.currentTarget.setAttribute('title', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        input.focus();
    });

    document.getElementById('loginThemeToggle').addEventListener('click', () => {
        const dark = document.documentElement.dataset.bsTheme === 'dark';
        if (window.erpSetColorTheme) window.erpSetColorTheme(dark ? 'light' : 'dark');
        else {
            localStorage.setItem('erp.color-theme', dark ? 'light' : 'dark');
            document.documentElement.dataset.bsTheme = dark ? 'light' : 'dark';
        }
    });
</script>
@endsection
