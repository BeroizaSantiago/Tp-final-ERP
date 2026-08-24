{{-- Vista: Stock. Presenta existencias valorizadas en un árbol Producto > Color > Talle. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
  <h4 class="mb-1">Stock</h4><small class="text-muted">Existencias actuales valorizadas por sucursal, depósito y variante</small>
</div>
<div class="card mb-4">
  <div class="card-body">
    <form id="filters" class="row g-3 align-items-end stock-tree-filters">
      <div class="col-md-3"><label class="form-label">Producto</label><input id="product" name="product" class="form-control" list="productOptions" minlength="3" placeholder="Nombre o código (mín. 3 caracteres)"><datalist id="productOptions"></datalist></div>
      <div id="sizeSelectWrap" class="col-md-2"><label class="form-label">Talle</label><select id="sizeId" name="size_id" class="form-select">
          <option value="">Todos</option>
        </select></div>
      <div id="sizeTextWrap" class="col-md-2 d-none"><label class="form-label">Talle libre</label><input id="sizeText" name="size_text" class="form-control" placeholder="Escribir talle..."></div>
      <div class="col-md-2">
        <div class="form-check mb-2"><input id="freeSize" class="form-check-input" type="checkbox"><label class="form-check-label" for="freeSize">Buscar talle por texto</label></div>
      </div>
      @foreach([['colorId','color_id','Color'],['warehouse','warehouse','Depósito'],['currency','currency','Moneda'],['brandId','brand_id','Marca'],['modelId','model_id','Modelo'],['categoryId','category_id','Categoría']] as [$id,$name,$label])
      <div class="col-md-2"><label class="form-label">{{ $label }}</label><select id="{{ $id }}" name="{{ $name }}" class="form-select">
          <option value="">Todos</option>
        </select></div>
      @endforeach
      <div class="col-md-3"><label class="form-label">Proveedor</label><input id="provider" name="provider" class="form-control" list="providerOptions" placeholder="Buscar proveedor..."><datalist id="providerOptions"></datalist></div>
      <div class="col-md-2"><label class="form-label">Activos</label><select name="active" class="form-select">
          <option value="active">Activos</option>
          <option value="inactive">Inactivos</option>
          <option value="all">Todos</option>
        </select></div>
      <div class="col-md-2"><label class="form-label">Último movimiento desde</label><input name="last_movement_from" type="date" class="form-control"></div>
      <div class="col-md-3"><label class="form-label">Código de referencia</label><input name="reference_code" class="form-control" placeholder="Código de referencia..."></div>
      <div class="col-12">
        <div class="d-flex flex-wrap gap-4 pt-1">
          @foreach([['physicalWarehouses','Ver depósitos físicos'],['variantsWithStock','Ver variantes con stock'],['originCurrency','Ver en moneda origen'],['byDispatch','Ver por despacho'],['showThresholds','Ver stock mínimo y reposición']] as [$id,$label])
          <div class="form-check"><input id="{{ $id }}" class="form-check-input report-option" type="checkbox"><label class="form-check-label" for="{{ $id }}">{{ $label }}</label></div>
          @endforeach
        </div>
      </div>
      <div class="col-12 d-flex justify-content-end gap-2"><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button id="exportExcel" type="button" class="btn btn-success"><i class="ri-file-excel-2-line me-1"></i>Excel</button><button type="submit" class="btn btn-primary">Buscar</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Stock valorizado</h5><small id="resultInfo" class="text-muted">Aplicá los filtros o presioná Buscar para consultar</small>
    </div>
    <div class="d-flex gap-2"><input id="quickSearch" class="form-control form-control-sm" style="width:240px" placeholder="Buscar en esta página..." disabled><select id="perPage" class="form-select form-select-sm" style="width:95px" disabled>
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select></div>
  </div>
  <div class="stock-tree-scroll">
    <table class="table table-hover table-bordered align-middle mb-0 stock-tree-table">
      <thead id="treeHead"></thead>
      <tbody id="treeBody">
        <tr>
          <td class="text-center text-muted py-5">La tabla se mostrará después de ejecutar la búsqueda.</td>
        </tr>
      </tbody>
    </table>
  </div>
  @include('components.api-pagination')
</div>

<style>
  .stock-tree-filters .form-control,
  .stock-tree-filters .form-select {
    height: 38px;
    min-height: 38px;
    padding-top: .3rem;
    padding-bottom: .3rem
  }

  .stock-tree-scroll {
    max-height: 68vh;
    overflow: auto;
    position: relative
  }

  .stock-tree-table {
    min-width: 1100px;
    border-collapse: separate;
    border-spacing: 0
  }

  .stock-tree-table thead th {
    position: sticky;
    background: var(--bs-tertiary-bg) !important;
    background-color: var(--bs-tertiary-bg) !important;
    white-space: nowrap;
    text-align: center;
    z-index: 5;
    box-shadow: inset 0 -1px 0 var(--bs-border-color)
  }

  .stock-tree-table thead tr:first-child th {
    top: 0;
    height: 43px
  }

  .stock-tree-table thead tr:nth-child(2) th {
    top: 43px;
    height: 43px
  }

  .stock-tree-table .name-cell {
    position: sticky;
    left: 0;
    z-index: 3;
    background: var(--bs-body-bg) !important;
    min-width: 280px;
    text-align: left
  }

  .stock-tree-table thead .name-cell {
    z-index: 8;
    background: var(--bs-tertiary-bg) !important
  }

  .tree-toggle {
    width: 27px;
    height: 27px;
    padding: 0;
    margin-right: .4rem
  }

  .tree-color .name-cell {
    padding-left: 2rem
  }

  .tree-size .name-cell {
    padding-left: 4rem
  }

  .tree-color td {
    background: rgba(140, 87, 255, .045)
  }epuraci

  .tree-size td {
    background: var(--bs-tertiary-bg)
  }

  .stock-meta {
    font-size: .75rem;
    color: var(--bs-secondary-color);
    margin-left: 2.2rem
  }

  .stock-number {
    text-align: right;
    white-space: nowrap
  }

  .grand-total {
    font-weight: 700;
    background: var(--bs-tertiary-bg) !important
  }
</style>
<script>
  const endpoint = `${window.APP_BASE_URL}/api/reports/stock/tree`,
    form = document.getElementById('filters'),
    head = document.getElementById('treeHead'),
    body = document.getElementById('treeBody');
  let report = {
      columns: [],
      rows: [],
      pagination: {}
    },
    page = 1,
    sort = {
      key: 'name',
      direction: 1
    };
  const expanded = new Set();
  const esc = v => String(v ?? '').replace(/[&<>'"]/g, c => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;'
  } [c]));
  const num = v => new Intl.NumberFormat('es-AR', {
    maximumFractionDigits: 4
  }).format(Number(v) || 0);
  const money = v => new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(v) || 0);

  function params() {
    const p = new URLSearchParams(new FormData(form));
    ['physical_warehouses', 'variants_with_stock', 'origin_currency', 'by_dispatch', 'show_thresholds'].forEach((k, i) => p.set(k, [physicalWarehouses, variantsWithStock, originCurrency, byDispatch, showThresholds][i].checked ? '1' : '0'));
    p.set('page', page);
    p.set('per_page', perPage.value);
    [...p.keys()].forEach(k => {
      if (p.get(k) === '') p.delete(k)
    });
    return p
  }

  function valid() {
    if (product.value && product.value.trim().length < 3) {
      erpAlert('Ingresá al menos 3 caracteres para buscar un producto.');
      return false
    }
    return true
  }
  async function load() {
    if (!valid()) return;
    body.innerHTML = '<tr><td class="text-center py-5">Cargando...</td></tr>';
    try {
      const response = await fetch(`${endpoint}?${params()}`, {
        headers: {
          Accept: 'application/json'
        }
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Error');
      report = data;
      quickSearch.disabled = false;
      perPage.disabled = false;
      render();
      resultInfo.textContent = `${data.pagination.total} producto(s) · ${data.context.origin_currency?'Moneda de origen':'Moneda principal '+data.context.main_currency}`
    } catch (error) {
      body.innerHTML = '<tr><td class="text-center text-danger py-5">No se pudieron cargar las existencias</td></tr>';
      erpAlert(error.message || 'No se pudo cargar el stock.')
    }
  }

  function headers() {
    const top = report.columns.map(c => `<th colspan="2">${esc(c.label)}</th>`).join('');
    const sub = report.columns.map(c => `<th role="button" data-sort-cell="${c.key}" data-metric="units">Unidades ↕</th><th role="button" data-sort-cell="${c.key}" data-metric="valued">Valorizado ↕</th>`).join('');
    head.innerHTML = `<tr><th class="name-cell" rowspan="2" role="button" data-sort="name">Producto ↕</th>${top}<th class="grand-total" colspan="2">Gran Total</th></tr><tr>${sub}<th class="grand-total" role="button" data-sort-cell="grand_total" data-metric="units">Unidades ↕</th><th class="grand-total" role="button" data-sort-cell="grand_total" data-metric="valued">Valorizado ↕</th></tr>`;
    head.querySelector('[data-sort="name"]').onclick = () => {
      sort = {
        key: 'name',
        direction: sort.key === 'name' ? -sort.direction : 1
      };
      renderBody()
    };
    head.querySelectorAll('[data-sort-cell]').forEach(th => th.onclick = () => {
      const key = `cell:${th.dataset.sortCell}:${th.dataset.metric}`;
      sort = {
        key,
        direction: sort.key === key ? -sort.direction : 1
      };
      renderBody()
    })
  }

  function cells(node) {
    return report.columns.map(c => {
      const x = node.cells[c.key] || {};
      return `<td class="stock-number">${num(x.units)}</td><td class="stock-number">${esc(x.currency||'')} ${money(x.valued)}</td>`
    }).join('') + `<td class="stock-number grand-total">${num(node.cells.grand_total?.units)}</td><td class="stock-number grand-total">${esc(node.cells.grand_total?.currency||'')} ${money(node.cells.grand_total?.valued)}</td>`
  }

  function toggle(level, key) {
    const id = `${level}:${key}`;
    expanded.has(id) ? expanded.delete(id) : expanded.add(id);
    renderBody()
  }

  function renderBody() {
    const search = quickSearch.value.trim().toLowerCase();
    let rows = report.rows.filter(r => !search || JSON.stringify(r).toLowerCase().includes(search));
    const value = row => {
      if (!sort.key.startsWith('cell:')) return row[sort.key] ?? '';
      const [, column, metric] = sort.key.split(':');
      return Number(row.cells[column]?.[metric] || 0)
    };
    rows.sort((a, b) => {
      const x = value(a),
        y = value(b);
      return (typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es')) * sort.direction
    });
    let html = '';
    rows.forEach(product => {
      const pid = `product:${product.key}`,
        hasChildren = Boolean(report.context?.show_variants) && product.colors.length > 0;
      html += `<tr class="tree-product"><td class="name-cell">${hasChildren?`<button class="btn btn-sm btn-outline-primary tree-toggle" data-level="product" data-key="${esc(product.key)}">${expanded.has(pid)?'−':'+'}</button>`:''}<strong>${esc(product.code)} · ${esc(product.name)}</strong>${report.context?.show_thresholds?`<div class="stock-meta">Stock mín.: ${num(product.min_stock)} · Reposición: ${num(product.reposition_stock)}</div>`:''}</td>${cells(product)}</tr>`;
      if (hasChildren && expanded.has(pid)) product.colors.forEach(color => {
        const cid = `color:${product.key}|${color.key}`;
        html += `<tr class="tree-color"><td class="name-cell"><button class="btn btn-sm btn-outline-primary tree-toggle" data-level="color" data-key="${esc(product.key+'|'+color.key)}">${expanded.has(cid)?'−':'+'}</button>Color: ${esc(color.label)}</td>${cells(color)}</tr>`;
        if (expanded.has(cid)) color.sizes.forEach(size => html += `<tr class="tree-size"><td class="name-cell">Talle: ${esc(size.label)}</td>${cells(size)}</tr>`)
      })
    });
    body.innerHTML = html || `<tr><td colspan="${3+report.columns.length*2}" class="text-center text-muted py-5">No hay existencias para los filtros aplicados</td></tr>`;
    body.querySelectorAll('.tree-toggle').forEach(b => b.onclick = () => toggle(b.dataset.level, b.dataset.key))
  }

  function render() {
    headers();
    renderBody();
    renderApiPagination(report.pagination, target => {
      page = target;
      load()
    })
  }
  form.onsubmit = e => {
    e.preventDefault();
    page = 1;
    load()
  };
  clear.onclick = () => {
    form.reset();
    page = 1;
    expanded.clear();
    report = {
      columns: [],
      rows: [],
      pagination: {}
    };
    freeSizeChange();
    head.innerHTML = '';
    body.innerHTML = '<tr><td class="text-center text-muted py-5">La tabla se mostrará después de ejecutar la búsqueda.</td></tr>';
    resultInfo.textContent = 'Aplicá los filtros o presioná Buscar para consultar';
    quickSearch.value = '';
    quickSearch.disabled = true;
    perPage.disabled = true;
    paginationInfo.textContent = '';
    pagination.innerHTML = ''
  };
  quickSearch.oninput = renderBody;
  perPage.onchange = () => {
    page = 1;
    load()
  };
  freeSize.onchange = freeSizeChange;

  function freeSizeChange() {
    sizeSelectWrap.classList.toggle('d-none', freeSize.checked);
    sizeTextWrap.classList.toggle('d-none', !freeSize.checked);
    if (freeSize.checked) sizeId.value = '';
    else sizeText.value = ''
  }
  exportExcel.onclick = () => {
    if (!valid()) return;
    const p = params();
    p.set('format', 'xlsx');
    p.delete('page');
    location.href = `${endpoint}/export?${p}`
  };

  function addOptions(id, values) {
    const select = document.getElementById(id);
    values.forEach(v => select.add(new Option(typeof v === 'object' ? v.name : v, typeof v === 'object' ? v.id : v)))
  }
  fetch(`${endpoint}/options`, {
    headers: {
      Accept: 'application/json'
    }
  }).then(r => {
    if (!r.ok) throw new Error();
    return r.json()
  }).then(data => {
    addOptions('sizeId', data.sizes);
    addOptions('colorId', data.colors);
    addOptions('warehouse', data.warehouses);
    addOptions('currency', data.currencies);
    addOptions('brandId', data.brands);
    addOptions('modelId', data.models);
    addOptions('categoryId', data.categories);
    data.products.forEach(v => {
      const option = new Option(v.name, v.name);
      option.label = `${v.code||'Sin código'} · ${v.name}`;
      productOptions.append(option)
    });
    data.providers.forEach(v => providerOptions.append(new Option(v, v)))
  }).catch(() => erpAlert('No se pudieron cargar los filtros de stock.'));
</script>
@endsection
