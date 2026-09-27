import { createContext, useCallback, useContext, useMemo, useState } from 'react'

/**
 * Avisos emergentes.
 *
 * Reemplaza a los SweetAlert del backend con mensajes no bloqueantes, usando
 * los mismos colores que definía el ERP para success / error / warning / info.
 */

const ToastContext = createContext(null)

const ICONS = {
  success: 'M20 6 9 17l-5-5',
  error: 'M12 8v5M12 16.5v.5M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
  warning: 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
  info: 'M12 16v-4M12 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
}

function ToastIcon({ type }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={ICONS[type] ?? ICONS.info} />
    </svg>
  )
}

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const dismiss = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
  }, [])

  const push = useCallback(
    (toast) => {
      const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`
      setToasts((current) => [...current, { id, type: 'info', ...toast }])

      // Los errores quedan más tiempo porque suelen requerir lectura.
      window.setTimeout(() => dismiss(id), toast.type === 'error' ? 7000 : 4000)
    },
    [dismiss],
  )

  const value = useMemo(
    () => ({
      success: (title, text) => push({ type: 'success', title, text }),
      error: (title, text) => push({ type: 'error', title, text }),
      warning: (title, text) => push({ type: 'warning', title, text }),
      info: (title, text) => push({ type: 'info', title, text }),
    }),
    [push],
  )

  return (
    <ToastContext.Provider value={value}>
      {children}

      <div className="toast-stack" role="region" aria-label="Notificaciones">
        {toasts.map((toast) => (
          <div key={toast.id} className={`toast toast--${toast.type}`} role="status">
            <span className="toast-icon">
              <ToastIcon type={toast.type} />
            </span>
            <div className="toast-body">
              {toast.title ? <strong className="toast-title">{toast.title}</strong> : null}
              {toast.text ? <span className="toast-text">{toast.text}</span> : null}
            </div>
            <button className="toast-close" type="button" onClick={() => dismiss(toast.id)} aria-label="Cerrar aviso">
              ×
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}

export function useToast() {
  const context = useContext(ToastContext)

  if (!context) {
    throw new Error('useToast debe usarse dentro de <ToastProvider>.')
  }

  return context
}
