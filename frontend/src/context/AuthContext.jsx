import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'

import { apiRequest } from '../lib/api'
import { clearToken, getToken, setToken } from '../lib/session'

/**
 * Estado de autenticación de la SPA.
 *
 * El token de Sanctum vive en localStorage, así que al recargar cualquier ruta
 * hay que validarlo contra GET /api/user para saber si la sesión sigue viva.
 * Mientras esa comprobación corre, `status` vale 'loading' y las rutas protegidas
 * muestran el loader en vez de expulsar al usuario.
 */

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [status, setStatus] = useState('loading')

  // Restaura la sesión al cargar la aplicación.
  useEffect(() => {
    if (!getToken()) {
      setStatus('guest')
      return undefined
    }

    let cancelled = false

    apiRequest('/user')
      .then((response) => {
        if (cancelled) return
        setUser(response.user)
        setStatus('authenticated')
      })
      .catch(() => {
        if (cancelled) return
        // Token vencido o revocado: se descarta y el usuario vuelve al login.
        clearToken()
        setUser(null)
        setStatus('guest')
      })

    return () => {
      cancelled = true
    }
  }, [])

  const login = useCallback(async ({ email, password, remember }) => {
    const response = await apiRequest('/login', {
      method: 'POST',
      auth: false,
      body: { email, password, remember: remember ? 1 : 0 },
    })

    setToken(response.token)
    setUser(response.user)
    setStatus('authenticated')

    return response.user
  }, [])

  const register = useCallback(async ({ firstName, lastName, email }) => {
    const response = await apiRequest('/register', {
      method: 'POST',
      auth: false,
      body: { first_name: firstName, last_name: lastName, email },
    })

    // El alta deja al usuario con sesión iniciada, igual que el alta web.
    setToken(response.token)
    setUser(response.user)
    setStatus('authenticated')

    return response
  }, [])

  const logout = useCallback(async () => {
    try {
      await apiRequest('/logout', { method: 'POST' })
    } catch {
      // Si el backend no responde, el token local se descarta igual.
    }

    clearToken()
    setUser(null)
    setStatus('guest')
  }, [])

  const value = useMemo(
    () => ({
      user,
      status,
      isAuthenticated: status === 'authenticated',
      login,
      register,
      logout,
    }),
    [user, status, login, register, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth debe usarse dentro de <AuthProvider>.')
  }

  return context
}
