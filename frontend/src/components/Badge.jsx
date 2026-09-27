/**
 * Badge de estado.
 *
 * Replica las clases `bg-label-*` del template Blade (primary, success, warning,
 * danger, secondary) con los mismos meanings semánticos.
 */
export function Badge({ tone = 'secondary', children, className = '' }) {
  return <span className={`erp-badge erp-badge--${tone} ${className}`.trim()}>{children}</span>
}

/**
 * Badge de stock.
 *
 * Mismo criterio que usaba el listado Blade: sin stock en rojo, stock bajo en
 * amarillo y por encima en verde.
 */
export function StockBadge({ value }) {
  const stock = Number(value ?? 0)

  if (stock <= 0) {
    return <Badge tone="danger">Sin stock</Badge>
  }

  if (stock <= 3) {
    return <Badge tone="warning">{stock}</Badge>
  }

  return <Badge tone="success">{stock}</Badge>
}

export function ActiveBadge({ active }) {
  return active ? <Badge tone="success">Activo</Badge> : <Badge tone="danger">Inhabilitado</Badge>
}

/** Estado de carga o de error dentro de una tarjeta o tabla. */
export function StateMessage({ loading, error, empty, emptyText, onRetry, children }) {
  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando…</span>
      </div>
    )
  }

  if (error) {
    return (
      <div className="table-state table-state--error" role="alert">
        <strong>No se pudo cargar la información</strong>
        <p>{error.message}</p>
        {onRetry ? (
          <button className="erp-btn erp-btn-outline erp-btn--sm" type="button" onClick={onRetry}>
            Reintentar
          </button>
        ) : null}
      </div>
    )
  }

  if (empty) {
    return (
      <div className="table-state">
        <strong>{emptyText ?? 'Sin resultados'}</strong>
      </div>
    )
  }

  return children
}
