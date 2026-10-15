import { apiRequest } from '../../lib/api'

/**
 * API del módulo de Ventas.
 *
 * Envuelve los endpoints de `routes/api.php` del dominio de ventas:
 * facturas, notas de crédito, clientes y productos.
 */

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

/* ------------------------------------------------------------- facturas */

export function listInvoices(params) {
  return apiRequest(`/invoices${buildQuery(params)}`)
}

export function getInvoice(id) {
  return apiRequest(`/invoices/${id}`)
}

export function createInvoice(payload) {
  return apiRequest(`/invoices`, { method: 'POST', body: payload })
}

export function addInvoicePayment(id, payload) {
  return apiRequest(`/invoices/${id}/payments`, { method: 'POST', body: payload })
}

export function finalizeInvoice(id) {
  return apiRequest(`/invoices/${id}/finalize`, { method: 'POST' })
}

export function getInvoicePdf(id) {
  return apiRequest(`/invoices/${id}/pdf`, { method: 'GET' })
}

/* ------------------------------------------------------------- formas de pago */

export function listPaymentMethods(id, params) {
  return apiRequest(`/invoices/${id}/payments${buildQuery(params)}`)
}

export function addPaymentMethod(id, payload) {
  return apiRequest(`/invoices/${id}/payments`, { method: 'POST', body: payload })
}

export function applyPromotion(id, payload) {
  return apiRequest(`/invoices/${id}/promotions/${payload.id}/apply`, {
    method: 'POST',
    body: payload,
  })
}

export function getCompatiblePromotions(id, paymentMethods) {
  const params = new URLSearchParams()
  if (paymentMethods && paymentMethods.length) {
    paymentMethods.forEach((m) => params.append('payment_methods[]', m))
  }
  return apiRequest(`/invoices/${id}/compatible-promotions${params.toString()}`)
}

/* ------------------------------------------------------------- crédito y notas */

export function listCreditNotes(params) {
  return apiRequest(`/credit-notes${buildQuery(params)}`)
}

export function getCreditNote(id) {
  return apiRequest(`/credit-notes/${id}`)
}

export function createCreditNote(payload) {
  return apiRequest(`/credit-notes`, { method: 'POST', body: payload })
}

export function authorizeArca(id) {
  return apiRequest(`/credit-notes/${id}/authorize`, { method: 'POST' })
}

/* ------------------------------------------------------------- clientes */

export function listClients(params) {
  return apiRequest(`/clients${buildQuery(params)}`)
}

export function getClient(id) {
  return apiRequest(`/clients/${id}`)
}

/* ------------------------------------------------------------- productos */

export function listProducts(params) {
  return apiRequest(`/products${buildQuery(params)}`)
}

export function searchProducts(query) {
  return apiRequest(
    `/products?${new URLSearchParams({
      search: query,
      per_page: '10',
      is_active: '1',
    }).toString()}`
  )
}

export function getProduct(id) {
  return apiRequest(`/products/${id}`)
}

export function searchClients(query) {
  return apiRequest(`/clients?${new URLSearchParams({ search: query }).toString()}`)
}

/* ------------------------------------------------------------- proveedores */

export function listProviders(params) {
  return apiRequest(`/providers${buildQuery(params)}`)
}

export function getProvider(id) {
  return apiRequest(`/providers/${id}`)
}

/* ------------------------------------------------------------- voucher */

export function lookupVoucher(code) {
  return apiRequest(`/vouchers/lookup/${encodeURIComponent(code)}`, {
    headers: { Accept: 'application/json' },
  })
}

export function getVoucher(id) {
  return apiRequest(`/vouchers/${id}`, { headers: { Accept: 'application/json' } })
}

/* ------------------------------------------------------------- catálogos */

export function listReasons(params) {
  return apiRequest(`/credit-note-reasons${buildQuery(params)}`)
}

export function listStockLocations() {
  return apiRequest(`/stock-locations`)
}

/* ------------------------------------------------------------- utilidades */

/** Etiqueta legible de una variante de producto. */
export function variantLabel(variant) {
  if (!variant) {
    return '—'
  }

  const parts = [
    variant.category?.name,
    variant.brand?.name,
    variant.publisher?.name,
    variant.model?.name,
    variant.collection?.name,
    variant.size?.name,
    variant.color?.name,
  ].filter(Boolean)

  return parts.length ? parts.join(' · ') : variant.sku || `Variante #${variant.id}`
}

/** Color de la variante para mostrar en la fila del comprobante. */
export function variantColor(variant) {
  return variant?.color?.name ?? null
}

const STATUS_TONES = [
  [/pagada|recibida|completada|aprobada/, 'success'],
  [/pendiente|parcial|borrador|registrado/, 'warning'],
  [/anulada|cancelada|rechazada/, 'danger'],
]

/** Mismo criterio de color de estado que las vistas Blade. */
export function statusTone(status) {
  const value = String(status ?? '').toLowerCase()

  const match = STATUS_TONES.find(([pattern]) => pattern.test(value))

  return match ? match[1] : 'secondary'
}