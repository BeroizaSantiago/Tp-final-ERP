import { Navigate, useLocation } from 'react-router-dom'

import { useAuth } from '../context/AuthContext'
import { HOME_PATH } from '../lib/routes'

/** Pantalla de espera mientras se valida el token guardado. */
export function FullScreenLoader({ message = 'Cargando…' }) {
  return (
    <div className="app-loader" role="status" aria-live="polite">
      <span className="app-loader-spinner" aria-hidden="true" />
      <span>{message}</span>
    </div>
  )
}

/**
 * Ruta que exige sesión activa.
 * Guarda la ruta pedida para volver a ella después de iniciar sesión.
 */
export function RequireAuth({ children }) {
  const { status } = useAuth()
  const location = useLocation()

  if (status === 'loading') {
    return <FullScreenLoader message="Validando sesión…" />
  }

  if (status === 'guest') {
    return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />
  }

  return children
}

/** Ruta que sólo tiene sentido para usuarios sin sesión (login, registro). */
export function RequireGuest({ children }) {
  const { status } = useAuth()

  if (status === 'loading') {
    return <FullScreenLoader message="Validando sesión…" />
  }

  if (status === 'authenticated') {
    return <Navigate to={HOME_PATH} replace />
  }

  return children
}
