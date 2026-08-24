<div class="mt-3">
    <label class="form-label" for="product_image_url">Imagen mediante URL</label>
    <div class="input-group">
        <span class="input-group-text">URL</span>
        <input
            type="url"
            class="form-control"
            id="product_image_url"
            maxlength="255"
            placeholder="https://sitio.com/imagen.jpg">
        <button class="btn btn-outline-secondary" id="pasteProductImageUrl" type="button" title="Pegar URL copiada">
            Pegar
        </button>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-2">
        <button class="btn btn-primary" id="addProductImageUrl" type="button">
            <i class="ri-add-line me-1"></i> Agregar
        </button>
    </div>
    <small class="text-muted">Podés subir archivos o utilizar una imagen externa. La URL debe comenzar con http:// o https://.</small>
    <div id="productImageUrlPreview" class="mt-2 d-none">
        <img class="rounded border" alt="Vista previa de imagen externa" style="width:100%;height:150px;object-fit:contain">
    </div>
    <div id="productImageUrlList" class="row g-2 mt-1"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('product_image_url');
    const preview = document.getElementById('productImageUrlPreview');
    const image = preview?.querySelector('img');
    const list = document.getElementById('productImageUrlList');
    if (!input || !preview || !image) return;

    const refreshPreview = () => {
        const url = input.value.trim();
        preview.classList.toggle('d-none', !url);
        image.src = url;
    };
    input.addEventListener('input', refreshPreview);
    input.closest('form')?.addEventListener('submit', () => {
        const url = input.value.trim();
        const existing = [...document.querySelectorAll('input[name="image_urls[]"]')].map(item => item.value);
        if (/^https?:\/\//i.test(url) && !existing.includes(url)) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'image_urls[]';
            hidden.value = url;
            input.closest('form').appendChild(hidden);
        }
    }, {capture: true});
    document.getElementById('addProductImageUrl')?.addEventListener('click', async () => {
        const url = input.value.trim();
        if (!/^https?:\/\//i.test(url)) {
            await Swal.fire({icon: 'warning', title: 'URL inválida', text: 'Ingresá una URL que comience con http:// o https://.'});
            return;
        }
        const existing = [...document.querySelectorAll('input[name="image_urls[]"]')].map(item => item.value);
        if (existing.includes(url)) {
            await Swal.fire({icon: 'info', title: 'Imagen repetida', text: 'Esa URL ya fue agregada a la galería.'});
            return;
        }
        const item = document.createElement('div');
        item.className = 'col-6';
        const safeUrl = url.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        item.innerHTML = `<div class="position-relative border rounded overflow-hidden" style="height:120px">
            <input type="hidden" name="image_urls[]" value="${safeUrl}">
            <img src="${safeUrl}" class="w-100 h-100" style="object-fit:contain" alt="Imagen por URL">
            <button class="btn btn-sm btn-danger position-absolute bottom-0 end-0 m-1" type="button" title="Quitar imagen">Quitar</button>
        </div>`;
        item.querySelector('button').addEventListener('click', () => item.remove());
        list.appendChild(item);
        input.value = '';
        refreshPreview();
    });
    document.getElementById('pasteProductImageUrl')?.addEventListener('click', async () => {
        try {
            input.value = (await navigator.clipboard.readText()).trim();
            refreshPreview();
            input.focus();
        } catch (error) {
            await Swal.fire({
                icon: 'info',
                title: 'Pegá la URL',
                text: 'El navegador no permitió leer el portapapeles. Hacé clic en el campo y presioná Ctrl + V.'
            });
        }
    });
});
</script>
