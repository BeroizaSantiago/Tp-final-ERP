import { apiRequest } from '../../lib/api'

/**
 * API del módulo de Compras.
 *
 * Envuelve los endpoints de `routes/api.php` del dominio de compras:
 * comprobantes, órdenes, gastos varios, tipos de gasto y proveedores.
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

/* ---------------------------------------------------------------- compras */

export function listPurchases(params) {
  return apiRequest(`/purchases${buildQuery(params)}`)
}

export function getPurchase(id) {
  return apiRequest(`/purchases/${id}`)
}

export function createPurchase(payload) {
  return apiRequest('/purchases', { method: 'POST', body: payload })
}

export function addPurchasePayment(id, payload) {
  return apiRequest(`/purchases/${id}/payments`, { method: 'POST', body: payload })
}

/* ------------------------------------------------------------- órdenes */

export function listPurchaseOrders(params) {
  return apiRequest(`/purchase-orders${buildQuery(params)}`)
}

export function getPurchaseOrder(id) {
  return apiRequest(`/purchase-orders/${id}`)
}

export function createPurchaseOrder(payload) {
  return apiRequest('/purchase-orders', { method: 'POST', body: payload })
}

/* ------------------------------------------------------- gastos varios */

export function listMiscExpenses(params) {
  return apiRequest(`/misc-expenses${buildQuery(params)}`)
}

export function getMiscExpense(id) {
  return apiRequest(`/misc-expenses/${id}`)
}

export function createMiscExpense(payload) {
  return apiRequest('/misc-expenses', { method: 'POST', body: payload })
}

export function addMiscExpensePayment(id, payload) {
  return apiRequest(`/misc-expenses/${id}/payments`, { method: 'POST', body: payload })
}

/* ----------------------------------------------------- tipos de gasto */

export function listExpenseTypes() {
  return apiRequest('/expense-types')
}

export function createExpenseType(payload) {
  return apiRequest('/expense-types', { method: 'POST', body: payload })
}

export function updateExpenseType(id, payload) {
  return apiRequest(`/expense-types/${id}`, { method: 'PUT', body: payload })
}

export function deleteExpenseType(id) {
  return apiRequest(`/expense-types/${id}`, { method: 'DELETE' })
}

/* ---------------------------------------------------------- proveedores */

export function listProviders(params) {
  return apiRequest(`/providers${buildQuery(params)}`)
}

export function getProvider(id) {
  return apiRequest(`/providers/${id}`)
}

export function createProvider(payload) {
  return apiRequest('/providers', { method: 'POST', body: payload })
}

export function updateProvider(id, payload) {
  return apiRequest(`/providers/${id}`, { method: 'PUT', body: payload })
}

/* ------------------------------------------------------ catálogos usados */

export function listStockLocations() {
  return apiRequest('/stock-locations')
}

export function searchProducts(query) {
  return apiRequest(`/products?${new URLSearchParams({ search: query, per_page: '10' }).toString()}`)
}

export function getProduct(id) {
  return apiRequest(`/products/${id}`)
}

export function searchProviders(query) {
  return apiRequest(`/providers?${new URLSearchParams({ search: query }).toString()}`)
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
