import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { DataTable } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { dateTime, money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { statusTone } from './api'

/** Listado de gastos varios, réplica de `misc-expenses/index.blade.php`. */
export function ExpenseListPage() {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')

  useEffect(() => {
    const timer = window.setTimeout(() => setQuery(search.trim().toLowerCase()), 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const { data, loading, error, reload } = useApiResource('/misc-expenses')
  const { items } = useMemo(() => normalizePaginator(data), [data])

  const visible = useMemo(() => {
    if (!query) {
      return items
    }

    return items.filter(
      (e) =>
        String(e.receipt_number ?? '').toLowerCase().includes(query) ||
        String(e.provider_name ?? e.provider?.name ?? '').toLowerCase().includes(query) ||
        String(e.receipt_type_name ?? '').toLowerCase().includes(query) ||
        String(e.status_name ?? '').toLowerCase().includes(query),
    )
  }, [items, query])

  const columns = [
    { key: 'issue_date', header: 'Fecha', render: (e) => dateTime(e.issue_date) },
    { key: 'receipt_type_name', header: 'Tipo comprobante', render: (e) => e.receipt_type_name ?? '—' },
    { key: 'receipt_number', header: 'Nro. comprobante', render: (e) => <strong>{e.receipt_number ?? '—'}</strong> },
    { key: 'provider', header: 'Proveedor', render: (e) => e.provider_name ?? e.provider?.name ?? '—' },
    { key: 'expense_type', header: 'Tipo gasto', render: (e) => e.expense_type?.name ?? '—' },
    { key: 'total_amount', header: 'Total', align: 'end', render: (e) => money(e.total_amount) },
    { key: 'status_name', header: 'Estado', render: (e) => <Badge tone={statusTone(e.status_name ?? 'Registrado')}>{e.status_name ?? 'Registrado'}</Badge> },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (e) => (
        <Link className="erp-btn erp-btn-outline erp-btn--sm" to={`/gastos/${e.id}`}>
          Ver
        </Link>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Gastos varios</h1>
          <p className="page-subtitle">Gastos registrados por comprobante</p>
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
          <Link className="erp-btn erp-btn-primary" to="/gastos/nuevo">
            <Icon name="add" size={18} />
            Nuevo gasto
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
              placeholder="Buscar gasto…"
              aria-label="Buscar gasto"
            />
          </div>
        </div>

        <DataTable
          columns={columns}
          rows={visible}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay gastos registrados"
        />
      </div>
    </>
  )
}
