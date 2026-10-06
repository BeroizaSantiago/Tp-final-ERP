import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { DataTable } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { dateTime, money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { statusTone } from './api'

/**
 * Listado de compras.
 *
 * Misma tabla que `purchases/index.blade.php`: búsqueda (número, proveedor,
 * tipo de comprobante y estado) y acceso al detalle de cada comprobante.
 */
export function PurchaseListPage() {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')

  useEffect(() => {
    const timer = window.setTimeout(() => setQuery(search.trim()), 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const path = useMemo(() => {
    const params = query ? `?search=${encodeURIComponent(query)}` : ''

    return `/purchases${params}`
  }, [query])

  const { data, loading, error, reload } = useApiResource(path)
  const { items } = useMemo(() => normalizePaginator(data), [data])

  const columns = [
    { key: 'issue_date', header: 'Fecha', render: (p) => dateTime(p.issue_date) },
    { key: 'receipt_type_name', header: 'Tipo de comprobante', render: (p) => p.receipt_type_name ?? '—' },
    {
      key: 'full_number',
      header: 'Nro. comprobante',
      render: (p) => <strong>{p.full_number ?? '—'}</strong>,
    },
    { key: 'provider', header: 'Proveedor', render: (p) => p.provider_name ?? p.provider?.name ?? '—' },
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
        <Link className="erp-btn erp-btn-outline erp-btn--sm" to={`/compras/${p.id}`}>
          Ver
        </Link>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Compras</h1>
          <p className="page-subtitle">Comprobantes de compra a proveedores</p>
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
          <Link className="erp-btn erp-btn-primary" to="/compras/nueva">
            <Icon name="add" size={18} />
            Nueva compra
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado de compras</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar compra…"
              aria-label="Buscar compra"
            />
          </div>
        </div>

        <DataTable
          columns={columns}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay compras registradas"
        />
      </div>
    </>
  )
}
