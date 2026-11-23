import { apiRequest } from '../../lib/api'

/**
 * API del asistente bibliográfico.
 *
 * Consume los endpoints de `Api\IA\RequestIAController`. Cada consulta queda
 * registrada en el backend con su estado, el modelo usado y la respuesta.
 */

/** Estado de la integración: si falta la clave, la SPA puede avisarlo. */
export function getAIStatus() {
  return apiRequest('/ia/status')
}

export function createAIRequest(payload) {
  return apiRequest('/ia/request', { method: 'POST', body: payload })
}

export function listAIRequests({ page = 1, perPage = 20 } = {}) {
  const params = new URLSearchParams({
    page: String(page),
    per_page: String(perPage),
  })

  return apiRequest(`/ia/request?${params.toString()}`)
}

export function getAIRequest(id) {
  return apiRequest(`/ia/request/${id}`)
}

/** Guarda la respuesta como ficha aprobada para futuras consultas. */
export function approveAIRequest(id) {
  return apiRequest(`/ia/request/${id}/approve`, { method: 'POST' })
}
