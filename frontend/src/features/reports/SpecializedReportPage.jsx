import { useEffect, useMemo, useState } from 'react'
import { NavLink, useParams } from 'react-router-dom'

import { DataTable } from '../../components/DataTable'
import { FormSection, Select, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money, number } from '../../lib/format'
import { RemoteSearchSelect, useRemoteSearch } from '../sales/RemoteSearchSelect'
import { downloadSpecializedReport, getSpecializedReport, getSpecializedReportOptions, searchReportProducts } from './api'

const TYPES = ['ventas', 'compras', 'stock', 'rentabilidad']
const LABELS = { ventas: 'Ventas', compras: 'Compras', stock: 'Stock actual', rentabilidad: 'Rentabilidad' }

function isoDate(date) {
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 10)
}

function defaults(type) {
  const today = new Date()
  const from = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate())
  if (type === 'stock') return { product: '', author_id: '', publisher_id: '', category_id: '', collection_id: '', active: 'active', physical_warehouses: 0, variants_with_stock: 0, origin_currency: 0, by_dispatch: 0, show_thresholds: 1, page: 1, per_page: 100 }
  return { date_from: isoDate(from), date_to: isoDate(today), product_id: '', author_id: '', publisher_id: '', category_id: '', collection_id: '', with_variant: 0 }
}

const selectOptions = (values = []) => values.map((item) => ({ value: item.id, label: item.name }))

function Summary({ type, report }) {
  if (!report) return null
  const rows = report.rows ?? []
  const summary = report.summary ?? {}
  const stockUnits = rows.reduce((sum, row) => sum + Number(row.cells?.grand_total?.units ?? 0), 0)
  const stockValue = rows.reduce((sum, row) => sum + Number(row.cells?.grand_total?.valued ?? 0), 0)
  const cards = type === 'ventas' ? [
    ['Registros', number(summary.records)], ['Unidades vendidas', number(summary.quantity)], ['Total vendido', money(summary.total)], ['Ganancia estimada', money(summary.profit)],
  ] : type === 'compras' ? [
    ['Registros', number(summary.records ?? rows.length)], ['Unidades compradas', number(summary.quantity)], ['Descuentos', money(summary.discount)], ['Total comprado', money(summary.total)],
  ] : type === 'rentabilidad' ? [
    ['Libros analizados', number(summary.records)], ['Ventas', money(summary.total_sale)], ['Ganancia', money(summary.profit)], ['Margen global', summary.total_sale ? `${number(summary.profit * 100 / summary.total_sale)}%` : '0%'],
  ] : [
    ['Títulos', number(report.pagination?.total ?? rows.length)], ['Unidades', number(stockUnits)], ['Stock valorizado', money(stockValue)], ['Con stock bajo', number(rows.filter((row) => Number(row.cells?.grand_total?.units ?? 0) <= Number(row.min_stock ?? 0)).length)],
  ]
  return <div className="specialized-summary">{cards.map(([label, value]) => <div key={label}><span>{label}</span><strong>{value}</strong></div>)}</div>
}

