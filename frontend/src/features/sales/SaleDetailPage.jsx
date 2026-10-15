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

export function SaleDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()

  const { data: sale, loading, error } = useApiResource(`/invoices/${id}`, { deps: [id] })

  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando venta…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to="/ventas">Volver a ventas</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  const items = sale.items ?? []
  const totalQuantity = items.reduce((sum, item) => sum + Number(item.quantity ?? 0), 0)
  const subtotal = Number(
    sale.taxed_amount ??
      sale.subtotal ??
      items.reduce((sum, item) => sum + Number(item.quantity ?? 0) * Number(item.unit_price ?? 0), 0),
  )
  const taxAmount = Number(
    sale.tax_amount ??
      items.reduce((sum, item) => sum + (Number(item.quantity ?? 0) * Number(item.unit_price ?? 0) * Number(item.tax_percentage ?? 0)) / 100, 0),
  )

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to="/ventas">Volver a ventas</BackLink>
          <h1 className="page-title">
            Venta <Badge tone="primary">{sale.full_number ?? `Nº ${sale.id}`}</Badge>
          </h1>
          <p className="page-subtitle">Comprobante interno de venta</p>
        </div>

        <div className="page-actions">
          <Badge tone={statusTone(sale.status_name)}>{sale.status_name ?? 'Sin estado'}</Badge>
          <Link className="erp-btn erp-btn-primary" to={`/ventas/${sale.id}/pago`}>
            <Icon name="money" size={18} />
            Formas de Pago
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Datos del comprobante</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Cliente" value={sale.client_name ?? sale.client?.name} />
          <Detail label="Tipo" value={sale.receipt_type_name} />
          <Detail label="Pto. Venta" value={sale.first_number} />
          <Detail label="Fecha Emisión" value={dateTime(sale.issue_date)} />
          <Detail label="Fecha Vencimiento" value={sale.payment_due_date ? dateTime(sale.payment_due_date) : null} />
          <Detail label="Moneda" value={sale.currency_name ?? 'Pesos'} />
          <Detail label="Condición" value={sale.payment_condition_name} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Resumen</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Productos" value={number(items.length)} />
          <Detail label="Unidades" value={number(totalQuantity)} />
          <Detail label="Total de venta" value={money(sale.total_amount)} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Productos de la venta</h2>
        </div>

        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Producto</th>
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
                      <strong>La venta no tiene productos registrados.</strong>
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
          {Number(sale.non_taxed_amount ?? 0) !== 0 ? (
            <div className="detail-item">
              <dt className="detail-label">No gravado</dt>
              <dd className="detail-value">{money(sale.non_taxed_amount)}</dd>
            </div>
          ) : null}
          {Number(sale.exempt_amount ?? 0) !== 0 ? (
            <div className="detail-item">
              <dt className="detail-label">Exento</dt>
              <dd className="detail-value">{money(sale.exempt_amount)}</dd>
            </div>
          ) : null}
          <div className="detail-item">
            <dt className="detail-label"><strong>Total</strong></dt>
            <dd className="detail-value"><strong>{money(sale.total_amount)}</strong></dd>
          </div>
        </dl>
      </div>

      {sale.notes ? (
        <div className="erp-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">Observaciones</h2>
          </div>
          <p className="detail-text">{sale.notes}</p>
        </div>
      ) : null}

      <div className="detail-footer">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/ventas')}>
          Volver al listado
        </button>
      </div>
    </>
  )
}
