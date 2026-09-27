import { Icon } from './icons'

/**
 * Tabla de datos con paginación.
 *
 * Reemplaza el par `table.table-hover` + `components/api-pagination` de Blade.
 * Las columnas se describen con { key, header, align, render }.
 */
export function DataTable({
  columns,
  rows,
  rowKey = (row) => row.id,
  loading,
  error,
  emptyText = 'No hay registros para mostrar',
  onRetry,
  footer,
}) {
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

  if (!rows.length) {
    return (
      <div className="table-state">
        <strong>{emptyText}</strong>
      </div>
    )
  }

  return (
    <>
      <div className="table-scroll">
        <table className="erp-table">
          <thead>
            <tr>
              {columns.map((column) => (
                <th key={column.key} className={column.align ? `is-${column.align}` : undefined}>
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={rowKey(row)}>
                {columns.map((column) => (
                  <td key={column.key} className={column.align ? `is-${column.align}` : undefined}>
                    {column.render ? column.render(row) : (row[column.key] ?? '—')}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {footer}
    </>
  )
}

/**
 * Paginación.
 *
 * Consume el sobre del paginador de Laravel (current_page, last_page, ...).
 */
export function Pagination({ meta, onChange }) {
  if (!meta || meta.lastPage <= 1) {
    return null
  }

  const { currentPage, lastPage, total, from, to } = meta

  // Ventana de páginas alrededor de la actual.
  const start = Math.max(1, Math.min(currentPage - 2, lastPage - 4))
  const end = Math.min(lastPage, Math.max(currentPage + 2, 5))
  const pages = []

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  return (
    <div className="pagination">
      <p className="pagination-summary">
        {from ?? 0}–{to ?? 0} de {total}
      </p>

      <div className="pagination-controls">
        <button
          className="pagination-button"
          type="button"
          onClick={() => onChange(currentPage - 1)}
          disabled={currentPage <= 1}
          aria-label="Página anterior"
        >
          <Icon name="chevronLeft" size={16} />
        </button>

        {start > 1 ? (
          <>
            <button className="pagination-button" type="button" onClick={() => onChange(1)}>
              1
            </button>
            {start > 2 ? <span className="pagination-gap">…</span> : null}
          </>
        ) : null}

        {pages.map((page) => (
          <button
            key={page}
            type="button"
            className={`pagination-button${page === currentPage ? ' is-active' : ''}`}
            onClick={() => onChange(page)}
            aria-current={page === currentPage ? 'page' : undefined}
          >
            {page}
          </button>
        ))}

        {end < lastPage ? (
          <>
            {end < lastPage - 1 ? <span className="pagination-gap">…</span> : null}
            <button className="pagination-button" type="button" onClick={() => onChange(lastPage)}>
              {lastPage}
            </button>
          </>
        ) : null}

        <button
          className="pagination-button"
          type="button"
          onClick={() => onChange(currentPage + 1)}
          disabled={currentPage >= lastPage}
          aria-label="Página siguiente"
        >
          <Icon name="chevronRight" size={16} />
        </button>
      </div>
    </div>
  )
}
