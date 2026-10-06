import { Link, useParams } from 'react-router-dom'

import { BackLink } from '../../components/fields'
import { useApiResource } from '../../lib/useApiResource'
import { addPurchasePayment } from './api'
import { PaymentMethods } from './PaymentMethods'

/** Formas de pago de una compra, réplica de `purchases/payment-method.blade.php`. */
export function PurchasePaymentPage() {
  const { id } = useParams()
  const { data: purchase, loading, error, setData } = useApiResource(`/purchases/${id}`, { deps: [id] })

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
        <BackLink to={`/compras/${id}`}>Volver a la compra</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to={`/compras/${id}`}>Volver a la compra</BackLink>
          <h1 className="page-title">Formas de pago</h1>
          <p className="page-subtitle">{purchase.full_number ?? `Compra #${purchase.id}`}</p>
        </div>
      </div>

      <PaymentMethods
        total={purchase.total_amount}
        payments={purchase.payments ?? []}
        onAdd={async (payload) => {
          const updated = await addPurchasePayment(id, payload)

          setData(updated)
        }}
      />

      <div className="detail-footer" style={{ marginTop: '1rem' }}>
        <Link className="erp-btn erp-btn-primary" to={`/compras/${id}`}>
          Finalizar
        </Link>
      </div>
    </>
  )
}
