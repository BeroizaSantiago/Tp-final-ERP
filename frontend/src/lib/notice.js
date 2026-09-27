/**
 * Avisos de una sola vez.
 *
 * Se usan para mostrar información que sólo importa en la navegación siguiente
 * (por ejemplo la contraseña inicial tras un alta) sin ensuciar la URL con
 * query params. Vive en sessionStorage, así que sobrevive a una recarga de la
 * pestaña y se descarta al cerrar el navegador.
 */

const NOTICE_KEY = 'erp.notice'

export function setNotice(notice) {
  try {
    sessionStorage.setItem(NOTICE_KEY, JSON.stringify(notice))
  } catch {
    /* Sin storage el aviso simplemente no se conserva. */
  }
}

/** Lee y borra el aviso pendiente, para que se muestre una sola vez. */
export function takeNotice() {
  try {
    const raw = sessionStorage.getItem(NOTICE_KEY)

    if (!raw) {
      return null
    }

    sessionStorage.removeItem(NOTICE_KEY)
    return JSON.parse(raw)
  } catch {
    return null
  }
}
