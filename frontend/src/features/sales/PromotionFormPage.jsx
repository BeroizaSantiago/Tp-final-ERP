import { useCallback, useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

import { BackLink, Field, FieldRow, FormSection, Select, Switch, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { ApiError, apiRequest } from '../../lib/api'
import { toInput } from '../../lib/format'
import { useToast } from '../../context/ToastContext'
import {
  APPLIES_TO_COLUMN,
  APPLIES_TO_LABEL,
  APPLIES_TO_OPTIONS,
  DISCOUNT_TYPE_LABEL,
  createSalesPromotion,
  getSalesPromotion,
  listAuthors,
  listBrands,
  listCollections,
  listProductCategories,
  listPublishers,
  updateSalesPromotion,
} from './api'

const DISCOUNT_TYPE_OPTIONS = [
  { value: 'percentage', label: 'Porcentaje' },
  { value: 'fixed_amount', label: 'Importe fijo' },
  { value: 'special_price', label: 'Precio especial' },
  { value: 'combo', label: 'Combo' },
]

/** Fetchers de opciones por alcance de la promoción. */
const SCOPE_FETCHERS = {
  product: listProductsRemote,
  author: listAuthors,
  category: listProductCategories,
  collection: listCollections,
  publisher: listPublishers,
  brand: listBrands,
}

/** Productos no tienen un listado dedicado en el módulo de ventas, se consulta el catálogo general. */
async function listProductsRemote(params) {
  return apiRequest(`/products${buildQuery(params)}`)
}

function buildQuery(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined || value === '') {
      return
    }
    search.set(key, String(value))
  })
  const query = search.toString()
  return query ? `?${query}` : ''
}

const emptyForm = () => ({
  name: '',
  applies_to: 'all',
  discount_type: 'percentage',
  discount_value: '',
  date_from: '',
  date_to: '',
  is_active: true,
})

