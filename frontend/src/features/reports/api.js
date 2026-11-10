import { apiRequest, ApiError } from '../../lib/api'
import { API_URL } from '../../lib/config'
import { getToken } from '../../lib/session'

export function reportQuery(filters, extra = {}) {
  const params = new URLSearchParams()
  const values = { ...filters, ...extra }

  Object.entries(values).forEach(([key, value]) => {
    if (value === '' || value === null || value === undefined) return
    if (Array.isArray(value)) {
      value.filter((item) => item !== '').forEach((item) => params.append(`${key}[]`, String(item)))
      return
    }
    params.set(key, String(value))
  })

  return params.toString()
}

export function getStockMovementOptions() {
  return apiRequest('/reports/stock/movements/options')
}

export function getStockMovements(filters) {
  return apiRequest(`/reports/stock/movements?${reportQuery(filters)}`)
}

export function searchReportProducts(search) {
  return apiRequest(`/products?${new URLSearchParams({ search, lookup: '1', per_page: '20' })}`)
}

export async function downloadStockMovementReport(filters, format) {
  const path = format === 'pdf'
    ? `/reports/stock/movements/pdf?${reportQuery(filters)}`
    : `/reports/stock/movements/export?${reportQuery(filters, { format })}`
  const token = getToken()
  const response = await fetch(`${API_URL}${path}`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    throw new ApiError(payload?.message || 'No se pudo descargar el reporte.', { status: response.status })
  }

  const extension = format === 'pdf' ? 'pdf' : format
  const url = URL.createObjectURL(await response.blob())
  const link = document.createElement('a')
  link.href = url
  link.download = `movimientos-stock.${extension}`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
