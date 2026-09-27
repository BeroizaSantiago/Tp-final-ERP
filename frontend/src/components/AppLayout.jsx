import { useEffect, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'

import { Icon } from './icons'
import { Logo } from './Logo'
import { useAuth } from '../context/AuthContext'
import { useTheme } from '../hooks/useTheme'
import { takeNotice } from '../lib/notice'
import { menuMasters } from '../features/masters/registry'

/**
 * Contenido del desplegable "Productos": libros, carga de libros y los
 * catálogos maestros, en el orden pedido. El menú se arma desde el registry,
 * así que agregar un maestro lo mete solo.
 */
const PRODUCT_ENTRIES = [
  { to: '/libros', label: 'Libros', icon: 'book' },
  { to: '/libros/nuevo', label: 'Cargar libro', icon: 'add' },
]

const MASTER_ENTRIES = menuMasters().map((config) => ({
  to: config.path,
  label: config.label,
  icon: config.icon,
}))

/** Clave para recordar si el grupo quedó desplegado. */
const NAV_OPEN_KEY = 'erp.nav.productos'

function ThemeButton() {
  const { theme, toggleTheme } = useTheme()

  return (
    <button
      className="sidebar-theme"
      type="button"
      onClick={toggleTheme}
      aria-label="Cambiar tema"
      title="Cambiar tema"
    >
      <Icon name={theme === 'dark' ? 'sun' : 'moonClear'} size={18} />
    </button>
  )
}

function UserMenu({ onLogout }) {
  const { user } = useAuth()
  const [open, setOpen] = useState(false)

  useEffect(() => {
    if (!open) {
      return undefined
    }

    const close = () => setOpen(false)
    window.addEventListener('click', close)

    return () => window.removeEventListener('click', close)
  }, [open])

  const initials = (user?.name ?? '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')

  return (
    <div className="sidebar-user">
      <button className="sidebar-user-button" type="button" onClick={() => setOpen((v) => !v)} aria-expanded={open}>
        <span className="sidebar-user-avatar">{initials}</span>
        <span className="sidebar-user-meta">
          <strong>{user?.name ?? 'Usuario'}</strong>
          <small>{user?.role ?? 'Sin rol'}</small>
        </span>
      </button>

      {open ? (
        <div className="sidebar-user-menu" onClick={(event) => event.stopPropagation()}>
          <p className="sidebar-user-email">{user?.email}</p>
          <button className="sidebar-user-action" type="button" onClick={onLogout}>
            <Icon name="logout" size={16} />
            Cerrar sesión
          </button>
        </div>
      ) : null}
    </div>
  )
}

/** Estructura de las pantallas internas: menú lateral, barra superior y contenido. */
export function AppLayout() {
  const { logout } = useAuth()
  const location = useLocation()
  const [mobileNavOpen, setMobileNavOpen] = useState(false)
  const [notice, setNotice] = useState(null)

  // El grupo arranca desplegado, salvo que el usuario lo haya cerrado antes.
  const [navOpen, setNavOpen] = useState(() => {
    try {
      return localStorage.getItem(NAV_OPEN_KEY) !== '0'
    } catch {
      return true
    }
  })

  // Aviso de una sola vez (por ejemplo la contraseña inicial tras el alta).
  useEffect(() => {
    setNotice(takeNotice())
  }, [])

  // Cada navegación cierra el menú en mobile para no tapar el contenido.
  useEffect(() => {
    setMobileNavOpen(false)
  }, [location.pathname])

  // Si el usuario cierra el grupo estando en una de sus páginas, se reabre: si
  // no, la ruta actual quedaría oculta sin forma de volver a ella.
  useEffect(() => {
    const inside = [...PRODUCT_ENTRIES, ...MASTER_ENTRIES].some((item) =>
      location.pathname.startsWith(item.to),
    )

    if (inside) {
      setNavOpen(true)
    }
  }, [location.pathname])

  const toggleNav = () => {
    setNavOpen((current) => {
      const next = !current

      try {
        localStorage.setItem(NAV_OPEN_KEY, next ? '1' : '0')
      } catch {
        /* Sin storage la preferencia sólo vive en memoria. */
      }

      return next
    })
  }

  const handleLogout = async () => {
    await logout()
  }

  return (
    <div className={`app-frame${mobileNavOpen ? ' app-frame--nav-open' : ''}`}>
      <aside className="app-sidebar">
        <div className="app-sidebar-head">
          <NavLink className="app-sidebar-brand" to="/libros">
            <Logo width={26} height={26} />
            <span>ORBY</span>
          </NavLink>
          <button className="app-sidebar-close" type="button" onClick={() => setMobileNavOpen(false)} aria-label="Cerrar menú">
            ×
          </button>
        </div>

        <nav className="app-sidebar-nav">
          <div className="app-nav-section">
            <button
              className={`app-nav-toggle${navOpen ? ' is-open' : ''}`}
              type="button"
              onClick={toggleNav}
              aria-expanded={navOpen}
              aria-controls="nav-productos"
            >
              <Icon name="stack" size={18} />
              <span>Productos</span>
              <Icon name={navOpen ? 'chevronUp' : 'chevronDown'} size={16} className="app-nav-caret" />
            </button>

            <div className="app-nav-sublist" id="nav-productos" hidden={!navOpen}>
              {PRODUCT_ENTRIES.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.to === '/libros'}
                  className={({ isActive }) => `app-nav-link app-nav-link--child${isActive ? ' is-active' : ''}`}
                >
                  <Icon name={item.icon} size={18} />
                  <span>{item.label}</span>
                </NavLink>
              ))}

              <hr className="app-nav-divider" />

              {MASTER_ENTRIES.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({ isActive }) => `app-nav-link app-nav-link--child${isActive ? ' is-active' : ''}`}
                >
                  <Icon name={item.icon} size={18} />
                  <span>{item.label}</span>
                </NavLink>
              ))}
            </div>
          </div>
        </nav>

        <div className="app-sidebar-foot">
          <ThemeButton />
          <UserMenu onLogout={handleLogout} />
        </div>
      </aside>

      <div className="app-main">
        <header className="app-bar">
          <button className="app-bar-menu" type="button" onClick={() => setMobileNavOpen(true)} aria-label="Abrir menú">
            <Icon name="menu" size={20} />
          </button>
        </header>

        <div className="app-content">
          {notice ? (
            <div className="app-notice" role="status">
              <span className="app-notice-icon">
                <Icon name="shieldCheck" size={18} />
              </span>
              <div>
                <strong>{notice.title}</strong>
                <p>{notice.message}</p>
              </div>
              <button
                className="app-notice-close"
                type="button"
                onClick={() => setNotice(null)}
                aria-label="Cerrar aviso"
              >
                ×
              </button>
            </div>
          ) : null}

          <Outlet />
        </div>
      </div>

      {mobileNavOpen ? (
        <button className="app-nav-scrim" type="button" onClick={() => setMobileNavOpen(false)} aria-label="Cerrar menú" />
      ) : null}
    </div>
  )
}
