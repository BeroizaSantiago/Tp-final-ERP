<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Controlador de Usuario.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Usuario del ERP.
 */
class UserController extends Controller
{
    public function edit(Request $request): View
    {
        return view('users.profile', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
