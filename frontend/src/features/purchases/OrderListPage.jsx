import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { DataTable } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { dateTime, money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { statusTone } from './api'

/** Listado de órdenes de compra, réplica de `purchase-orders/index.blade.php`. */
export function OrderListPage() {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')

  useEffect(() => {
    const timer = window.setTimeout(() => setQuery(search.trim().toLowerCase()), 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const { data, loading, error, reload } = useApiResource('/purchase-orders')
  const { items } = useMemo(() => normalizePaginator(data), [data])

  // El listado Blade filtra en el cliente por nro, proveedor y estado.
  const visible = useMemo(() => {
    if (!query) {
      return items
    }

    return items.filter(
      (o) =>
        String(o.order_number ?? '').toLowerCase().includes(query) ||
        String(o.provider_name ?? o.provider?.name ?? '').toLowerCase().includes(query) ||
        String(o.status_name ?? '').toLowerCase().includes(query),
    )
  }, [items, query])

  const columns = [
    { key: 'issue_date', header: 'Fecha', render: (o) => dateTime(o.issue_date) },
    { key: 'order_number', header: 'Nro. comprobante', render: (o) => <strong>{o.order_number ?? '—'}</strong> },
    { key: 'provider', header: 'Proveedor', render: (o) => o.provider_name ?? o.provider?.name ?? '—' },
    { key: 'currency_name', header: 'Moneda', render: (o) => o.currency_name ?? 'Pesos' },
    { key: 'total_amount', header: 'Total', align: 'end', render: (o) => money(o.total_amount) },
    { key: 'status_name', header: 'Estado', render: (o) => <Badge tone={statusTone(o.status_name)}>{o.status_name ?? 'Pendiente'}</Badge> },
    { key: 'created_by', header: 'Creado por', render: (o) => o.created_by ?? '—' },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (o) => (
        <Link className="erp-btn erp-btn-outline erp-btn--sm" to={`/ordenes/${o.id}`}>
          Ver
        </Link>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Órdenes de compra</h1>
          <p className="page-subtitle">Solicitudes emitidas a proveedores</p>
        </div>

        <div className="page-actions">
          <button
            className="erp-btn erp-btn-outline erp-btn--icon"
            type="button"
            onClick={reload}
            disabled={loading}
            title="Actualizar"
            aria-label="Actualizar el listado"
          >
            <Icon name="refresh" size={18} />
          </button>
          <Link className="erp-btn erp-btn-primary" to="/ordenes/nueva">
            <Icon name="add" size={18} />
            Nueva orden
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar orden…"
              aria-label="Buscar orden"
            />
          </div>
        </div>

        <DataTable
          columns={columns}
          rows={visible}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay órdenes de compra registradas"
        />
      </div>
    </>
  )
}
