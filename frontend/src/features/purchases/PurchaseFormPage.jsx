import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'

import { BackLink, Field, FormSection, Select, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money } from '../../lib/format'
import { RemoteSearchSelect, useRemoteSearch } from './RemoteSearchSelect'
import { createPurchase, getProduct, listStockLocations, searchProducts, searchProviders, variantColor, variantLabel } from './api'

const RECEIPT_TYPES = ['Factura', 'Nota de Crédito', 'Nota de Débito', 'Ticket', 'Recibo', 'Sin Factura']
const LETTERS = ['A', 'B', 'C', 'X']
const CURRENCIES = ['Pesos', 'Dólares']

const emptyItem = () => ({
  product_id: '',
  product_label: '',
  product_variant_id: '',
  quantity: '1',
  unit_price: '0',
  tax_percentage: '21',
  discount_percentage: '0',
})

/** Fila de la grilla de productos: buscador, variante, cantidades y total. */
function PurchaseItemRow({ index, item, onChange, onRemove }) {
  const [product, setProduct] = useState(null)
  const [variants, setVariants] = useState([])
  const [variant, setVariant] = useState(null)
  const search = useRemoteSearch(async (q) => {
    const data = await searchProducts(q)

    return (data?.data ?? data ?? []).map((p) => ({
      value: String(p.id),
      label: p.name,
      hint: [p.code, p.bar_code].filter(Boolean).join(' · '),
    }))
  })

  // Al elegir un producto se cargan sus variantes para el select de variante.
  useEffect(() => {
    if (!product) {
      setVariants([])
      setVariant(null)
      return
    }

    let cancelled = false

    getProduct(product.value)
      .then((detail) => {
        if (cancelled) {
          return
        }

        const list = (detail?.variants ?? []).filter((v) => v.is_active !== false)

        setVariants(list)
        setVariant(list[0] ?? null)
        onChange(index, {
          ...item,
          product_id: product.value,
          product_label: product.label,
          product_variant_id: list[0] ? String(list[0].id) : '',
          unit_price: String(list[0]?.price_a_with_tax ?? detail?.price_a_with_tax ?? 0),
        })
      })
      .catch(() => setVariants([]))

    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [product])

  const lineTotal = Number(item.quantity || 0) * Number(item.unit_price || 0)

  return (
    <tr>
      <td style={{ minWidth: '16rem' }}>
        <RemoteSearchSelect
          placeholder="Buscar producto (mín. 2 caracteres)…"
          search={search.query}
          onSearch={search.setQuery}
          results={search.results}
          loading={search.loading}
          selected={product ? { label: product.label } : null}
          onSelect={(picked) => {
            setProduct(picked)
            onChange(index, { ...emptyItem(), product_id: picked ? picked.value : '', product_label: picked?.label ?? '' })
          }}
        />
      </td>
      <td>
        <select
          className="erp-control erp-select"
          value={item.product_variant_id}
          onChange={(event) => {
            const next = variants.find((v) => String(v.id) === event.target.value) ?? null

            setVariant(next)
            onChange(index, {
              ...item,
              product_variant_id: event.target.value,
              unit_price: String(next?.price_a_with_tax ?? item.unit_price),
            })
          }}
        >
          {!variants.length ? <option value="">Sin variante</option> : null}
          {variants.map((v) => (
            <option key={v.id} value={String(v.id)}>
              {variantLabel(v)}
            </option>
          ))}
        </select>
      </td>
      <td>{variantColor(variant) ?? '—'}</td>
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
      <td>
        <input
          type="number"
          className="erp-control"
          min="0"
          step="0.01"
          value={item.tax_percentage}
          onChange={(event) => onChange(index, { ...item, tax_percentage: event.target.value })}
          aria-label="% IVA"
        />
      </td>
      <td className="is-end"><strong>{money(lineTotal)}</strong></td>
      <td className="is-end">
        <button
          type="button"
          className="erp-btn erp-btn-outline-danger erp-btn--icon erp-btn--sm"
          onClick={() => onRemove(index)}
          aria-label="Quitar producto"
        >
          <Icon name="close" size={14} />
        </button>
      </td>
    </tr>
  )
}

/** Alta de comprobante de compra, réplica de `purchases/create.blade.php`. */
export function PurchaseFormPage() {
  const navigate = useNavigate()
  const toast = useToast()

  const [values, setValues] = useState({
    provider_id: '',
    receipt_type_name: 'Factura',
    letter: 'A',
    first_number: '',
    second_number: '',
    issue_date: new Date().toISOString().substring(0, 10),
    payment_due_date: new Date().toISOString().substring(0, 10),
    branch_name: '',
    warehouse_name: '',
    currency_name: 'Pesos',
  })
  const [provider, setProvider] = useState(null)
  const [items, setItems] = useState([emptyItem()])
  const [stockLocations, setStockLocations] = useState([])
  const [perception, setPerception] = useState({
    tax_type: '',
    regime_name: '',
    amount: '0',
    calculated_amount: '0',
  })
  const [saving, setSaving] = useState(false)

  const providerSearch = useRemoteSearch(async (q) => {
    const data = await searchProviders(q)

    return (data?.data ?? data ?? []).map((p) => ({
      value: String(p.id),
      label: p.name,
      hint: [p.code, p.identification_number].filter(Boolean).join(' · '),
    }))
  })

  useEffect(() => {
    listStockLocations()
      .then((locations) => {
        const list = Array.isArray(locations) ? locations : []

        setStockLocations(list)

        if (list[0]) {
          setValues((current) => ({
            ...current,
            branch_name: list[0].name,
            warehouse_name: list[0].warehouses?.[0]?.name ?? '',
          }))
        }
      })
      .catch(() => toast.error('No se pudieron cargar las sucursales y depósitos.'))
  }, [])

  const warehouses = useMemo(() => {
    const branch = stockLocations.find((item) => item.name === values.branch_name)

    return branch?.warehouses ?? []
  }, [stockLocations, values.branch_name])

  const change = (field) => (event) => {
    const { value } = event.target

    setValues((current) => {
      const next = { ...current, [field]: value }

      // Al cambiar de sucursal se reinicia el depósito con el primero disponible.
      if (field === 'branch_name') {
        const branch = stockLocations.find((item) => item.name === value)

        next.warehouse_name = branch?.warehouses?.[0]?.name ?? ''
      }

      return next
    })
  }

  const updateItem = (index, row) => {
    setItems((current) => current.map((item, i) => (i === index ? row : item)))
  }

  const total = items.reduce((acc, item) => acc + Number(item.quantity || 0) * Number(item.unit_price || 0), 0)

  const handleSubmit = async (event) => {
    event.preventDefault()

    const validItems = items.filter((item) => item.product_id)

    if (!provider) {
      toast.error('Falta el proveedor', 'Seleccioná un proveedor para la compra.')
      return
    }

    if (!validItems.length) {
      toast.error('Faltan productos', 'Agregá al menos un producto.')
      return
    }

    setSaving(true)

    try {
      const payload = {
        provider_id: Number(provider.value),
        issue_date: values.issue_date,
        payment_due_date: values.payment_due_date || null,
        receipt_type_name: values.receipt_type_name,
        letter: values.letter,
        first_number: values.first_number,
        second_number: values.second_number,
        currency_name: values.currency_name,
        branch_name: values.branch_name,
        warehouse_name: values.warehouse_name,
        items: validItems.map((item) => ({
          product_id: Number(item.product_id),
          product_variant_id: item.product_variant_id ? Number(item.product_variant_id) : null,
          quantity: Number(item.quantity),
          unit_price: Number(item.unit_price),
          tax_percentage: Number(item.tax_percentage),
          discount_percentage: Number(item.discount_percentage || 0),
        })),
        perceptions:
          perception.tax_type && Number(perception.amount) > 0
            ? [
                {
                  tax_type: perception.tax_type,
                  regime_name: perception.regime_name || perception.tax_type,
                  amount: Number(perception.amount),
                  calculated_amount: Number(perception.calculated_amount || perception.amount),
                  is_automatic: false,
                },
              ]
            : [],
      }

      const purchase = await createPurchase(payload)

      toast.success('Compra registrada', `Comprobante ${purchase.full_number ?? `#${purchase.id}`} creado.`)
      navigate(`/compras/${purchase.id}/pago`, { replace: true })
    } catch (error) {
      // Laravel no tiene las traducciones de validation: los mensajes llegan
      // como keys (p.ej. "validation.exists"). Mostramos el campo fallido.
      let message = error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.'

      if (error instanceof ApiError && error.errors && Object.keys(error.errors).length) {
        const details = Object.entries(error.errors)
          .map(([field, messages]) => `${field}: ${messages[0]}`)
          .join(' · ')

        message = `${message} — ${details}`
      }

      toast.error('No se pudo guardar la compra', message)
      setSaving(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="page-head">
        <div>
          <BackLink to="/compras">Volver a compras</BackLink>
          <h1 className="page-title">Nueva compra</h1>
          <p className="page-subtitle">Registrá un comprobante de compra a un proveedor.</p>
        </div>

        <div className="page-actions">
          <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/compras')} disabled={saving}>
            Cancelar
          </button>
          <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
            <Icon name="save" size={18} />
            {saving ? 'Guardando…' : 'Continuar a método de pago'}
          </button>
        </div>
      </div>

      <FormSection title="Comprobante" description="Datos del encabezado de la compra.">
        <div className="form-grid">
          <RemoteSearchSelect
            label="Proveedor"
            placeholder="Buscar proveedor por nombre, código o identificación…"
            search={providerSearch.query}
            onSearch={providerSearch.setQuery}
            results={providerSearch.results}
            loading={providerSearch.loading}
            selected={provider ? { label: provider.label } : null}
            onSelect={(picked) => {
              setProvider(picked)
              setValues((current) => ({ ...current, provider_id: picked ? picked.value : '' }))
            }}
          />

          <Select
            name="receipt_type_name"
            label="Tipo comprobante"
            value={values.receipt_type_name}
            onChange={change('receipt_type_name')}
            options={RECEIPT_TYPES.map((value) => ({ value, label: value }))}
          />

          <Select
            name="letter"
            label="Letra"
            value={values.letter}
            onChange={change('letter')}
            options={LETTERS.map((value) => ({ value, label: value }))}
          />

          <TextInput
            name="first_number"
            label="Pto. Vta."
            value={values.first_number}
            onChange={change('first_number')}
          />

          <TextInput
            name="second_number"
            label="Número"
            value={values.second_number}
            onChange={change('second_number')}
          />

          <TextInput
            name="issue_date"
            label="Fecha"
            type="date"
            value={values.issue_date}
            onChange={change('issue_date')}
          />

          <TextInput
            name="payment_due_date"
            label="Vencimiento"
            type="date"
            value={values.payment_due_date}
            onChange={change('payment_due_date')}
          />

          <Select
            name="warehouse_name"
            label="Depósito"
            value={values.warehouse_name}
            onChange={change('warehouse_name')}
            options={warehouses.map((warehouse) => ({ value: warehouse.name, label: warehouse.name }))}
          />

          <Select
            name="currency_name"
            label="Moneda"
            value={values.currency_name}
            onChange={change('currency_name')}
            options={CURRENCIES.map((value) => ({ value, label: value }))}
          />
        </div>
      </FormSection>

      <FormSection title="Productos" description="Ítems del comprobante.">
        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Variante</th>
                <th>Color</th>
                <th>Cant.</th>
                <th>P. Unit.</th>
                <th>% IVA</th>
                <th className="is-end">Total</th>
                <th className="is-end" />
              </tr>
            </thead>
            <tbody>
              {items.map((item, index) => (
                <PurchaseItemRow
                  key={index}
                  index={index}
                  item={item}
                  onChange={updateItem}
                  onRemove={(i) => setItems((current) => current.filter((_, j) => j !== i))}
                />
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
            Total compra: <strong>{money(total)}</strong>
          </h4>
        </div>
      </FormSection>


      <div className="form-actions">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/compras')} disabled={saving}>
          Cancelar
        </button>
        <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
          <Icon name="save" size={18} />
          {saving ? 'Guardando…' : 'Continuar a método de pago'}
        </button>
      </div>
    </form>
  )
}
