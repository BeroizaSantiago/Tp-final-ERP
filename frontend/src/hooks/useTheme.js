import { useCallback, useEffect, useState } from 'react'

/**
 * Tema claro/oscuro del ERP.
 *
 * Replica la lógica del backend: la preferencia se guarda en
 * localStorage['erp.color-theme'] con los valores 'light', 'dark' o 'system',
 * y se refleja en <html data-bs-theme> para que la hoja de estilos reaccione.
 * La misma clave la usa el script inline de index.html para evitar el
 * destello claro al cargar.
 */

export const THEME_STORAGE_KEY = 'erp.color-theme'

function readPreference() {
  try {
    const stored = localStorage.getItem(THEME_STORAGE_KEY)
    return stored === 'light' || stored === 'dark' ? stored : 'system'
  } catch {
    return 'system'
  }
}

/** Resuelve la preferencia 'system' contra la del sistema operativo. */
function resolveTheme(preference) {
  if (preference !== 'system') {
    return preference
  }

  const prefersDark =
    typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches

  return prefersDark ? 'dark' : 'light'
}

export function useTheme() {
  const [preference, setPreference] = useState(readPreference)
  const [theme, setTheme] = useState(() => resolveTheme(readPreference()))

  useEffect(() => {
    try {
      localStorage.setItem(THEME_STORAGE_KEY, preference)
    } catch {
      /* Sin storage la preferencia solo vive en memoria. */
    }
  }, [preference])

  useEffect(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)')

    const apply = () => {
      const resolved = resolveTheme(preference)
      setTheme(resolved)

      const root = document.documentElement
      root.dataset.bsTheme = resolved
      root.dataset.colorTheme = preference
    }

    apply()

    // Mientras la preferencia sea 'system' hay que seguir al sistema operativo.
    if (preference !== 'system') {
      return undefined
    }

    media.addEventListener('change', apply)
    return () => media.removeEventListener('change', apply)
  }, [preference])

  const toggleTheme = useCallback(() => {
    setPreference((current) => (resolveTheme(current) === 'dark' ? 'light' : 'dark'))
  }, [])

  return { theme, preference, toggleTheme }
}
