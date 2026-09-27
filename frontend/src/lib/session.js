/**
 * Persistencia del token de Sanctum.
 *
 * El backend autentica al cliente React con un token personal (Bearer), por lo
 * que el token es lo único que debe sobrevivir a una recarga de la página.
 */

const TOKEN_KEY = 'erp.token'

export function getToken() {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token) {
  try {
    localStorage.setItem(TOKEN_KEY, token)
  } catch {
    /* Modo privado o storage bloqueado: la sesión durará lo que dure la pestaña. */
  }
}

export function clearToken() {
  try {
    localStorage.removeItem(TOKEN_KEY)
  } catch {
    /* Sin storage no hay token que limpiar. */
  }
}
