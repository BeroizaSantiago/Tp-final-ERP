import { useNavigate, useParams } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { BackLink } from '../../components/fields'
import { dateTime, money, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { statusTone } from './api'

function Detail({ label, value, wide }) {
  return (
    <div className={`detail-item${wide ? ' detail-item--wide' : ''}`}>
      <dt className="detail-label">{label}</dt>
      <dd className="detail-value">{value ?? '—'}</dd>
    </div>
  )
}

/** Detalle de la orden de compra, réplica de `purchase-orders/show.blade.php`. */
export function OrderDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()

  const { data: order, loading, error } = useApiResource(`/purchase-orders/${id}`, { deps: [id] })

  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando orden…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to="/ordenes">Volver a órdenes</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  const items = order.items ?? []
  const totalQuantity = items.reduce((sum, item) => sum + Number(item.quantity ?? 0), 0)

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to="/ordenes">Volver a órdenes</BackLink>
          <h1 className="page-title">
            Orden <Badge tone="primary">{order.order_number}</Badge>
          </h1>
          <p className="page-subtitle">Detalle de la orden de compra</p>
        </div>

        <div className="page-actions">
          <Badge tone={statusTone(order.status_name)}>{order.status_name ?? 'Pendiente'}</Badge>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Datos de la orden</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Proveedor" value={order.provider_name ?? order.provider?.name} />
          <Detail label="Número" value={order.order_number} />
          <Detail label="Fecha" value={dateTime(order.issue_date)} />
          <Detail label="Moneda" value={order.currency_name ?? 'Pesos'} />
          <Detail label="Creado por" value={order.created_by} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Productos solicitados</h2>
        </div>

        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th className="is-end">Cantidad</th>
                <th className="is-end">Precio unitario</th>
                <th className="is-end">Total</th>
              </tr>
            </thead>
            <tbody>
              {items.length ? (
                items.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <strong>{item.description ?? item.product?.name ?? '—'}</strong>
                    </td>
                    <td className="is-end">{number(item.quantity)}</td>
                    <td className="is-end">{money(item.unit_price)}</td>
                    <td className="is-end"><strong>{money(item.total_amount)}</strong></td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4}>
                    <div className="table-state">
                      <strong>La orden no tiene productos registrados.</strong>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        <p className="detail-text" style={{ textAlign: 'right', marginTop: '1rem' }}>
          {number(totalQuantity)} unidades · <strong>{money(order.total_amount)}</strong>
        </p>
      </div>

      {order.notes ? (
        <div className="erp-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">Observaciones</h2>
          </div>
          <p className="detail-text">{order.notes}</p>
        </div>
      ) : null}

      <div className="detail-footer">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/ordenes')}>
          Volver al listado
        </button>
      </div>
    </>
  )
}
