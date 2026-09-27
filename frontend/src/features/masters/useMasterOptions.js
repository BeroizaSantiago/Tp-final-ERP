import { useMemo } from 'react'

import { useApiResource } from '../../lib/useApiResource'

/**
 * Opciones de un catálogo maestro para los selects.
 *
 * Usa `?lookup=1`, que en el backend devuelve un array plano con los registros
 * activos, así que no hace falta paginar ni normalizar el sobre.
 */
export function useMasterOptions(resource, { enabled = true } = {}) {
  const { data, loading, error } = useApiResource(resource ? `/${resource}?lookup=1` : null, {
    enabled: enabled && Boolean(resource),
    deps: [resource],
  })

  const options = useMemo(
    () =>
      (Array.isArray(data) ? data : []).map((item) => ({
        value: String(item.id),
        label: item.name,
      })),
    [data],
  )

  return { options, loading, error }
}

/**
 * Resuelve el nombre de un maestro a partir de su id, para mostrar la etiqueta
 * en el detalle de un libro en lugar del id.
 */
export function useMasterLabel(resource) {
  const { options } = useMasterOptions(resource)

  return useMemo(() => {
    const map = new Map(options.map((option) => [option.value, option.label]))

    return (id) => (id === null || id === undefined || id === '' ? null : (map.get(String(id)) ?? null))
  }, [options])
}
