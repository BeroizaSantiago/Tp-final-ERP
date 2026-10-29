import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { BackLink } from '../../components/fields'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { dateTime, money, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { updateSupplierBookReturnStatus } from './api'

const STATUS_OPTIONS = [
  { value: 'pending', label: 'Pendiente' },
  { value: 'approved', label: 'Aprobada' },
  { value: 'rejected', label: 'Rechazada' },
  { value: 'completed', label: 'Completada' },
]

const STATUS_TONES = {
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
  completed: 'secondary',
}

const STATUS_LABELS = Object.fromEntries(
  STATUS_OPTIONS.map((option) => [option.value, option.label]),
)

function Detail({ label, value }) {
  return (
    <div className="detail-item">
      <dt className="detail-label">{label}</dt>
      <dd className="detail-value">{value ?? '—'}</dd>
    </div>
  )
}

function unwrap(response) {
  return response?.data?.data ?? response?.data ?? response
}

export function BookReturnPurchaseDetailPage() {
  const { id } = useParams()
  const toast = useToast()

  const {
    data,
    loading,
    error,
    reload,
  } = useApiResource(`/supplier-book-returns/${id}`, { deps: [id] })

  const result = unwrap(data)
  const returnData = result?.data ?? result

  const [status, setStatus] = useState('pending')
  const [savingStatus, setSavingStatus] = useState(false)

  useEffect(() => {
    if (returnData?.status) {
      setStatus(returnData.status)
    }
  }, [returnData?.status])

  async function handleStatusUpdate(event) {
    event.preventDefault()

    if (!returnData?.id || !status) return

    setSavingStatus(true)

    try {
      const response = await updateSupplierBookReturnStatus(
        returnData.id,
        status,
      )

      toast.success(
        'Estado actualizado',
        response?.message ?? 'Se actualizó el estado de la devolución.',
      )

      await reload()
    } catch (error) {
      toast.error(
        'No se pudo actualizar el estado',
        error instanceof ApiError
          ? error.message
          : 'Ocurrió un error al actualizar la devolución.',
      )
    } finally {
      setSavingStatus(false)
    }
  }

  if (loading) {
    return (
      <div className="table-state">
        <span
          className="app-loader-spinner app-loader-spinner--sm"
          aria-hidden="true"
        />
        <span>Cargando devolución…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to="/devoluciones-compras">
          Volver a devoluciones
        </BackLink>
        <div className="erp-alert" role="alert">
          {error.message}
        </div>
      </>
    )
  }

  if (!returnData?.id) {
    return (
      <>
        <BackLink to="/devoluciones-compras">
          Volver a devoluciones
        </BackLink>
        <div className="erp-alert" role="alert">
          No se encontró la devolución solicitada.
        </div>
      </>
    )
  }

  const items = returnData.items ?? []
  const purchase = returnData.purchase ?? {}
  const provider = returnData.provider ?? purchase.provider ?? {}

  const totalQuantity = items.reduce(
    (sum, item) => sum + Number(item.quantity ?? 0),
    0,
  )

  const purchaseNumber =
    purchase.full_number ??
    purchase.display_number ??
    returnData.full_number ??
    (returnData.purchase_id ? `Compra #${returnData.purchase_id}` : '—')

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to="/devoluciones-compras">
            Volver a devoluciones
          </BackLink>

          <h1 className="page-title">
            Devolución #{returnData.id}
          </h1>

          <p className="page-subtitle">
            Detalle de la devolución a proveedor
          </p>
        </div>

        <div className="page-actions">
          <Badge tone={STATUS_TONES[returnData.status] ?? 'secondary'}>
            {STATUS_LABELS[returnData.status] ?? returnData.status ?? 'Sin estado'}
          </Badge>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Datos de la devolución</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail
            label="Número de devolución"
            value={`#${returnData.id}`}
          />
          <Detail
            label="Fecha de devolución"
            value={returnData.return_date
              ? dateTime(returnData.return_date)
              : '—'}
          />
          <Detail
            label="Proveedor"
            value={provider.name ?? returnData.provider_name}
          />
          <Detail
            label="Comprobante de compra"
            value={returnData.receipt_type_name + ' ' + purchaseNumber}
          />
          <Detail
            label="Estado"
            value={STATUS_LABELS[returnData.status] ?? returnData.status}
          />
          <Detail
            label="Motivo"
            value={returnData.reason || '—'}
          />
        </dl>

        {purchase.id && (
          <div className="detail-footer">
            <Link
              className="erp-btn erp-btn-outline"
              to={`/compras/${purchase.id}`}
            >
              Ver compra original
            </Link>
          </div>
        )}
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Libros devueltos</h2>
        </div>

        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th>Libro</th>
                <th>Cantidad</th>
                <th>Condición</th>
                <th>Observaciones</th>
              </tr>
            </thead>

            <tbody>
              {items.length > 0 ? (
                items.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <strong>
                        {item.product_name ??
                          item.product?.name ??
                          item.purchase_item?.product_name ??
                          'Libro sin nombre'}
                      </strong>
                    </td>
                    <td >
                      {number(item.quantity)}
                    </td>
                    <td>
                      {item.condition === 'sellable'
                        ? 'Vendible'
                        : item.condition === 'damaged'
                          ? 'Dañado'
                          : item.condition === 'review'
                            ? 'Requiere revisión'
                            : item.condition ?? '—'}
                    </td>
                    <td>{item.notes || '—'}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4}>
                    No hay libros registrados en esta devolución.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Cantidad de libros" value={number(items.length)} />
          <Detail label="Unidades devueltas" value={number(totalQuantity)} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Cambiar estado</h2>
        </div>

        <form className="return-status-form" onSubmit={handleStatusUpdate}>
          <div className="form-grid">
            <div className="erp-field-block">
              <label className="erp-label" htmlFor="return-status">
                Estado de la devolución
              </label>

              <select
                id="return-status"
                className="erp-control erp-select"
                value={status}
                onChange={(event) => setStatus(event.target.value)}
                disabled={savingStatus}
                required
              >
                {STATUS_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="form-actions">
            <button
              className="erp-btn erp-btn-primary"
              type="submit"
              disabled={savingStatus || status === returnData.status}
            >
              {savingStatus ? 'Guardando…' : 'Guardar estado'}
            </button>
          </div>
        </form>
      </div>

      <div className="detail-footer">
        <Link
          className="erp-btn erp-btn-outline"
          to="/devoluciones-compras"
        >
          Volver al listado
        </Link>
      </div>
    </>
  )
}
