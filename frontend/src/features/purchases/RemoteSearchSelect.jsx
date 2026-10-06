import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'

/**
 * Select con búsqueda remota.
 *
 * Reemplaza a los `data-remote-url` de las vistas Blade: el usuario escribe en
 * la caja y se consultan los resultados contra la API con un rebote de 300 ms.
 * La opción elegida se comunica con el objeto completo (id + nombre), así la
 * fila puede cargar el detalle del producto o del proveedor.
 */
export function RemoteSearchSelect({
  label,
  placeholder = 'Buscar…',
  search,
  onSearch,
  results,
  loading,
  selected,
  onSelect,
  error,
  minChars = 2,
}) {
  const [open, setOpen] = useState(false)
  const [text, setText] = useState(selected?.label ?? '')
  const [menuStyle, setMenuStyle] = useState(null)
  const containerRef = useRef(null)
  const inputRef = useRef(null)
  const menuRef = useRef(null)

  // Mantiene el texto sincronizado cuando cambia la selección desde afuera.
  useEffect(() => {
    setText(selected?.label ?? '')
  }, [selected])

  // Cierra el desplegable al hacer click fuera.
  useEffect(() => {
    if (!open) {
      return undefined
    }

    const close = (event) => {
      if (
        !containerRef.current?.contains(event.target) &&
        !menuRef.current?.contains(event.target)
      ) {
        setOpen(false)
      }
    }

    window.addEventListener('mousedown', close)

    return () => window.removeEventListener('mousedown', close)
  }, [open])

  // El desplegable se porta a document.body con posición fija: dentro de una
  // tabla con overflow (table-scroll) quedaría recortado. Se reubica por si la
  // página se desplaza o cambia de tamaño.
  useLayoutEffect(() => {
    if (!open) {
      return undefined
    }

    const update = () => {
      const rect = inputRef.current?.getBoundingClientRect()

      if (rect) {
        setMenuStyle({
          position: 'fixed',
          top: rect.bottom + 4,
          left: rect.left,
          width: rect.width,
          zIndex: 1000,
        })
      }
    }

    update()
    window.addEventListener('scroll', update, true)
    window.addEventListener('resize', update)

    return () => {
      window.removeEventListener('scroll', update, true)
      window.removeEventListener('resize', update)
    }
  }, [open])

  const handleInput = (event) => {
    const value = event.target.value

    setText(value)

    if (selected) {
      onSelect(null)
    }

    if (value.trim().length >= minChars) {
      onSearch(value.trim())
      setOpen(true)
    } else {
      onSearch('')
      setOpen(false)
    }
  }

  const handlePick = (item) => {
    onSelect(item)
    setText(item.label)
    setOpen(false)
  }

  return (
    <div className="erp-field-block" ref={containerRef} style={{ position: 'relative' }}>
      {label ? <span className="erp-label">{label}</span> : null}

      <input
        ref={inputRef}
        type="search"
        className={`erp-control${error ? ' is-invalid' : ''}`}
        value={text}
        onChange={handleInput}
        onFocus={() => {
          if (text.trim().length >= minChars && results.length) {
            setOpen(true)
          }
        }}
        placeholder={placeholder}
        aria-label={label ?? placeholder}
        autoComplete="off"
      />

      {open && menuStyle
        ? createPortal(
            <div className="remote-select-menu" ref={menuRef} style={menuStyle}>
              {loading ? (
                <div className="remote-select-option remote-select-option--muted">Buscando…</div>
              ) : results.length ? (
                results.map((item) => (
                  <button
                    key={item.value}
                    type="button"
                    className="remote-select-option"
                    onClick={() => handlePick(item)}
                  >
                    <strong>{item.label}</strong>
                    {item.hint ? <small>{item.hint}</small> : null}
                  </button>
                ))
              ) : (
                <div className="remote-select-option remote-select-option--muted">Sin resultados</div>
              )}
            </div>,
            document.body,
          )
        : null}

      {error ? <div className="erp-invalid-feedback">{error}</div> : null}
    </div>
  )
}

/** Hook de búsqueda con rebote para alimentar a RemoteSearchSelect. */
export function useRemoteSearch(fetcher) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState([])
  const [loading, setLoading] = useState(false)

  const fetcherRef = useRef(fetcher)
  fetcherRef.current = fetcher

  useEffect(() => {
    if (!query) {
      setResults([])
      setLoading(false)
      return undefined
    }

    let cancelled = false
    setLoading(true)

    const timer = window.setTimeout(async () => {
      try {
        const items = await fetcherRef.current(query)

        if (!cancelled) {
          setResults(items)
        }
      } catch {
        if (!cancelled) {
          setResults([])
        }
      } finally {
        if (!cancelled) {
          setLoading(false)
        }
      }
    }, 300)

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [query])

  return { query, setQuery, results, loading }
}
