<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;

/**
 * Formato de usuario compartido por los endpoints de sesión y de alta.
 *
 * La SPA consume siempre la misma estructura, así que se define en un solo
 * lugar para que login, registro y /api/user no puedan divergir.
 */
trait FormatsUserPayload
{
    /**
     * Normaliza el usuario que consume el cliente React.
     */
    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role?->name,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }
}
