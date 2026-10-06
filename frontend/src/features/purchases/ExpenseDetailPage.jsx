import { Link, useNavigate, useParams } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { BackLink } from '../../components/fields'
import { Icon } from '../../components/icons'
import { dateTime, money } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { paymentMethodLabel } from './PaymentMethods'
import { statusTone } from './api'

function Detail({ label, value, wide }) {
  return (
    <div className={`detail-item${wide ? ' detail-item--wide' : ''}`}>
      <dt className="detail-label">{label}</dt>
      <dd className="detail-value">{value ?? '—'}</dd>
    </div>
  )
}

/** Detalle del gasto vario, réplica de `misc-expenses/show.blade.php`. */
export function ExpenseDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()

  const { data: expense, loading, error } = useApiResource(`/misc-expenses/${id}`, { deps: [id] })

  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando gasto…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to="/gastos">Volver a gastos</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  const payments = expense.payments ?? []

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to="/gastos">Volver a gastos</BackLink>
          <h1 className="page-title">
            Gasto vario <Badge tone={statusTone(expense.status_name ?? 'Registrado')}>{expense.status_name ?? 'Registrado'}</Badge>
          </h1>
          <p className="page-subtitle">{expense.receipt_type_name ?? 'Comprobante'} {expense.receipt_number ?? ''}</p>
        </div>

        <div className="page-actions">
          <Link className="erp-btn erp-btn-primary" to={`/gastos/${expense.id}/pago`}>
            <Icon name="money" size={18} />
            Pagos
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Datos del gasto</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Proveedor" value={expense.provider_name ?? expense.provider?.name} />
          <Detail label="Tipo de gasto" value={expense.expense_type?.name} />
          <Detail label="Tipo de comprobante" value={expense.receipt_type_name} />
          <Detail label="Número" value={expense.receipt_number} />
          <Detail label="Fecha" value={dateTime(expense.issue_date)} />
          <Detail label="Sucursal" value={expense.branch_name} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Importes</h2>
        </div>

        <dl className="detail-list detail-list--grid">
          <Detail label="Neto" value={money(expense.net_amount)} />
          <Detail label="Descuento" value={money(expense.discount_amount)} />
          <Detail label="Recargo" value={money(expense.surcharge_amount)} />
          <Detail label="IVA" value={money(expense.tax_amount)} />
          <Detail label="Total" value={money(expense.total_amount)} />
        </dl>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Pagos registrados</h2>
        </div>

        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th>Medio</th>
                <th>Banco</th>
                <th>Referencia</th>
                <th className="is-end">Total</th>
              </tr>
            </thead>
            <tbody>
              {payments.length ? (
                payments.map((payment, index) => (
                  <tr key={payment.id ?? index}>
                    <td>{paymentMethodLabel(payment.payment_method)}</td>
                    <td>{payment.bank_name ?? '—'}</td>
                    <td>{payment.reference ?? '—'}</td>
                    <td className="is-end">{money(payment.total_paid)}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4}>
                    <div className="table-state">
                      <strong>Sin pagos registrados</strong>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {expense.notes ? (
        <div className="erp-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">Observaciones</h2>
          </div>
          <p className="detail-text">{expense.notes}</p>
        </div>
      ) : null}

      <div className="detail-footer">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/gastos')}>
          Volver al listado
        </button>
      </div>
    </>
  )
}
