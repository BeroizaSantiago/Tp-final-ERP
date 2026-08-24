<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Security\Role;

/**
 * Controlador de Registered Usuario.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Registered Usuario del ERP.
 */
class RegisteredUserController extends Controller
{
    public const DEFAULT_PASSWORD = 'ERP123';

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = User::create([
            ...$data,
            'username'=>str($data['email'])->before('@').'-'.(User::max('id')+1),
            'first_name'=>$data['name'],
            'role_id'=>Role::where('name','Vendedor')->value('id'),
            'is_active'=>true,
            'password' => self::DEFAULT_PASSWORD,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/demo/dashboard')
            ->with('success', 'Usuario creado. La contraseña inicial es '.self::DEFAULT_PASSWORD.'.');
    }
}
