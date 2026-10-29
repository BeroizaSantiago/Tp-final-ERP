
import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { BackLink, FormSection, Select, TextInput } from '../../components/fields'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { listInvoices, getInvoice, createCustomerBookReturn } from './api'
import { DataTable } from '../../components/DataTable'

function unwrap(response) {
  return response?.data?.data ?? response?.data ?? response
}

function extractRows(response) {
  const result = unwrap(response)

  if (Array.isArray(result)) return result
  if (Array.isArray(result?.data)) return result.data
  if (Array.isArray(result?.data?.data)) return result.data.data

  return []
}

export function BookReturnSaleFormPage() {
  const navigate = useNavigate()
  const toast = useToast()

  const [invoices, setInvoices] = useState([])
  const [invoiceId, setInvoiceId] = useState('')
  const [invoice, setInvoice] = useState(null)
  const [items, setItems] = useState([])
  const [returnDate, setReturnDate] = useState(
    new Date().toLocaleDateString('en-CA'),
  )
  const [reason, setReason] = useState('')
  const [loadingInvoices, setLoadingInvoices] = useState(true)
  const [loadingInvoice, setLoadingInvoice] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    let active = true

    async function loadInvoices() {
      try {
        const response = await listInvoices({ per_page: 100 })
        const rows = extractRows(response)

        if (active) setInvoices(rows)
      } catch (error) {
        if (active) {
          toast.error(
            'Error',
            error instanceof ApiError
              ? error.message
              : 'No se pudieron cargar las facturas de venta.',
          )
        }
      } finally {
        if (active) setLoadingInvoices(false)
      }
    }

    loadInvoices()

    return () => {
      active = false
    }
  }, [])

  async function handleInvoiceChange(event) {
    const selectedId = event.target.value

    setInvoiceId(selectedId)
    setInvoice(null)
    setItems([])

    if (!selectedId) return

    setLoadingInvoice(true)

    try {
      const response = await getInvoice(selectedId)
      const data = unwrap(response)

      setInvoice(data)

      const invoiceItems = Array.isArray(data.items) ? data.items : []

      setItems(
        invoiceItems.map((item) => ({
          invoice_item_id: item.id,
          product_id: item.product_id ?? item.product?.id ?? null,
          description:
            item.product_name ??
            item.product?.name ??
            'Libro sin nombre',
          invoiced_quantity: Math.abs(Number(item.quantity ?? 0)),
          max_returnable_quantity: Math.max(
            0,
            Number(
              item.remaining_returnable_quantity ??
              Math.abs(Number(item.quantity ?? 0)),
            ),
          ),
          quantity: '',
          condition: 'sellable',
          notes: '',
        })),
      )
    } catch (error) {
      toast.error(
        'Error',
        error instanceof ApiError
          ? error.message
          : 'No se pudo cargar el detalle de la factura.',
      )
    } finally {
      setLoadingInvoice(false)
    }
  }

  function updateItem(index, field, value) {
    setItems((current) =>
      current.map((item, itemIndex) =>
        itemIndex === index ? { ...item, [field]: value } : item,
      ),
    )
  }

  async function handleSubmit(event) {
    event.preventDefault()

    if (!invoiceId || !invoice) {
      toast.error('Falta la factura', 'Seleccioná una factura de venta.')
      return
    }

    const selectedItems = items
      .filter((item) => Number(item.quantity) > 0)
      .map((item) => ({
        invoice_item_id: item.invoice_item_id,
        quantity: Number(item.quantity),
        condition: item.condition,
        notes: item.notes.trim() || null,
      }))

    if (selectedItems.length === 0) {
      toast.error(
        'Faltan libros',
        'Ingresá una cantidad mayor que cero en al menos un libro.',
      )
      return
    }

    const invalidQuantity = selectedItems.some((selected) => {
      const source = items.find(
        (item) => item.invoice_item_id === selected.invoice_item_id,
      )

      return (
        !Number.isFinite(selected.quantity) ||
        selected.quantity <= 0 ||
        selected.quantity > source.max_returnable_quantity
      )
    })

    if (invalidQuantity) {
      toast.error(
        'Cantidad inválida',
        'La cantidad a devolver no puede superar la cantidad disponible para devolución.',
      )
      return
    }

    if (!returnDate) {
      toast.error(
        'Falta la fecha',
        'Seleccioná la fecha de devolución.',
      )
      return
    }

    if (!reason.trim()) {
      toast.error(
        'Falta el motivo',
        'Ingresá el motivo de la devolución.',
      )
      return
    }

    setSubmitting(true)

    try {
      const idempotencyKey =
        globalThis.crypto?.randomUUID?.() ??
        `customer-return-${Date.now()}-${Math.random().toString(36).slice(2)}`

      const response = await createCustomerBookReturn({
        invoice_id: Number(invoiceId),
        return_date: returnDate,
        reason: reason.trim(),
        idempotency_key: idempotencyKey,
        items: selectedItems,
      })

      toast.success(
        'Devolución registrada',
        response?.message ??
          'La devolución quedó registrada con estado pendiente.',
      )

      navigate('/devoluciones-ventas')
    } catch (error) {
      toast.error(
        'Error al registrar',
        error instanceof ApiError
          ? error.message
          : 'No se pudo registrar la devolución.',
      )
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="page">
      <div className="page-head">
        <div>
          <BackLink to="/devoluciones-ventas">
            Volver a devoluciones
          </BackLink>

          <h1 className="page-title">
            Nueva devolución de cliente
          </h1>

          <p className="page-subtitle">
            Seleccioná la factura de venta y los libros que devuelve el cliente.
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit} noValidate>
        <FormSection
          title="Factura de venta"
          description="Seleccioná la factura que contiene los libros que se devolverán."
        >
          <div className="form-grid">
            <Select
              name="invoice_id"
              label="Factura"
              value={invoiceId}
              onChange={handleInvoiceChange}
              disabled={loadingInvoices || submitting}
              required
              placeholder={
                loadingInvoices
                  ? 'Cargando facturas...'
                  : 'Seleccionar factura'
              }
              options={invoices.map((invoiceOption) => ({
                value: String(invoiceOption.id),
                label: `${invoiceOption.receipt_type_name ?? 'Factura'} ${
                  invoiceOption.full_number ??
                  invoiceOption.display_number ??
                  `#${invoiceOption.id}`
                }`,
              }))}
            />

            <TextInput
              name="issue_date"
              label="Fecha de la factura"
              value={
                invoice?.issue_date?.slice(0, 10) ??
                invoice?.invoice_date?.slice(0, 10) ??
                ''
              }
              disabled
            />

            <TextInput
              name="client_name"
              label="Cliente"
              value={
                invoice?.client?.name ??
                invoice?.client_name ??
                invoice?.customer_name ??
                ''
              }
              disabled
            />

            <TextInput
              name="return_date"
              type="date"
              label="Fecha de devolución"
              value={returnDate}
              onChange={(event) => setReturnDate(event.target.value)}
              disabled={submitting}
              required
            />

            <TextInput
              name="reason"
              label="Motivo"
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              placeholder="Ej.: Libro con páginas dañadas"
              disabled={submitting}
              required
            />
          </div>
        </FormSection>

        <FormSection
          title="Libros a devolver"
          description="Indicá la cantidad, la condición y las observaciones de cada libro."
        >
          {loadingInvoice && <p>Cargando detalle de la factura...</p>}

          {!loadingInvoice && invoiceId && items.length === 0 && (
            <p>La factura no tiene libros disponibles para cargar.</p>
          )}

          {!invoiceId && (
            <p>Primero seleccioná una factura de venta.</p>
          )}

          {items.length > 0 && (
            <DataTable
              columns={[
                {
                  key: 'description',
                  header: 'Libro',
                  render: (item) => (
                    <strong>{item.description || '—'}</strong>
                  ),
                },
                {
                  key: 'invoiced_quantity',
                  header: 'Cantidad facturada',
                  align: 'center',
                  render: (item) => item.invoiced_quantity,
                },
                {
                  key: 'quantity',
                  header: 'Cantidad a devolver',
                  align: 'center',
                  render: (item) => (
                    <input
                      className="erp-control"
                      type="number"
                      min="0"
                      max={item.max_returnable_quantity}
                      step="0.01"
                      value={item.quantity}
                      onChange={(event) => {
                        const index = items.findIndex(
                          (current) =>
                            current.invoice_item_id === item.invoice_item_id,
                        )
                        updateItem(index, 'quantity', event.target.value)
                      }}
                      disabled={submitting || item.max_returnable_quantity <= 0}
                    />
                  ),
                },
                {
                  key: 'condition',
                  header: 'Condición',
                  render: (item) => (
                    <select
                      className="erp-control"
                      value={item.condition}
                      onChange={(event) => {
                        const index = items.findIndex(
                          (current) =>
                            current.invoice_item_id === item.invoice_item_id,
                        )
                        updateItem(index, 'condition', event.target.value)
                      }}
                      disabled={submitting}
                    >
                      <option value="sellable">Vendible</option>
                      <option value="damaged">Dañado</option>
                      <option value="review">Requiere revisión</option>
                    </select>
                  ),
                },
                {
                  key: 'notes',
                  header: 'Observaciones',
                  render: (item) => (
                    <input
                      className="erp-control"
                      type="text"
                      value={item.notes}
                      onChange={(event) => {
                        const index = items.findIndex(
                          (current) =>
                            current.invoice_item_id === item.invoice_item_id,
                        )
                        updateItem(index, 'notes', event.target.value)
                      }}
                      placeholder="Opcional"
                      disabled={submitting}
                    />
                  ),
                },
              ]}
              rows={items}
              loading={loadingInvoice}
              error={null}
              emptyText="La factura no tiene libros disponibles."
            />
          )}
        </FormSection>

        <div className="form-actions">
          <button
            className="erp-btn erp-btn-outline"
            type="button"
            onClick={() => navigate('/devoluciones-ventas')}
            disabled={submitting}
          >
            Cancelar
          </button>

          <button
            className="erp-btn erp-btn-primary"
            type="submit"
            disabled={
              submitting ||
              loadingInvoice ||
              loadingInvoices ||
              !invoiceId
            }
          >
            {submitting ? 'Guardando...' : 'Registrar devolución'}
          </button>
        </div>
      </form>
    </div>
  )
}
