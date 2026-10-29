import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {BackLink, Field, FormSection, Select, TextInput} from '../../components/fields'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import {listPurchases, getPurchase, createSupplierBookReturn} from './api'
import { DataTable } from '../../components/DataTable'

function unwrap(response) {
  return response?.data?.data ?? response?.data ?? response
}

export function BookReturnPurchaseFormPage() {
  const navigate = useNavigate()
  const toast = useToast()

  const [purchases, setPurchases] = useState([])
  const [purchaseId, setPurchaseId] = useState('')
  const [purchase, setPurchase] = useState(null)
  const [items, setItems] = useState([])
  const [returnDate, setReturnDate] = useState(
    new Date().toLocaleDateString('en-CA'),
  )
  const [reason, setReason] = useState('')
  const [loadingReceipts, setLoadingReceipts] = useState(true)
  const [loadingReceipt, setLoadingReceipt] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    let active = true

    async function loadReceipts() {
      try {
        const response = await listPurchases()
        const result = unwrap(response)

        const rows = Array.isArray(result)
          ? result
          : Array.isArray(result?.data)
            ? result.data
            : Array.isArray(result?.data?.data)
              ? result.data.data
              : []

        console.log('Respuesta de compras:', response)
        console.log('Compras encontradas:', rows)

        if (active) {
          setPurchases(rows)
        }
      } catch (error) {
        if (active) {
          toast.error(
            'Error',
            error instanceof ApiError
              ? error.message
              : 'No se pudieron cargar los comprobantes de compra.',
          )
        }
      } finally {
        if (active) setLoadingReceipts(false)
      }
    }

    loadReceipts()

    return () => {
      active = false
    }
  }, [])

  async function handleReceiptChange(event) {
    const selectedId = event.target.value

    setPurchaseId(selectedId)
    setPurchase(null)
    setItems([])

    if (!selectedId) return

    setLoadingReceipt(true)

    try {
      const response = await getPurchase(selectedId)
      const data = unwrap(response)

      setPurchase(data)
      
    setItems(
      (data.items ?? []).map((item) => ({
      purchase_item_id: item.id,
      product_id: item.product_id,
      description:
        item.product_name ?? item.product?.name ?? 'Libro sin nombre',
      purchased_quantity: Number(item.quantity ?? 0),
      max_returnable_quantity: Number(
        item.remaining_returnable_quantity ?? item.quantity ?? 0,
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
          : 'No se pudo cargar el detalle de la compra.',
      )
    } finally {
      setLoadingReceipt(false)
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

    if (!purchaseId || !purchase) {
      toast.error('Falta el comprobante', 'Seleccioná una compra.')
      return
    }

    const selectedItems = items
      .filter((item) => Number(item.quantity) > 0)
      .map((item) => ({
        purchase_item_id: item.purchase_item_id,
        quantity: Number(item.quantity),
        condition: item.condition,
        notes: item.notes || null,
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
        (item) => item.purchase_item_id === selected.purchase_item_id,
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
        'La cantidad a devolver debe ser mayor que cero y no puede superar la cantidad comprada.',
      )
      return
    }

    setSubmitting(true)

    try {
      const idempotencyKey =
        globalThis.crypto?.randomUUID?.() ??
        `supplier-return-${Date.now()}-${Math.random().toString(36).slice(2)}`

      const response = await createSupplierBookReturn({
        purchase_id: Number(purchaseId),
        return_date: returnDate,
        reason: reason.trim() || null,
        idempotency_key: idempotencyKey,
        items: selectedItems,
      })

      const result = unwrap(response)
      const returnId = result?.id ?? result?.data?.id

      toast.success(
        'Devolución registrada',
        response?.message ??
          'La devolución quedó registrada con estado pendiente.',
      )

      navigate('/devoluciones-compras')
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
          <BackLink to="/devoluciones-compras">
            Volver a devoluciones
          </BackLink>
          <h1 className="page-title">Nueva devolución a proveedor</h1>
          <p className="page-subtitle">
            Seleccioná el comprobante y los libros que necesitás devolver.
          </p>
        </div>
      </div>

      <form onSubmit={handleSubmit} noValidate>
        <FormSection
          title="Comprobante de compra"
          description="Seleccioná una compra registrada para cargar sus libros."
        >
          <div className="form-grid">
            <Select
              name="purchase_id"
              label="Comprobante"
              value={purchaseId}
              onChange={handleReceiptChange}
              disabled={loadingReceipts || submitting}
              required
              placeholder={
                loadingReceipts
                  ? 'Cargando comprobantes...'
                  : 'Seleccionar comprobante'
              }
              options={purchases.map((purchaseOption) => ({
                value: String(purchaseOption.id),
                label: `${purchaseOption.receipt_type_name ?? 'Comprobante'} ${
                  purchaseOption.full_number ?? `#${purchaseOption.id}`
                }`,
              }))}
            />

            <TextInput
              name="issue_date"
              label="Fecha del comprobante"
              value={purchase?.issue_date?.slice(0, 10) ?? ''}
              disabled
            />

            <TextInput
              name="provider_name"
              label="Proveedor"
              value={purchase?.provider?.name ?? purchase?.provider_name ?? ''}
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
              placeholder="Ej.: Libros dañados al recibirlos"
              disabled={submitting}
            />
          </div>
        </FormSection>

        <FormSection
          title="Libros a devolver"
          description="Indicá la cantidad de cada libro. Dejá vacío o en cero lo que no se devuelve."
        >
          {loadingReceipt && <p>Cargando detalle de la compra...</p>}

          {!loadingReceipt && purchaseId && items.length === 0 && (
            <p>El comprobante no tiene libros para devolver.</p>
          )}

          {!purchaseId && (
            <p>Primero seleccioná un comprobante de compra.</p>
          )}

          {items.length > 0 && (
            <DataTable
              columns={[
                {
                  key: 'description',
                  header: 'Libro',
                  render: (item) => (
                    <div style={{ fontSize: '15px', fontWeight: 'bold' }}>
                      {item.description || '—'} <br></br>
                    </div>
                  ),
                },
                {
                  key: 'purchased_quantity',
                  header: 'Cantidad comprada',
                  align: 'center',
                  render: (item) => item.purchased_quantity,
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
                        updateItem(
                          items.findIndex(
                            (current) =>
                              current.purchase_item_id === item.purchase_item_id,
                          ),
                          'quantity',
                          event.target.value,
                        )
                      }}
                      disabled={submitting}
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
                        updateItem(
                          items.findIndex(
                            (current) =>
                              current.purchase_item_id === item.purchase_item_id,
                          ),
                          'condition',
                          event.target.value,
                        )
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
                        updateItem(
                          items.findIndex(
                            (current) =>
                              current.purchase_item_id === item.purchase_item_id,
                          ),
                          'notes',
                          event.target.value,
                        )
                      }}
                      placeholder="Opcional"
                      disabled={submitting}
                    />
                  ),
                },
              ]}
              rows={items}
              loading={loadingReceipt}
              error={null}
              emptyText="El comprobante no tiene libros para devolver."
            />
          )}
        </FormSection>

        <div className="form-actions">
          <button
            className="erp-btn erp-btn-outline"
            type="button"
            onClick={() => navigate('/devoluciones-compras')}
            disabled={submitting}
          >
            Cancelar
          </button>

          <button
            className="erp-btn erp-btn-primary"
            type="submit"
            disabled={
              submitting ||
              loadingReceipt ||
              loadingReceipts ||
              !purchaseId
            }
          >
            {submitting ? 'Guardando...' : 'Registrar devolución'}
          </button>
        </div>
      </form>
    </div>
  )
}
