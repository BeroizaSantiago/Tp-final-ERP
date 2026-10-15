import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { DataTable } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { dateTime, money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { statusTone } from './api'

export function SaleListPage() {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')

  useEffect(() => {
    const timer = window.setTimeout(() => setQuery(search.trim()), 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const path = useMemo(() => {
    const params = query ? `?search=${encodeURIComponent(query)}` : ''

    return `/invoices${params}`
  }, [query])

  const { data, loading, error, reload } = useApiResource(path)
  const { items } = useMemo(() => normalizePaginator(data), [data])

  const columns = [
    { key: 'issue_date', header: 'Fecha', render: (p) => dateTime(p.issue_date) },
    { key: 'full_number', header: 'Comprobante', 
      render: (p) => (<div><strong>{p.full_number ?? '—'}</strong><br></br><small class="text-muted">{p.receipt_type_name ?? '—'}</small></div>),},
    { key: 'receipt_type_name', header: 'Cliente', render: (p) => p.customer_name ?? '—' },
    { key: 'currency_name', header: 'Moneda', render: (p) => p.currency_name ?? 'Pesos' },
    { key: 'total_amount', header: 'Total', align: 'end', render: (p) => money(p.total_amount) },
    {
      key: 'status_name',
      header: 'Estado',
      render: (p) => <Badge tone={statusTone(p.status_name)}>{p.status_name ?? 'Pendiente'}</Badge>,
    },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (p) => (
        <Link className="erp-btn erp-btn-outline erp-btn--sm" to={`/ventas/${p.id}`}>
          Ver
        </Link>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Ventas</h1>
          <p className="page-subtitle">Comprobantes emitidos a clientes</p>
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
          <Link className="erp-btn erp-btn-primary" to="/ventas/nueva">
            <Icon name="add" size={18} />
            Nueva Venta
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado de ventas</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar venta…"
              aria-label="Buscar venta"
            />
          </div>
        </div>

        <DataTable
          columns={columns}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay ventas registradas"
        />
      </div>
    </>
  )
}
