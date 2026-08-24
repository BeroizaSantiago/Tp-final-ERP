<div class="modal fade" id="globalPriceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Consulta rápida de precios</h5>
                    <small class="text-muted">Buscar por código, código de barras, referencia o nombre</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input id="globalPriceQuery" class="form-control form-control-lg mb-3" placeholder="Escribí código, código de barras o nombre..." autocomplete="off">
                <div id="globalPriceResults" class="text-center text-muted py-4">Ingresá al menos 3 caracteres para buscar productos.</div>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const query = document.getElementById('globalPriceQuery');
    const results = document.getElementById('globalPriceResults');
    let timer = null;
    let controller = null;
    const money = value => Number(value ?? 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
    const esc = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const initial = () => {
        results.className = 'text-center text-muted py-4';
        results.innerHTML = 'Ingresá al menos 3 caracteres para buscar productos.';
    };
    function render(items) {
        results.className = '';
        if (!items.length) {
            results.innerHTML = '<div class="alert alert-warning mb-0">No se encontraron productos.</div>';
            return;
        }
        results.innerHTML = `<div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Producto</th><th>Código</th><th>Cód. barras</th><th class="text-end">Precio A</th><th class="text-end">Stock</th><th>Variantes</th></tr></thead>
            <tbody>${items.slice(0, 20).map(product => `<tr>
                <td><strong>${esc(product.name || '-')}</strong><br><small class="text-muted">${esc([product.brand?.name, product.model?.name].filter(Boolean).join(' '))}</small></td>
                <td>${esc(product.code || '-')}</td><td>${esc(product.bar_code || '-')}</td>
                <td class="text-end"><strong>${money(product.price_a_with_tax)}</strong></td><td class="text-end">${esc(product.current_stock ?? 0)}</td>
                <td>${(product.variants ?? []).length ? product.variants.slice(0, 12).map(variant =>
                    `<span class="badge bg-label-secondary me-1 mb-1">${esc(variant.size?.name || 'Sin talle')} / ${esc(variant.color?.name || 'Sin color')} · Stock ${esc(variant.current_stock ?? 0)}</span>`
                ).join('') : '<span class="text-muted">Sin variantes</span>'}</td>
            </tr>`).join('')}</tbody>
        </table></div>`;
    }
    async function search() {
        const term = query.value.trim();
        if (term.length < 3) return initial();
        controller?.abort();
        controller = new AbortController();
        results.className = 'text-center text-muted py-4';
        results.innerHTML = 'Buscando...';
        try {
            const url = new URL(`${window.APP_BASE_URL}/api/products`);
            url.searchParams.set('lookup', '1'); url.searchParams.set('per_page', '20'); url.searchParams.set('search', term);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo consultar precios.');
            render(data.data ?? data);
        } catch (error) {
            if (error.name !== 'AbortError') {
                results.className = '';
                results.innerHTML = `<div class="alert alert-danger mb-0">${esc(error.message)}</div>`;
            }
        }
    }
    window.openGlobalPriceLookup = () => {
        query.value = ''; initial();
        const modalElement = document.getElementById('globalPriceModal');
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        modalElement.addEventListener('shown.bs.modal', () => query.focus(), { once: true });
    };
    query.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(search, 300); });
    query.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); search(); }
    });
})();
</script>
