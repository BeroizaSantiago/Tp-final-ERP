import { createContext, useCallback, useContext, useRef, useState } from 'react'

/**
 * Diálogos de confirmación.
 *
 * Sustituye a `window.erpConfirm` del backend. En vez de `window.confirm` (que
 * bloquea y no se puede estilizar) se expone una promesa que resuelve con true
 * o false, de modo que las acciones quedan igual de legibles.
 */

const ConfirmContext = createContext(null)

export function ConfirmProvider({ children }) {
  const [request, setRequest] = useState(null)
  const resolverRef = useRef(null)

  const confirm = useCallback((options) => {
    setRequest({
      title: 'Confirmar',
      message: '',
      confirmText: 'Aceptar',
      cancelText: 'Cancelar',
      tone: 'primary',
      ...options,
    })

    return new Promise((resolve) => {
      resolverRef.current = resolve
    })
  }, [])

  const settle = useCallback((result) => {
    setRequest(null)
    resolverRef.current?.(result)
    resolverRef.current = null
  }, [])

  return (
    <ConfirmContext.Provider value={confirm}>
      {children}

      {request ? (
        <div className="modal-backdrop-custom" role="presentation" onClick={() => settle(false)}>
          <div
            className="modal-card"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="confirmTitle"
            onClick={(event) => event.stopPropagation()}
          >
            <h3 className="modal-card-title" id="confirmTitle">
              {request.title}
            </h3>
            {request.message ? <p className="modal-card-text">{request.message}</p> : null}

            <div className="modal-card-actions">
              <button className="erp-btn erp-btn-outline" type="button" onClick={() => settle(false)}>
                {request.cancelText}
              </button>
              <button
                className={`erp-btn erp-btn-${request.tone}`}
                type="button"
                onClick={() => settle(true)}
                autoFocus
              >
                {request.confirmText}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </ConfirmContext.Provider>
  )
}

export function useConfirm() {
  const context = useContext(ConfirmContext)

  if (!context) {
    throw new Error('useConfirm debe usarse dentro de <ConfirmProvider>.')
  }

  return context
}
