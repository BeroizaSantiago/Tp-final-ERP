import { useParams } from 'react-router-dom'
import { useState } from 'react'

import { BackLink } from '../../components/fields'
import { useApiResource } from '../../lib/useApiResource'
import { useToast } from '../../context/ToastContext'
import { addInvoicePayment, applyPromotion, finalizeInvoice, getCompatiblePromotions, downloadInvoicePdf, sendInvoicePdfEmail } from './api'
import { PaymentPanel } from '../../components/PaymentPanel'

export function SalePaymentPage() {
  const toast = useToast()
  const { id } = useParams()
  const [saving, setSaving] = useState(false)
  const { data: sale, loading, error, reload } = useApiResource(`/invoices/${id}`, { deps: [id] })
  const saleFinalized = Boolean(sale?.stock_applied_at)

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

    if (res?.invoice) {
      try {
        const result = await sendInvoicePdfEmail(id)

        toast.success(
          'Venta finalizada exitosamente',
          result.message || 'Comprobante enviado al email del cliente.'
        )
      } catch (emailError) {
        toast.error(
          'La venta se finalizó, pero no se pudo enviar el comprobante por correo.',
          emailError.message
        )
      }

      window.location.href = '/ventas'
      return
    }

    if (res?.message) {
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
        saleFinalized={saleFinalized}
        onDownloadPdf={async () => {
          try {
            await downloadInvoicePdf(id)
          } catch (error) {
            toast.error(
              'No se pudo descargar el comprobante PDF.',
              error.message
            )
          }
        }}
        finishLabel="Finalizar Venta"
      />
    </>
  )
}
