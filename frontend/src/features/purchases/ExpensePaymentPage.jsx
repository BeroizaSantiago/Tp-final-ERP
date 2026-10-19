import { useParams } from 'react-router-dom'

import { BackLink } from '../../components/fields'
import { useApiResource } from '../../lib/useApiResource'
import { addMiscExpensePayment } from './api'
import { PaymentPanel } from '../../components/PaymentPanel'

/** Formas de pago de un gasto vario sobre el comprobante ya cargado. */
export function ExpensePaymentPage() {
  const { id } = useParams()
  const { data: expense, loading, error, setData } = useApiResource(`/misc-expenses/${id}`, { deps: [id] })

  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to={`/gastos/${id}`}>Volver al gasto</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to={`/gastos/${id}`}>Volver al gasto</BackLink>
          <h1 className="page-title">Formas de pago</h1>
          <p className="page-subtitle">{expense.receipt_type_name ?? 'Gasto'} {expense.receipt_number ?? `#${expense.id}`}</p>
        </div>
      </div>

      <PaymentPanel
        itemType="gasto"
        total={expense.total_amount}
        payments={expense.payments ?? []}
        items={expense.items ?? []}
        onAdd={async (payload) => {
          const updated = await addMiscExpensePayment(id, payload)
          setData(updated)
        }}
        finishTo={`/gastos/${id}`}
        finishLabel="Finalizar"
      />
    </>
  )
}
