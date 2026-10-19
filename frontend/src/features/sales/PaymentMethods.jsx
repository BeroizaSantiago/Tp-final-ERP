import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money } from '../../lib/format'
import {
  applyPromotion,
  getCompatiblePromotions,
  DISCOUNT_TYPE_LABEL,
} from './api'

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

export function PaymentMethods({
  total,
  payments,
  onAdd,
  addLabel = 'Agregar pago',
  invoiceId,
  onRefresh,
}) {
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
  const [promotions, setPromotions] = useState([])
  const [loadingPromos, setLoadingPromos] = useState(false)

  const paymentMethodsUsed = useMemo(
    () => payments.map((p) => p.payment_method).filter(Boolean),
    [payments],
  )

  const paid = useMemo(
    () => payments.reduce((acc, p) => acc + Number(p.total_paid ?? 0), 0),
    [payments],
  )
  const balance = Math.max(0, Number(total ?? 0) - paid)

  const set = (field) => (event) => setForm((current) => ({ ...current, [field]: event.target.value }))

  const selectMethod = (method) => {
    setSelectedMethod(method)
    setForm((current) => ({ ...current, amount: String(Math.max(0, Number(total ?? 0))) }))
  }

  const loadPromotions = useCallback(async () => {
    if (!invoiceId) return
    setLoadingPromos(true)
    try {
      const data = await getCompatiblePromotions(invoiceId, paymentMethodsUsed)
      const list = Array.isArray(data) ? data : (data?.data ?? [])
      setPromotions(list)
    } catch {
      setPromotions([])
    } finally {
      setLoadingPromos(false)
    }
  }, [invoiceId, paymentMethodsUsed])

  useEffect(() => {
    loadPromotions()
  }, [loadPromotions])

  const handleApplyPromotion = async (promotion) => {
    setSaving(true)
    try {
      await applyPromotion(invoiceId, { id: promotion.id })
      toast.success(
        'Promoción aplicada',
        `Se descontó ${money(promotion.estimated_discount ?? 0)} de la venta.`,
      )
      await onRefresh?.()
    } catch (error) {
      toast.error(
        'No se pudo aplicar la promoción',
        error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.',
      )
    } finally {
      setSaving(false)
    }
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

      await onRefresh?.()
      toast.success('Pago agregado', `${paymentMethodLabel(selectedMethod)} por ${money(amount)}.`)
    } catch (error) {
      toast.error('No se pudo agregar el pago', error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.')
    } finally {
      setSaving(false)
    }
  }

  const isPromotionApplied = useMemo(() => payments.some((p) => p.payment_method === 'promotion'), [payments])

  return (
    <div style={{ maxWidth: '1200px', margin: '0 auto', display: 'flex', flexDirection: 'column', gap: '1rem' }}>

      {/* ------------------------------- Totales y medio de pago ------------------------------- */}
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

        <div className="erp-filters" style={{ padding: '1rem 1.15rem' }}>
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

      {/* ------------------------------- Formulario del medio de pago ------------------------------- */}
      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">{paymentMethodLabel(selectedMethod)}</h2>
        </div>

        <div className="form-grid">
          {selectedMethod === 'cash' ? (
            <>
              <div style={{ gridColumn: 'span 2' }}>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
              </div>
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
              <div style={{ gridColumn: 'span 2' }}>
                <label className="erp-field-block">
                  <span className="erp-label">Banco</span>
                  <input className="erp-control" value={form.bank_name} onChange={set('bank_name')} />
                </label>
              </div>
              <div style={{ gridColumn: 'span 2' }}>
                <label className="erp-field-block">
                  <span className="erp-label">Referencia</span>
                  <input className="erp-control" value={form.reference} onChange={set('reference')} />
                </label>
              </div>
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
              <div style={{ gridColumn: 'span 2' }}>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
              </div>
            </>
          ) : null}

          {selectedMethod === 'check' ? (
            <>
              <div style={{ gridColumn: 'span 2' }}>
                <label className="erp-field-block">
                  <span className="erp-label">Referencia</span>
                  <input className="erp-control" value={form.reference} onChange={set('reference')} />
                </label>
              </div>
              <div style={{ gridColumn: 'span 2' }}>
                <label className="erp-field-block">
                  <span className="erp-label">Banco</span>
                  <input className="erp-control" value={form.bank_name} onChange={set('bank_name')} />
                </label>
              </div>
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

      {/* ------------------------------- Promociones disponibles (debajo del bloque de pago) ------------------------------- */}
      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Promociones disponibles</h2>
          <span className="erp-card-meta" style={{ marginLeft: 'auto' }}>
            {loadingPromos ? 'Buscando…' : `${promotions.length} encontrada(s)`}
          </span>
        </div>

        <div style={{ padding: '1rem 1.15rem' }}>
          {loadingPromos ? (
            <div className="table-state" style={{ padding: '1.5rem 1rem' }}>
              <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
              <span>Cargando promociones…</span>
            </div>
          ) : promotions.length ? (
            promotions.map((promotion) => (
              <div key={promotion.id} className="promo-card">
                <div className="promo-info">
                  <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.5rem', marginBottom: '0.25rem' }}>
                    <strong style={{ fontSize: '0.95rem' }}>{promotion.name}</strong>
                    <span className="promo-badge">{DISCOUNT_TYPE_LABEL[promotion.discount_type] ?? promotion.discount_type}</span>
                  </div>
                  <div style={{ color: 'var(--erp-secondary-color)', fontSize: '0.85rem', marginBottom: '0.25rem' }}>
                    {promotion.discount_type === 'percentage'
                      ? `${Number(promotion.discount_value || 0)}% de descuento`
                      : money(promotion.discount_value)}
                  </div>
                  <div style={{ fontSize: '0.85rem', color: 'var(--erp-secondary-color)' }}>
                    Aplica a <strong>{promotion.applicable_items_count ?? 0}</strong>
                    {promotion.applicable_items_count === 1 ? ' ítem' : ' ítems'}
                    de la venta
                  </div>
                  <div style={{ marginTop: '0.25rem', fontSize: '0.9rem', fontWeight: '600' }}>
                    Ahorro estimado: {money(promotion.estimated_discount ?? 0)}
                  </div>
                </div>
                <button
                  type="button"
                  className="erp-btn erp-btn--sm erp-btn-outline"
                  onClick={() => handleApplyPromotion(promotion)}
                  disabled={saving || isPromotionApplied}
                  title={isPromotionApplied ? 'Ya se aplicó una promoción a esta venta' : 'Aplicar descuento'}
                >
                  {saving ? 'Aplicando…' : 'Aplicar descuento'}
                </button>
              </div>
            ))
          ) : (
            <div className="table-state" style={{ padding: '1.5rem 1rem' }}>
              <strong>No hay promociones aplicables</strong>
              <p style={{ margin: '0.4rem 0 0', color: 'var(--erp-secondary-color)', fontSize: '0.85rem' }}>
                No se encontraron promociones vigentes que coincidan con los ítems de esta venta.
              </p>
            </div>
          )}
        </div>
      </div>

      {/* ------------------------------- Resumen de pagos agregados ------------------------------- */}
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
              payments.map((payment) => (
                <tr key={payment.id}>
                  <td>{paymentMethodLabel(payment.payment_method)}{payment.payment_method === 'promotion' ? ' (promoción)' : ''}</td>
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

        <div className="payment-summary" style={{ borderTop: '1px solid var(--erp-border-color)', padding: '1rem', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', justifyItems: 'center' }}>
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
