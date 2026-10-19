import { useState } from 'react'

import { FieldRow, Switch, TextInput, Textarea } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { createClient, updateClient } from './api'

/**
 * Formulario de cliente.
 *
 * Se usa en dos lugares: el ABM de clientes del módulo de ventas (alta y
 * edición) y el alta rápida desde la pantalla de nueva venta. Por eso el
 * formulario acepta un modo "quick", que muestra sólo los datos indispensables
 * para poder cobrar, y un modo "full" con todos los campos de la ficha.
 */

const EMPTY_CLIENT = {
  name: '',
  fantasy_name: '',
  code: '',
  document_type: '',
  document_number: '',
  vat_classification: '',
  gross_income_classification: '',
  birth_date: '',
  first_phone: '',
  second_phone: '',
  email: '',
  address: '',
  address_number: '',
  apartment: '',
  neighborhood: '',
  zip_code: '',
  city: '',
  state: '',
  country: '',
  payment_condition: 'CONTADO',
  currency: 'Pesos',
  price_type: 'Precio A',
  discount: '0',
  credit_limit: '',
  is_wholesaler: false,
  use_credit_invoice: false,
  is_active: true,
  notes: '',
}

const DOCUMENT_TYPES = ['DNI', 'CUIT', 'CUIL', 'Pasaporte', 'Consumidor Final']
const PAYMENT_CONDITIONS = ['CONTADO', 'Cuenta Corriente']
const CURRENCIES = ['Pesos', 'Dólares']
const PRICE_TYPES = ['Precio A', 'Precio B', 'Precio C']

/** Normaliza el cliente que llega de la API a los valores del formulario. */
function toFormValues(client) {
  return {
    ...EMPTY_CLIENT,
    ...client,
    // La API devuelve las fechas con hora: los <input type="date"> quieren
    // sólo el día.
    birth_date: client?.birth_date ? String(client.birth_date).slice(0, 10) : '',
    discount: client?.discount === null || client?.discount === undefined ? '0' : String(client.discount),
    credit_limit: client?.credit_limit === null || client?.credit_limit === undefined ? '' : String(client.credit_limit),
  }
}

/** Convierte los valores del formulario en el cuerpo que espera la API. */
export function clientPayload(values) {
  return {
    name: values.name,
    fantasy_name: values.fantasy_name || null,
    code: values.code || null,
    document_type: values.document_type || null,
    document_number: values.document_number || null,
    vat_classification: values.vat_classification || null,
    gross_income_classification: values.gross_income_classification || null,
    birth_date: values.birth_date || null,
    first_phone: values.first_phone || null,
    second_phone: values.second_phone || null,
    email: values.email || null,
    address: values.address || null,
    address_number: values.address_number || null,
    apartment: values.apartment || null,
    neighborhood: values.neighborhood || null,
    zip_code: values.zip_code || null,
    city: values.city || null,
    state: values.state || null,
    country: values.country || null,
    payment_condition: values.payment_condition || null,
    currency: values.currency || null,
    price_type: values.price_type || null,
    discount: values.discount === '' ? 0 : Number(values.discount),
    credit_limit: values.credit_limit === '' ? null : Number(values.credit_limit),
    is_wholesaler: Boolean(values.is_wholesaler),
    use_credit_invoice: Boolean(values.use_credit_invoice),
    is_active: Boolean(values.is_active),
    notes: values.notes || null,
  }
}