export function SpecializedReportPage() {
  const { type = 'ventas' } = useParams()
  const currentType = TYPES.includes(type) ? type : 'ventas'
  const toast = useToast()
  const [filters, setFilters] = useState(() => defaults(currentType))
  const [options, setOptions] = useState({})
  const [selectedProduct, setSelectedProduct] = useState(null)
  const [report, setReport] = useState(null)
  const [loading, setLoading] = useState(false)
  const [downloading, setDownloading] = useState('')
  const [error, setError] = useState(null)

  const productSearch = useRemoteSearch(async (query) => {
    const response = await searchReportProducts(query)
    return (response?.data ?? []).map((product) => ({ value: String(product.id), label: product.name, hint: [product.bar_code, product.code].filter(Boolean).join(' · ') }))
  })

  useEffect(() => {
    setFilters(defaults(currentType)); setSelectedProduct(null); setReport(null); setError(null)
    getSpecializedReportOptions(currentType).then(setOptions).catch(() => toast.error('No se pudieron cargar los filtros del reporte.'))
  }, [currentType])

  const set = (field) => (event) => setFilters((value) => ({ ...value, [field]: event.target.value }))
  const search = async (event) => {
    event?.preventDefault()
    setLoading(true); setError(null)
    try { setReport(await getSpecializedReport(currentType, filters)) } catch (requestError) { setError(requestError); setReport(null) } finally { setLoading(false) }
  }
  const clear = () => { setFilters(defaults(currentType)); setSelectedProduct(null); setReport(null); setError(null) }
  const download = async (format) => {
    setDownloading(format)
    try { await downloadSpecializedReport(currentType, filters, format) } catch (e) { toast.error('No se pudo descargar', e instanceof ApiError ? e.message : 'Ocurrió un error inesperado.') } finally { setDownloading('') }
  }

  const rows = report?.rows ?? []
  const columns = useMemo(() => {
    if (currentType === 'ventas') return [
      { key: 'date', header: 'Fecha' }, { key: 'number', header: 'Comprobante' },
      { key: 'product', header: 'Libro', render: (r) => <div><strong>{r.product}</strong><small className="cell-subtitle">{r.barcode} · {r.author}</small></div> },
      { key: 'publisher', header: 'Editorial' }, { key: 'quantity', header: 'Cantidad', align: 'end', render: (r) => number(r.quantity) },
      { key: 'total', header: 'Venta', align: 'end', render: (r) => money(r.total) }, { key: 'profit', header: 'Ganancia', align: 'end', render: (r) => money(r.profit) },
    ]
    if (currentType === 'compras') return [
      { key: 'date', header: 'Fecha' }, { key: 'number', header: 'Comprobante' },
      { key: 'product', header: 'Libro', render: (r) => <div><strong>{r.product}</strong><small className="cell-subtitle">{r.barcode} · {r.author}</small></div> },
      { key: 'publisher', header: 'Editorial' }, { key: 'provider', header: 'Proveedor' },
      { key: 'quantity', header: 'Cantidad', align: 'end', render: (r) => number(r.quantity) }, { key: 'total', header: 'Total', align: 'end', render: (r) => money(r.total) },
    ]
    if (currentType === 'rentabilidad') return [
      { key: 'product', header: 'Libro' }, { key: 'code', header: 'Código' },
      { key: 'total_sale', header: 'Ventas', align: 'end', render: (r) => money(r.total_sale) },
      { key: 'profit', header: 'Ganancia', align: 'end', render: (r) => money(r.profit) },
      { key: 'average_margin', header: 'Margen', align: 'end', render: (r) => `${number(r.average_margin)}%` },
      { key: 'average_profitability', header: 'Rentabilidad', align: 'end', render: (r) => `${number(r.average_profitability)}%` },
    ]
    return [
      { key: 'name', header: 'Libro', render: (r) => <div><strong>{r.name}</strong><small className="cell-subtitle">{r.isbn || r.code} · {r.author}</small></div> },
      { key: 'publisher', header: 'Editorial' }, { key: 'category', header: 'Género' }, { key: 'collection', header: 'Colección' },
      { key: 'units', header: 'Stock', align: 'end', render: (r) => number(r.cells?.grand_total?.units) },
      { key: 'value', header: 'Valor', align: 'end', render: (r) => money(r.cells?.grand_total?.valued) },
    ]
  }, [currentType])

  return <>
    <div className="page-head"><div><h1 className="page-title">Reportes especializados</h1><p className="page-subtitle">Análisis comercial de ventas, compras, existencias y rentabilidad</p></div>
      <div className="page-actions"><button className="erp-btn erp-btn-outline" type="button" onClick={() => download('xlsx')} disabled={!report || Boolean(downloading)}>Excel</button>{currentType !== 'stock' && <button className="erp-btn erp-btn-outline" type="button" onClick={() => download('pdf')} disabled={!report || Boolean(downloading)}>PDF</button>}</div></div>

    <div className="report-kind-tabs">{TYPES.map((value) => <NavLink key={value} to={`/reportes/especializados/${value}`} className={({ isActive }) => isActive ? 'is-active' : ''}>{LABELS[value]}</NavLink>)}</div>
    <form onSubmit={search}><FormSection title={`Filtros de ${LABELS[currentType].toLowerCase()}`} description="Podés combinar los datos bibliográficos para acotar el resultado."><div className="form-grid">
      {currentType !== 'stock' && <><TextInput label="Fecha desde" type="date" value={filters.date_from} onChange={set('date_from')} required /><TextInput label="Fecha hasta" type="date" value={filters.date_to} onChange={set('date_to')} required /></>}
      <RemoteSearchSelect label="Libro, título o ISBN" placeholder="Buscar por título, ISBN o código…" search={productSearch.query} onSearch={productSearch.setQuery} results={productSearch.results} loading={productSearch.loading} selected={selectedProduct} onSelect={(product) => { setSelectedProduct(product); setFilters((value) => ({ ...value, ...(currentType === 'stock' ? { product: product?.label ?? '' } : { product_id: product?.value ?? '' }) })) }} minChars={3} />
      <Select label="Autor" value={filters.author_id} onChange={set('author_id')} options={selectOptions(options.authors)} placeholder="Todos" />
      <Select label="Editorial" value={filters.publisher_id} onChange={set('publisher_id')} options={selectOptions(options.publishers)} placeholder="Todas" />
      <Select label="Género" value={filters.category_id} onChange={set('category_id')} options={selectOptions(options.categories)} placeholder="Todos" />
      <Select label="Colección" value={filters.collection_id} onChange={set('collection_id')} options={selectOptions(options.collections)} placeholder="Todas" />
    </div><div className="form-actions"><button type="button" className="erp-btn erp-btn-outline" onClick={clear}>Limpiar</button><button type="submit" className="erp-btn erp-btn-primary" disabled={loading}><Icon name="search" size={17} />{loading ? 'Consultando…' : 'Generar reporte'}</button></div></FormSection></form>

    <Summary type={currentType} report={report} />
    <div className="erp-card"><div className="erp-card-head"><div><h2 className="erp-card-title">{LABELS[currentType]}</h2>{report && <small>{rows.length} filas visibles</small>}</div></div><DataTable columns={columns} rows={rows} rowKey={(row) => `${row.invoice_id ?? row.key ?? row.product}-${row.number ?? row.code ?? ''}`} loading={loading} error={error} onRetry={search} emptyText={report ? 'No hay resultados para los filtros seleccionados' : 'Aplicá los filtros y generá el reporte'} /></div>
  </>
}
