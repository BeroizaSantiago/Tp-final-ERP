import { Link, useNavigate, useParams } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { BackLink } from '../../components/fields'
import { Icon } from '../../components/icons'
import { dateTime, money, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { statusTone, variantLabel } from './api'

function Detail({ label, value, wide }) {
  return (
    <div className={`detail-item${wide ? ' detail-item--wide' : ''}`}>
      <dt className="detail-label">{label}</dt>
      <dd className="detail-value">{value ?? '—'}</dd>
    </div>
  )
}

/** Detalle del comprobante de compra, réplica de `purchases/show.blade.php`. */
export function PurchaseDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()

  const { data: purchase, loading, error } = useApiResource(`/purchases/${id}`, { deps: [id] })

  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando compra…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to="/compras">Volver a compras</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  const items = purchase.items ?? []
  const totalQuantity = items.reduce((sum, item) => sum + Number(item.quantity ?? 0), 0)
  const subtotal = Number(
    purchase.taxed_amount ??
      purchase.subtotal ??
      items.reduce((sum, item) => sum + Number(item.quantity ?? 0) * Number(item.unit_price ?? 0), 0),
  )
  const taxAmount = Number(
    purchase.tax_amount ??
      items.reduce((sum, item) => sum + (Number(item.quantity ?? 0) * Number(item.unit_price ?? 0) * Number(item.tax_percentage ?? 0)) / 100, 0),
  )

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to="/compras">Volver a compras</BackLink>
          <h1 className="page-title">
            Compra <Badge tone="primary">{purchase.full_number ?? `Nº ${purchase.id}`}</Badge>
          </h1>
          <p className="page-subtitle">Detalle del comprobante de compra</p>
        </div>

        <div className="page-actions">
          <Badge tone={statusTone(purchase.status_name)}>{purchase.status_name ?? 'Sin estado'}</Badge>
          <Link className="erp-btn erp-btn-primary" to={`/compras/${purchase.id}/pago`}>
            <Icon name="money" size={18} />
            Pagos
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Datos del comprobante</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Proveedor" value={purchase.provider_name ?? purchase.provider?.name} />
          <Detail label="Tipo de comprobante" value={purchase.receipt_type_name} />
          <Detail label="Número" value={purchase.full_number} />
          <Detail label="Fecha" value={dateTime(purchase.issue_date)} />
          <Detail label="Moneda" value={purchase.currency_name ?? 'Pesos'} />
          <Detail label="Vencimiento" value={purchase.payment_due_date ? dateTime(purchase.payment_due_date) : null} />
          <Detail label="Sucursal" value={purchase.branch_name} />
          <Detail label="Depósito" value={purchase.warehouse_name} />
          <Detail label="Condición de pago" value={purchase.payment_condition_name} />
          <Detail label="Cotización" value={purchase.exchange_rate ? number(purchase.exchange_rate) : null} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Resumen</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Productos" value={number(items.length)} />
          <Detail label="Unidades" value={number(totalQuantity)} />
          <Detail label="Total de compra" value={money(purchase.total_amount)} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Productos de la compra</h2>
        </div>

        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Variante</th>
                <th>Color</th>
                <th className="is-end">Cantidad</th>
                <th className="is-end">Precio unitario</th>
                <th className="is-end">IVA</th>
                <th className="is-end">Total</th>
              </tr>
            </thead>
            <tbody>
              {items.length ? (
                items.map((item) => (
                  <tr key={item.id}>
                    <td>{item.product_code ?? '—'}</td>
                    <td>
                      <strong>{item.product_name ?? '—'}</strong>
                      {item.description ? <small className="cell-subtitle">{item.description}</small> : null}
                    </td>
                    <td>{item.size_name ?? variantLabel(item.variant)}</td>
                    <td>{item.color_name ?? '—'}</td>
                    <td className="is-end">{number(item.quantity)}</td>
                    <td className="is-end">{money(item.unit_price)}</td>
                    <td className="is-end">{number(item.tax_percentage)}%</td>
                    <td className="is-end"><strong>{money(item.total_amount)}</strong></td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={8}>
                    <div className="table-state">
                      <strong>La compra no tiene productos registrados.</strong>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
        <dl className="erp-card" style={{ minWidth: '20rem', padding: '1rem 1.25rem' }}>
          <div className="detail-item">
            <dt className="detail-label">Subtotal</dt>
            <dd className="detail-value">{money(subtotal)}</dd>
          </div>
          <div className="detail-item">
            <dt className="detail-label">IVA</dt>
            <dd className="detail-value">{money(taxAmount)}</dd>
          </div>
          {Number(purchase.non_taxed_amount ?? 0) !== 0 ? (
            <div className="detail-item">
              <dt className="detail-label">No gravado</dt>
              <dd className="detail-value">{money(purchase.non_taxed_amount)}</dd>
            </div>
          ) : null}
          {Number(purchase.exempt_amount ?? 0) !== 0 ? (
            <div className="detail-item">
              <dt className="detail-label">Exento</dt>
              <dd className="detail-value">{money(purchase.exempt_amount)}</dd>
            </div>
          ) : null}
          <div className="detail-item">
            <dt className="detail-label"><strong>Total</strong></dt>
            <dd className="detail-value"><strong>{money(purchase.total_amount)}</strong></dd>
          </div>
        </dl>
      </div>

      {purchase.notes ? (
        <div className="erp-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">Observaciones</h2>
          </div>
          <p className="detail-text">{purchase.notes}</p>
        </div>
      ) : null}

      <div className="detail-footer">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/compras')}>
          Volver al listado
        </button>
      </div>
    </>
  )
}
