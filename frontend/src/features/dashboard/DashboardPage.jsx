import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js'
import { Bar, Doughnut, Line } from 'react-chartjs-2'
import { Link } from 'react-router-dom'

import { Badge } from '../../components/Badge'
import { Icon } from '../../components/icons'
import { date, money, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'

ChartJS.register(CategoryScale, LinearScale, LineElement, PointElement, BarElement, ArcElement, Filler, Tooltip, Legend)

const purple = '#8b5cf6'
const palette = ['#8b5cf6', '#22c55e', '#3b82f6', '#f59e0b', '#ec4899', '#64748b']

function MetricCard({ icon, label, value, detail, tone = 'primary' }) {
  return <div className="dashboard-metric"><span className={`dashboard-metric-icon is-${tone}`}><Icon name={icon} size={19} /></span><span className="dashboard-metric-label">{label}</span><strong>{value}</strong><small>{detail}</small></div>
}

function Panel({ title, subtitle, badge, children, className = '' }) {
  return <section className={`dashboard-panel ${className}`.trim()}><header className="dashboard-panel-head"><div><h2>{title}</h2>{subtitle ? <p>{subtitle}</p> : null}</div>{badge ? <span className="dashboard-period">{badge}</span> : null}</header><div className="dashboard-panel-body">{children}</div></section>
}

export function DashboardPage() {
  const { data, loading, error, reload } = useApiResource('/dashboard')

  if (loading) return <div className="table-state"><span className="app-loader-spinner" /><span>Cargando indicadores…</span></div>
  if (error) return <div className="table-state table-state--error"><strong>No se pudo cargar el dashboard</strong><p>{error.message}</p><button className="erp-btn erp-btn-outline" type="button" onClick={reload}>Reintentar</button></div>

  const summary = data.summary ?? {}
  const topProducts = data.top_products ?? []
  const publishers = data.publisher_volume ?? []
  const lowStock = data.low_stock_products ?? []
  const purchases = data.recent_purchases ?? []
  const returns = data.pending_returns ?? { total: 0, items: [] }
  const period = data.period?.label ?? 'Últimos 30 días'
  const topBook = topProducts[0]
  const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { intersect: false } },
    scales: { x: { grid: { display: false }, ticks: { color: '#817d95', maxRotation: 0 } }, y: { beginAtZero: true, grid: { color: 'rgba(127,123,145,.12)' }, ticks: { color: '#817d95' } } },
  }

  return <>
    <div className="page-head dashboard-heading"><div><h1 className="page-title">Resumen general</h1><p className="page-subtitle">Indicadores comerciales y operativos de la librería</p></div><button type="button" className="erp-btn erp-btn-outline erp-btn--icon" onClick={reload} title="Actualizar"><Icon name="refresh" size={18} /></button></div>

    <div className="dashboard-metrics">
      <MetricCard icon="sales" label="Ventas del período" value={money(summary.sales_period)} detail={`${number(summary.transactions_period)} operaciones`} />
      <MetricCard icon="receipt" label="Ticket promedio" value={money(summary.transactions_period ? summary.sales_period / summary.transactions_period : 0)} detail={period} tone="blue" />
      <MetricCard icon="book" label="Libro más vendido" value={topBook ? number(topBook.units) : '0'} detail={topBook?.name ?? 'Sin ventas en el período'} tone="green" />
      <MetricCard icon="warning" label="Títulos con poco stock" value={number(summary.low_stock_items)} detail="Requieren reposición" tone="orange" />
      <MetricCard icon="cart" label="Compras recientes" value={number(summary.recent_purchases)} detail={period} tone="blue" />
      <MetricCard icon="back" label="Devoluciones pendientes" value={number(summary.pending_returns)} detail="Clientes y editoriales" tone="orange" />
      <MetricCard icon="book" label="Libros activos" value={number(summary.active_products)} detail="Títulos disponibles en catálogo" tone="green" />
      <MetricCard icon="money" label="Stock valorizado" value={money(summary.stock_value)} detail={`${number(summary.stock_units)} unidades`} />
    </div>

    <div className="dashboard-grid dashboard-grid--charts">
      <Panel title="Evolución de ventas" subtitle="Facturación diaria del período" badge={period}><div className="dashboard-chart"><Line options={{ ...chartOptions, interaction: { mode: 'index', intersect: false } }} data={{ labels: data.sales?.labels ?? [], datasets: [{ label: 'Ventas', data: data.sales?.totals ?? [], borderColor: purple, backgroundColor: 'rgba(139,92,246,.13)', pointBackgroundColor: purple, pointRadius: 2.5, borderWidth: 2.5, tension: 0.35, fill: true }] }} /></div></Panel>

      <Panel title="Libros más vendidos" subtitle="Unidades vendidas por título" badge="TOP 7"><div className="dashboard-chart"><Bar options={{ ...chartOptions, indexAxis: 'y', plugins: { ...chartOptions.plugins, tooltip: { callbacks: { label: (context) => `${number(context.raw)} unidades` } } } }} data={{ labels: topProducts.map((item) => item.name), datasets: [{ data: topProducts.map((item) => item.units), backgroundColor: topProducts.map((_, index) => palette[index % palette.length]), borderRadius: 6, barThickness: 15 }] }} /></div></Panel>

      <Panel title="Volumen por editorial" subtitle="Participación sobre las ventas" badge={period}><div className="dashboard-doughnut-layout"><div className="dashboard-doughnut"><Doughnut options={{ responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }} data={{ labels: publishers.map((item) => item.name), datasets: [{ data: publishers.map((item) => item.amount), backgroundColor: palette, borderWidth: 0 }] }} /></div><div className="dashboard-legend">{publishers.length ? publishers.map((item, index) => <div key={`${item.id}-${item.name}`}><span style={{ background: palette[index % palette.length] }} /><p><strong>{item.name}</strong><small>{money(item.amount)} · {number(item.units)} u.</small></p></div>) : <p className="dashboard-empty">Sin ventas por editorial</p>}</div></div></Panel>

      <Panel title="Estado operativo" subtitle="Alertas que requieren atención" badge="ACTUAL"><div className="dashboard-status-breakdown"><div><strong>{number(lowStock.length)}</strong><span>Títulos con poco stock</span></div><div><strong>{number(returns.customer_count)}</strong><span>Devoluciones de clientes</span></div><div><strong>{number(returns.supplier_count)}</strong><span>Devoluciones a editoriales</span></div><div><strong>{number(purchases.length)}</strong><span>Últimas compras listadas</span></div></div></Panel>
    </div>

    <div className="dashboard-grid dashboard-grid--lists">
      <Panel title="Títulos con poco stock" subtitle="Existencias iguales o inferiores al mínimo"><div className="dashboard-list">{lowStock.length ? lowStock.map((item) => <Link to={`/libros/${item.id}`} key={item.id}><div><strong>{item.name}</strong><small>{item.code ?? 'Sin código'} · mínimo {number(item.minimum)}</small></div><Badge tone={item.stock <= 0 ? 'danger' : 'warning'}>{number(item.stock)} u.</Badge></Link>) : <p className="dashboard-empty">No hay títulos debajo del mínimo.</p>}</div></Panel>

      <Panel title="Compras recientes" subtitle="Últimos comprobantes de proveedores"><div className="dashboard-list">{purchases.length ? purchases.map((item) => <Link to={`/compras/${item.id}`} key={item.id}><div><strong>{item.provider}</strong><small>{item.number} · {date(item.date)}</small></div><span className="dashboard-list-value">{money(item.total)}</span></Link>) : <p className="dashboard-empty">No hay compras registradas.</p>}</div></Panel>

      <Panel title="Devoluciones pendientes" subtitle="Operaciones pendientes de resolución"><div className="dashboard-list">{returns.items?.length ? returns.items.map((item) => <Link to={item.type === 'customer' ? `/devoluciones-cliente/${item.id}` : `/devoluciones-proveedor/${item.id}`} key={`${item.type}-${item.id}`}><div><strong>{item.party}</strong><small>{item.reason || 'Sin motivo'} · {date(item.date)}</small></div><Badge tone="warning">Pendiente</Badge></Link>) : <p className="dashboard-empty">No hay devoluciones pendientes.</p>}</div></Panel>
    </div>
  </>
}
