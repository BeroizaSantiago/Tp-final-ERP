import { useEffect, useMemo, useState } from 'react'

import { Badge } from '../../components/Badge'
import { DataTable } from '../../components/DataTable'
import { FormSection, Select, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { number } from '../../lib/format'
import { RemoteSearchSelect, useRemoteSearch } from '../sales/RemoteSearchSelect'
import {
  downloadStockMovementReport,
  getStockMovementOptions,
  getStockMovements,
  searchReportProducts,
} from './api'

function localDate(value = new Date()) {
  const offset = value.getTimezoneOffset() * 60000
  return new Date(value.getTime() - offset).toISOString().slice(0, 10)
}

function initialFilters() {
  const today = new Date()
  return {
    date_from: localDate(new Date(today.getFullYear(), today.getMonth(), 1)),
    date_to: localDate(today),
    product_ids: [],
    name: '',
    branches: [],
    warehouses: [],
    modules: [],
    operation_types: [],
    reason_ids: [],
  }
}

const emptyOptions = {
  branches: [],
  warehouses: [],
  modules: {},
  operation_types: [],
  reasons: [],
}

export function StockMovementReportPage() {
  const toast = useToast()
  const [filters, setFilters] = useState(initialFilters)
  const [options, setOptions] = useState(emptyOptions)
  const [selectedProduct, setSelectedProduct] = useState(null)
  const [report, setReport] = useState(null)
  const [loading, setLoading] = useState(false)
  const [downloading, setDownloading] = useState('')
  const [error, setError] = useState(null)

  const productSearch = useRemoteSearch(async (query) => {
    const response = await searchReportProducts(query)
    return (response?.data ?? []).map((product) => ({
      value: String(product.id),
      label: product.name,
      hint: [product.code, product.bar_code].filter(Boolean).join(' · '),
    }))
  })

  useEffect(() => {
    getStockMovementOptions()
      .then((data) => setOptions({ ...emptyOptions, ...data }))
      .catch(() => toast.error('No se pudieron cargar los filtros del reporte.'))
  }, [])

  const operationOptions = useMemo(() => {
    const module = filters.modules[0]
    return options.operation_types.filter((item) => !module || item.module === module)
  }, [options.operation_types, filters.modules])

  const set = (field) => (event) => setFilters((current) => ({
    ...current,
    [field]: event.target.value,
  }))
  const setArray = (field) => (event) => setFilters((current) => ({
    ...current,
    [field]: event.target.value ? [event.target.value] : [],
    ...(field === 'modules' ? { operation_types: [] } : {}),
  }))

  const search = async (event) => {
    event?.preventDefault()
    if (!filters.date_from || !filters.date_to || filters.date_from > filters.date_to) {
      toast.error('Revisá las fechas', 'La fecha desde no puede ser posterior a la fecha hasta.')
      return
    }
    setLoading(true)
    setError(null)
    try {
      setReport(await getStockMovements(filters))
    } catch (requestError) {
      setError(requestError)
      setReport(null)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { search() }, [])

  const download = async (format) => {
    setDownloading(format)
    try {
      await downloadStockMovementReport(filters, format)
    } catch (downloadError) {
      toast.error('No se pudo descargar', downloadError instanceof ApiError ? downloadError.message : 'Ocurrió un error inesperado.')
    } finally {
      setDownloading('')
    }
  }

  const clear = () => {
    setFilters(initialFilters())
    setSelectedProduct(null)
    setReport(null)
    setError(null)
  }

  const rows = report?.rows ?? []
  const summary = report?.summary ?? { income: 0, expense: 0, net: 0 }
  const columns = [
    { key: 'date', header: 'Fecha' },
    { key: 'operation', header: 'Operación', render: (row) => <Badge tone={row.quantity >= 0 ? 'success' : 'warning'}>{row.operation}</Badge> },
    { key: 'product', header: 'Libro', render: (row) => <div><strong>{row.product}</strong><small className="cell-subtitle">{row.product_code !== '-' ? row.product_code : row.bar_code}</small></div> },
    { key: 'reason', header: 'Motivo' },
    { key: 'document', header: 'Operación relacionada' },
    { key: 'quantity', header: 'Cantidad', align: 'end', render: (row) => <strong style={{ color: row.quantity >= 0 ? 'var(--erp-success)' : 'var(--erp-danger)' }}>{row.quantity > 0 ? '+' : ''}{number(row.quantity)}</strong> },
    { key: 'stock', header: 'Stock resultante', align: 'end', render: (row) => number(row.stock) },
    { key: 'user', header: 'Usuario responsable' },
  ]

  return (
    <>
      <div className="page-head">
        <div>
          <h1 className="page-title">Trazabilidad del inventario</h1>
          <p className="page-subtitle">Historial de compras, ventas, devoluciones y ajustes de cada libro</p>
        </div>
        <div className="page-actions">
          <button type="button" className="erp-btn erp-btn-outline" onClick={() => download('xlsx')} disabled={Boolean(downloading)}><Icon name="fileList" size={17} />{downloading === 'xlsx' ? 'Generando…' : 'Excel'}</button>
          <button type="button" className="erp-btn erp-btn-outline" onClick={() => download('pdf')} disabled={Boolean(downloading)}><Icon name="fileList" size={17} />{downloading === 'pdf' ? 'Generando…' : 'PDF'}</button>
        </div>
      </div>

      <form onSubmit={search}>
        <FormSection title="Filtros" description="Acotá el historial por período, libro, ubicación u operación.">
          <div className="form-grid">
            <TextInput label="Fecha desde" type="date" value={filters.date_from} onChange={set('date_from')} required />
            <TextInput label="Fecha hasta" type="date" value={filters.date_to} onChange={set('date_to')} required />
            <RemoteSearchSelect
              label="Libro"
              placeholder="Buscar por título, ISBN o código…"
              search={productSearch.query}
              onSearch={productSearch.setQuery}
              results={productSearch.results}
              loading={productSearch.loading}
              selected={selectedProduct}
              onSelect={(product) => {
                setSelectedProduct(product)
                setFilters((current) => ({ ...current, product_ids: product ? [Number(product.value)] : [] }))
              }}
              minChars={3}
            />
            <TextInput label="Nombre del libro" value={filters.name} onChange={set('name')} placeholder="Coincidencia por nombre" />
            <Select label="Sucursal" value={filters.branches[0] ?? ''} onChange={setArray('branches')} options={options.branches.map((value) => ({ value, label: value }))} placeholder="Todas" />
            <Select label="Depósito" value={filters.warehouses[0] ?? ''} onChange={setArray('warehouses')} options={options.warehouses.map((value) => ({ value, label: value }))} placeholder="Todos" />
            <Select label="Módulo" value={filters.modules[0] ?? ''} onChange={setArray('modules')} options={Object.entries(options.modules).map(([value, label]) => ({ value, label }))} placeholder="Todos" />
            <Select label="Tipo de operación" value={filters.operation_types[0] ?? ''} onChange={setArray('operation_types')} options={operationOptions} placeholder="Todas" />
            <Select label="Motivo de ajuste" value={filters.reason_ids[0] ?? ''} onChange={setArray('reason_ids')} options={options.reasons.map((item) => ({ value: item.id, label: item.name }))} placeholder="Todos" />
          </div>
          <div className="form-actions">
            <button type="button" className="erp-btn erp-btn-outline" onClick={clear}>Limpiar</button>
            <button type="submit" className="erp-btn erp-btn-primary" disabled={loading}><Icon name="search" size={17} />{loading ? 'Buscando…' : 'Consultar historial'}</button>
          </div>
        </FormSection>
      </form>

      <div className="report-summary-grid" aria-label="Resumen de movimientos">
        <div className="report-summary-card report-summary-card--income">
          <span className="report-summary-label">Ingresos</span>
          <strong className="report-summary-value">+{number(summary.income)}</strong>
          <small>Unidades incorporadas</small>
        </div>
        <div className="report-summary-card report-summary-card--expense">
          <span className="report-summary-label">Egresos</span>
          <strong className="report-summary-value">-{number(summary.expense)}</strong>
          <small>Unidades descontadas</small>
        </div>
        <div className="report-summary-card report-summary-card--net">
          <span className="report-summary-label">Movimiento neto</span>
          <strong className="report-summary-value">{summary.net > 0 ? '+' : ''}{number(summary.net)}</strong>
          <small>Variación del período</small>
        </div>
        <div className="report-summary-card report-summary-card--count">
          <span className="report-summary-label">Movimientos</span>
          <strong className="report-summary-value">{number(rows.length)}</strong>
          <small>Operaciones encontradas</small>
        </div>
      </div>

      <div className="erp-card">
        <div className="erp-card-head"><h2 className="erp-card-title">Movimientos registrados</h2></div>
        <DataTable columns={columns} rows={rows} rowKey={(row) => `${row.date}-${row.document}-${row.product}-${row.quantity}`} loading={loading} error={error} onRetry={search} emptyText="No hay movimientos para los filtros seleccionados" />
      </div>
    </>
  )
}
