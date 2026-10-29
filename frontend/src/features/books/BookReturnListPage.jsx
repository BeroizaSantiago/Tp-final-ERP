import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'

import { ActiveBadge, StockBadge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { useMasterOptions } from '../masters/useMasterOptions'
import { listBookReturns } from './api'

export function BookReturnListPage() {
  const toast = useToast()
  const confirm = useConfirm()
  const [searchParams, setSearchParams] = useSearchParams()

  const [search, setSearch] = useState(searchParams.get('search') ?? '')
  const returnType = searchParams.get('return_type') ?? 'client'
  const status = searchParams.get('status') ?? ''

  const { options: clients } = useMasterOptions('clients') || []
  const { options: providers } = useMasterOptions('providers') || []

  // Búsqueda con rebote de 300 ms.
  useEffect(() => {
    const timer = window.setTimeout(() => {
      const next = new URLSearchParams(searchParams)
      const value = search.trim()

      if (value) {
        next.set('search', value)
      } else {
        next.delete('search')
      }

      next.delete('page')
      setSearchParams(next, { replace: true })
    }, 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const path = useMemo(() => {
    const params = new URLSearchParams({
      returnType,
      per_page: '20',
    })
    const query = searchParams.get('search')

    if (query) {
      params.set('search', query)
    }

    if (status) {
      params.set('status', status)
    }

    return `/book-returns?${params.toString()}`
  }, [returnType, status, searchParams])

  const { data, loading, error, reload } = useApiResource(path)

  const { items, meta } = useMemo(() => normalizePaginator(data), [data])

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

  const goToPage = (value) => {
    const next = new URLSearchParams(searchParams)
    next.set('page', String(value))
    setSearchParams(next, { replace: true })
  }

  const hasFilters = Boolean(searchParams.get('search') || returnType || status)

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Devoluciones de Libros</h1>
          <p className="page-subtitle">
            Devoluciones a {' '}
            <span className="text-primary">
              {returnType === 'client' ? 'clientes' : 'proveedores'}
            </span>
          </p>
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
          <Link className="erp-btn erp-btn-primary" to={`/devoluciones-libros/nuevo?return_type=${returnType}`}>
            <Icon name="add" size={18} />
            Nueva devolución
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado de devoluciones</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar devolución..."
              aria-label="Buscar devolución"
            />
          </div>
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
                  <small>{item.return_type === 'client' ? 'A cliente' : 'A proveedor'}</small>
                </div>
              ),
            },
            {
              key: 'product',
              header: 'Producto',
              render: (item) => {
                const productName = item.returnable?.name ?? 'Producto sin identificar'
                return (
                  <div>
                    <strong>{productName}</strong>
                    <br />
                    <small>Cantidad: {item.quantity} libro(s)</small>
                  </div>
                )
              },
            },
            {
              key: 'clientProvider',
              header: 'Cliente / Proveedor',
              render: (item) => {
                if (item.return_type === 'client' && item.client?.name) {
                  return (
                    <div>
                      <strong>{item.client.name}</strong>
                      <br />
                      <small>{item.client.document_number || '—'}</small>
                    </div>
                  )
                }
                if (item.return_type === 'supplier' && item.provider?.name) {
                  return (
                    <div>
                      <strong>{item.provider.name}</strong>
                      <br />
                      <small>
                        {item.provider.document_type?.toUpperCase()} {item.provider.identification_number || '—'}
                      </small>
                    </div>
                  )
                }
                return '—'
              },
            },
            {
              key: 'status',
              header: 'Estado',
              align: 'center',
              render: (item) => {
                const tones = {
                  pending: 'warning',
                  approved: 'success',
                  rejected: 'danger',
                  completed: 'secondary',
                }
                const tone = tones[item.status] ?? 'secondary'
                return <Badge tone={tone}>{item.status?.toUpperCase() ?? '—'}</Badge>
              },
            },
            {
              key: 'details',
              header: 'Detalles',
              render: (item) => (
                <div>
                  <small>Motivo: {item.reason || '—'}</small>
                  <br />
                  <small>Condición: {item.condition || '—'}</small>
                </div>
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
                    to={`/devoluciones-libros/${item.id}`}
                    title="Ver detalles"
                    aria-label={`Ver devolución #${item.id}`}
                  >
                    <Icon name="eye" size={16} />
                  </Link>
                  <Link
                    className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
                    to={`/devoluciones-libros/${item.id}/editar`}
                    title="Editar"
                    aria-label={`Editar devolución #${item.id}`}
                  >
                    <Icon name="edit" size={16} />
                  </Link>
                  <button
                    className={`erp-btn erp-btn--icon erp-btn--sm ${item.status === 'pending' ? 'erp-btn-outline-warning' : 'erp-btn-outline'}`}
                    type="button"
                    onClick={() => {
                      // Handler would be implemented
                    }}
                    title={item.status === 'pending' ? 'Cambiar estado' : 'Estado actualizado'}
                    aria-label={
                      item.status === 'pending'
                        ? 'Cambiar estado de la devolución'
                        : 'Estado ya actualizado'
                    }
                  >
                    <Icon
                      name={
                        item.status === 'pending'
                          ? 'clock'
                          : item.status === 'approved'
                            ? 'checkCircle'
                            : 'times'
                        }
                      size={16}
                    />
                  </button>
                </div>
              ),
            },
          ]}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay devoluciones para mostrar"
          footer={<Pagination meta={meta} onChange={goToPage} />}
        />
      </div>
    </>
  )
}