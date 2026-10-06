import { useEffect, useMemo, useState } from 'react'

import { ActiveBadge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { createProvider, updateProvider } from './api'

const EMPTY_FORM = {
  name: '',
  fantasy_name: '',
  document_type: '',
  identification_number: '',
  vat_classification: '',
  gross_income_number: '',
  address: '',
  city_name: '',
  province_name: '',
  primary_phone: '',
  email: '',
  is_active: true,
}

const DOCUMENT_TYPES = ['CUIT', 'DNI', 'CUIL', 'Pasaporte']

/** Modal de alta / edición de proveedor (campos del Blade de proveedores). */
function ProviderForm({ draft, onClose, onSaved }) {
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

    const body = {
      name: values.name,
      fantasy_name: values.fantasy_name || null,
      document_type: values.document_type || null,
      identification_number: values.identification_number || null,
      vat_classification: values.vat_classification || null,
      gross_income_number: values.gross_income_number || null,
      address: values.address || null,
      city_name: values.city_name || null,
      province_name: values.province_name || null,
      primary_phone: values.primary_phone || null,
      email: values.email || null,
      is_active: Boolean(values.is_active),
    }

    try {
      if (isEditing) {
        await updateProvider(draft.id, body)
        toast.success('Proveedor actualizado')
      } else {
        await createProvider(body)
        toast.success('Proveedor creado')
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
        aria-labelledby="providerFormTitle"
        onClick={(event) => event.stopPropagation()}
      >
        <form onSubmit={handleSubmit} noValidate>
          <div className="modal-card-head">
            <h3 className="modal-card-title" id="providerFormTitle">
              {isEditing ? 'Editar proveedor' : 'Nuevo proveedor'}
            </h3>
            <button className="modal-card-close" type="button" onClick={onClose} aria-label="Cerrar">
              <Icon name="close" size={18} />
            </button>
          </div>

          <div className="modal-card-body">
            <div className="erp-field-row">
              <div className="erp-field-block">
                <label className="erp-label" htmlFor="name">
                  Razón Social<span className="erp-required" aria-hidden="true"> *</span>
                </label>
                <input
                  id="name"
                  type="text"
                  className={`erp-control erp-field-control${errors.name ? ' is-invalid' : ''}`}
                  value={values.name ?? ''}
                  onChange={change('name')}
                />
                {errors.name ? <div className="erp-invalid-feedback">{errors.name}</div> : null}
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="fantasy_name">Nombre Fantasía</label>
                <input
                  id="fantasy_name"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.fantasy_name ?? ''}
                  onChange={change('fantasy_name')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="document_type">Tipo Documento</label>
                <select
                  id="document_type"
                  className="erp-control erp-select"
                  value={values.document_type ?? ''}
                  onChange={change('document_type')}
                >
                  <option value="">Seleccione...</option>
                  {DOCUMENT_TYPES.map((type) => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="identification_number">Número Documento</label>
                <input
                  id="identification_number"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.identification_number ?? ''}
                  onChange={change('identification_number')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="vat_classification">Condición frente al IVA</label>
                <input
                  id="vat_classification"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.vat_classification ?? ''}
                  onChange={change('vat_classification')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="gross_income_number">Número de Ingresos Brutos</label>
                <input
                  id="gross_income_number"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.gross_income_number ?? ''}
                  onChange={change('gross_income_number')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="address">Domicilio</label>
                <input
                  id="address"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.address ?? ''}
                  onChange={change('address')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="city_name">Localidad</label>
                <input
                  id="city_name"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.city_name ?? ''}
                  onChange={change('city_name')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="province_name">Provincia</label>
                <input
                  id="province_name"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.province_name ?? ''}
                  onChange={change('province_name')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="primary_phone">Teléfono</label>
                <input
                  id="primary_phone"
                  type="text"
                  className="erp-control erp-field-control"
                  value={values.primary_phone ?? ''}
                  onChange={change('primary_phone')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-label" htmlFor="email">Correo electrónico</label>
                <input
                  id="email"
                  type="email"
                  className="erp-control erp-field-control"
                  placeholder="compras@proveedor.com"
                  value={values.email ?? ''}
                  onChange={change('email')}
                />
              </div>

              <div className="erp-field-block">
                <label className="erp-switch" htmlFor="is_active">
                  <input
                    id="is_active"
                    type="checkbox"
                    role="switch"
                    checked={Boolean(values.is_active)}
                    onChange={change('is_active')}
                  />
                  <span className="erp-switch-track" aria-hidden="true">
                    <span className="erp-switch-thumb" />
                  </span>
                  <span className="erp-switch-label">Proveedor activo</span>
                </label>
              </div>
            </div>
          </div>

          <div className="modal-card-actions">
            <button className="erp-btn erp-btn-outline" type="button" onClick={onClose}>
              Cancelar
            </button>
            <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
              {saving ? 'Guardando…' : 'Guardar proveedor'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

/** Listado de proveedores con alta y edición por modal. */
export function ProvidersPage() {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)
  const [draft, setDraft] = useState(null)

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

    return `/providers?${params.toString()}`
  }, [page, query])

  const { data, loading, error, reload } = useApiResource(path)
  const { items, meta } = useMemo(() => normalizePaginator(data), [data])

  const openCreate = () => setDraft({ id: null, values: { ...EMPTY_FORM } })

  const openEdit = (row) => {
    setDraft({ id: row.id, values: { ...EMPTY_FORM, ...row } })
  }

  const columns = [
    { key: 'name', header: 'Razón Social', render: (p) => <strong>{p.name}</strong> },
    { key: 'fantasy_name', header: 'Nombre Fantasía', render: (p) => p.fantasy_name ?? '—' },
    { key: 'document_type', header: 'Tipo Doc.', render: (p) => p.document_type ?? '—' },
    { key: 'identification_number', header: 'Nro. Documento', render: (p) => p.identification_number ?? '—' },
    { key: 'primary_phone', header: 'Teléfono', render: (p) => p.primary_phone ?? '—' },
    { key: 'email', header: 'Email', render: (p) => p.email ?? '—' },
    { key: 'is_active', header: 'Estado', render: (p) => <ActiveBadge active={p.is_active} /> },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (p) => (
        <button
          className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
          type="button"
          onClick={() => openEdit(p)}
          title={`Editar ${p.name}`}
          aria-label={`Editar ${p.name}`}
        >
          <Icon name="edit" size={16} />
        </button>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Proveedores</h1>
          <p className="page-subtitle">Listado de proveedores registrados</p>
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
            Nuevo proveedor
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
              placeholder="Buscar proveedor…"
              aria-label="Buscar proveedor"
            />
          </div>
        </div>

        <DataTable
          columns={columns}
          rows={items}
          loading={loading}
          error={error}
          onRetry={reload}
          emptyText="No hay proveedores para mostrar"
          footer={<Pagination meta={meta} onChange={setPage} />}
        />
      </div>

      {draft ? <ProviderForm draft={draft} onClose={() => setDraft(null)} onSaved={reload} /> : null}
    </>
  )
}
