<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controlador de Authenticated Session.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Authenticated Session del ERP.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field=filter_var($data['email'],FILTER_VALIDATE_EMAIL)?'email':'username';
        if (! Auth::attempt([$field=>$data['email'],'password'=>$data['password'],'is_active'=>1], $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'El correo o la contraseña no son correctos.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->update(['last_login_at'=>now()]);

        return redirect()->intended('/demo/dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
