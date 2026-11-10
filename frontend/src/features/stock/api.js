import { apiRequest } from '../../lib/api'

export function adjustmentOptions() {
  return apiRequest('/stock-adjustments-options')
}

export function adjustmentReasons() {
  return apiRequest('/stock-adjustment-reasons?per_page=100')
}

export function searchStockProducts(search) {
  return apiRequest(`/products?${new URLSearchParams({ search, lookup: '1', per_page: '20' })}`)
}

export function stockProduct(id) {
  return apiRequest(`/products/${id}`)
}

export function currentStock(params) {
  return apiRequest(`/stock-adjustments-current-stock?${new URLSearchParams(params)}`)
}

export function createStockAdjustment(payload) {
  return apiRequest('/stock-adjustments', { method: 'POST', body: payload })
}
