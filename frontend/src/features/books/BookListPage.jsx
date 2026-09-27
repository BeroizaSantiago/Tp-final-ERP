import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'

import { ActiveBadge, StockBadge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { useMasterOptions } from '../masters/useMasterOptions'
import { setBookStatus } from './api'

/** Portada del libro, con la primera imagen o un ícono de reemplazo. */
function BookCover({ book }) {
  const image = book.image_full_url ?? book.images?.[0]?.full_url ?? null

  if (!image) {
    return (
      <span className="book-cover book-cover--empty">
        <Icon name="book" size={22} />
      </span>
    )
  }

  return <img className="book-cover" src={image} alt="" loading="lazy" />
}

/** Etiqueta de un maestro o el valor de texto plano que guarda el producto. */
function relatedName(relation, raw) {
  return relation?.name ?? (raw || null) ?? '—'
}

/**
 * Listado del catálogo.
 *
 * Réplica de la tabla de `products/catalog/index.blade.php`: mismos datos,
 * mismos badges y la misma acción de habilitar / dar de baja. Los filtros por
 * categoría, marca y estado se agregaron en la API para este listado.
 */
export function BookListPage() {
  const toast = useToast()
  const confirm = useConfirm()
  const [searchParams, setSearchParams] = useSearchParams()

  const [search, setSearch] = useState(searchParams.get('search') ?? '')
  const page = Number(searchParams.get('page') ?? 1)
  const categoryId = searchParams.get('category_id') ?? ''
  const brandId = searchParams.get('brand_id') ?? ''
  const status = searchParams.get('is_active') ?? ''

  const { options: categories } = useMasterOptions('product-categories')
  const { options: brands } = useMasterOptions('brands')

  // Búsqueda con rebote de 300 ms, igual que el backend.
  useEffect(() => {
    const timer = window.setTimeout(() => {
      const next = new URLSearchParams(searchParams)
      const value = search.trim()

      if (value) {
        next.set('search', value)
      } else {
        next.delete('search')
      }

      next.delete('page')
      setSearchParams(next, { replace: true })
    }, 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const path = useMemo(() => {
    const params = new URLSearchParams({ page: String(page), per_page: '20' })
    const query = searchParams.get('search')

    if (query) {
      params.set('search', query)
    }

    if (categoryId) {
      params.set('category_id', categoryId)
    }

    if (brandId) {
      params.set('brand_id', brandId)
    }

    if (status !== '') {
      params.set('is_active', status)
    }

    return `/products?${params.toString()}`
  }, [page, searchParams, categoryId, brandId, status])

  const { data, loading, error, reload } = useApiResource(path)

  const { items, meta } = useMemo(() => normalizePaginator(data), [data])

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)

    if (value) {
      next.set(key, value)
    } else {
      next.delete(key)
    }

    next.delete('page')
    setSearchParams(next, { replace: true })
  }

  const goToPage = (value) => {
    const next = new URLSearchParams(searchParams)
    next.set('page', String(value))
    setSearchParams(next, { replace: true })
  }

  const handleToggleStatus = async (book) => {
    const nextActive = !book.is_active

    const confirmed = await confirm({
      title: nextActive ? 'Habilitar libro' : 'Dar de baja el libro',
      message: `¿Confirmás que querés ${nextActive ? 'habilitar' : 'dar de baja'} "${book.name}"?`,
      confirmText: nextActive ? 'Sí, habilitar' : 'Sí, dar de baja',
      tone: nextActive ? 'primary' : 'danger',
    })

    if (!confirmed) {
      return
    }

    try {
      const response = await setBookStatus(book.id, nextActive)
      toast.success(
        nextActive ? 'Libro habilitado' : 'Libro dado de baja',
        response.message,
      )
      reload()
    } catch (err) {
      toast.error(
        'No se pudo actualizar',
        err instanceof ApiError ? err.message : 'Ocurrió un error inesperado.',
      )
    }
  }

  const columns = [
    {
      key: 'book',
      header: 'Libro',
      render: (book) => (
        <div className="cell-media">
          <BookCover book={book} />
          <div>
            <Link className="cell-title" to={`/libros/${book.id}`}>
              {book.name}
            </Link>
            <small className="cell-subtitle">
              {/* En el dominio del backend el ISBN se carga en bar_code. */}
              ISBN {book.bar_code || '—'} · ID {book.id}
            </small>
          </div>
        </div>
      ),
    },
    {
      key: 'relations',
      header: 'Categoría / Marca',
      render: (book) => (
        <>
          <strong>{relatedName(book.category, book.category)}</strong>
          <small className="cell-subtitle">
            {[relatedName(book.brand, book.brand), relatedName(book.model, book.model)]
              .filter((value) => value !== '—')
              .join(' · ') || '—'}
          </small>
        </>
      ),
    },
    {
      key: 'price',
      header: 'Precio',
      align: 'end',
      render: (book) => money(book.price_a_with_tax),
    },
    {
      key: 'stock',
      header: 'Stock',
      align: 'center',
      render: (book) => <StockBadge value={book.current_stock} />,
    },
    {
      key: 'status',
      header: 'Estado',
      align: 'center',
      render: (book) => <ActiveBadge active={book.is_active} />,
    },
    {
      key: 'warehouse',
      header: 'Depósito',
      render: (book) => book.inventory_items?.[0]?.warehouse_name || '—',
    },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (book) => (
        // Botones cuadrados de sólo ícono: con texto la columna se ensanchaba
        // demasiado y había que scrollear la tabla para ver el estado.
        <div className="row-actions">
          <Link
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            to={`/libros/${book.id}`}
            title="Administrar"
            aria-label={`Administrar ${book.name}`}
          >
            <Icon name="settings" size={16} />
          </Link>
          <Link
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            to={`/libros/${book.id}/editar`}
            title="Editar"
            aria-label={`Editar ${book.name}`}
          >
            <Icon name="edit" size={16} />
          </Link>
          <button
            className={`erp-btn erp-btn--icon erp-btn--sm ${book.is_active ? 'erp-btn-outline-danger' : 'erp-btn-outline'}`}
            type="button"
            onClick={() => handleToggleStatus(book)}
            title={book.is_active ? 'Dar de baja' : 'Habilitar'}
            aria-label={book.is_active ? `Dar de baja ${book.name}` : `Habilitar ${book.name}`}
          >
            <Icon name={book.is_active ? 'forbid' : 'checkCircle'} size={16} />
          </button>
        </div>
      ),
    },
  ]

  const hasFilters = Boolean(categoryId || brandId || status !== '' || searchParams.get('search'))

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Libros</h1>
          <p className="page-subtitle">Catálogo, precios, variantes y stock</p>
        </div>

        <div className="page-actions">
          <button
            className="erp-btn erp-btn-outline erp-btn--icon"
            type="button"
            onClick={reload}
            disabled={loading}
            title="Actualizar"
            aria-label="Actualizar el listado"
          >
            <Icon name="refresh" size={18} />
          </button>
          <Link className="erp-btn erp-btn-primary" to="/libros/nuevo">
            <Icon name="add" size={18} />
            Nuevo libro
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado de libros</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar por título, ISBN o código…"
              aria-label="Buscar libro"
            />
          </div>
        </div>

        <div className="erp-filters">
          <select
            className="erp-control erp-select erp-filter"
            value={categoryId}
            onChange={(event) => updateParam('category_id', event.target.value)}
            aria-label="Filtrar por categoría"
          >
            <option value="">Todas las categorías</option>
            {categories.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>

          <select
            className="erp-control erp-select erp-filter"
            value={brandId}
            onChange={(event) => updateParam('brand_id', event.target.value)}
            aria-label="Filtrar por marca"
          >
            <option value="">Todas las marcas</option>
            {brands.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>

          <select
            className="erp-control erp-select erp-filter"
            value={status}
            onChange={(event) => updateParam('is_active', event.target.value)}
            aria-label="Filtrar por estado"
          >
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inhabilitados</option>
          </select>

          {hasFilters ? (
            <button
              className="erp-btn erp-btn-ghost erp-btn--sm"
              type="button"
              onClick={() => {
                setSearch('')
                setSearchParams(new URLSearchParams(), { replace: true })
              }}
            >
              <Icon name="close" size={15} />
              Limpiar filtros
            </button>
          ) : null}
        </div>

        <DataTable
          columns={columns}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay libros para mostrar"
          footer={<Pagination meta={meta} onChange={goToPage} />}
        />
      </div>
    </>
  )
}
