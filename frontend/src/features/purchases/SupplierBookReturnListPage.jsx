import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'

import { ActiveBadge, StockBadge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { useMasterOptions } from '../masters/useMasterOptions'
import { listSupplierBookReturns, getSupplierBookReturn, updateSupplierBookReturnStatus } from './api'

/** Estado de devolución para badges */
function returnStatusBadge(status) {
  const tones = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    completed: 'secondary',
  }
  const tone = tones[status] ?? 'secondary'
  return <Badge tone={tone}>{status?.toUpperCase() ?? '—'}</Badge>
}

/** Formatea la cantidad de libros devueltos. */
function formatQuantity(quantity) {
  return quantity ? `${quantity} ${quantity === 1 ? 'libro' : 'libros'}` : '—'
}

export function SupplierBookReturnListPage() {
  const toast = useToast()
  const confirm = useConfirm()
  const [searchParams, setSearchParams] = useSearchParams()

  const [search, setSearch] = useState(searchParams.get('search') ?? '')
  const status = searchParams.get('status') ?? ''

  const { options: productStatus } = useMasterOptions('product-status') || [
    { value: '', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'approved', label: 'Aprobado' },
    { value: 'rejected', label: 'Rechazado' },
    { value: 'completed', label: 'Completado' },
  ]

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
      return_type: 'supplier',
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
  }, [status, searchParams])

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

  const handleToggleStatus = async (returnItem) => {
    const nextStatus =
      returnItem.status === 'pending' ? 'approved' :
      returnItem.status === 'approved' ? 'rejected' :
      'pending'

    const confirmed = await confirm({
      title: 'Cambiar estado de devolución',
      message: `¿Confirmás que querés cambiar el estado a "${nextStatus}" de esta devolución a proveedor?`,
      confirmText: 'Sí, cambiar',
      tone: 'primary',
    })

    if (!confirmed) {
      return
    }

    try {
      const response = await updateSupplierBookReturnStatus(returnItem.id, nextStatus)
      toast.success(
        `Estado actualizado a ${nextStatus}`,
        response.message,
      )
      reload()
    } catch (err) {
      toast.error(
        'No se pudo actualizar',
        err instanceof ApiError ? err.message : 'Ocurrió un error inesperado.',
      )
    }
  }

  const filteredItems = items.filter((item) => {
    if (item.return_type !== 'supplier') {
      return false
    }
    return true
  })

  const hasFilters = Boolean(searchParams.get('search') || status)

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Devoluciones a Proveedores</h1>
          <p className="page-subtitle">
            Devoluciones de libros a proveedores registradas en el sistema
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
          <Link
            className="erp-btn erp-btn-primary"
            to="/devoluciones-proveedor/nuevo"
          >
            <Icon name="add" size={18} />
            Nueva devolución a proveedor
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado de devoluciones a proveedores</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar razón, condición o ID..."
              aria-label="Buscar devolución a proveedor"
            />
          </div>
        </div>

        <div className="erp-filters">
          <select
            className="erp-control erp-select erp-filter"
            value={status}
            onChange={(event) => updateParam('status', event.target.value)}
            aria-label="Filtrar por estado"
          >
            <option value="">Todos los estados</option>
            {productStatus.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>

          {hasFilters ? (
            <button
              className="erp-btn erp-btn-ghost erp-btn--sm"
              type="button"
              onClick={() => {
                setSearch('')
                setSearchParams(new URLSearchParams(), { replace: true })
              }}
            >
              <Icon name="close" size={15} />
              Limpiar filtros
            </button>
          ) : null}
        </div>

        <DataTable
          columns={[
            {
              key: 'return',
              header: 'Devolución',
              render: (item) => (
                <div className="cell-media">
                  <span className="cell-title">
                    {`Devolución #${item.id}`}
                  </span>
                  <small className="cell-subtitle">
                    Tipo: {item.return_type === 'supplier' ? 'Proveedor' : 'Cliente'}
                  </small>
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
                    <small className="cell-subtitle">
                      Cantidad: {formatQuantity(item.quantity)}
                    </small>
                  </div>
                )
              },
            },
            {
              key: 'provider',
              header: 'Proveedor',
              render: (item) => {
                if (item.provider?.name) {
                  return (
                    <div>
                      <strong>{item.provider.name}</strong>
                      <small className="cell-subtitle">
                        {item.provider.document_type?.toUpperCase() +
                          ' ' +
                          (item.provider.identification_number || '—')}
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
              render: (item) => returnStatusBadge(item.status),
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
              render: (item) => {
                return (
                  <div className="row-actions">
                    <Link
                      className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
                      to={`/devoluciones-proveedor/${item.id}`
                      }
                      title="Ver detalles"
                      aria-label={`Ver detalles de devolución #${item.id}`}
                    >
                      <Icon name="eye" size={16} />
                    </Link>
                    <Link
                      className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
                      to={`/devoluciones-proveedor/${item.id}/editar`}
                      title="Editar"
                      aria-label={`Editar devolución #${item.id}`}
                    >
                      <Icon name="edit" size={16} />
                    </Link>
                    <button
                      className={`erp-btn erp-btn--icon erp-btn--sm ${item.status === 'pending' ? 'erp-btn-outline-warning' : 'erp-btn-outline'}`}
                      type="button"
                      onClick={() => handleToggleStatus(item)}
                      title={
                        item.status === 'pending'
                          ? 'Cambiar estado'
                          : 'Estado actualizado'
                      }
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
                )
              },
            },
          ]}
          rows={filteredItems}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay devoluciones a proveedor para mostrar"
          footer={<Pagination meta={meta} onChange={goToPage} />}
        />
      </div>
    </>
  )
}