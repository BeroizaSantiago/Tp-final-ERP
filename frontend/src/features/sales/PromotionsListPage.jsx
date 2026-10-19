import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { date, money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { useToast } from '../../context/ToastContext'
import {
  APPLIES_TO_LABEL,
  DISCOUNT_TYPE_LABEL,
  listSalesPromotions,
  toggleSalesPromotion,
} from './api'

const STATUS_OPTIONS = [
  { value: '', label: 'Todos' },
  { value: 'active', label: 'Activas' },
  { value: 'inactive', label: 'Inactivas' },
]

const VALIDITY_OPTIONS = [
  { value: '', label: 'Todas' },
  { value: 'current', label: 'Vigentes' },
  { value: 'future', label: 'Futuras' },
  { value: 'expired', label: 'Vencidas' },
]

export function PromotionsListPage() {
  const { success, error: toastError } = useToast()

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [validity, setValidity] = useState('')
  const [page, setPage] = useState(1)

  const path = useMemo(() => {
    const params = { page }
    if (search.trim()) params.search = search.trim()
    if (status) params.status = status
    if (validity) params.validity = validity

    return `/sales-promotions?${new URLSearchParams(params).toString()}`
  }, [page, search, status, validity])

  const { data, loading, error, reload } = useApiResource(path)
  const { items, meta } = useMemo(() => normalizePaginator(data), [data])

  const toggle = async (id) => {
    try {
      await toggleSalesPromotion(id)
      success('Promoción actualizada', 'El estado de la promoción se cambió correctamente.')
      reload()
    } catch (err) {
      toastError('No se pudo actualizar la promoción', err.message)
    }
  }

  const columns = [
    {
      key: 'name',
      header: 'Nombre',
      render: (p) => <div><strong>{p.name}</strong></div>,
    },
    {
      key: 'discount_type',
      header: 'Tipo de descuento',
      render: (p) => (
        <span className="promotion-pill promotion-pill--neutral">
          {DISCOUNT_TYPE_LABEL[p.discount_type] ?? p.discount_type}
        </span>
      ),
    },
    {
      key: 'vigencia',
      header: 'Vigencia',
      render: (p) => (
        <div className="promotion-validity">
          <span>{p.date_from ? date(p.date_from) : 'Sin inicio'}</span>
          <small>hasta {p.date_to ? date(p.date_to) : 'sin vencimiento'}</small>
        </div>
      ),
    },
    {
      key: 'applies_to',
      header: 'Alcance',
      render: (p) => (
        <span className="promotion-pill promotion-pill--scope">
          {APPLIES_TO_LABEL[p.applies_to] ?? p.applies_to}
        </span>
      ),
    },
    {
      key: 'discount',
      header: 'Descuento',
      render: (p) => {
        if (p.discount_type === 'percentage') {
          return <strong className="promotion-discount">{Number(p.discount_value || 0)}%</strong>
        }
        return <strong className="promotion-discount">{money(p.discount_value)}</strong>
      },
    },
    {
      key: 'is_active',
      header: 'Estado',
      render: (p) => (
        <Badge tone={p.is_active ? 'success' : 'secondary'}>
          {p.is_active ? 'Activa' : 'Inactiva'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (p) => (
        <div className="promotion-row-actions">
          <Link
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            to={`/ventas/promociones/${p.id}/editar`}
            title="Editar promoción"
            aria-label={`Editar ${p.name}`}
          >
            <Icon name="edit" size={16} />
          </Link>
          <button
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            type="button"
            onClick={() => toggle(p.id)}
            disabled={loading}
            title={p.is_active ? 'Desactivar promoción' : 'Activar promoción'}
            aria-label={p.is_active ? 'Desactivar promoción' : 'Activar promoción'}
          >
            <Icon name={p.is_active ? 'forbid' : 'checkCircle'} size={16} />
          </button>
        </div>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Promociones y Descuentos</h1>
          <p className="page-subtitle">
            Descuentos y periodos de vigencia aplicables a libros, autores, géneros, colecciones y
            editoriales.
          </p>
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
          <Link className="erp-btn erp-btn-primary" to="/ventas/promociones/nuevo">
            <Icon name="add" size={18} />
            Nueva promoción
          </Link>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Listado de promociones</h2>
          <span className="promotion-count">{meta?.total ?? items.length} registradas</span>
        </div>

          <form id="promotion-filters" className="promotion-filter-bar" onSubmit={(event) => event.preventDefault()}>
            <div className="erp-filter">
              <label className="erp-label">Nombre</label>
              <input
                className="erp-control"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Buscar promoción…"
                aria-label="Buscar promoción"
              />
            </div>

            <div className="erp-filter">
              <label className="erp-label">Estado</label>
              <select
                className="erp-control erp-select"
                value={status}
                onChange={(event) => {
                  setStatus(event.target.value)
                  setPage(1)
                }}
              >
                {STATUS_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="erp-filter">
              <label className="erp-label">Vigencia</label>
              <select
                className="erp-control erp-select"
                value={validity}
                onChange={(event) => {
                  setValidity(event.target.value)
                  setPage(1)
                }}
              >
                {VALIDITY_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="promotion-filter-actions">
              <button
                className="erp-btn erp-btn-outline"
                type="button"
                onClick={() => {
                  setSearch('')
                  setStatus('')
                  setValidity('')
                  setPage(1)
                }}
                disabled={!search && !status && !validity}
              >
                Limpiar
              </button>
            </div>
          </form>

        <DataTable
          columns={columns}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay promociones registradas"
        />

        <Pagination meta={meta} onChange={setPage} />
      </div>
    </>
  )
}