export function PromotionFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { success, error: toastError } = useToast()
  const isEdit = Boolean(id)

  const [form, setForm] = useState(emptyForm)
  const [items, setItems] = useState([])
  const [options, setOptions] = useState([])
  const [pendingAdd, setPendingAdd] = useState('')
  const [errors, setErrors] = useState({})
  const [generalError, setGeneralError] = useState(null)
  const [saving, setSaving] = useState(false)
  const [loading, setLoading] = useState(isEdit)

  const column = useMemo(() => APPLIES_TO_COLUMN[form.applies_to], [form.applies_to])
  const scopeLabel = useMemo(() => APPLIES_TO_LABEL[form.applies_to], [form.applies_to])
  const availableOptions = useMemo(() => {
    const selectedIds = new Set(items.map((item) => item.id))
    return options.filter((option) => !selectedIds.has(option.value))
  }, [options, items])

  // Carga los opciones del maestro correspondiente cuando cambia el alcance.
  useEffect(() => {
    setOptions([])
    setPendingAdd('')
    if (form.applies_to === 'all') return

    const fetcher = SCOPE_FETCHERS[form.applies_to]
    if (!fetcher) return

    let cancelled = false
    setLoading(true)
    fetcher({ per_page: '100' })
      .then((payload) => {
        if (cancelled) return
        const list = payload?.data ?? payload ?? []
        setOptions(list.map((option) => ({ value: String(option.id), label: option.name })))
      })
      .catch(() => setOptions([]))
      .finally(() => setLoading(false))

    return () => {
      cancelled = true
    }
  }, [form.applies_to])

  const changeScope = useCallback((nextScope) => {
    setForm((current) => ({ ...current, applies_to: nextScope }))
    setItems([])
    setOptions([])
    setPendingAdd('')
    setErrors((current) => ({ ...current, applies_to: undefined, items: undefined }))
  }, [])

  // Al editar, carga la promoción y sus ítems alcanzados.
  useEffect(() => {
    if (!id) {
      setLoading(false)
      return
    }
    let cancelled = false
    setLoading(true)
    getSalesPromotion(id)
      .then((promotion) => {
        if (cancelled) return
        setForm({
          name: promotion.name ?? '',
          applies_to: promotion.applies_to ?? 'all',
          discount_type: promotion.discount_type ?? 'percentage',
          discount_value: toInput(promotion.discount_value),
          date_from: promotion.date_from ?? '',
          date_to: promotion.date_to ?? '',
          is_active: promotion.is_active ?? true,
        })
        const col = APPLIES_TO_COLUMN[promotion.applies_to] || 'product_id'
        setItems(
          (promotion.items ?? [])
            .map((item) => {
              const value = item[col] == null ? null : String(item[col])
              return {
                key: col,
                id: value,
                label:
                  item.product?.name ??
                  item.author?.name ??
                  item.category?.name ??
                  item.collection?.name ??
                  item.publisher?.name ??
                  item.brand?.name ??
                  'Sin nombre',
              }
            })
            .filter((item) => item.id),
        )
      })
      .catch(() => toastError('No se pudo cargar la promoción', 'Inténtalo nuevamente.'))
      .finally(() => setLoading(false))

    return () => {
      cancelled = true
    }
  }, [id, toastError])

  const addItem = useCallback(() => {
    if (!column || !pendingAdd) return
    if (items.some((item) => item.id === pendingAdd)) {
      setPendingAdd('')
      return
    }
    const option = options.find((option) => option.value === pendingAdd)
    if (!option) return
    setItems([...items, { key: column, id: pendingAdd, label: option.label }])
    setPendingAdd('')
  }, [column, pendingAdd, items, options])

  const removeItem = useCallback((index) => {
    setItems(items.filter((_, i) => i !== index))
  }, [items])

  const validate = useCallback(() => {
    const next = {}
    if (!form.name.trim()) {
      next.name = 'Ingresá el nombre de la promoción.'
    }
    const value = Number(form.discount_value)
    if (form.discount_value === '' || !form.discount_type || Number.isNaN(value) || value < 0) {
      next.discount_value = 'Ingresá un descuento válido (mayor o igual a 0).'
    } else if (form.discount_type === 'percentage' && value > 100) {
      next.discount_value = 'El porcentaje no puede superar el 100%.'
    }
    if (form.applies_to !== 'all' && items.length === 0) {
      next.items = `Seleccioná al menos un elemento de ${scopeLabel.toLowerCase()}.`
    }
    if (form.date_from && form.date_to && form.date_to < form.date_from) {
      next.date_to = 'La fecha de vigencia hasta no puede ser anterior a la desde.'
    }
    setErrors(next)
    return Object.keys(next).length === 0
  }, [form, items, scopeLabel])

  const buildPayload = useCallback(() => {
    const itemsPayload =
      form.applies_to === 'all'
        ? []
        : items.map((item) => ({ [item.key]: item.id }))

    return {
      name: form.name.trim(),
      promotion_type: 'venta',
      applies_to: form.applies_to,
      discount_type: form.discount_type,
      discount_value: Number(form.discount_value) || 0,
      display_mode: 'line',
      date_from: form.date_from || null,
      date_to: form.date_to || null,
      is_active: form.is_active,
      items: itemsPayload,
    }
  }, [form, items])

  const onSubmit = async (event) => {
    event.preventDefault()
    setGeneralError(null)
    if (!validate()) return

    setSaving(true)
    try {
      const payload = buildPayload()
      if (isEdit) {
        await updateSalesPromotion(id, payload)
        success('Promoción actualizada', 'Los cambios se guardaron correctamente.')
      } else {
        await createSalesPromotion(payload)
        success('Promoción creada', 'La promoción quedó registrada y lista para usarse.')
      }
      navigate('/ventas/promociones')
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setErrors(
          Object.fromEntries(
            Object.entries(err.errors).map(([field, messages]) => [
              field,
              Array.isArray(messages) ? messages[0] : messages,
            ]),
          ),
        )
      }
      setGeneralError(err?.message || 'No se pudo guardar la promoción. Intentá nuevamente.')
      toastError('No se pudo guardar la promoción', err?.message || 'Verificá los datos e intentá nuevamente.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <BackLink to="/ventas/promociones">Volver a promociones</BackLink>

      <div className="page-head promotion-form-head">
        <div>
          <h1 className="page-title">{isEdit ? 'Editar promoción' : 'Nueva promoción'}</h1>
          <p className="page-subtitle">
            Configurá el descuento, los libros alcanzados y el período de vigencia.
          </p>
        </div>
        <span className={`promotion-status ${form.is_active ? 'is-active' : 'is-inactive'}`}>
          {form.is_active ? 'Activa' : 'Inactiva'}
        </span>
      </div>

      {generalError ? (
        <div className="promotion-form-error" role="alert">{generalError}</div>
      ) : null}

        <form className="promotion-form" onSubmit={onSubmit} noValidate>
          <FormSection title="Datos de la promoción">
            <FieldRow>
              <TextInput
                label="Nombre"
                error={errors.name}
                required
                hint="Ejemplo: 20% OFF en novedades o Descuento Día del Libro."
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
              />

              <Select
                label="Alcance"
                error={errors.applies_to}
                options={APPLIES_TO_OPTIONS}
                value={form.applies_to}
                onChange={(event) => changeScope(event.target.value)}
                required
              />
            </FieldRow>

            {form.applies_to !== 'all' ? (
              <div className="promotion-scope-picker">
                <Field label={`Ítems de ${scopeLabel}`} hint="Seleccioná uno o más alcances específicos.">
                  <div className="promotion-selected-items">
                    {items.map((item, index) => (
                      <span key={item.id} className="promo-chip">
                        {item.label}
                        <button type="button" onClick={() => removeItem(index)} aria-label={`Quitar ${item.label}`}>
                          ×
                        </button>
                      </span>
                    ))}
                    {items.length === 0 && (
                      <span style={{ color: 'var(--erp-secondary-color)', fontSize: '0.85rem' }}>
                        Sin ítems seleccionados
                      </span>
                    )}
                  </div>
                  <div className="promotion-add-scope">
                    <Select
                      options={availableOptions}
                      value={pendingAdd}
                      placeholder="Seleccionar…"
                      onChange={(event) => setPendingAdd(event.target.value)}
                    />
                    <button
                      className="erp-btn erp-btn-primary erp-btn--sm"
                      type="button"
                      onClick={addItem}
                      disabled={!pendingAdd || saving}
                    >
                      Agregar
                    </button>
                  </div>
                  {errors.items ? <div className="erp-invalid-feedback">{errors.items}</div> : null}
                </Field>
              </div>
            ) : null}

            <Switch
              label="Promoción activa"
              checked={form.is_active}
              onChange={(event) => setForm({ ...form, is_active: event.target.checked })}
              hint="Solo las promociones activas se muestran al cobrar una venta."
            />
          </FormSection>

          <FormSection title="Descuento">
            <FieldRow>
              <Select
                label="Tipo de descuento"
                error={errors.discount_type}
                options={DISCOUNT_TYPE_OPTIONS}
                value={form.discount_type}
                onChange={(event) => setForm({ ...form, discount_type: event.target.value })}
                required
              />

              <TextInput
                label={form.discount_type === 'percentage' ? 'Porcentaje' : 'Importe'}
                error={errors.discount_value}
                hint={form.discount_type === 'percentage' ? 'Ingresá un valor entre 0 y 100.' : 'Ingresá el importe en pesos.'}
                type="number"
                min="0"
                max={form.discount_type === 'percentage' ? '100' : undefined}
                step="any"
                value={form.discount_value}
                onChange={(event) => setForm({ ...form, discount_value: event.target.value })}
                required
              />
            </FieldRow>
          </FormSection>

          <FormSection title="Vigencia">
            <FieldRow>
              <TextInput
                label="Vigente desde"
                type="date"
                value={form.date_from}
                onChange={(event) => setForm({ ...form, date_from: event.target.value })}
              />

              <TextInput
                label="Vigente hasta"
                error={errors.date_to}
                type="date"
                value={form.date_to}
                onChange={(event) => setForm({ ...form, date_to: event.target.value })}
              />
            </FieldRow>
          </FormSection>

          <div className="form-actions promotion-form-actions">
            <button
              className="erp-btn erp-btn-outline"
              type="button"
              onClick={() => navigate('/ventas/promociones')}
              disabled={saving}
            >
              Cancelar
            </button>
            <button
              className="erp-btn erp-btn-primary"
              type="submit"
              disabled={saving}
            >
              {saving ? <span>Guardando…</span> : isEdit ? 'Guardar cambios' : 'Crear promoción'}
            </button>
          </div>
        </form>
    </>
  )
}
