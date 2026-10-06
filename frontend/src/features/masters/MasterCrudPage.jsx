import { useCallback, useEffect, useMemo, useState } from 'react'

import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { normalizePaginator, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { createMasterItem, deleteMasterItem, updateMasterItem } from '../books/api'
import { useMasterOptions } from './useMasterOptions'
import { masterDefaults } from './registry'

/** Renderiza una celda según el tipo de columna declarada en el registro. */
function Cell({ column, row, config }) {
  if (column === 'name') {
    return <strong>{row.name}</strong>
  }

  if (column === 'is_active') {
    return row.is_active ? (
      <span className="erp-badge erp-badge--success">Activo</span>
    ) : (
      <span className="erp-badge erp-badge--danger">Inhabilitado</span>
    )
  }

  if (column === 'hex_code') {
    return row.hex_code ? (
      <span className="master-swatch">
        <span className="master-swatch-dot" style={{ background: row.hex_code }} />
        <code>{row.hex_code}</code>
      </span>
    ) : (
      '—'
    )
  }

  if (column === 'web_order') {
    return number(row.web_order)
  }

  // Columnas *_id: se resuelve el nombre con el catálogo correspondiente.
  const field = config.fields.find((item) => item.name === column)

  if (field?.type === 'master') {
    return <MasterCell field={field} value={row[column]} />
  }

  return row[column] ?? '—'
}

function MasterCell({ field, value }) {
  const { options } = useMasterOptions(field.resource)
  const match = options.find((option) => option.value === String(value ?? ''))

  return match ? match.label : '—'
}

/**
 * Formulario modal de alta / edición de un maestro.
 * Se monta sólo cuando hay un `draft`, así que los hooks quedan estables.
 */
function MasterForm({ config, draft, onClose, onSaved }) {
  const toast = useToast()
  const isEditing = Boolean(draft.id)

  const [values, setValues] = useState(draft.values)
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const change = (name) => (event) => {
    const { value, checked, type } = event.target
    setValues((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
    setErrors((current) => ({ ...current, [name]: undefined }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setSaving(true)
    setErrors({})

    // Los selects vacíos se mandan como null para no guardar '' en una FK. Los
    // campos numéricos en cambio van con su default (0), porque columnas como
    // web_order son NOT NULL y MySQL rechaza el null con un error de integridad.
    const body = config.fields.reduce((acc, field) => {
      const value = values[field.name]

      if (field.type === 'switch') {
        acc[field.name] = Boolean(value)
      } else if (field.type === 'number') {
        acc[field.name] = value === '' || value === null || value === undefined ? 0 : Number(value)
      } else {
        acc[field.name] = value === '' || value === null ? null : value
      }

      return acc
    }, {})

    try {
      if (isEditing) {
        await updateMasterItem(config.resource, draft.id, body)
        toast.success(`${config.singular[0].toUpperCase()}${config.singular.slice(1)} actualizada`)
      } else {
        await createMasterItem(config.resource, body)
        toast.success(`${config.singular[0].toUpperCase()}${config.singular.slice(1)} creada`)
      }

      onSaved()
      onClose()
    } catch (error) {
      if (error instanceof ApiError) {
        const fieldErrors = error.errors ?? {}

        if (Object.keys(fieldErrors).length) {
          setErrors(fieldErrors)
        } else {
          toast.error('No se pudo guardar', error.message)
        }
      } else {
        toast.error('No se pudo guardar', 'Ocurrió un error inesperado.')
      }
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="modal-backdrop-custom" role="presentation" onClick={onClose}>
      <div
        className="modal-card modal-card--form"
        role="dialog"
        aria-modal="true"
        aria-labelledby="masterFormTitle"
        onClick={(event) => event.stopPropagation()}
      >
        <form onSubmit={handleSubmit} noValidate>
          <div className="modal-card-head">
            <h3 className="modal-card-title" id="masterFormTitle">
              {isEditing ? `Editar ${config.singular}` : `${config.article ?? 'Nueva'} ${config.singular}`}
            </h3>
            <button className="modal-card-close" type="button" onClick={onClose} aria-label="Cerrar">
              <Icon name="close" size={18} />
            </button>
          </div>

          <div className="modal-card-body">
            <div className="erp-field-row">
              {config.fields.map((field) => (
                <MasterField
                  key={field.name}
                  field={field}
                  value={values[field.name]}
                  error={errors[field.name]}
                  onChange={change(field.name)}
                />
              ))}
            </div>
          </div>

          <div className="modal-card-actions">
            <button className="erp-btn erp-btn-outline" type="button" onClick={onClose}>
              Cancelar
            </button>
            <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
              {saving ? 'Guardando…' : 'Guardar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

function MasterField({ field, value, error, onChange }) {
  const { options, loading } = useMasterOptions(field.resource)
  const inputClass = 'erp-control erp-field-control'

  if (field.type === 'switch') {
    return (
      <div className="erp-field-block">
        <label className="erp-switch" htmlFor={field.name}>
          <input
            id={field.name}
            type="checkbox"
            role="switch"
            checked={Boolean(value)}
            onChange={onChange}
          />
          <span className="erp-switch-track" aria-hidden="true">
            <span className="erp-switch-thumb" />
          </span>
          <span className="erp-switch-label">{field.label}</span>
        </label>
      </div>
    )
  }

  return (
    <div className="erp-field-block">
      <label className="erp-label" htmlFor={field.name}>
        {field.label}
        {field.required ? <span className="erp-required" aria-hidden="true"> *</span> : null}
      </label>

      {field.type === 'master' ? (
        <select
          id={field.name}
          name={field.name}
          className={`erp-control erp-select${error ? ' is-invalid' : ''}`}
          value={value ?? ''}
          onChange={onChange}
          disabled={loading}
        >
          <option value="">{loading ? 'Cargando…' : 'Sin asignar'}</option>
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      ) : (
        <input
          id={field.name}
          name={field.name}
          type={field.type === 'number' ? 'number' : 'text'}
          className={`${inputClass}${error ? ' is-invalid' : ''}`}
          value={value ?? ''}
          onChange={onChange}
          placeholder={field.placeholder}
          step={field.type === 'number' ? '1' : undefined}
        />
      )}

      {error ? <div className="erp-invalid-feedback">{error}</div> : null}
    </div>
  )
}

/**
 * CRUD genérico de catálogos maestros.
 *
 * Una sola pantalla maneja los seis maestros: la definición de columnas y
 * campos viene de `registry.js` y las operaciones van a los endpoints que ya
 * expone el backend.
 */
export function MasterCrudPage({ config }) {
  const toast = useToast()
  const confirm = useConfirm()

  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)
  const [draft, setDraft] = useState(null)

  // Búsqueda con rebote, igual que el listado Blade (300 ms).
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

    return `/${config.resource}?${params.toString()}`
  }, [config.resource, page, query])

  const { data, loading, error, reload } = useApiResource(path)

  const { items, meta } = useMemo(() => normalizePaginator(data), [data])

  // Los maestros no exponen búsqueda en el backend: se filtra en el cliente.
  const visible = useMemo(() => {
    if (!query) {
      return items
    }

    const needle = query.toLowerCase()

    return items.filter((item) => String(item.name ?? '').toLowerCase().includes(needle))
  }, [items, query])

  const openCreate = () => setDraft({ id: null, values: masterDefaults(config) })

  const openEdit = (row) => {
    const values = config.fields.reduce((acc, field) => {
      acc[field.name] = row[field.name] ?? (field.type === 'switch' ? Boolean(row.is_active) : '')
      return acc
    }, {})

    setDraft({ id: row.id, values })
  }

  const handleDelete = useCallback(
    async (row) => {
      const confirmed = await confirm({
        title: `Eliminar ${config.singular}`,
        message: `¿Seguro que querés eliminar "${row.name}"? Esta acción no se puede deshacer.`,
        confirmText: 'Sí, eliminar',
        tone: 'danger',
      })

      if (!confirmed) {
        return
      }

      try {
        await deleteMasterItem(config.resource, row.id)
        toast.success(`${config.singular[0].toUpperCase()}${config.singular.slice(1)} eliminada`)
        reload()
      } catch (err) {
        // Los maestros devuelven el mensaje en inglés; se informa el real.
        const message =
          err instanceof ApiError && err.status === 0
            ? 'No se pudo conectar con el servidor.'
            : `No se pudo eliminar. Es posible que esté en uso.`

        toast.error('No se pudo eliminar', message)
      }
    },
    [config, confirm, reload, toast],
  )

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">{config.label}</h1>
          <p className="page-subtitle">{config.description}</p>
        </div>

        <div className="page-actions">
          <button className="erp-btn erp-btn-primary" type="button" onClick={openCreate}>
            <Icon name="add" size={18} />
            {config.article ?? 'Nueva'} {config.singular}
          </button>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Listado de {config.label.toLowerCase()}</h2>

          <div className="erp-search">
            <Icon name="search" size={16} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder={`Buscar ${config.singular}…`}
              aria-label={`Buscar ${config.singular}`}
            />
          </div>
        </div>

        <div className="table-scroll">
          <table className="erp-table">
            <thead>
              <tr>
                {config.columns.map((column) => (
                  <th key={column}>{column === 'name' ? 'Nombre' : COLUMN_LABELS[column] ?? column}</th>
                ))}
                <th className="is-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={config.columns.length + 1}>
                    <div className="table-state">
                      <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
                      <span>Cargando…</span>
                    </div>
                  </td>
                </tr>
              ) : error ? (
                <tr>
                  <td colSpan={config.columns.length + 1}>
                    <div className="table-state table-state--error" role="alert">
                      <strong>No se pudo cargar la información</strong>
                      <p>{error.message}</p>
                      <button className="erp-btn erp-btn-outline erp-btn--sm" type="button" onClick={reload}>
                        Reintentar
                      </button>
                    </div>
                  </td>
                </tr>
              ) : !visible.length ? (
                <tr>
                  <td colSpan={config.columns.length + 1}>
                    <div className="table-state">
                      <strong>
                        {query
                          ? `Sin resultados para "${query}"`
                          : `Todavía no hay ${config.label.toLowerCase()}`}
                      </strong>
                    </div>
                  </td>
                </tr>
              ) : (
                visible.map((row) => (
                  <tr key={row.id}>
                    {config.columns.map((column) => (
                      <td key={column}>
                        <Cell column={column} row={row} config={config} />
                      </td>
                    ))}
                    <td className="is-end">
                      <div className="row-actions">
                        <button
                          className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
                          type="button"
                          onClick={() => openEdit(row)}
                          title={`Editar ${row.name}`}
                          aria-label={`Editar ${row.name}`}
                        >
                          <Icon name="edit" size={16} />
                        </button>
                        <button
                          className="erp-btn erp-btn-ghost-danger erp-btn--icon erp-btn--sm"
                          type="button"
                          onClick={() => handleDelete(row)}
                          title={`Eliminar ${row.name}`}
                          aria-label={`Eliminar ${row.name}`}
                        >
                          <Icon name="trash" size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {draft ? (
        <MasterForm config={config} draft={draft} onClose={() => setDraft(null)} onSaved={reload} />
      ) : null}
    </>
  )
}

const COLUMN_LABELS = {
  description: 'Descripción',
  external_code: 'Código externo',
  web_order: 'Orden',
  is_active: 'Estado',
  hex_code: 'Color',
  parent_id: 'Padre',
  brand_id: 'Marca',
  size_type_id: 'Tipo de talle',
}
