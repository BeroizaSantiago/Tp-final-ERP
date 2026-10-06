import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { BackLink } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money } from '../../lib/format'

export const PAYMENT_METHODS = [
  { key: 'card', label: 'Tarjeta' },
  { key: 'cash', label: 'Efectivo' },
  { key: 'transfer', label: 'Transferencia' },
  { key: 'check', label: 'Cheque' },
]

export function paymentMethodLabel(method) {
  return (
    {
      card: 'Tarjeta',
      cash: 'Efectivo',
      transfer: 'Transferencia',
      check: 'Cheque',
    }[method] ?? method
  )
}

/**
 * Panel de formas de pago, compartido por compras y gastos varios.
 *
 * Replica el comportamiento de `payment-method.blade.php`: métodos en botones,
 * campos según el método elegido, resumen con saldo y total pagado.
 */
export function PaymentMethods({ total, payments, onAdd, addLabel = 'Agregar pago' }) {
  const toast = useToast()
  const [selectedMethod, setSelectedMethod] = useState('cash')
  const [form, setForm] = useState({
    amount: String(Math.max(0, Number(total ?? 0))),
    discount: '0',
    surcharge: '0',
    bank_name: '',
    reference: '',
    card_type: 'credit',
    card_name: '',
    card_holder: '',
    card_last_four: '',
    card_installments: '1',
    card_authorization: '',
  })
  const [saving, setSaving] = useState(false)

  const paid = useMemo(() => payments.reduce((acc, p) => acc + Number(p.total_paid ?? 0), 0), [payments])
  const balance = Math.max(0, Number(total ?? 0) - paid)

  const set = (field) => (event) => setForm((current) => ({ ...current, [field]: event.target.value }))

  const selectMethod = (method) => {
    setSelectedMethod(method)
    setForm((current) => ({ ...current, amount: String(Math.max(0, Number(total ?? 0))) }))
  }

  const handleAdd = async () => {
    const amount = Number(form.amount || 0)

    if (amount <= 0) {
      toast.error('Importe inválido', 'El importe debe ser mayor a 0.')
      return
    }

    setSaving(true)

    try {
      const cardReference = selectedMethod === 'card'
        ? [
            form.card_type === 'debit' ? 'Débito' : 'Crédito',
            form.card_name,
            form.card_holder && `Titular: ${form.card_holder}`,
            form.card_last_four && `Terminación: ${form.card_last_four}`,
            form.card_installments && `Cuotas: ${form.card_installments}`,
            form.card_authorization && `Autorización: ${form.card_authorization}`,
          ].filter(Boolean).join(' | ')
        : form.reference

      await onAdd({
        payment_method: selectedMethod,
        amount,
        discount_amount: Number(form.discount || 0),
        surcharge_amount: Number(form.surcharge || 0),
        bank_name: form.bank_name || null,
        reference: cardReference || null,
      })

      toast.success('Pago agregado', `${paymentMethodLabel(selectedMethod)} por ${money(amount)}.`)
    } catch (error) {
      toast.error('No se pudo agregar el pago', error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '1rem' }}>
      <div>
        <div className="erp-card">
          <div className="erp-card-head" style={{ justifyContent: 'space-between', display: 'flex' }}>
            <div>
              <small className="detail-label">TOTAL</small>
              <h3 style={{ margin: 0 }}>{money(total)}</h3>
            </div>
            <div style={{ textAlign: 'right' }}>
              <small className="detail-label">TOTAL A PAGAR</small>
              <h3 style={{ margin: 0 }}>{money(balance)}</h3>
            </div>
          </div>

          <div className="erp-filters">
            {PAYMENT_METHODS.map((method) => (
              <button
                key={method.key}
                type="button"
                className={`erp-btn erp-btn--sm ${selectedMethod === method.key ? 'erp-btn-primary' : 'erp-btn-outline'}`}
                onClick={() => selectMethod(method.key)}
              >
                {method.label}
              </button>
            ))}
          </div>
        </div>

        <div className="erp-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">{paymentMethodLabel(selectedMethod)}</h2>
          </div>

          <div className="form-grid">
            {selectedMethod === 'cash' ? (
              <>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Descuento</span>
                  <input className="erp-control" type="number" value={form.discount} onChange={set('discount')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Recargo</span>
                  <input className="erp-control" type="number" value={form.surcharge} onChange={set('surcharge')} />
                </label>
              </>
            ) : null}

            {selectedMethod === 'transfer' ? (
              <>
                <label className="erp-field-block">
                  <span className="erp-label">Banco</span>
                  <input className="erp-control" value={form.bank_name} onChange={set('bank_name')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Referencia</span>
                  <input className="erp-control" value={form.reference} onChange={set('reference')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
              </>
            ) : null}

            {selectedMethod === 'card' ? (
              <>
                <label className="erp-field-block">
                  <span className="erp-label">Tipo</span>
                  <select className="erp-control" value={form.card_type} onChange={set('card_type')}>
                    <option value="credit">Crédito</option>
                    <option value="debit">Débito</option>
                  </select>
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Tarjeta</span>
                  <input className="erp-control" value={form.card_name} onChange={set('card_name')} placeholder="Visa, Mastercard..." />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Titular</span>
                  <input className="erp-control" value={form.card_holder} onChange={set('card_holder')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Últimos 4 dígitos</span>
                  <input className="erp-control" value={form.card_last_four} onChange={set('card_last_four')} maxLength={4} inputMode="numeric" />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Cuotas</span>
                  <input className="erp-control" type="number" min="1" value={form.card_installments} onChange={set('card_installments')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Autorización / cupón</span>
                  <input className="erp-control" value={form.card_authorization} onChange={set('card_authorization')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
              </>
            ) : null}

            {selectedMethod === 'check' ? (
              <>
                <label className="erp-field-block">
                  <span className="erp-label">Referencia</span>
                  <input className="erp-control" value={form.reference} onChange={set('reference')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Banco</span>
                  <input className="erp-control" value={form.bank_name} onChange={set('bank_name')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
              </>
            ) : null}

          </div>

          <div style={{ marginTop: '1rem' }}>
            <button type="button" className="erp-btn erp-btn-primary" onClick={handleAdd} disabled={saving}>
              {saving ? 'Guardando…' : addLabel}
            </button>
          </div>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Resumen formas de pago</h2>
        </div>

        <table className="erp-table">
          <thead>
            <tr>
              <th>Medio</th>
              <th className="is-end">Total</th>
            </tr>
          </thead>
          <tbody>
            {payments.length ? (
              payments.map((payment, index) => (
                <tr key={payment.id ?? index}>
                  <td>{paymentMethodLabel(payment.payment_method)}</td>
                  <td className="is-end">{money(payment.total_paid)}</td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={2}>
                  <div className="table-state">
                    <strong>Sin pagos agregados</strong>
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>

        <div className="payment-summary" style={{ marginTop: '1rem', borderTop: '1px solid var(--erp-border-color)', paddingTop: '0.85rem', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
          <div>
            <small className="detail-label">Saldo</small>
            <h4 style={{ margin: 0 }}>{money(balance)}</h4>
          </div>
          <div style={{ textAlign: 'right' }}>
            <small className="detail-label">Total pagado</small>
            <h4 style={{ margin: 0 }}>{money(paid)}</h4>
          </div>
        </div>
      </div>
    </div>
  )
}

/** Estructura común de la página de pagos: título, panel y botón finalizar. */
export function PaymentPage({ backTo, backLabel, title, item, resource, onAdd, finishTo, finishLabel = 'Finalizar' }) {
  const payments = resource?.payments ?? []

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to={backTo}>{backLabel}</BackLink>
          <h1 className="page-title">{title}</h1>
          <p className="page-subtitle">{item}</p>
        </div>
      </div>

      <PaymentMethods
        total={resource?.total_amount ?? 0}
        payments={payments}
        onAdd={onAdd}
      />

      <div className="detail-footer" style={{ marginTop: '1rem' }}>
        <Link className="erp-btn erp-btn-primary" to={finishTo}>
          <Icon name="check" size={16} />
          {finishLabel}
        </Link>
      </div>
    </>
  )
}
