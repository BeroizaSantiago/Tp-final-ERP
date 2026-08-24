{{-- Vista: Stock según Ventas. Compara existencias actuales con unidades netas vendidas. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
  <h4 class="mb-1">Stock según Ventas</h4><small class="text-muted">Comparación interactiva entre existencias actuales y ventas netas del período</small>
</div>
<div class="card mb-4">
  <div class="card-body">
    <form id="filters" class="row g-3 align-items-end stock-sales-filters">
      <div class="col-md-2"><label class="form-label">Fecha Desde *</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
      <div class="col-md-2"><label class="form-label">Fecha Hasta *</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
      <div class="col-md-2"><label class="form-label">Depósito</label><select id="warehouse" name="warehouse" class="form-select">
          <option value="">Todos</option>
        </select></div>
  <div class="col-md-3"><x-multi-select id="products" name="product_ids[]" label="Producto" remote-url="{{ url('/api/products') }}" /></div>
      <div class="col-md-3"><label class="form-label">Nombre</label><input name="name" class="form-control" placeholder="Buscar por nombre..."></div>
      @foreach([['providers','provider_ids[]','Proveedor'],['categories','category_ids[]','Categoría'],['brands','brand_ids[]','Marca'],['models','model_ids[]','Modelo']] as [$id,$name,$label])
      <div class="col-md-3"><x-multi-select :id="$id" :name="$name" :label="$label" /></div>
      @endforeach
      <div class="col-12 d-flex justify-content-end gap-2"><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button id="exportExcel" type="button" class="btn btn-success"><i class="ri-file-excel-2-line me-1"></i>Excel</button><button type="submit" class="btn btn-primary">Buscar</button></div>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body"><small class="text-muted">Productos</small>
        <h4 id="summaryProducts" class="mb-0">0</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body"><small class="text-muted">Stock actual</small>
        <h4 id="summaryStock" class="mb-0">0</h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body"><small class="text-muted">Unidades vendidas netas</small>
        <h4 id="summarySold" class="mb-0">0</h4>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Resultados</h5><small id="tableContext" class="text-muted">Aplicá los filtros para consultar</small>
    </div><input id="tableSearch" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar en esta página...">
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width:45px"></th>
          <th data-sort="code" role="button">Código ↕</th>
          <th data-sort="product" role="button">Producto ↕</th>
          <th id="stockHeader" data-sort="stock" role="button">Stock actual ↕</th>
          <th id="soldHeader" data-sort="sold" role="button">Ventas netas ↕</th>
        </tr>
        <tr class="column-filters">
          <th></th>
          <th><input data-column="code" class="form-control form-control-sm" placeholder="Código"></th>
          <th><input data-column="product" class="form-control form-control-sm" placeholder="Producto"></th>
          <th><input data-column="stock" type="number" class="form-control form-control-sm" placeholder="Mínimo"></th>
          <th><input data-column="sold" type="number" class="form-control form-control-sm" placeholder="Mínimo"></th>
        </tr>
      </thead>
      <tbody id="results">
        <tr>
          <td colspan="5" class="text-center text-muted py-5">Sin consulta ejecutada</td>
        </tr>
      </tbody>
    </table>
  </div>
  @include('components.api-pagination')
</div>

<style>
  .stock-sales-filters .form-control,
  .stock-sales-filters .form-select {
    min-height: 38px;
    height: 38px;
    padding-top: .35rem;
    padding-bottom: .35rem
  }

  .stock-sales-filters .erp-multiselect-control {
    min-height: 38px;
    padding-top: .25rem;
    padding-bottom: .25rem
  }

  .stock-toggle {
    width: 30px;
    height: 30px;
    padding: 0
  }

  .variant-row td {
    background: var(--bs-tertiary-bg)
  }

  .negative-value {
    color: #dc3545;
    font-weight: 600
  }

  .column-filters input {
    min-width: 90px
  }
</style>
<script>
  const form = document.getElementById('filters'),
    body = document.getElementById('results'),
    multiIds = ['products', 'providers', 'categories', 'brands', 'models'];
  let rows = [],
    sort = {
      key: 'product',
      direction: 1
    },
    page = 1;
  const enhancers = multiIds.map(id => erpEnhanceMultiSelect(document.getElementById(id)));

  function number(v) {
    return new Intl.NumberFormat('es-AR', {
      maximumFractionDigits: 4
    }).format(Number(v) || 0)
  }

  function params() {
    const p = new URLSearchParams(new FormData(form));
    [...p.keys()].forEach(k => {
      if (!p.get(k)) p.delete(k)
    });
    p.set('page', page);
    p.set('per_page', '25');
    return p
  }

  function valid() {
    if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) {
      erpAlert('Revisá el rango de fechas.');
      return false
    }
    return true
  }

  function visibleRows() {
    const text = tableSearch.value.trim().toLowerCase(),
      filters = {};
    document.querySelectorAll('[data-column]').forEach(i => filters[i.dataset.column] = i.value.trim().toLowerCase());
    return rows.filter(r => (!text || JSON.stringify(r).toLowerCase().includes(text)) && (!filters.code || r.code.toLowerCase().includes(filters.code)) && (!filters.product || r.product.toLowerCase().includes(filters.product)) && (!filters.stock || r.stock >= Number(filters.stock)) && (!filters.sold || r.sold >= Number(filters.sold))).sort((a, b) => {
      const x = a[sort.key],
        y = b[sort.key];
      return (typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es')) * sort.direction
    })
  }

  function render() {
    const data = visibleRows();
    if (!data.length) {
      body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5">No hay resultados para los filtros aplicados</td></tr>';
      return
    }
    body.innerHTML = data.map(r => `<tr><td><button class="btn btn-sm btn-outline-primary stock-toggle" data-key="${r.key}">+</button></td><td>${r.code}</td><td><strong>${r.product}</strong><small class="d-block text-muted">${r.variants.length} variante(s)</small></td><td>${number(r.stock)}</td><td class="${r.sold<0?'negative-value':''}">${number(r.sold)}</td></tr><tr class="variant-container d-none" data-variants="${r.key}"><td></td><td colspan="4" class="p-0"><table class="table table-sm mb-0"><thead><tr><th>Variante</th><th>Color</th><th>Talle</th><th>Sucursal / Depósito</th><th>Stock actual</th><th>Ventas netas</th></tr></thead><tbody>${r.variants.map(v=>`<tr class="variant-row"><td>${v.variant}</td><td>${v.color}</td><td>${v.size}</td><td>${v.branch} · ${v.warehouse}</td><td>${number(v.stock)}</td><td class="${v.sold<0?'negative-value':''}">${number(v.sold)}</td></tr>`).join('')}</tbody></table></td></tr>`).join('');
    document.querySelectorAll('.stock-toggle').forEach(b => b.onclick = () => {
      const detail = document.querySelector(`[data-variants="${CSS.escape(b.dataset.key)}"]`);
      detail.classList.toggle('d-none');
      b.textContent = detail.classList.contains('d-none') ? '+' : '−'
    })
  }
  async function load() {
    if (!valid()) return;
    body.innerHTML = '<tr><td colspan="5" class="text-center py-5">Cargando...</td></tr>';
    try {
      const response = await fetch(`${window.APP_BASE_URL}/api/reports/stock/sales-based?${params()}`, {
        headers: {
          Accept: 'application/json'
        }
      });
      if (!response.ok) throw new Error();
      const data = await response.json();
      rows = data.rows;
      summaryProducts.textContent = data.summary.products;
      summaryStock.textContent = number(data.summary.stock);
      summarySold.textContent = number(data.summary.sold);
      tableContext.textContent = `${dateFrom.value} al ${dateTo.value} · ${data.context.warehouse}`;
      stockHeader.textContent = `Stock actual · ${data.context.warehouse} ↕`;
      soldHeader.textContent = `Ventas netas · ${data.context.warehouse} ↕`;
      render();
      renderApiPagination(data.pagination, target => {
        page = target;
        load()
      })
    } catch (e) {
      body.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-5">No se pudo cargar el reporte</td></tr>';
      erpAlert('No se pudo cargar Stock según Ventas.')
    }
  }
  form.onsubmit = e => {
    e.preventDefault();
    page = 1;
    load()
  };
  clear.onclick = () => {
    form.reset();
    enhancers.forEach(x => x.setAll(false));
    rows = [];
    page = 1;
    render();
    paginationInfo.textContent = '';
    pagination.innerHTML = ''
  };
  exportExcel.onclick = () => {
    if (!valid()) return;
    const p = params();
    p.set('format', 'xlsx');
    p.delete('page');
    location.href = `${window.APP_BASE_URL}/api/reports/stock/sales-based/export?${p}`
  };
  tableSearch.oninput = render;
  document.querySelectorAll('[data-column]').forEach(i => i.oninput = render);
  document.querySelectorAll('[data-sort]').forEach(h => h.onclick = () => {
    sort.direction = sort.key === h.dataset.sort ? -sort.direction : 1;
    sort.key = h.dataset.sort;
    render()
  });
  fetch(`${window.APP_BASE_URL}/api/reports/stock/sales-based/options`, {
    headers: {
      Accept: 'application/json'
    }
  }).then(r => {
    if (!r.ok) throw new Error();
    return r.json()
  }).then(data => {
    data.warehouses.forEach(v => warehouse.add(new Option(v, v)));
    multiIds.forEach(id => data[id].forEach(v => document.getElementById(id).add(new Option(`${v.code?`${v.code} · `:''}${v.name}`, v.id))));
    enhancers.forEach(x => x.refresh());
    load()
  }).catch(() => erpAlert('No se pudieron cargar los filtros.'));
</script>
@endsection
