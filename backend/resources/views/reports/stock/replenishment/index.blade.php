{{-- Vista: productos que alcanzaron el umbral de reposición. --}}@extends('layouts.app')@section('content')<h4 class="mb-4">Reposición de Stock</h4>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Criterio de reposición</label><select name="criterion" class="form-select">
                    <option value="reposition">Stock de Reposición</option>
                    <option value="minimum">Stock Mínimo</option>
                </select></div>
            <div class="col-md-3"><label class="form-label">Sucursal</label><select id="branch" name="branch" class="form-select" required>
                    <option value="">Seleccione...</option>
                </select></div>
            <div class="col-md-3"><x-multi-select id="warehouses" name="warehouses[]" label="Depósito" /></div>
<div class="col-md-3"><x-multi-select id="products" name="product_ids[]" label="Producto" remote-url="{{ url('/api/products') }}" /></div>
            <div class="col-md-3"><x-multi-select id="providers" name="provider_ids[]" label="Proveedor" /></div>
            <div class="col-md-3"><x-multi-select id="categories" name="category_ids[]" label="Categoría" /></div>
            <div class="col-md-3"><x-multi-select id="brands" name="brand_ids[]" label="Marca" /></div>
            <div class="col-md-3"><x-multi-select id="models" name="model_ids[]" label="Modelo" /></div>
            <div class="col-md-4">
                <div class="form-check form-switch mt-3"><input id="includeVariants" type="checkbox" class="form-check-input"><label class="form-check-label" for="includeVariants">Incluir variantes</label></div>
            </div>
            <div class="col-md-8 d-flex flex-wrap justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button data-export="xlsx" type="button" class="btn btn-success">Excel</button><button data-export="csv" type="button" class="btn btn-outline-success future-action">CSV</button><button id="openPdf" type="button" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Reposición de Stock" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/stock/replenishment`,
        form = filters,
        enhancers = [warehouses, products, providers, categories, brands, models].map(erpEnhanceMultiSelect);

    function p() {
        const q = new URLSearchParams(new FormData(form));
        q.set('include_variants', includeVariants.checked ? '1' : '0');
        return q
    }

    function valid() {
        if (!branch.value) {
            erpAlert('Seleccioná una sucursal.');
            return false
        }
        return true
    }

    function load() {
        if (!valid()) return;
        reportViewer.src = `${endpoint}/pdf?${p()}`;
        reportViewer.classList.remove('d-none');
        reportEmpty.classList.add('d-none')
    }
    form.onsubmit = e => {
        e.preventDefault();
        load()
    };
    clear.onclick = () => {
        form.reset();
        enhancers.forEach(x => x.setAll(false))
    };
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        if (valid()) {
            const q = p();
            q.set('format', b.dataset.export);
            location.href = `${endpoint}/export?${q}`
        }
    });
    openPdf.onclick = () => {
        if (valid()) window.open(`${endpoint}/pdf?${p()}`, '_blank')
    };
    fetch(`${endpoint}/options`).then(r => r.json()).then(d => {
        d.branches.forEach(v => branch.add(new Option(v, v)));
        d.warehouses.forEach(v => warehouses.add(new Option(v, v)));
        [
            ['products', products],
            ['providers', providers],
            ['categories', categories],
            ['brands', brands],
            ['models', models]
        ].forEach(([k, s]) => d[k].forEach(v => s.add(new Option(`${v.code??''}${v.code?' · ':''}${v.name}`, v.id))));
        enhancers.forEach(x => x.refresh())
    });
</script>@endsection
