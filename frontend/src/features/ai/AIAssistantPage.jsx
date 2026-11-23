import { useMemo, useState } from 'react'

import { Badge } from '../../components/Badge'
import { DataTable, Pagination } from '../../components/DataTable'
import { Select, Textarea } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { dateTime, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { listBooks } from '../books/api'
import { useMasterOptions } from '../masters/useMasterOptions'
import { RemoteSearchSelect, useRemoteSearch } from '../sales/RemoteSearchSelect'
import { searchClients } from '../sales/api'
import { approveAIRequest, createAIRequest, getAIRequest } from './api'

/**
 * Asistente bibliográfico con Gemini.
 *
 * La SPA arma la consulta, la envía a `POST /api/ia/request` y muestra la
 * respuesta. El backend es quien habla con Gemini y guarda el historial, así
 * que la clave API nunca llega al navegador.
 */

const REQUEST_TYPES = {
  sinopsis: {
    label: 'Sinopsis de un libro',
    product: 'required',
    author: false,
    promptLabel: 'Indicaciones para la sinopsis',
    promptPlaceholder: 'Ej.: tono cálido, 120 palabras, sin revelar el final…',
    defaultPrompt: 'Redactá una sinopsis comercial de aproximadamente 120 palabras.',
  },
  resumen_autor: {
    label: 'Resumen de autor',
    product: false,
    author: 'required',
    promptLabel: 'Indicaciones para el resumen',
    promptPlaceholder: 'Ej.: estilo enciclopédico, un párrafo…',
    defaultPrompt: 'Redactá un resumen bibliográfico breve del autor.',
  },
  generos_palabras_clave: {
    label: 'Géneros y palabras clave',
    product: 'optional',
    author: false,
    promptLabel: 'Indicaciones para las sugerencias',
    promptPlaceholder: 'Ej.: separá géneros de palabras clave…',
    defaultPrompt: 'Sugerí géneros, temas y palabras clave pertinentes para este libro.',
  },
  consulta_bibliografica: {
    label: 'Consulta bibliográfica',
    product: 'optional',
    author: false,
    promptLabel: 'Consulta',
    promptPlaceholder: 'Ej.: ¿qué libros del catálogo tratan sobre novela histórica latinoamericana?',
    defaultPrompt: '',
  },
  recomendacion_personalizada: {
    label: 'Recomendación personalizada',
    product: false,
    author: false,
    client: 'required',
    promptLabel: 'Preferencias o necesidad actual',
    promptPlaceholder: 'Ej.: busca una novela para vacaciones, prefiere historias breves y sin terror…',
    defaultPrompt: 'Recomendá entre 3 y 5 libros disponibles según sus compras anteriores y explicá brevemente cada sugerencia.',
  },
}

const TYPE_OPTIONS = Object.entries(REQUEST_TYPES).map(([value, item]) => ({
  value,
  label: item.label,
}))

const STATUS_TONES = {
  pendiente: 'warning',
  completado: 'success',
  error: 'danger',
}

const STATUS_LABELS = {
  pendiente: 'Pendiente',
  completado: 'Completado',
  error: 'Error',
}

function typeLabel(value) {
  return REQUEST_TYPES[value]?.label ?? value
}

export function AIAssistantPage() {
  const toast = useToast()

  const [type, setType] = useState('sinopsis')
  const [prompt, setPrompt] = useState(REQUEST_TYPES.sinopsis.defaultPrompt)
  const [product, setProduct] = useState(null)
  const [authorId, setAuthorId] = useState('')
  const [client, setClient] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [approving, setApproving] = useState(false)
  const [result, setResult] = useState(null)
  const [formError, setFormError] = useState(null)
  const [page, setPage] = useState(1)

  const config = REQUEST_TYPES[type]
  const showProduct = Boolean(config.product)

  const { options: authors } = useMasterOptions('authors')
  const { data: status } = useApiResource('/ia/status')

  const historyPath = useMemo(() => `/ia/request?page=${page}`, [page])
  const {
    data: historyData,
    loading: historyLoading,
    error: historyError,
    reload: reloadHistory,
  } = useApiResource(historyPath)

  const { items: historyItems, meta: historyMeta } = useMemo(
    () => normalizePaginator(historyData),
    [historyData],
  )

  const productSearch = useRemoteSearch(async (query) => {
    const response = await listBooks({ search: query, per_page: 10 })

    return (response?.data ?? []).map((book) => ({
      value: String(book.id),
      label: book.name,
      hint: [book.code, book.bar_code].filter(Boolean).join(' · '),
    }))
  })

  const clientSearch = useRemoteSearch(async (query) => {
    const response = await searchClients(query)
    return (response?.data ?? []).map((item) => ({
      value: String(item.id),
      label: item.name,
      hint: [item.document_number, item.email].filter(Boolean).join(' · '),
    }))
  })

  const changeType = (event) => {
    const next = event.target.value

    setType(next)
    setPrompt(REQUEST_TYPES[next].defaultPrompt)
    setProduct(null)
    setAuthorId('')
    setClient(null)
    setFormError(null)
    productSearch.setQuery('')
    clientSearch.setQuery('')
  }

  const submit = async (event) => {
    event.preventDefault()
    setFormError(null)

    if (!prompt.trim()) {
      setFormError('Escribí qué querés que genere el asistente.')
      return
    }

    if (config.product === 'required' && !product) {
      setFormError('Elegí el libro sobre el que querés generar la sinopsis.')
      return
    }

    if (config.author === 'required' && !authorId) {
      setFormError('Elegí el autor sobre el que querés generar el resumen.')
      return
    }

    if (config.client === 'required' && !client) {
      setFormError('Elegí el cliente para analizar sus compras anteriores.')
      return
    }

    setSubmitting(true)

    try {
      const response = await createAIRequest({
        tipo_solicitud: type,
        solicitud: prompt.trim(),
        product_id: product ? Number(product.value) : null,
        author_id: authorId ? Number(authorId) : null,
        cliente_id: client ? Number(client.value) : null,
      })

      setResult(response.data)
      setPage(1)
      reloadHistory()
      toast.success('Respuesta generada', 'La consulta quedó guardada en el historial.')
    } catch (error) {
      const message = error instanceof ApiError
        ? error.message
        : 'Ocurrió un error inesperado.'

      setFormError(message)
      toast.error('No se pudo generar la respuesta', message)
      reloadHistory()
    } finally {
      setSubmitting(false)
    }
  }

  const openRequest = async (row) => {
    try {
      const response = await getAIRequest(row.id)
      setResult(response.data)
    } catch (error) {
      toast.error(
        'No se pudo abrir la consulta',
        error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.',
      )
    }
  }

  const copyResponse = async () => {
    try {
      await navigator.clipboard.writeText(result?.respuesta ?? '')
      toast.success('Respuesta copiada')
    } catch {
      toast.error('No se pudo copiar', 'Tu navegador no permitió acceder al portapapeles.')
    }
  }

  const approveResult = async () => {
    if (!result?.id) {
      return
    }

    setApproving(true)

    try {
      await approveAIRequest(result.id)
      setResult((current) => current ? { ...current, ficha_aprobada: true } : current)
      toast.success(
        'Ficha guardada',
        'Las próximas consultas van a usar esta información aprobada como contexto.',
      )
    } catch (error) {
      toast.error(
        'No se pudo guardar la ficha',
        error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.',
      )
    } finally {
      setApproving(false)
    }
  }

  const canApprove = Boolean(
    result?.id
      && result?.estado === 'completado'
      && result?.respuesta
      && !result?.ficha_aprobada
      && result?.tipo_solicitud !== 'consulta_bibliografica'
      && (result?.product_id || result?.author_id),
  )

  const clearForm = () => {
    setPrompt(REQUEST_TYPES[type].defaultPrompt)
    setProduct(null)
    setAuthorId('')
    setClient(null)
    setFormError(null)
    productSearch.setQuery('')
    clientSearch.setQuery('')
  }

  const columns = [
    {
      key: 'created_at',
      header: 'Fecha',
      render: (row) => dateTime(row.created_at),
    },
    {
      key: 'tipo_solicitud',
      header: 'Tipo',
      render: (row) => typeLabel(row.tipo_solicitud),
    },
    {
      key: 'solicitud',
      header: 'Solicitud',
      render: (row) => (
        <span className="ai-history-prompt" title={row.solicitud}>
          {row.solicitud}
        </span>
      ),
    },
    {
      key: 'estado',
      header: 'Estado',
      render: (row) => (
        <Badge tone={STATUS_TONES[row.estado] ?? 'secondary'}>
          {STATUS_LABELS[row.estado] ?? row.estado}
        </Badge>
      ),
    },
    {
      key: 'modelo_ia',
      header: 'Modelo',
      render: (row) => row.modelo_ia ?? '—',
    },
    {
      key: 'actions',
      header: 'Acciones',
      align: 'end',
      render: (row) => (
        <button
          className="erp-btn erp-btn-outline erp-btn--sm"
          type="button"
          onClick={() => openRequest(row)}
        >
          <Icon name="eyeFilled" size={15} />
          Ver
        </button>
      ),
    },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Asistente IA</h1>
          <p className="page-subtitle">
            Generá sinopsis, resúmenes y sugerencias a partir de los datos del catálogo
          </p>
        </div>
      </div>

      {status && !status.configured ? (
        <div className="ai-banner ai-banner--warning" role="alert">
          <Icon name="warning" size={20} />
          <div>
            <strong>La IA todavía no está configurada</strong>
            <p>
              Agregá <code>GEMINI_API_KEY</code> en <code>backend/.env</code> y ejecutá{' '}
              <code>php artisan config:clear</code>.
            </p>
          </div>
        </div>
      ) : null}

      <div className="ai-layout">
        <div className="erp-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">Nueva consulta</h2>
            {status?.model ? <span className="erp-card-meta">{status.model}</span> : null}
          </div>

          <form className="ai-form" onSubmit={submit}>
            <Select
              label="Tipo de solicitud"
              value={type}
              onChange={changeType}
              options={TYPE_OPTIONS}
              required
            />

            {showProduct ? (
              <RemoteSearchSelect
                label={config.product === 'required' ? 'Libro' : 'Libro (opcional)'}
                placeholder="Buscar por título, código o ISBN…"
                search={productSearch.query}
                onSearch={productSearch.setQuery}
                results={productSearch.results}
                loading={productSearch.loading}
                selected={product}
                onSelect={setProduct}
                minChars={2}
              />
            ) : null}

            {config.author === 'required' ? (
              <Select
                label="Autor"
                value={authorId}
                onChange={(event) => setAuthorId(event.target.value)}
                options={authors}
                placeholder="Elegí un autor…"
                required
              />
            ) : null}

            {config.client === 'required' ? (
              <RemoteSearchSelect
                label="Cliente"
                placeholder="Buscar por nombre, documento o correo…"
                search={clientSearch.query}
                onSearch={clientSearch.setQuery}
                results={clientSearch.results}
                loading={clientSearch.loading}
                selected={client}
                onSelect={setClient}
                minChars={2}
              />
            ) : null}

            <Textarea
              label={config.promptLabel}
              value={prompt}
              onChange={(event) => setPrompt(event.target.value)}
              placeholder={config.promptPlaceholder}
              rows={7}
              maxLength={5000}
              required
            />

            <div className="ai-form-foot">
              <span className="erp-hint">{prompt.length}/5000</span>

              <div className="form-actions">
                <button className="erp-btn erp-btn-outline" type="button" onClick={clearForm}>
                  Limpiar
                </button>
                <button className="erp-btn erp-btn-primary" type="submit" disabled={submitting}>
                  <Icon name="sparkle" size={17} />
                  {submitting ? 'Generando…' : 'Generar respuesta'}
                </button>
              </div>
            </div>

            {formError ? (
              <div className="ai-form-error" role="alert">
                {formError}
              </div>
            ) : null}
          </form>
        </div>

        <div className="erp-card ai-result-card">
          <div className="erp-card-head">
            <h2 className="erp-card-title">Respuesta</h2>

            {canApprove || result?.respuesta ? (
              <div className="ai-result-actions">
                {canApprove ? (
                  <button
                    className="erp-btn erp-btn-outline erp-btn--sm"
                    type="button"
                    onClick={approveResult}
                    disabled={approving}
                    title="Usar esta respuesta como ficha aprobada del catálogo"
                  >
                    <Icon name="save" size={15} />
                    {approving ? 'Guardando…' : 'Guardar ficha'}
                  </button>
                ) : null}

                {result?.respuesta ? (
                  <button
                    className="erp-btn erp-btn-outline erp-btn--sm"
                    type="button"
                    onClick={copyResponse}
                  >
                    <Icon name="copy" size={15} />
                    Copiar
                  </button>
                ) : null}
              </div>
            ) : null}
          </div>

          {submitting ? (
            <div className="table-state">
              <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
              <span>Consultando a Gemini…</span>
            </div>
          ) : result ? (
            <div className="ai-result">
              <div className="ai-result-meta">
                <Badge tone={STATUS_TONES[result.estado] ?? 'secondary'}>
                  {STATUS_LABELS[result.estado] ?? result.estado}
                </Badge>
                <span>{typeLabel(result.tipo_solicitud)}</span>
                {result.modelo_ia ? <span>{result.modelo_ia}</span> : null}
                <span>{dateTime(result.created_at)}</span>
              </div>

              {result.estado === 'error' && result.mensaje_error ? (
                <p className="ai-result-error">{result.mensaje_error}</p>
              ) : null}

              <div className="ai-result-text">
                {result.respuesta || 'La consulta no devolvió texto.'}
              </div>
            </div>
          ) : (
            <div className="ai-empty">
              <Icon name="sparkle" size={30} />
              <strong>Tu respuesta aparecerá acá</strong>
              <p>
                Elegí un tipo de consulta, completá los datos y generá la respuesta con Gemini.
              </p>
            </div>
          )}
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head">
          <h2 className="erp-card-title">Historial de consultas</h2>

          <button
            className="erp-btn erp-btn-outline erp-btn--icon erp-btn--sm"
            type="button"
            onClick={reloadHistory}
            disabled={historyLoading}
            title="Actualizar historial"
            aria-label="Actualizar historial"
          >
            <Icon name="refresh" size={16} />
          </button>
        </div>

        <DataTable
          columns={columns}
          rows={historyItems}
          loading={historyLoading}
          error={historyError}
          onRetry={reloadHistory}
          emptyText="Todavía no hiciste consultas a la IA"
          footer={<Pagination meta={historyMeta} onChange={setPage} />}
        />
      </div>
    </>
  )
}
