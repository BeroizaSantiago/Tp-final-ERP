import { Link } from 'react-router-dom'

import { Icon } from './icons'
import { Logo } from './Logo'
import { useTheme } from '../hooks/useTheme'
import { HERO_IMAGE_URL } from '../lib/config'

/**
 * Estructura común de las pantallas públicas de acceso (login y alta de usuario).
 *
 * Reproduce el `blankLayout` + la composición de `auth/login.blade.php`:
 * panel ilustrado a la izquierda, formulario a la derecha, marca, kicker y pie
 * con enlace a la pantalla contigua.
 */
export function AuthLayout({ kicker, title, subtitle, footer, children }) {
  const { theme, toggleTheme } = useTheme()

  return (
    <main className="erp-login-page">
      <div className="erp-login-layout">
        <section className="erp-login-visual" aria-hidden="true">
          <div className="erp-login-brand">
            <Logo width={32} height={32} />
            <span>ORBY</span>
          </div>
          <img className="erp-login-hero" src={HERO_IMAGE_URL} alt="" />
        </section>

        <section className="erp-login-form-panel">
          <button
            className="erp-login-theme"
            type="button"
            onClick={toggleTheme}
            aria-label="Cambiar tema"
            title="Cambiar tema"
          >
            <Icon name={theme === 'dark' ? 'sun' : 'moonClear'} size={20} />
          </button>

          <div className="erp-login-form">
            <div className="erp-login-mobile-brand">
              <Logo width={30} height={30} />
              <span>Gestión ERP</span>
            </div>

            {kicker ? (
              <span className="erp-login-kicker">
                <Icon name={kicker.icon} /> {kicker.text}
              </span>
            ) : null}

            <h1 className="erp-login-title">{title}</h1>
            {subtitle ? <p className="erp-login-subtitle">{subtitle}</p> : null}

            {children}

            {footer ? <p className="erp-login-footer">{footer}</p> : null}
          </div>
        </section>
      </div>
    </main>
  )
}

/** Pie con enlace a la pantalla contigua, usado por login y alta de usuario. */
export function AuthFooterLink({ question, action, to }) {
  return (
    <>
      {question} <Link className="erp-login-link" to={to}>{action}</Link>
    </>
  )
}
