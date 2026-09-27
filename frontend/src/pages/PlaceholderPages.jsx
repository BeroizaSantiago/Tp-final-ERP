import { Link } from 'react-router-dom'

import { Icon } from '../components/icons'
import { Logo } from '../components/Logo'
import { useTheme } from '../hooks/useTheme'
import { HOME_PATH } from '../lib/routes'

/**
 * Botón de cambio de tema para las pantallas internas de la SPA.
 */
function ThemeButton() {
  const { theme, toggleTheme } = useTheme()

  return (
    <button
      className="erp-login-theme app-topbar-theme"
      type="button"
      onClick={toggleTheme}
      aria-label="Cambiar tema"
      title="Cambiar tema"
    >
      <Icon name={theme === 'dark' ? 'sun' : 'moonClear'} size={18} />
    </button>
  )
}

/** Ruta inexistente dentro de la SPA. */
export function NotFoundPage() {
  return (
    <div className="app-shell app-shell--centered">
      <div className="app-empty">
        <Logo width={30} height={30} />
        <span className="app-empty-badge">Error 404</span>
        <h1 className="app-page-title">Página no encontrada</h1>
        <p className="app-page-subtitle">La dirección ingresada no corresponde a ninguna vista.</p>
        <Link className="erp-btn erp-btn-primary app-empty-action" to={HOME_PATH}>
          Volver al catálogo
        </Link>
      </div>
    </div>
  )
}
