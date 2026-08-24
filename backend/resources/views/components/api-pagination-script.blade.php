{{-- Componente: Lógica de paginación. Conecta los controles de paginación con los endpoints del ERP. --}}
<script>
window.createApiPagination = window.createApiPagination || function (parent) {
    if (!parent) return null;

    const current = parent.querySelector(':scope > [data-api-pagination]');
    if (current) return current;

    const footer = document.createElement('div');
    footer.className = 'card-footer d-flex justify-content-between align-items-center flex-wrap gap-2';
    footer.dataset.apiPagination = '';
    footer.dataset.autoPagination = 'true';
    footer.innerHTML = `
        <small class="text-muted" data-pagination-info></small>
        <div class="d-flex gap-1" data-pagination-controls></div>
    `;
    parent.appendChild(footer);

    return footer;
};

window.renderApiPagination = window.renderApiPagination || function (meta, loadPage, root = null) {
    root = root ?? document.querySelector('[data-api-pagination]');
    const container = root?.querySelector('[data-pagination-controls]');
    const info = root?.querySelector('[data-pagination-info]');

    if (!container || !info) return;

    const current = Number(meta.current_page ?? 1);
    const last = Number(meta.last_page ?? 1);
    const total = Number(meta.total ?? 0);

    info.textContent = total
        ? `Mostrando ${meta.from ?? 0} a ${meta.to ?? 0} de ${total}`
        : 'Sin resultados';
    container.innerHTML = '';

    const addButton = (label, page, disabled = false, active = false) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm ${active ? 'btn-primary' : 'btn-outline-secondary'}`;
        button.innerHTML = label;
        button.disabled = disabled;
        button.addEventListener('click', () => loadPage(page));
        container.appendChild(button);
    };

    addButton('&lsaquo;', current - 1, current <= 1);

    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);
    for (let page = start; page <= end; page++) {
        addButton(String(page), page, false, page === current);
    }

    addButton('&rsaquo;', current + 1, current >= last);
};
</script>
