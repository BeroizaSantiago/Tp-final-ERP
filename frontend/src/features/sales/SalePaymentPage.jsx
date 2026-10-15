import { Link, useParams } from 'react-router-dom'
import { useState } from 'react'

import { BackLink } from '../../components/fields'
import { useApiResource } from '../../lib/useApiResource'
import { useToast } from '../../context/ToastContext'
import { addInvoicePayment } from './api'
import { finalizeInvoice } from './api'
import { PaymentMethods } from './PaymentMethods'

export function SalePaymentPage() {
  const toast = useToast()
  const { id } = useParams()
  const [saving, setSaving] = useState(false)
  const { data: sale, loading, error, setData } = useApiResource(`/invoices/${id}`, { deps: [id] })

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

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to={`/ventas/${id}`}>Volver a la venta</BackLink>
          <h1 className="page-title">Formas de pago</h1>
          <p className="page-subtitle">{sale.full_number ?? `Venta #${sale.id}`}</p>
        </div>
      </div>

      <PaymentMethods
        total={sale.total_amount}
        payments={sale.payments ?? []}
        onAdd={async (payload) => {
          const updated = await addInvoicePayment(id, payload)

          setData(updated)
        }}
      />

      <div className="detail-footer" style={{ marginTop: '1rem' }}>
        <button
          className="erp-btn erp-btn-primary"
          onClick={async () => {
            setSaving(true)
            try {
              const res = await finalizeInvoice(id)
              if (res && res.invoice) {
                // Usar los datos del invoice regresado para actualizar el estado
                setData(res.invoice)
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
          }}
          disabled={saving}
        >
          {saving ? 'Finalizando…' : 'Finalizar Venta'}
        </button>
      </div>
    </>
  )
}
