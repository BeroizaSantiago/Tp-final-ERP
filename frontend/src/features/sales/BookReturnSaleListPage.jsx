
import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { dateTime, normalizePaginator, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'

const STATUS_LABELS = {
  pending: 'Pendiente',
  approved: 'Aprobada',
  rejected: 'Rechazada',
  completed: 'Completada',
}

const STATUS_TONES = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
  completed: 'secondary',
}

export function BookReturnSaleListPage() {
  const toast = useToast()
  const [searchParams, setSearchParams] = useSearchParams()

  const [search, setSearch] = useState(searchParams.get('search') ?? '')
  const status = searchParams.get('status') ?? ''

  useEffect(() => {
  const timer = window.setTimeout(() => {
    const value = search.trim()
    const currentValue = searchParams.get('search') ?? ''

    // Evita actualizar la URL si el valor no cambió.
    if (value === currentValue) return

    const next = new URLSearchParams(searchParams)

    if (value) {
      next.set('search', value)
    } else {
      next.delete('search')
    }

    next.delete('page')
    setSearchParams(next, { replace: true })
  }, 300)

  return () => window.clearTimeout(timer)
}, [search, searchParams, setSearchParams])

  const path = useMemo(() => {
    const params = new URLSearchParams()
    params.set('per_page', '20')

    const query = searchParams.get('search')
    const page = searchParams.get('page')

    if (query) params.set('search', query)
    if (status) params.set('status', status)
    if (page) params.set('page', page)

    return `/customer-book-returns?${params.toString()}`
  }, [searchParams, status])

  const { data, loading, error, reload } = useApiResource(path)

  const { items, meta } = useMemo(
    () => normalizePaginator(data),
    [data],
  )

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)

    if (value) {
      next.set(key, value)
    } else {
      next.delete(key)
    }

    next.delete('page')
    setSearchParams(next, { replace: true })
  }

  async function handleRefresh() {
    try {
      await reload()
    } catch (error) {
      toast.error(
        'Error',
        error instanceof ApiError
          ? error.message
          : 'No se pudo actualizar el listado.',
      )
    }
  }

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Devoluciones de clientes</h1>
          <p className="page-subtitle">
            Devoluciones de libros de clientes registradas en el sistema.
          </p>
        </div>

        <div className="page-actions">
          <button
            className="erp-btn erp-btn-outline erp-btn--icon"
            type="button"
            onClick={handleRefresh}
            disabled={loading}
            title="Actualizar"
            aria-label="Actualizar el listado"
          >
            <Icon name="refresh" size={18} />
          </button>

          <Link
            className="erp-btn erp-btn-primary"
            to="/devoluciones-ventas/nueva"
          >
            <Icon name="add" size={18} />
            Nueva devolución
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">
            Listado de devoluciones
          </h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar por factura, cliente o motivo..."
              aria-label="Buscar devolución"
            />
          </div>
        </div>

        <div className="erp-card-head erp-card-head--wrap">
          <label className="erp-field-block">
            <span className="erp-label">Filtrar por estado</span>
            <select
              className="erp-control erp-select"
              value={status}
              onChange={(event) =>
                updateParam('status', event.target.value)
              }
            >
              <option value="">Todos los estados</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Aprobada</option>
              <option value="rejected">Rechazada</option>
              <option value="completed">Completada</option>
            </select>
          </label>
        </div>

        <DataTable
          columns={[
            {
              key: 'return',
              header: 'Devolución',
              render: (item) => (
                <div>
                  <strong>Devolución #{item.id}</strong>
                  <br />
                  <small>
                    {item.return_date
                      ? dateTime(item.return_date)
                      : 'Sin fecha'}
                  </small>
                </div>
              ),
            },
            {
              key: 'invoice',
              header: 'Factura de venta',
              render: (item) => {
                const invoiceNumber =
                  item.full_number ??
                  item.invoice?.full_number ??
                  item.invoice?.display_number ??
                  (item.invoice_id
                    ? `Factura #${item.invoice_id}`
                    : '—')

                return (
                  <div>
                    <strong>{invoiceNumber}</strong>
                    <br />
                    <small>
                      {item.receipt_type_name ??
                        item.invoice?.receipt_type_name ??
                        ''}
                    </small>
                  </div>
                )
              },
            },
            {
              key: 'client',
              header: 'Cliente',
              render: (item) => (
                <strong>
                  {item.client?.name ??
                    item.client_name ??
                    item.invoice?.client?.name ??
                    '—'}
                </strong>
              ),
            },
            {
              key: 'items',
              header: 'Libros devueltos',
              render: (item) => {
                const returnItems = item.items ?? []
                const quantity = returnItems.reduce(
                  (sum, returnItem) =>
                    sum + Number(returnItem.quantity ?? 0),
                  0,
                )

                return (
                  <div>
                    <strong>
                      {number(returnItems.length)} producto(s)
                    </strong>
                    <br />
                    <small>
                      {number(quantity)} unidad(es)
                    </small>
                  </div>
                )
              },
            },
            {
              key: 'reason',
              header: 'Motivo',
              render: (item) => item.reason || '—',
            },
            {
              key: 'status',
              header: 'Estado',
              align: 'center',
              render: (item) => (
                <Badge tone={STATUS_TONES[item.status] ?? 'secondary'}>
                  {STATUS_LABELS[item.status] ?? item.status ?? '—'}
                </Badge>
              ),
            },
            {
              key: 'actions',
              header: 'Acciones',
              align: 'end',
              render: (item) => (
                <div className="row-actions">
                  <Link
                    className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
                    to={`/devoluciones-cliente/${item.id}`}
                    title="Ver detalle"
                    aria-label={`Ver devolución #${item.id}`}
                  >
                    <Icon name="settings" size={16} />
                  </Link>

                  <Link
                    className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
                    to={`/devoluciones-cliente/${item.id}/editar`}
                    title="Editar estado"
                    aria-label={`Editar estado de devolución #${item.id}`}
                  >
                    <Icon name="edit" size={16} />
                  </Link>
                </div>
              ),
            },
          ]}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay devoluciones de clientes para mostrar."
          footer={<Pagination meta={meta} onChange={goToPage} />}
        />
      </div>
    </>
  )

  function goToPage(value) {
    const next = new URLSearchParams(searchParams)
    next.set('page', String(value))
    setSearchParams(next, { replace: true })
  }
}
