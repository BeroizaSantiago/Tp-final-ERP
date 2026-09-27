import { useCallback, useEffect, useRef, useState } from 'react'

import { ApiError, apiRequest } from './api'

/**
 * Consulta a la API con estados de carga y error.
 *
 * Cancela la petición anterior cuando cambian las dependencias, para que una
 * búsqueda rápida no deje resultados viejos por encima de los nuevos.
 *
 * @param {string|null} path Ruta de la API, o null para no consultar.
 * @param {{ enabled?: boolean, deps?: unknown[] }} options
 */
export function useApiResource(path, { enabled = true, deps = [] } = {}) {
  const [data, setData] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(Boolean(enabled && path))

  const controllerRef = useRef(null)

  const load = useCallback(async () => {
    if (!enabled || !path) {
      setLoading(false)
      return
    }

    controllerRef.current?.abort()
    const controller = new AbortController()
    controllerRef.current = controller

    setLoading(true)
    setError(null)

    try {
      const response = await apiRequest(path, { signal: controller.signal })
      setData(response)
    } catch (err) {
      if (err?.name === 'AbortError') {
        return
      }

      setError(
        err instanceof ApiError ? err : new ApiError('Ocurrió un error inesperado.'),
      )
    } finally {
      if (!controller.signal.aborted) {
        setLoading(false)
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path, enabled, ...deps])

  useEffect(() => {
    load()

    return () => controllerRef.current?.abort()
  }, [load])

  return { data, error, loading, reload: load, setData }
}