export function ClientFormModal({ client = null, mode = 'full', onClose, onSaved }) {
  const toast = useToast()
  const isEditing = Boolean(client?.id)

  const [values, setValues] = useState(() => toFormValues(client))
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const change = (name) => (event) => {
    const { value, checked, type } = event.target

    setValues((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
    setErrors((current) => ({ ...current, [name]: undefined }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setSaving(true)
    setErrors({})

    try {
      const body = clientPayload(values)

      const saved = isEditing
        ? await updateClient(client.id, body)
        : await createClient(body)

      toast.success(isEditing ? 'Cliente actualizado' : 'Cliente creado', saved?.name ?? body.name)
      onSaved(saved)
      onClose()
    } catch (error) {
      if (error instanceof ApiError) {
        const fieldErrors = error.errors ?? {}

        if (Object.keys(fieldErrors).length) {
          setErrors(fieldErrors)
        } else {
          toast.error('No se pudo guardar', error.message)
        }
      } else {
        toast.error('No se pudo guardar', 'Ocurrió un error inesperado.')
      }
    } finally {
      setSaving(false)
    }
  }

  const title = isEditing ? 'Editar cliente' : 'Nuevo cliente'

  return (
    <div className="modal-backdrop-custom" role="presentation" onClick={onClose}>
      <div
        className="modal-card modal-card--form"
        role="dialog"
        aria-modal="true"
        aria-labelledby="clientFormTitle"
        onClick={(event) => event.stopPropagation()}
      >
        <form onSubmit={handleSubmit} noValidate>
          <div className="modal-card-head">
            <h3 className="modal-card-title" id="clientFormTitle">
              {title}
            </h3>
            <button className="modal-card-close" type="button" onClick={onClose} aria-label="Cerrar">
              <Icon name="close" size={18} />
            </button>
          </div>

          <div className="modal-card-body" style={{ maxHeight: '68vh', overflowY: 'auto' }}>
            <FieldRow>
              <TextInput
                name="name"
                label="Nombre / Razón social"
                required
                value={values.name}
                onChange={change('name')}
                error={errors.name?.[0]}
              />

              <TextInput
                name="fantasy_name"
                label="Nombre fantasía"
                value={values.fantasy_name}
                onChange={change('fantasy_name')}
                error={errors.fantasy_name?.[0]}
              />
            </FieldRow>

            <FieldRow>
              <div className="erp-field-block">
                <label className="erp-label" htmlFor="client-document-type">Tipo de documento</label>
                <select
                  id="client-document-type"
                  className={`erp-control erp-select${errors.document_type ? ' is-invalid' : ''}`}
                  value={values.document_type ?? ''}
                  onChange={change('document_type')}
                >
                  <option value="">Seleccione…</option>
                  {DOCUMENT_TYPES.map((type) => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
                {errors.document_type ? <div className="erp-invalid-feedback">{errors.document_type[0]}</div> : null}
              </div>

              <TextInput
                name="document_number"
                label="Nro. de documento"
                value={values.document_number}
                onChange={change('document_number')}
                error={errors.document_number?.[0]}
              />
            </FieldRow>

            <FieldRow>
              <TextInput
                name="first_phone"
                label="Teléfono"
                value={values.first_phone}
                onChange={change('first_phone')}
                error={errors.first_phone?.[0]}
              />

              <TextInput
                name="email"
                label="Correo electrónico"
                type="email"
                value={values.email}
                onChange={change('email')}
                error={errors.email?.[0]}
              />
            </FieldRow>

            {mode === 'quick' ? null : (
              <>
                <hr className="app-nav-divider" />

                <FieldRow>
                  <TextInput
                    name="code"
                    label="Código"
                    value={values.code}
                    onChange={change('code')}
                    error={errors.code?.[0]}
                  />

                  <TextInput
                    name="birth_date"
                    label="Fecha de nacimiento"
                    type="date"
                    value={values.birth_date ?? ''}
                    onChange={change('birth_date')}
                    error={errors.birth_date?.[0]}
                  />
                </FieldRow>

                <FieldRow>
                  <TextInput
                    name="vat_classification"
                    label="Condición frente al IVA"
                    value={values.vat_classification}
                    onChange={change('vat_classification')}
                    error={errors.vat_classification?.[0]}
                  />

                  <TextInput
                    name="gross_income_classification"
                    label="Ingresos brutos"
                    value={values.gross_income_classification}
                    onChange={change('gross_income_classification')}
                    error={errors.gross_income_classification?.[0]}
                  />
                </FieldRow>

                <FieldRow>
                  <TextInput
                    name="address"
                    label="Domicilio"
                    value={values.address}
                    onChange={change('address')}
                    error={errors.address?.[0]}
                  />

                  <TextInput
                    name="address_number"
                    label="Nro."
                    value={values.address_number}
                    onChange={change('address_number')}
                    error={errors.address_number?.[0]}
                  />
                </FieldRow>

                <FieldRow>
                  <TextInput
                    name="city"
                    label="Localidad"
                    value={values.city}
                    onChange={change('city')}
                    error={errors.city?.[0]}
                  />

                  <TextInput
                    name="state"
                    label="Provincia"
                    value={values.state}
                    onChange={change('state')}
                    error={errors.state?.[0]}
                  />
                </FieldRow>

                <FieldRow>
                  <div className="erp-field-block">
                    <label className="erp-label" htmlFor="client-payment-condition">Condición de pago</label>
                    <select
                      id="client-payment-condition"
                      className="erp-control erp-select"
                      value={values.payment_condition ?? ''}
                      onChange={change('payment_condition')}
                    >
                      <option value="">Seleccione…</option>
                      {PAYMENT_CONDITIONS.map((option) => (
                        <option key={option} value={option}>{option}</option>
                      ))}
                    </select>
                  </div>

                  <div className="erp-field-block">
                    <label className="erp-label" htmlFor="client-price-type">Tipo de precio</label>
                    <select
                      id="client-price-type"
                      className="erp-control erp-select"
                      value={values.price_type ?? ''}
                      onChange={change('price_type')}
                    >
                      <option value="">Seleccione…</option>
                      {PRICE_TYPES.map((option) => (
                        <option key={option} value={option}>{option}</option>
                      ))}
                    </select>
                  </div>
                </FieldRow>

                <FieldRow>
                  <div className="erp-field-block">
                    <label className="erp-label" htmlFor="client-currency">Moneda</label>
                    <select
                      id="client-currency"
                      className="erp-control erp-select"
                      value={values.currency ?? ''}
                      onChange={change('currency')}
                    >
                      <option value="">Seleccione…</option>
                      {CURRENCIES.map((option) => (
                        <option key={option} value={option}>{option}</option>
                      ))}
                    </select>
                  </div>

                  <div className="erp-field-block">
                    <label className="erp-label" htmlFor="client-second-phone">Teléfono alternativo</label>
                    <input
                      id="client-second-phone"
                      type="text"
                      className="erp-control"
                      value={values.second_phone ?? ''}
                      onChange={change('second_phone')}
                    />
                  </div>
                </FieldRow>

                <FieldRow>
                  <TextInput
                    name="credit_limit"
                    label="Límite de crédito"
                    type="number"
                    min="0"
                    step="0.01"
                    value={values.credit_limit}
                    onChange={change('credit_limit')}
                    error={errors.credit_limit?.[0]}
                  />

                  <TextInput
                    name="discount"
                    label="Descuento"
                    type="number"
                    min="0"
                    step="0.01"
                    value={values.discount}
                    onChange={change('discount')}
                    error={errors.discount?.[0]}
                  />
                </FieldRow>

                <Textarea
                  name="notes"
                  label="Notas"
                  rows={3}
                  value={values.notes ?? ''}
                  onChange={change('notes')}
                  error={errors.notes?.[0]}
                />
              </>
            )}

            <FieldRow columns={3}>
              <div className="erp-field-block">
                <Switch
                  id="client-is-wholesaler"
                  label="Es mayorista"
                  checked={Boolean(values.is_wholesaler)}
                  onChange={change('is_wholesaler')}
                />
              </div>

              <div className="erp-field-block">
                <Switch
                  id="client-use-credit-invoice"
                  label="Usa factura de crédito"
                  checked={Boolean(values.use_credit_invoice)}
                  onChange={change('use_credit_invoice')}
                />
              </div>

              <div className="erp-field-block">
                <Switch
                  id="client-is-active"
                  label="Cliente activo"
                  checked={Boolean(values.is_active)}
                  onChange={change('is_active')}
                />
              </div>
            </FieldRow>
          </div>

          <div className="modal-card-actions">
            <button className="erp-btn erp-btn-outline" type="button" onClick={onClose}>
              Cancelar
            </button>
            <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
              {saving ? 'Guardando…' : 'Guardar cliente'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
