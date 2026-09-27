/**
 * Formateo de datos para la interfaz.
 *
 * Centraliza los formatos que el backend ya usa en sus vistas Blade para que
 * la SPA muestre los mismos valores (precios, números y fechas en es-AR).
 */

const currencyFormatter = new Intl.NumberFormat('es-AR', {
  style: 'currency',
  currency: 'ARS',
})

const decimalFormatter = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 })

const dateTimeFormatter = new Intl.DateTimeFormat('es-AR', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  hour12: false,
})

export function money(value) {
  return currencyFormatter.format(Number(value ?? 0))
}

export function number(value) {
  return decimalFormatter.format(Number(value ?? 0))
}

/** Fecha corta para tablas: 25/09/2026. */
export function date(value) {
  if (!value) {
    return '—'
  }

  const parsed = new Date(value)

  if (Number.isNaN(parsed.getTime())) {
    return '—'
  }

  return new Intl.DateTimeFormat('es-AR', { dateStyle: 'short' }).format(parsed)
}

export function dateTime(value) {
  if (!value) {
    return 'Sin registro'
  }

  const parsed = new Date(value)

  if (Number.isNaN(parsed.getTime())) {
    return 'Sin registro'
  }

  return dateTimeFormatter.format(parsed).replace(',', '')
}

/** Convierte un valor numérico de la API en string para los inputs. */
export function toInput(value) {
  if (value === null || value === undefined || value === '') {
    return ''
  }

  return String(value)
}

/**
 * Normaliza una respuesta paginada de Laravel.
 * Acepta tanto el sobre del paginador como un array plano (respuestas `lookup`).
 */
export function normalizePaginator(payload, fallback = []) {
  if (Array.isArray(payload)) {
    return { items: payload, meta: null }
  }

  if (payload && Array.isArray(payload.data)) {
    return {
      items: payload.data,
      meta: {
        currentPage: Number(payload.current_page ?? 1),
        lastPage: Number(payload.last_page ?? 1),
        total: Number(payload.total ?? payload.data.length),
        perPage: Number(payload.per_page ?? payload.data.length),
        from: payload.from ?? null,
        to: payload.to ?? null,
      },
    }
  }

  return { items: fallback, meta: null }
}
