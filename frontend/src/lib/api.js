/**
 * Cliente HTTP de la API del ERP.
 *
 * Centraliza el token de Sanctum, el prefijo /api y la normalización de errores
 * para que las vistas no tengan que interactuar con fetch directamente.
 */

import { API_URL } from './config'
import { clearToken, getToken } from './session'

export class ApiError extends Error {
  constructor(message, { status = 0, errors = {} } = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  /**
   * Primer mensaje de validación de un campo concreto.
   * Laravel responde 422 con la forma { errors: { email: ['...'] } }.
   */
  fieldError(field) {
    const messages = this.errors?.[field]
    return Array.isArray(messages) ? messages[0] : undefined
  }
}

/**
 * Ejecuta una petición contra la API.
 *
 * @param {string} path Ruta relativa al prefijo /api (por ejemplo '/login').
 * @param {{ method?: string, body?: unknown, auth?: boolean, signal?: AbortSignal }} options
 *   `body` puede ser un objeto (se envía como JSON) o un FormData (subida de
 *   archivos, en cuyo caso el navegador define el boundary del multipart).
 * @returns {Promise<any>} El cuerpo JSON de la respuesta.
 */
export async function apiRequest(path, { method = 'GET', body, auth = true, signal } = {}) {
  const isFormData = typeof FormData !== 'undefined' && body instanceof FormData

  const headers = { Accept: 'application/json' }

  // Con FormData no se setea Content-Type: el navegador agrega el boundary.
  if (body !== undefined && !isFormData) {
    headers['Content-Type'] = 'application/json'
  }

  const token = auth ? getToken() : null

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  let response

  try {
    response = await fetch(`${API_URL}${path}`, {
      method,
      headers,
      signal,
      body:
        body === undefined ? undefined : isFormData ? body : JSON.stringify(body),
    })
  } catch (error) {
    if (error?.name === 'AbortError') {
      throw error
    }

    throw new ApiError('No se pudo conectar con el servidor. Verificá que el backend esté en ejecución.')
  }

  if (response.status === 204) {
    return null
  }

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    // Un 401 sobre una ruta autenticada significa token vencido o revocado.
    if (response.status === 401 && token) {
      clearToken()
    }

    throw new ApiError(payload?.message || 'Ocurrió un error inesperado. Intentá nuevamente.', {
      status: response.status,
      errors: payload?.errors ?? {},
    })
  }

  return payload
}
