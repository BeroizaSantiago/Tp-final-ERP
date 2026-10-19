import { useParams } from 'react-router-dom'
import { useState } from 'react'

import { BackLink } from '../../components/fields'
import { useApiResource } from '../../lib/useApiResource'
import { useToast } from '../../context/ToastContext'
import { addInvoicePayment } from './api'
import { applyPromotion } from './api'
import { finalizeInvoice } from './api'
import { getCompatiblePromotions } from './api'
import { PaymentPanel } from '../../components/PaymentPanel'

export function SalePaymentPage() {
  const toast = useToast()
  const { id } = useParams()
  const [saving, setSaving] = useState(false)
  const { data: sale, loading, error, reload } = useApiResource(`/invoices/${id}`, { deps: [id] })

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
        <BackLink to={`/ventas/${id}`}>Volver a la venta</BackLink>
        <div className="erp-alert" role="alert">{error.message}</div>
      </>
    )
  }

  const handleFinish = async () => {
    setSaving(true)
    try {
      const res = await finalizeInvoice(id)
      if (res && res.invoice) {
        toast.success(
          'Venta finalizada exitosamente',
          'Comprobante: ' + (res.invoice.full_number ?? res.invoice.id)
        )
        window.location.href = `/ventas`
      } else if (res && res.message) {
        toast.error(res.message)
      } else {
        toast.success('Venta finalizada exitosamente')
      }
    } catch (error) {
      toast.error('Error al finalizar', error.message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to={`/ventas/${id}`}>Volver a la venta</BackLink>
          <h1 className="page-title">Formas de pago</h1>
          <p className="page-subtitle">{sale.full_number ?? `Venta #${sale.id}`}</p>
        </div>
      </div>

      <PaymentPanel
        itemType="venta"
        total={sale.total_amount}
        payments={sale.payments ?? []}
        items={sale.items ?? []}
        invoiceId={id}
        onAdd={async (payload) => {
          await addInvoicePayment(id, payload)
          await reload()
        }}
        onRefresh={reload}
        applyPromotion={applyPromotion}
        getCompatiblePromotions={getCompatiblePromotions}
        appliedPromotion={sale.sales_promotion}
        promotionDiscount={sale.promotion_discount_amount}
        onFinish={handleFinish}
        finishLabel="Finalizar Venta"
      />
    </>
  )
}
