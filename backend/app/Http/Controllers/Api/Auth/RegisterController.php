<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Controller;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controlador de Registered User.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Registered User del ERP.
 * Expone el alta de usuarios para el cliente React y, al igual que el alta web,
 * asigna la contraseña inicial definida por el ERP e inicia sesión inmediatamente.
 */
class RegisterController extends Controller
{
    use FormatsUserPayload;

    public function store(Request $request): JsonResponse
    {
        // La app no publica traducciones, por lo que los mensajes se definen aquí
        // para que el cliente React muestre siempre texto en español.
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ], [
            'first_name.required' => 'Ingresá tu nombre.',
            'last_name.required' => 'Ingresá tu apellido.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'role_id' => Role::where('name', 'Vendedor')->value('id'),
            'is_active' => true,
            // El modelo castea 'password' a hashed, así que se asigna en claro.
            'password' => RegisteredUserController::DEFAULT_PASSWORD,
        ]);

        // El username se deriva del correo recién conocido el id. El alta web usa
        // max(id) + 1, lo que puede colisionar si se registran dos usuarios juntos.
        $user->update(['username' => Str::before($user->email, '@').'-'.$user->id]);

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $user->createToken('erp-frontend')->plainTextToken,
            'user' => $this->payload($user->load('role')),
            // Se devuelve para poder mostrarla una sola vez, igual que el aviso
            // que el alta web deja en la sesión.
            'initial_password' => RegisteredUserController::DEFAULT_PASSWORD,
        ], 201);
    }
}
