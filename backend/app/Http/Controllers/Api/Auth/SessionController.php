<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de Session.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Session del ERP.
 * Replica las reglas del login Blade y emite un token de Sanctum para el cliente React.
 */
class SessionController extends Controller
{
    use FormatsUserPayload;

    public function store(Request $request): JsonResponse
    {
        // La app no publica traducciones, por lo que los mensajes se definen aquí
        // para que el cliente React muestre siempre texto en español.
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresá tu usuario o correo.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        // El login Blade acepta indistintamente el username o el correo del usuario.
        $field = filter_var($data['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $data['email'], 'password' => $data['password'], 'is_active' => 1], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        $user = $request->user();
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $user->createToken('erp-frontend')->plainTextToken,
            'user' => $this->payload($user->load('role')),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->payload($request->user()->load('role'))]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        Auth::guard('web')->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
