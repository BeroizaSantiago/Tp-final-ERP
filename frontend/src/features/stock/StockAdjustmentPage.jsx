import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'

import { BackLink, FormSection, Select, Textarea, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { number } from '../../lib/format'
import { RemoteSearchSelect, useRemoteSearch } from '../sales/RemoteSearchSelect'
import {
  adjustmentOptions,
  adjustmentReasons,
  createStockAdjustment,
  currentStock,
  searchStockProducts,
  stockProduct,
} from './api'

function today() {
  const now = new Date()
  return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10)
}

function variantName(variant) {
  return [variant.sku, variant.bar_code, variant.publisher?.name, variant.category?.name]
    .filter(Boolean)
    .join(' · ') || `Variante #${variant.id}`
}

export function StockAdjustmentPage() {
  const navigate = useNavigate()
  const toast = useToast()
  const [locations, setLocations] = useState([])
  const [reasons, setReasons] = useState([])
  const [product, setProduct] = useState(null)
  const [productDetail, setProductDetail] = useState(null)
  const [values, setValues] = useState({
    date: today(),
    branch_name: '',
    warehouse_name: '',
    reason_id: '',
    product_variant_id: '',
    quantity: '',
    notes: '',
  })
  const [stockBefore, setStockBefore] = useState(null)
  const [saving, setSaving] = useState(false)

  const productSearch = useRemoteSearch(async (query) => {
    const response = await searchStockProducts(query)
    return (response?.data ?? []).map((item) => ({
      value: String(item.id),
      label: item.name,
      hint: [item.code, item.bar_code].filter(Boolean).join(' · '),
    }))
  })

  useEffect(() => {
    Promise.all([adjustmentOptions(), adjustmentReasons()])
      .then(([locationData, reasonData]) => {
        const nextLocations = locationData?.locations ?? []
        setLocations(nextLocations)
        setReasons(reasonData?.data ?? reasonData ?? [])
        if (nextLocations[0]) {
          setValues((current) => ({
            ...current,
            branch_name: nextLocations[0].branch_name,
            warehouse_name: nextLocations[0].warehouse_name,
          }))
        }
      })
      .catch(() => toast.error('No se pudieron cargar las opciones de stock.'))
  }, [])

  const warehouses = useMemo(() => locations.filter((item) => item.branch_name === values.branch_name), [locations, values.branch_name])
  const variants = productDetail?.variants ?? []
  const adjustByVariant = Boolean(productDetail?.has_variants && variants.length)

  useEffect(() => {
    if (!product || !values.branch_name || !values.warehouse_name || (adjustByVariant && !values.product_variant_id)) {
      setStockBefore(null)
      return
    }

    let cancelled = false
    currentStock({
      product_id: product.value,
      product_variant_id: values.product_variant_id || '',
      branch_name: values.branch_name,
      warehouse_name: values.warehouse_name,
    }).then((response) => {
      if (!cancelled) setStockBefore(Number(response.current_stock ?? 0))
    }).catch(() => {
      if (!cancelled) setStockBefore(null)
    })
    return () => { cancelled = true }
  }, [product, values.branch_name, values.warehouse_name, values.product_variant_id, adjustByVariant])

  const selectProduct = async (selected) => {
    setProduct(selected)
    setProductDetail(null)
    setStockBefore(null)
    setValues((current) => ({ ...current, product_variant_id: '', quantity: '' }))
    if (!selected) return

    try {
      const detail = await stockProduct(selected.value)
      const activeVariants = (detail.variants ?? []).filter((variant) => variant.is_active !== false)
      setProductDetail({ ...detail, variants: activeVariants })
      if (detail.has_variants && activeVariants[0]) {
        setValues((current) => ({ ...current, product_variant_id: String(activeVariants[0].id) }))
      }
    } catch {
      toast.error('No se pudo cargar el libro seleccionado.')
    }
  }

  const difference = values.quantity === '' || stockBefore === null
    ? null
    : Number(values.quantity) - stockBefore

  const submit = async (event) => {
    event.preventDefault()
    if (!product || stockBefore === null || values.quantity === '') {
      toast.error('Ajuste incompleto', 'Seleccioná un libro y cargá el stock físico contado.')
      return
    }
    setSaving(true)
    try {
      const response = await createStockAdjustment({
        branch_name: values.branch_name,
        warehouse_name: values.warehouse_name,
        date: values.date,
        reason_id: values.reason_id ? Number(values.reason_id) : null,
        adjust_by_variant: adjustByVariant,
        notes: values.notes || null,
        items: [{
          product_id: Number(product.value),
          product_variant_id: adjustByVariant ? Number(values.product_variant_id) : null,
          quantity: Number(values.quantity),
        }],
      })
      toast.success('Stock ajustado', `Ajuste Nº ${response.adjustment?.number ?? response.adjustment?.id} registrado en la trazabilidad.`)
      navigate('/stock', { replace: true })
    } catch (error) {
      const message = error instanceof ApiError
        ? Object.values(error.errors ?? {}).flat()[0] ?? error.message
        : 'Ocurrió un error inesperado.'
      toast.error('No se pudo ajustar el stock', message)
      setSaving(false)
    }
  }

  return (
    <form onSubmit={submit} noValidate>
      <div className="page-head">
        <div>
          <BackLink to="/stock">Volver a stock</BackLink>
          <h1 className="page-title">Nuevo ajuste de stock</h1>
          <p className="page-subtitle">Corregí la existencia según el conteo físico. La diferencia quedará registrada en la trazabilidad.</p>
        </div>
      </div>

      <FormSection title="Ubicación y motivo" description="Indicá dónde se realizó el conteo y por qué se ajusta.">
        <div className="form-grid">
          <TextInput label="Fecha" type="date" value={values.date} onChange={(event) => setValues((current) => ({ ...current, date: event.target.value }))} required />
          <Select label="Sucursal" value={values.branch_name} onChange={(event) => {
            const branch = event.target.value
            const first = locations.find((item) => item.branch_name === branch)
            setValues((current) => ({ ...current, branch_name: branch, warehouse_name: first?.warehouse_name ?? '' }))
          }} options={[...new Set(locations.map((item) => item.branch_name))].map((value) => ({ value, label: value }))} required />
          <Select label="Depósito" value={values.warehouse_name} onChange={(event) => setValues((current) => ({ ...current, warehouse_name: event.target.value }))} options={warehouses.map((item) => ({ value: item.warehouse_name, label: item.warehouse_name }))} required />
          <Select label="Motivo" value={values.reason_id} onChange={(event) => setValues((current) => ({ ...current, reason_id: event.target.value }))} options={reasons.map((reason) => ({ value: reason.id, label: reason.name }))} placeholder="Sin motivo específico" />
        </div>
      </FormSection>

      <FormSection title="Libro y conteo físico" description="El valor ingresado reemplaza el stock actual; no representa unidades a sumar o restar.">
        <div className="form-grid">
          <RemoteSearchSelect label="Libro" placeholder="Buscar por título, ISBN o código…" search={productSearch.query} onSearch={productSearch.setQuery} results={productSearch.results} loading={productSearch.loading} selected={product} onSelect={selectProduct} minChars={3} />
          {adjustByVariant ? <Select label="Variante" value={values.product_variant_id} onChange={(event) => setValues((current) => ({ ...current, product_variant_id: event.target.value }))} options={variants.map((variant) => ({ value: variant.id, label: variantName(variant) }))} required /> : <div />}
          <div className="stock-adjustment-current">
            <span className="detail-label">Stock registrado</span>
            <strong>{stockBefore === null ? '—' : number(stockBefore)}</strong>
          </div>
          <TextInput label="Stock físico contado" type="number" min="0" step="0.01" value={values.quantity} onChange={(event) => setValues((current) => ({ ...current, quantity: event.target.value }))} required />
        </div>

        {difference !== null ? (
          <div className={`stock-adjustment-difference${difference < 0 ? ' is-negative' : difference > 0 ? ' is-positive' : ''}`}>
            <span>Diferencia que se registrará</span>
            <strong>{difference > 0 ? '+' : ''}{number(difference)} unidades</strong>
          </div>
        ) : null}

        <Textarea label="Observaciones" rows={3} value={values.notes} onChange={(event) => setValues((current) => ({ ...current, notes: event.target.value }))} placeholder="Detalle del conteo o la corrección realizada…" />
      </FormSection>

      <div className="form-actions">
        <button type="button" className="erp-btn erp-btn-outline" onClick={() => navigate('/stock')} disabled={saving}>Cancelar</button>
        <button type="submit" className="erp-btn erp-btn-primary" disabled={saving || difference === null}><Icon name="save" size={17} />{saving ? 'Guardando…' : 'Registrar ajuste'}</button>
      </div>
    </form>
  )
}
