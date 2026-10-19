import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { BackLink } from './fields'
import { useToast } from '../context/ToastContext'
import { ApiError } from '../lib/api'
import { money } from '../lib/format'

const DISCOUNT_TYPE_LABEL = {
  percentage: 'Porcentaje',
  fixed_amount: 'Importe fijo',
  special_price: 'Precio especial',
  combo: 'Combo',
}

const LIGHT_PURPLE_BG = 'rgba(139, 92, 246, 0.08)'
const LIGHT_PURPLE_BORDER = 'rgba(139, 92, 246, 0.35)'

/**
 * Panel de formas de pago.
 *
 * Replica el diseño de la pantalla de pagos del ERP: dos columnas, la
 * izquierda con el total, los medios de pago, los productos, el formulario
 * del medio elegido y las promociones disponibles; la derecha con el resumen
 * de pagos, el saldo y las opciones de cierre.
 *
 * Se usa tanto para ventas como para compras. Las props de promoción solo
 * aplican a ventas.
 */
export function PaymentPanel({
  total,
  payments,
  onAdd,
  addLabel = 'Agregar pago',
  items = [],
  itemType = 'venta', // 'venta' | 'compra'
  invoiceId,
  onRefresh,
  applyPromotion,
  getCompatiblePromotions,
  appliedPromotion,
  promotionDiscount = 0,
  finishTo,
  finishLabel = 'Finalizar',
  onFinish,
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

  const paid = useMemo(
    () => payments.reduce((acc, p) => acc + Number(p.total_paid ?? 0), 0),
    [payments],
  )
  const balance = Math.max(0, Number(total ?? 0) - paid)

  const paymentMethodUsed = useMemo(() => {
    return selectedMethod === 'card' ? 'card' : selectedMethod
  }, [selectedMethod])

  const set = (field) => (event) => setForm((current) => ({ ...current, [field]: event.target.value }))

  const selectMethod = (method, type) => {
    setSelectedMethod(method)
    if (method === 'card') {
      setForm((current) => ({
        ...current,
        card_type: type ?? 'credit',
        amount: String(Math.max(0, Number(total ?? 0))),
      }))
    } else {
      setForm((current) => ({ ...current, amount: String(Math.max(0, Number(total ?? 0))) }))
    }
  }

  const isMethodActive = (method, type) => {
    if (method === 'card') {
      return selectedMethod === 'card' && form.card_type === type
    }
    return selectedMethod === method
  }

  const loadPromotions = useCallback(async () => {
    if (!invoiceId || !getCompatiblePromotions) return
    setLoadingPromos(true)
    try {
      const data = await getCompatiblePromotions(invoiceId, [paymentMethodUsed])
      const list = Array.isArray(data) ? data : (data?.data ?? [])
      setPromotions(list)
    } catch {
      setPromotions([])
    } finally {
      setLoadingPromos(false)
    }
  }, [invoiceId, getCompatiblePromotions, paymentMethodUsed])

  useEffect(() => {
    loadPromotions()
  }, [loadPromotions])

  const handleApplyPromotion = async (promotion) => {
    if (!applyPromotion || !invoiceId) return
    setSaving(true)
    try {
      await applyPromotion(invoiceId, { id: promotion.id })
      toast.success(
        'Promoción aplicada',
        `Se descontó ${money(promotion.estimated_discount ?? 0)} de la ${itemType}.`,
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
      toast.success(
        'Pago agregado',
        `${selectedMethod === 'card' ? `${form.card_type === 'debit' ? 'Débito' : 'Crédito'}` : selectedMethod} por ${money(amount)}.`,
      )
    } catch (error) {
      toast.error('No se pudo agregar el pago', error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.')
    } finally {
      setSaving(false)
    }
  }

  const handleFinish = async () => {
    if (onFinish) {
      await onFinish()
      return
    }
    window.location.href = finishTo
  }

  const isPromotionApplied = Boolean(appliedPromotion?.id)

  // ------------------------------------------------------------------ métodos
  const PAYMENT_METHODS = [
    { key: 'cash', label: 'Efectivo', method: 'cash', type: null },
    { key: 'card_credit', label: 'Tarjeta Crédito', method: 'card', type: 'credit' },
    { key: 'card_debit', label: 'Tarjeta Débito', method: 'card', type: 'debit' },
    { key: 'transfer', label: 'Transferencia', method: 'transfer', type: null },
    { key: 'mp_qr', label: 'Mercado Pago QR', method: null, type: null, disabled: true },
    { key: 'voucher', label: 'Voucher', method: null, type: null, disabled: true },
    { key: 'current_account', label: 'Cuenta Corriente', method: null, type: null, disabled: true },
  ]

  const buttonStyle = (active) => ({
    border: '1px solid',
    borderColor: active ? 'var(--erp-primary)' : 'var(--erp-border-color)',
    color: active ? '#fff' : 'var(--erp-heading-color)',
    backgroundColor: active ? 'var(--erp-primary)' : 'transparent',
    borderRadius: '2rem',
    padding: '0.45rem 1rem',
    fontSize: '0.85rem',
    fontWeight: 500,
  })

  const methodTitle = () => {
    if (selectedMethod === 'cash') return 'Efectivo'
    if (selectedMethod === 'card') return form.card_type === 'debit' ? 'Tarjeta Débito' : 'Tarjeta Crédito'
    if (selectedMethod === 'transfer') return 'Transferencia'
    return 'Cheque'
  }

  return (
    <div style={{ maxWidth: '1200px', margin: '0 auto', display: 'grid', gridTemplateColumns: '1fr 380px', gap: '1.5rem' }}>

      {/* ============================ Columna izquierda ============================ */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>

        {/* Totales */}
        <div className="erp-card" style={{ padding: 0 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', gap: '1rem', padding: '1rem 1.15rem', borderBottom: '1px solid var(--erp-border-color)' }}>
            <div>
              <small className="detail-label">SUBTOTAL {itemType.toUpperCase()}</small>
              <h3 style={{ margin: 0 }}>{money(total)}</h3>
            </div>
            <div style={{ textAlign: 'right' }}>
              <small className="detail-label">TOTAL A COBRAR</small>
              <h3 style={{ margin: 0, color: 'var(--erp-success)', fontSize: '1.35rem' }}>{money(balance)}</h3>
            </div>
          </div>

          <div style={{ padding: '1rem 1.15rem', display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
            {PAYMENT_METHODS.map((m) => {
              const active = isMethodActive(m.method, m.type) && !m.disabled
              const disabled = m.disabled || false
              return (
                <button
                  key={m.key}
                  type="button"
                  disabled={disabled}
                  onClick={disabled ? undefined : () => selectMethod(m.method, m.type)}
                  style={{
                    ...buttonStyle(active),
                    opacity: disabled ? 0.5 : 1,
                    cursor: disabled ? 'not-allowed' : 'pointer',
                  }}
                >
                  {m.label}
                </button>
              )
            })}
          </div>
        </div>

        {/* Productos de la venta/compra */}
        {items.length ? (
          <div className="erp-card" style={{ padding: 0 }}>
            <div style={{ padding: '1rem 1.15rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid var(--erp-border-color)' }}>
              <h3 className="erp-card-title" style={{ margin: 0, fontSize: '0.95rem' }}>Productos de la {itemType}</h3>
            </div>
            <table className="erp-table" style={{ fontSize: '0.85rem' }}>
              <thead>
                <tr>
                  <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase' }}>Producto</th>
                  <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'center' }}>Cant.</th>
                  <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Precio</th>
                  <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Desc.</th>
                  <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Total</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item, index) => {
                  const product =
                    item.variant?.product?.name ??
                    item.product?.name ??
                    item.product_name ??
                    'Producto'
                  const qty = Number(item.quantity ?? 0)
                  const price = Number(item.unit_price_with_taxes ?? item.unit_price ?? 0)
                  const discount = Number(item.discount_percentage ?? 0)
                  const lineTotal = qty * price * (1 - discount / 100)
                  return (
                    <tr key={index} style={{ borderBottom: '1px solid var(--erp-border-color)' }}>
                      <td style={{ padding: '0.6rem 1.15rem' }}>{product}</td>
                      <td style={{ padding: '0.6rem 1.15rem', textAlign: 'center' }}>{qty}</td>
                      <td style={{ padding: '0.6rem 1.15rem', textAlign: 'right' }}>{money(price)}</td>
                      <td style={{ padding: '0.6rem 1.15rem', textAlign: 'right' }}>{discount}%</td>
                      <td style={{ padding: '0.6rem 1.15rem', textAlign: 'right', fontWeight: 600 }}>{money(lineTotal)}</td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        ) : null}

        {/* Formulario del medio de pago */}
        <div className="erp-card" style={{ padding: 0 }}>
          <div style={{ padding: '1rem 1.15rem', borderBottom: '1px solid var(--erp-border-color)' }}>
            <h3 className="erp-card-title" style={{ margin: 0, fontSize: '1rem' }}>{methodTitle()}</h3>
          </div>

          <div className="form-grid" style={{ padding: '1rem 1.15rem' }}>
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

            {selectedMethod === 'card' ? (
              <>
                <label className="erp-field-block">
                  <span className="erp-label">Tarjeta</span>
                  <select className="erp-control" value={form.card_name || ''} onChange={set('card_name')} placeholder="Seleccione tarjeta...">
                    <option value="">Seleccione tarjeta...</option>
                  </select>
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Plan</span>
                  <select className="erp-control" value={form.card_installments} onChange={set('card_installments')}>
                    <option value="1">Seleccione plan</option>
                    {[1, 3, 6, 12].map((n) => (
                      <option key={n} value={String(n)}>
                        {n} cuota{n === 1 ? '' : 's'}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Importe</span>
                  <input className="erp-control" type="number" value={form.amount} onChange={set('amount')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Nro. Cupón <span style={{ color: 'var(--erp-danger)' }}>*</span></span>
                  <input className="erp-control" value={form.reference} onChange={set('reference')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Nro. Lote</span>
                  <input className="erp-control" value={form.card_authorization} onChange={set('card_authorization')} />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Últimos 4 dígitos</span>
                  <input className="erp-control" value={form.card_last_four} onChange={set('card_last_four')} maxLength={4} inputMode="numeric" />
                </label>
                <label className="erp-field-block">
                  <span className="erp-label">Nro. Comercio</span>
                  <input className="erp-control" value={form.card_holder} onChange={set('card_holder')} />
                </label>
              </>
            ) : null}
          </div>

          {/* Resumen del medio de pago (caja gris como en la imagen) */}
          <div style={{ padding: '0.85rem 1.15rem', background: LIGHT_PURPLE_BG, borderRadius: '0 0 0.85rem 0.85rem', border: '1px solid var(--erp-border-color)', borderTop: '0' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85rem', marginBottom: '0.2rem' }}>
              <span>Recargo: ${money(Number(form.surcharge || 0)).replace('$', '')}</span>
              <span style={{ fontWeight: 700 }}>Total a pagar: ${money(balance)}</span>
            </div>
          </div>

          <div style={{ marginTop: '1rem' }}>
            <button type="button" className="erp-btn erp-btn-primary" onClick={handleAdd} disabled={saving} style={{ width: '100%' }}>
              {saving ? 'Guardando…' : addLabel}
            </button>
          </div>
        </div>

        {/* Promociones disponibles (debajo del bloque de pago) */}
        <div className="erp-card" style={{ padding: 0 }}>
          <div style={{ padding: '1rem 1.15rem', borderBottom: '1px solid var(--erp-border-color)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h3 className="erp-card-title" style={{ margin: 0, fontSize: '0.95rem' }}>Promociones disponibles</h3>
            <span style={{ color: 'var(--erp-secondary-color)', fontSize: '0.8rem' }}>
              {loadingPromos ? 'Buscando…' : `${promotions.length} encontrada(s)`}
            </span>
          </div>
          <div style={{ padding: '1rem 1.15rem' }}>
            {isPromotionApplied ? (
              <div
                role="status"
                style={{
                  padding: '0.85rem 1rem',
                  marginBottom: '0.75rem',
                  border: '1px solid rgba(34, 197, 94, 0.45)',
                  borderRadius: '0.6rem',
                  background: 'rgba(34, 197, 94, 0.1)',
                  color: '#15803d',
                }}
              >
                <strong>Descuento aplicado</strong>
                <div style={{ marginTop: '0.2rem', fontSize: '0.85rem' }}>
                  {appliedPromotion.name}
                  {Number(promotionDiscount) > 0 ? ` · Ahorro: ${money(promotionDiscount)}` : ''}
                </div>
              </div>
            ) : null}
            {loadingPromos ? (
              <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '0.5rem', padding: '1.5rem 1rem', color: 'var(--erp-secondary-color)' }}>
                <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
                <span>Cargando promociones…</span>
              </div>
            ) : promotions.length ? (
              promotions.map((promotion) => (
                <div key={promotion.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '0.75rem', padding: '0.75rem 1rem', border: '1px solid var(--erp-border-color)', borderRadius: '0.6rem', background: LIGHT_PURPLE_BG, marginBottom: '0.5rem' }}>
                  <div>
                    <div style={{ display: 'flex', alignItems: 'baseline', gap: '0.5rem', marginBottom: '0.25rem' }}>
                      <strong style={{ fontSize: '0.95rem' }}>{promotion.name}</strong>
                      <span style={{ display: 'inline-block', fontSize: '0.72rem', fontWeight: 700, padding: '0.1rem 0.45rem', borderRadius: '0.35rem', background: LIGHT_PURPLE_BG, color: 'var(--erp-accent)', border: `1px solid ${LIGHT_PURPLE_BORDER}` }}>
                        {DISCOUNT_TYPE_LABEL[promotion.discount_type] ?? promotion.discount_type}
                      </span>
                    </div>
                    <div style={{ color: 'var(--erp-secondary-color)', fontSize: '0.85rem', marginBottom: '0.25rem' }}>
                      {promotion.discount_type === 'percentage'
                        ? `${Number(promotion.discount_value || 0)}% de descuento`
                        : money(promotion.discount_value)}
                    </div>
                    <div style={{ fontSize: '0.85rem', color: 'var(--erp-secondary-color)' }}>
                      Aplica a <strong>{promotion.applicable_items_count ?? 0}</strong>
                      {promotion.applicable_items_count === 1 ? ' ítem' : ' ítems'}
                      de la {itemType}
                    </div>
                  </div>
                  <button
                    type="button"
                    className="erp-btn erp-btn--sm erp-btn-outline"
                    onClick={() => handleApplyPromotion(promotion)}
                    disabled={saving || isPromotionApplied}
                  >
                    {appliedPromotion?.id === promotion.id
                      ? 'Descuento aplicado'
                      : saving
                        ? 'Aplicando…'
                        : 'Aplicar descuento'}
                  </button>
                </div>
              ))
            ) : (
              <div style={{ color: 'var(--erp-secondary-color)', fontSize: '0.85rem', padding: '1rem' }}>
                No hay promociones compatibles con esta {itemType} y medio de pago.
              </div>
            )}
          </div>
        </div>

      </div>

      {/* ============================ Columna derecha: resumen ============================ */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>

        <div className="erp-card" style={{ padding: 0 }}>
          <div style={{ padding: '1rem 1.15rem', borderBottom: '1px solid var(--erp-border-color)' }}>
            <h2 className="erp-card-title" style={{ margin: 0, fontSize: '1rem' }}>Resumen formas de pago</h2>
          </div>

          <table className="erp-table" style={{ fontSize: '0.85rem' }}>
            <thead>
              <tr>
                <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase' }}>Medio</th>
                <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Total</th>
                <th style={{ padding: '0.6rem 1.15rem', fontSize: '0.75rem', textTransform: 'uppercase', textAlign: 'right' }}>Acción</th>
              </tr>
            </thead>
            <tbody>
              {payments.length ? (
                payments.map((payment) => (
                  <tr key={payment.id} style={{ borderBottom: '1px solid var(--erp-border-color)' }}>
                    <td style={{ padding: '0.6rem 1.15rem' }}>{paymentMethodLabel(payment.payment_method)}</td>
                    <td style={{ padding: '0.6rem 1.15rem', textAlign: 'right' }}>{money(payment.total_paid)}</td>
                    <td style={{ padding: '0.6rem 1.15rem', textAlign: 'right' }} />
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={3} style={{ padding: '1.5rem 1.15rem', textAlign: 'center', color: 'var(--erp-secondary-color)' }}>
                    Sin pagos agregados
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          <div style={{ borderTop: '1px solid var(--erp-border-color)', padding: '1rem', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', justifyItems: 'center' }}>
            <div>
              <small className="detail-label">Saldo</small>
              <h4 style={{ margin: 0 }}>{money(balance)}</h4>
            </div>
            <div style={{ textAlign: 'right' }}>
              <small className="detail-label">Total cobrado</small>
              <h4 style={{ margin: 0 }}>{money(paid)}</h4>
            </div>
          </div>

          <div style={{ marginTop: 'auto' }}>
            <button className="erp-btn erp-btn-primary" style={{ width: '100%', backgroundColor: '#22c55e', borderColor: '#22c55e' }} onClick={handleFinish}>
              {itemType === 'venta' ? 'Saldo Pendiente' : 'Finalizar'}
            </button>
          </div>
        </div>

      </div>
    </div>
  )
}

/** Etiqueta legible de un medio de pago. */
export function paymentMethodLabel(method) {
  return (
    {
      card: 'Tarjeta',
      cash: 'Efectivo',
      transfer: 'Transferencia',
      check: 'Cheque',
      promotion: 'Promoción',
    }[method] ?? method
  )
}
