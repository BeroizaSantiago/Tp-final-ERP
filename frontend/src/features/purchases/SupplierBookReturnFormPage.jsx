import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

import { BackLink, Field, FormSection, Select, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { useMasterOptions } from '../masters/useMasterOptions'
import { createSupplierBookReturn, getSupplierBookReturn, updateSupplierBookReturnStatus } from './api'
import { listProviders } from './api'

/** Página para crear o editar una devolución de libros a proveedor. */
export function SupplierBookReturnFormPage() {
  const navigate = useNavigate()
  const { id } = useParams()
  const [returnType, setReturnType] = useState('supplier')
  const [form, setForm] = useState({
    return_type: 'supplier',
    returnable_type: '',
    returnable_id: '',
    quantity: 1,
    condition: '',
    reason: '',
    provider_id: '',
    purchase_id: '',
    invoice_id: '',
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
          const response = await getSupplierBookReturn(id)
          setForm({
            return_type: response.return_type,
            returnable_type: response.returnable_type,
            returnable_id: response.returnable_id?.toString() ?? '',
            quantity: response.quantity ?? 1,
            condition: response.condition ?? '',
            reason: response.reason ?? '',
            provider_id: response.provider_id ?? '',
            purchase_id: response.purchase_id ?? '',
            invoice_id: response.invoice_id ?? '',
            idempotency_key: response.idempotency_key ?? '',
          })
        } catch (err) {
          toast.error('Error', 'No se cargó la devolución')
          navigate('/devoluciones-proveedor')
        }
      }
      loadReturn()
    }
  }, [id])

  // Master options
  const { options: providers } = useMasterOptions('providers') || []
  const { options: productTypes } = useMasterOptions('product-types') || [
    { value: '', label: 'Seleccionar producto' },
  ]

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

  // Cambiar tipo de devolución (siempre supplier en esta página, pero mantener compatibilidad)
  const handleReturnTypeChange = (e) => {
    const value = e.target.value
    setReturnType(value)
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

  // Submit del formulario
  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitDisabled(true)

    try {
      const payload = {
        return_type: form.return_type,
        returnable_type: form.returnable_type,
        returnable_id: parseInt(form.returnable_id) || undefined,
        quantity: form.quantity,
        condition: form.condition,
        reason: form.reason,
        idempotency_key: form.idempotency_key || `return-${Date.now()}`,
      }

      // Agregar provider_id según el tipo
      if (form.return_type === 'supplier' && form.provider_id) {
        payload.provider_id = parseInt(form.provider_id)
      }

      let response

      if (id) {
        // Actualizar existente
        response = await updateSupplierBookReturnStatus(id, form.return_type)
      } else {
        // Crear nueva
        response = await createSupplierBookReturn(payload)
      }

      toast.success(
        id ? 'Devolución actualizada' : 'Devolución creada',
        response.message || 'La operación se completó exitosamente.',
      )
      navigate(`/devoluciones-proveedor/${response.id ?? id}`)
    } catch (err) {
      console.error('Error creating supplier book return:', err)
      toast.error(
        'Error',
        err instanceof ApiError ? err.message : 'Ocurrió un error inesperado.',
      )
    } finally {
      setSubmitDisabled(false)
    }
  }

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <h1 className="page-title">
            {id ? 'Editar devolución a proveedor' : 'Nueva devolución a proveedor'}
          </h1>
          <p className="page-subtitle">
            Devolución de libros a {' '}
            <span className="text-primary">un proveedor</span>
          </p>
        </div>

        <div className="page-actions">
          <BackLink to="/devoluciones-proveedor">
            <Icon name="arrowLeft" size={18} />
            Volver
          </BackLink>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">
            {id ? 'Modificar devolución' : 'Crear nueva devolución'}
          </h2>
        </div>

        <form onSubmit={handleSubmit} noValidate>
          <Input
            type="hidden"
            name="returnable_type"
            value={form.returnable_type}
            disabled
          />

          {/* Tipo de devolución */}
          <Field
            label="Tipo de devolución"
            disabled={loading}
            error
          >
            <Select
              name="return_type"
              label="Tipo de devolución"
              value={returnType}
              onChange={handleReturnTypeChange}
              disabled={loading}
              error
            >
              <option value="">Seleccionar tipo</option>
              <option value="supplier">A proveedor</option>
            </Select>
          </Field>

          {/* Producto a devolver */}
          <Field
            label="Producto a devolver"
            disabled={loading}
            error
          >
            <Select
              name="returnable_id"
              label="Producto a devolver"
              value={form.returnable_id}
              onChange={handleReturnableChange}
              disabled={loading}
              error
            >
              <option value="">Seleccionar producto</option>
              {productTypes.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))
            </Select>
          </Field>

          {/* Cantidad */}
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

          {/* Condición */}
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

          {/* Razón */}
          <TextInput
            type="text"
            name="reason"
            label="Motivo de la devolución"
            value={form.reason}
            onChange={handleChange}
            rows={3}
            placeholder="Motivo de la devolución..."
            disabled={loading}
            error
            required
          />

          {/* Proveedor */}
          <Field
            label="Proveedor"
            disabled={loading}
            error
          >
            <Select
              name="provider_id"
              label="Proveedor"
              value={form.provider_id}
              onChange={handleChange}
              disabled={loading}
              error
            >
              <option value="">Seleccionar proveedor</option>
              {providers.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </Select>
          </Field>

          {/* Referencias opcionales */}
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

          <div className="erp-form-actions">
            <button type="submit" className="erp-btn erp-btn-primary" disabled={!isValid || submitDisabled}>
              {id ? 'Actualizar' : 'Crear'}
            </button>
            <button
              type="button"
              className="erp-btn erp-btn-outline"
              onClick={() => navigate('/devoluciones-proveedor')}
              disabled={submitDisabled}
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}