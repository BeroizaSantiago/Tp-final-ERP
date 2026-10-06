import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

import { BackLink, FormSection, Select, TextInput, Textarea } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money } from '../../lib/format'
import { RemoteSearchSelect, useRemoteSearch } from './RemoteSearchSelect'
import { createPurchaseOrder, getProduct, searchProducts, searchProviders } from './api'

const CURRENCIES = ['Pesos', 'Dólares']

const emptyItem = () => ({
  product_id: '',
  product_label: '',
  quantity: '1',
  unit_price: '0',
})

/** Alta de orden de compra, réplica de `purchase-orders/create.blade.php`. */
export function OrderFormPage() {
  const navigate = useNavigate()
  const toast = useToast()

  const [values, setValues] = useState({
    provider_id: '',
    issue_date: new Date().toISOString().substring(0, 10),
    order_number: `OC-${Date.now()}`,
    currency_name: 'Pesos',
    notes: '',
  })
  const [provider, setProvider] = useState(null)
  const [items, setItems] = useState([emptyItem()])
  const [saving, setSaving] = useState(false)

  const providerSearch = useRemoteSearch(async (q) => {
    const data = await searchProviders(q)

    return (data?.data ?? data ?? []).map((p) => ({
      value: String(p.id),
      label: p.name,
      hint: [p.code, p.identification_number].filter(Boolean).join(' · '),
    }))
  })

  const change = (field) => (event) => setValues((current) => ({ ...current, [field]: event.target.value }))

  const updateItem = (index, row) => setItems((current) => current.map((item, i) => (i === index ? row : item)))

  const total = items.reduce((acc, item) => acc + Number(item.quantity || 0) * Number(item.unit_price || 0), 0)

  const handleSubmit = async (event) => {
    event.preventDefault()

    const validItems = items.filter((item) => item.product_id)

    if (!provider) {
      toast.error('Falta el proveedor', 'Seleccioná un proveedor para la orden.')
      return
    }

    if (!validItems.length) {
      toast.error('Faltan productos', 'Agregá al menos un producto.')
      return
    }

    setSaving(true)

    try {
      const order = await createPurchaseOrder({
        provider_id: Number(provider.value),
        issue_date: values.issue_date,
        order_number: values.order_number,
        currency_name: values.currency_name,
        notes: values.notes || null,
        items: validItems.map((item) => ({
          product_id: Number(item.product_id),
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
        })),
      })

      toast.success('Orden registrada', `${order.order_number} quedó cargada.`)
      navigate(`/ordenes/${order.id}`, { replace: true })
    } catch (error) {
      toast.error('No se pudo guardar la orden', error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.')
      setSaving(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="page-head">
        <div>
          <BackLink to="/ordenes">Volver a órdenes</BackLink>
          <h1 className="page-title">Nueva orden de compra</h1>
          <p className="page-subtitle">Solicitá productos a un proveedor.</p>
        </div>

        <div className="page-actions">
          <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/ordenes')} disabled={saving}>
            Cancelar
          </button>
          <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
            <Icon name="save" size={18} />
            {saving ? 'Guardando…' : 'Guardar orden'}
          </button>
        </div>
      </div>

      <FormSection title="Orden" description="Datos de la solicitud.">
        <div className="form-grid">
          <RemoteSearchSelect
            label="Proveedor"
            placeholder="Buscar proveedor por nombre, código o identificación…"
            search={providerSearch.query}
            onSearch={providerSearch.setQuery}
            results={providerSearch.results}
            loading={providerSearch.loading}
            selected={provider ? { label: provider.label } : null}
            onSelect={setProvider}
          />

          <TextInput
            name="issue_date"
            label="Fecha emisión"
            type="date"
            value={values.issue_date}
            onChange={change('issue_date')}
          />

          <TextInput
            name="order_number"
            label="Nro. orden"
            value={values.order_number}
            onChange={change('order_number')}
          />

          <Select
            name="currency_name"
            label="Moneda"
            value={values.currency_name}
            onChange={change('currency_name')}
            options={CURRENCIES.map((value) => ({ value, label: value }))}
          />

          <Textarea
            className="form-span-2"
            name="notes"
            label="Observaciones"
            rows={3}
            value={values.notes}
            onChange={change('notes')}
          />
        </div>
      </FormSection>

      <FormSection title="Productos solicitados" description="Ítems de la orden.">
        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th style={{ width: '40%' }}>Producto</th>
                <th>Cant.</th>
                <th>P. Unit.</th>
                <th className="is-end">Total</th>
                <th className="is-end" />
              </tr>
            </thead>
            <tbody>
              {items.map((item, index) => (
                <OrderItemRow key={index} index={index} item={item} onChange={updateItem} onRemove={(i) => setItems((current) => current.filter((_, j) => j !== i))} />
              ))}
            </tbody>
          </table>
        </div>

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '1rem' }}>
          <button type="button" className="erp-btn erp-btn-outline" onClick={() => setItems((current) => [...current, emptyItem()])}>
            <Icon name="add" size={16} />
            Agregar producto
          </button>

          <h4 style={{ margin: 0 }}>
            Total orden: <strong>{money(total)}</strong>
          </h4>
        </div>
      </FormSection>

      <div className="form-actions">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/ordenes')} disabled={saving}>
          Cancelar
        </button>
        <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
          <Icon name="save" size={18} />
          {saving ? 'Guardando…' : 'Guardar orden'}
        </button>
      </div>
    </form>
  )
}

function OrderItemRow({ index, item, onChange, onRemove }) {
  const [product, setProduct] = useState(null)
  const search = useRemoteSearch(async (q) => {
    const data = await searchProducts(q)

    return (data?.data ?? data ?? []).map((p) => ({
      value: String(p.id),
      label: p.name,
      hint: [p.code, p.bar_code].filter(Boolean).join(' · '),
      price: p.price_a_with_tax ?? 0,
    }))
  })

  const lineTotal = Number(item.quantity || 0) * Number(item.unit_price || 0)

  return (
    <tr>
      <td>
        <RemoteSearchSelect
          placeholder="Buscar producto (mín. 2 caracteres)…"
          search={search.query}
          onSearch={search.setQuery}
          results={search.results}
          loading={search.loading}
          selected={product ? { label: product.label } : null}
          onSelect={async (picked) => {
            setProduct(picked)

            if (!picked) {
              onChange(index, { ...emptyItem() })
              return
            }

            let price = picked.price ?? 0

            try {
              const detail = await getProduct(picked.value)

              price = detail?.price_a_with_tax ?? price
            } catch {
              /* sin detalle, se usa el precio del listado */
            }

            onChange(index, { ...item, product_id: picked.value, product_label: picked.label, unit_price: String(price) })
          }}
        />
      </td>
      <td>
        <input
          type="number"
          className="erp-control"
          min="0.01"
          step="0.01"
          value={item.quantity}
          onChange={(event) => onChange(index, { ...item, quantity: event.target.value })}
          aria-label="Cantidad"
        />
      </td>
      <td>
        <input
          type="number"
          className="erp-control"
          min="0"
          step="0.01"
          value={item.unit_price}
          onChange={(event) => onChange(index, { ...item, unit_price: event.target.value })}
          aria-label="Precio unitario"
        />
      </td>
      <td className="is-end"><strong>{money(lineTotal)}</strong></td>
      <td className="is-end">
        <button type="button" className="erp-btn erp-btn-outline-danger erp-btn--icon erp-btn--sm" onClick={() => onRemove(index)} aria-label="Quitar producto">
          <Icon name="close" size={14} />
        </button>
      </td>
    </tr>
  )
}
