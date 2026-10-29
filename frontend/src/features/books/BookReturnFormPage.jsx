import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

import { BackLink, Field, FormSection, Select, TextInput } from '../../components/fields'
import { RemoteSearchSelect, useRemoteSearch } from '../../features/sales/RemoteSearchSelect'
import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { useMasterOptions } from '../masters/useMasterOptions'
import { apiRequest } from '../../lib/api'
import { createBookReturn, getBookReturn, updateBookReturnStatus } from './api'
import { createInvoice, getProduct, listStockLocations, searchProducts, searchClients, variantLabel } from '../sales/api'

/** Página para crear o editar una devolución de libros. */
export function BookReturnFormPage() {
  const navigate = useNavigate()
  const { id } = useParams()
  const [returnType, setReturnType] = useState('client')
  const [form, setForm] = useState({
    return_type: 'client',
    returnable_type: '',
    returnable_id: '',
    quantity: 1,
    condition: '',
    reason: '',
    client_id: '',
    provider_id: '',
    invoice_id: '',
    purchase_id: '',
    idempotency_key: '',
  })
  const [loading, setLoading] = useState(false)
  const [submitDisabled, setSubmitDisabled] = useState(false)
  const toast = useToast()
  const confirm = useConfirm()

  // Cargar datos de la devolución si es edición
  useEffect(() => {
    if (id) {
      const loadReturn = async () => {
        try {
          const response = await getBookReturn(id)
          setForm({
            return_type: response.return_type,
            returnable_type: response.returnable_type,
            returnable_id: response.returnable_id?.toString() ?? '',
            quantity: response.quantity ?? 1,
            condition: response.condition ?? '',
            reason: response.reason ?? '',
            client_id: response.client_id ?? '',
            provider_id: response.provider_id ?? '',
            invoice_id: response.invoice_id ?? '',
            purchase_id: response.purchase_id ?? '',
            idempotency_key: response.idempotency_key ?? '',
          })
          setReturnType(response.return_type)
        } catch (err) {
          toast.error('Error', 'No se cargó la devolución')
          navigate('/devoluciones-libros')
        }
      }
      loadReturn()
    }
  }, [id])

  // Master options
  const { options: clients } = useMasterOptions('clients') || []
  const { options: providers } = useMasterOptions('providers') || []
  const { options: productTypes } = useMasterOptions('product-types') || [
    { value: '', label: 'Seleccionar producto' },
  ]

  // Búsqueda de cliente/proveedor con remote search
  const { options: clientSearchOptions } = useMasterOptions('clients') || []

  const clientSearch = useRemoteSearch(async (q) => {
      const data = await searchClients(q)
  
      return (data?.data ?? data ?? []).map((p) => ({
        value: String(p.id),
        label: p.name,
        hint: [p.code, p.identification_number].filter(Boolean).join(' · '),
      }))
  })

  const { options: providerSearchOptions } = useMasterOptions('providers') || []
  const providerSearch = useRemoteSearch(async (query) => {
    const value = query.trim()
    if (!value) {
      return []
    }
    // Filtrar proveedores por nombre o documento
    return providerSearchOptions
      .filter((opt) => {
        const nameMatch = (opt.label || '').toLowerCase().includes(value.toLowerCase())
        const docMatch = (opt.hint || '').toLowerCase().includes(value.toLowerCase())
        return nameMatch || docMatch
      })
      .slice(0, 10)
  })

  // Buscar facturas para el cliente seleccionado
  const [invoices, setInvoices] = useState([])
  const [invoicesLoading, setInvoicesLoading] = useState(false)

  // Cargar facturas cuando el cliente cambia
  useEffect(() => {
    const loadInvoices = async () => {
      if (!form.client_id) {
        setInvoices([])
        setInvoicesLoading(false)
        return
      }
      setInvoicesLoading(true)
      try {
        // Usar la búsqueda existente de facturas con filtro client_id
        const result = await apiRequest(`/invoices?client_id=${form.client_id}`, {
          headers: { Accept: 'application/json' }
        })
        setInvoices(Array.isArray(result) ? result : [])
      } catch (err) {
        console.error('Error loading invoices:', err)
        setInvoices([])
      } finally {
        setInvoicesLoading(false)
      }
    }
    loadInvoices()
  }, [form.client_id])

  // Validar que los campos requeridos estén completos
  const isValid = useMemo(() => {
    const { return_type, returnable_type, returnable_id, quantity, reason } = form
    return (
      return_type &&
      returnable_type &&
      returnable_id &&
      quantity > 0 &&
      reason.trim().length > 0
    )
  }, [form])

  // Cambiar tipo de devolución y limpiar campos relacionados
  const handleReturnTypeChange = (e) => {
    const value = e.target.value
    setReturnType(value)
    setForm((prev) => {
      const newForm = { ...prev, return_type: value }

      // Limpiar campos dependientes del tipo
      if (value === 'client') {
        newForm.client_id = ''
        newForm.provider_id = ''
        newForm.invoice_id = ''
        newForm.purchase_id = ''
      } else if (value === 'supplier') {
        newForm.provider_id = ''
        newForm.invoice_id = ''
      }
      return newForm
    })
  }

  // Cambiar producto (returnable)
  const handleReturnableChange = (e) => {
    const value = e.target.value
    setForm((prev) => ({ ...prev, returnable_id: value }))
  }

  // Cambiar campo de formulario
  const handleChange = (e) => {
    const { name, value } = e.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setSubmitDisabled(true)

    try {
      // Determinar qué ID enviar según el tipo
      const payload = {
        return_type: form.return_type,
        returnable_type: form.returnable_type,
        returnable_id: parseInt(form.returnable_id) || undefined,
        quantity: form.quantity,
        condition: form.condition,
        reason: form.reason,
        idempotency_key: form.idempotency_key || `return-${Date.now()}`,
      }

      // Agregar client_id o provider_id según el tipo
      if (form.return_type === 'client' && form.client_id) {
        payload.client_id = parseInt(form.client_id)
      }
      if (form.return_type === 'supplier' && form.provider_id) {
        payload.provider_id = parseInt(form.provider_id)
      }

      // Agregar invoice_id si está seleccionado y es tipo client
      if (form.return_type === 'client' && form.invoice_id) {
        payload.invoice_id = parseInt(form.invoice_id)
      }

      let response

      if (id) {
        // Actualizar existente - usar PUT en el endpoint appropriate
        response = await updateBookReturnStatus(id, form.return_type === 'pending' ? 'approved' : 'pending')
      } else {
        // Crear nueva
        response = await createBookReturn(payload)
      }

      toast.success(
        id ? 'Devolución actualizada' : 'Devolución creada',
        response.message || 'La operación se completó exitosamente.',
      )
      navigate(`/devoluciones-libros/${response.id ?? id}`)
    } catch (err) {
      console.error('Error creating book return:', err)
      toast.error(
        'Error',
        err instanceof ApiError ? err.message : 'Ocurrió un error inesperado.',
      )
    } finally {
      setSubmitDisabled(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="page">
        <div className="page-head">
          <div>
            <BackLink to="/devoluciones-libros">Volver a devoluciones</BackLink>
            <h1 className="page-title">{id ? 'Editar devolución' : 'Nueva devolución'}</h1>
            <p className="page-subtitle">Devolución de libros a {' '}
              <span className="text-primary">{returnType === 'client' ? 'un cliente' : 'un proveedor'}</span>
            </p>
          </div>
        </div>

        <FormSection title="Comprobante" description="Datos del encabezado de la venta.">
          <div className="form-grid">
            <Field disabled={loading} error>
              {returnType === 'client' ? (
                <RemoteSearchSelect
                  label="Cliente"
                  placeholder="Buscar cliente por nombre, código o identificación…"
                  onSearch={clientSearch.setQuery}
                  results={clientSearch.results}
                  loading={clientSearch.loading}
                  selected={form.client_id ? { label: form.client_id } : null}
                  onSelect={(picked) => {
                    setForm((prev) => ({ ...prev, client_id: picked ? String(picked.value) : '' }))
                  }}
                />
              ) : null}

              {returnType === 'supplier' ? (
                <RemoteSearchSelect
                  label="Proveedor"
                  placeholder="Buscar proveedor por nombre, código o identificación…"
                  onSearch={providerSearch.setQuery}
                  results={providerSearch.results}
                  loading={providerSearch.loading}
                  selected={form.provider_id ? { label: form.provider_id } : null}
                  onSelect={(picked) => {
                    setForm((prev) => ({ ...prev, provider_id: picked ? String(picked.value) : '' }))
                  }}
                />
              ) : null}
            </Field>

            {returnType === 'client' && form.client_id ? (
              <Field disabled={invoicesLoading} error>
                <Select
                  name="invoice_id"
                  label="Comprobante Compra"
                  value={form.invoice_id || ''}
                  onChange={handleChange}
                  error
                >
                  <option value="">Seleccionar comprobante</option>
                  {invoices.length > 0 ? (
                    invoices.map((option) => (
                      <option key={option.id} value={option.id}>
                        {option.full_number || (option.customer_name ? `Venta ${option.customer_name}` : '')}
                        {option.receipt_type_name ? `<small>${option.receipt_type_name}</small>` : null}
                        {option.issue_date ? `<small>${option.issue_date}</small>` : null}
                      </option>
                    ))
                  ) : (
                    <option value="">No hay comprobantes de venta</option>
                  )}
                </Select>
              </Field>
            ) : null}

            {returnType === 'client' ? (
              <TextInput
                type="text"
                name="invoice_id_alt"
                label="Factura relacionada"
                value={form.invoice_id}
                onChange={handleChange}
                placeholder="ID de factura relacionada (opcional)"
                disabled={loading}
                error
              />
            ) : null}

            {returnType === 'supplier' ? (
              <TextInput
                type="text"
                name="purchase_id"
                label="Compra relacionada"
                value={form.purchase_id}
                onChange={handleChange}
                placeholder="ID de compra relacionada (opcional)"
                disabled={loading}
                error
              />
            ) : null}
          
          <input
            type="hidden"
            name="returnable_type"
            value={form.returnable_type}
            disabled
          />

          <Field disabled={loading} error>
            <Select
              name="return_type"
              label="Tipo de devolución"
              value={returnType}
              onChange={handleReturnTypeChange}
              disabled={loading}
              error
            >
              <option value="">Seleccionar tipo</option>
              <option value="client">A cliente</option>
              <option value="supplier">A proveedor</option>
            </Select>
          </Field>

          <Field disabled={loading || returnType === 'supplier'} error>
            <Select
              name="returnable_id"
              label="Producto a devolver"
              value={form.returnable_id}
              onChange={handleReturnableChange}
              disabled={loading || returnType === 'supplier'}
              error
            >
              <option value="">Seleccionar producto</option>
              {productTypes.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </Select>
          </Field>

          <TextInput
            type="number"
            name="quantity"
            label="Cantidad"
            value={form.quantity}
            onChange={handleChange}
            min={1}
            disabled={loading}
            error
            placeholder="1"
          />

          <TextInput
            type="text"
            name="condition"
            label="Condición"
            value={form.condition}
            onChange={handleChange}
            placeholder="Ej: Bueno, Dañado, etc."
            disabled={loading}
            error
          />

          <TextInput
            type="text"
            name="reason"
            label="Motivo de la devolución"
            value={form.reason}
            onChange={handleChange}
            placeholder="Motivo de la devolución..."
            disabled={loading}
            error
            required
          />

          {/* Key de idempotencia */}
          <TextInput
            type="text"
            name="idempotency_key"
            label="Key de idempotencia"
            value={form.idempotency_key}
            onChange={handleChange}
            placeholder="Autogenerado (opcional)"
            disabled={loading}
            error
            helpText="Dejar vacío para generar automáticamente"
          />
        </div>
      </FormSection>

      <div className="form-actions">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/devoluciones-libros')} disabled={submitDisabled}>
          Cancelar
        </button>
        <button className="erp-btn erp-btn-primary" type="submit" disabled={!isValid || submitDisabled}>
          {id ? 'Actualizar' : 'Crear'}
        </button>
      </div>
    </div>
  </form>
  )
}