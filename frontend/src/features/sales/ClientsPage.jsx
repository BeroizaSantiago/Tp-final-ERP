import { useEffect, useMemo, useState } from 'react'

import { ActiveBadge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { updateClient } from './api'
import { ClientFormModal, clientPayload } from './ClientFormModal'

/**
 * ABM de clientes del módulo de ventas.
 *
 * Consume `Api\Clients\Catalog\ClientController`. El catálogo no tiene
 * endpoint de borrado: la baja de un cliente se hace inhalibitando el
 * registro, igual que en el resto de los catálogos del ERP.
 */

const ACTIVE_FILTERS = [
  { value: '', label: 'Todos' },
  { value: '1', label: 'Activos' },
  { value: '0', label: 'De baja' },
]

/** Lleva la fila del listado a la forma que espera la API al actualizarla. */
function rowToPayload(row) {
  return clientPayload({
    ...row,
    birth_date: row.birth_date ? String(row.birth_date).slice(0, 10) : '',
    discount: row.discount === null || row.discount === undefined ? '0' : String(row.discount),
    credit_limit: row.credit_limit === null || row.credit_limit === undefined ? '' : String(row.credit_limit),
  })
}

export function ClientsPage() {
  const toast = useToast()

  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [draft, setDraft] = useState(null)
  const [toggling, setToggling] = useState(null)

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setQuery(search.trim())
      setPage(1)
    }, 300)

    return () => window.clearTimeout(timer)
  }, [search])

  const path = useMemo(() => {
    const params = new URLSearchParams({ page: String(page), per_page: '20' })

    if (query) {
      params.set('search', query)
    }

    if (status !== '') {
      params.set('is_active', status)
    }

    return `/clients?${params.toString()}`
  }, [page, query, status])

  const { data, loading, error, reload } = useApiResource(path)
  const { items, meta } = useMemo(() => normalizePaginator(data), [data])

  const openCreate = () => setDraft({ client: null })

  const openEdit = (row) => setDraft({ client: row })

  /** Habilita o da de baja un cliente, mandando el registro completo. */
  const toggleActive = async (row) => {
    setToggling(row.id)

    try {
      await updateClient(row.id, { ...rowToPayload(row), is_active: !row.is_active })

      toast.success(row.is_active ? 'Cliente dado de baja' : 'Cliente habilitado')
      reload()
    } catch (err) {
      toast.error(
        'No se pudo actualizar el cliente',
        err instanceof ApiError ? err.message : 'Ocurrió un error inesperado.',
      )
    } finally {
      setToggling(null)
    }
  }

  const columns = [
    {
      key: 'name',
      header: 'Nombre / Razón social',
      render: (row) => (
        <div>
          <strong>{row.name}</strong>
          {row.fantasy_name ? <div className="erp-hint">{row.fantasy_name}</div> : null}
        </div>
      ),
    },
    {
      key: 'document',
      header: 'Documento',
      render: (row) =>
        row.document_number
          ? [row.document_type, row.document_number].filter(Boolean).join(' · ')
          : '—',
    },
    { key: 'first_phone', header: 'Teléfono', render: (row) => row.first_phone ?? '—' },
    { key: 'email', header: 'Correo', render: (row) => row.email ?? '—' },
    { key: 'city', header: 'Localidad', render: (row) => row.city ?? '—' },
    {
      key: 'credit_limit',
      header: 'Límite de crédito',
      align: 'end',
      render: (row) => (row.credit_limit === null || row.credit_limit === undefined ? '—' : money(row.credit_limit)),
    },
    { key: 'is_active', header: 'Estado', render: (row) => <ActiveBadge active={row.is_active} /> },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (row) => (
        <div style={{ display: 'flex', gap: '0.35rem', justifyContent: 'flex-end' }}>
          <button
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            type="button"
            onClick={() => toggleActive(row)}
            disabled={toggling === row.id}
            title={row.is_active ? `Dar de baja ${row.name}` : `Habilitar ${row.name}`}
            aria-label={row.is_active ? `Dar de baja ${row.name}` : `Habilitar ${row.name}`}
          >
            <Icon name={row.is_active ? 'forbid' : 'checkCircle'} size={16} />
          </button>

          <button
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            type="button"
            onClick={() => openEdit(row)}
            title={`Editar ${row.name}`}
            aria-label={`Editar ${row.name}`}
          >
            <Icon name="edit" size={16} />
          </button>
        </div>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Clientes</h1>
          <p className="page-subtitle">Listado de clientes registrados</p>
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
          <button className="erp-btn erp-btn-primary" type="button" onClick={openCreate}>
            <Icon name="add" size={18} />
            Nuevo cliente
          </button>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head erp-card-head--wrap">
          <h2 className="erp-card-title">Listado</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar cliente por nombre, documento o código…"
              aria-label="Buscar cliente"
            />
          </div>
        </div>

        <div className="erp-filters">
          <select
            className="erp-control erp-select erp-filter"
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              setPage(1)
            }}
            aria-label="Filtrar por estado"
          >
            {ACTIVE_FILTERS.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>
        </div>

        <DataTable
          columns={columns}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay clientes para mostrar"
          footer={<Pagination meta={meta} onChange={setPage} />}
        />
      </div>

      {draft ? (
        <ClientFormModal
          client={draft.client}
          onClose={() => setDraft(null)}
          onSaved={reload}
        />
      ) : null}
    </>
  )
}
