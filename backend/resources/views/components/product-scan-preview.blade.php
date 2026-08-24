<div class="product-scan-backdrop" data-product-scan-preview aria-hidden="true">
    <section class="product-scan-card" role="status" aria-live="polite">
        <button type="button" class="product-scan-close" data-product-scan-close aria-label="Cerrar">
            <i class="ri-close-line"></i>
        </button>
        <div class="product-scan-kicker"><i class="ri-barcode-line"></i> Producto escaneado</div>
        <h2 class="product-scan-name" data-product-scan-name></h2>
        <div class="product-scan-content">
            <div class="product-scan-image-wrap">
                <img data-product-scan-image alt="" class="product-scan-image">
                <div class="product-scan-image-empty" data-product-scan-image-empty>
                    <i class="ri-image-line"></i><span>Sin imagen</span>
                </div>
            </div>
            <div class="product-scan-info">
                <dl data-product-scan-details></dl>
                <div class="product-scan-price-label">Precio final</div>
                <div class="product-scan-price" data-product-scan-price></div>
            </div>
        </div>
        <div class="product-scan-timer"><span data-product-scan-timer></span></div>
    </section>
</div>

<style>
    .product-scan-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1095;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(18, 18, 32, .58);
        backdrop-filter: blur(5px);
        opacity: 0;
        visibility: hidden;
        transition: opacity .18s ease, visibility .18s ease
    }

    .product-scan-backdrop.is-visible {
        opacity: 1;
        visibility: visible
    }

    .product-scan-card {
        position: relative;
        width: min(1080px, 100%);
        padding: 2rem;
        border: 1px solid color-mix(in srgb, var(--bs-primary) 22%, var(--bs-border-color));
        border-radius: 1.25rem;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, .3);
        transform: translateY(12px) scale(.98);
        transition: transform .2s ease;
        overflow: hidden
    }

    .product-scan-backdrop.is-visible .product-scan-card {
        transform: none
    }

    .product-scan-kicker {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .45rem;
        color: var(--bs-primary);
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase
    }

    .product-scan-name {
        margin: 0 2.5rem 1.25rem 0;
        font-size: clamp(1.45rem, 3vw, 2.2rem);
        line-height: 1.15;
        color: var(--bs-heading-color)
    }

    .product-scan-content {
        display: grid;
        grid-template-columns: minmax(380px, 52%) minmax(0, 1fr);
        gap: 2rem;
        align-items: stretch
    }

    .product-scan-info {
        display: flex;
        min-width: 0;
        flex-direction: column
    }

    .product-scan-info dl {
        display: grid;
        grid-template-columns: max-content minmax(0, 1fr);
        gap: .7rem 1rem;
        margin: 0 0 1.25rem
    }

    .product-scan-info dt {
        color: var(--bs-secondary-color);
        font-weight: 500
    }

    .product-scan-info dd {
        margin: 0;
        color: var(--bs-heading-color);
        font-weight: 650;
        overflow-wrap: anywhere
    }

    .product-scan-price-label {
        margin-top: auto;
        color: var(--bs-secondary-color);
        font-size: .85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em
    }

    .product-scan-price {
        font-size: clamp(2.25rem, 6vw, 4rem);
        font-weight: 800;
        line-height: 1;
        color: var(--bs-primary);
        margin-top: .35rem
    }

    .product-scan-image-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 430px;
        border-radius: 1rem;
        background: color-mix(in srgb, var(--bs-primary) 6%, var(--bs-body-bg));
        border: 1px solid var(--bs-border-color);
        overflow: hidden
    }

    .product-scan-image {
        display: none;
        width: 100%;
        height: 100%;
        max-height: 500px;
        object-fit: contain;
        background: #fff
    }

    .product-scan-image.is-visible {
        display: block
    }

    .product-scan-image-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .5rem;
        color: var(--bs-secondary-color)
    }

    .product-scan-image-empty i {
        font-size: 3rem
    }

    .product-scan-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: grid;
        place-items: center;
        width: 2.25rem;
        height: 2.25rem;
        border: 0;
        border-radius: 50%;
        background: color-mix(in srgb, var(--bs-secondary-color) 12%, transparent);
        color: var(--bs-body-color);
        font-size: 1.25rem
    }

    .product-scan-timer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 4px;
        background: color-mix(in srgb, var(--bs-primary) 15%, transparent)
    }

    .product-scan-timer span {
        display: block;
        width: 100%;
        height: 100%;
        background: var(--bs-primary);
        transform-origin: left
    }

    .product-scan-backdrop.is-visible .product-scan-timer span {
        animation: productScanTimer 7s linear forwards
    }

    @keyframes productScanTimer {
        to {
            transform: scaleX(0)
        }
    }

    @media(max-width:850px) {
        .product-scan-card {
            padding: 1.15rem
        }

        .product-scan-content {
            grid-template-columns: 1fr
        }

        .product-scan-image-wrap {
            order: -1;
            min-height: 190px;
            max-height: 220px
        }

        .product-scan-image {
            max-height: 220px
        }

        .product-scan-price {
            font-size: 2.5rem
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const overlay = document.querySelector('[data-product-scan-preview]');
        if (!overlay || !window.APP_BASE_URL) return;

        const name = overlay.querySelector('[data-product-scan-name]');
        const details = overlay.querySelector('[data-product-scan-details]');
        const price = overlay.querySelector('[data-product-scan-price]');
        const image = overlay.querySelector('[data-product-scan-image]');
        const imageEmpty = overlay.querySelector('[data-product-scan-image-empty]');
        const timerBar = overlay.querySelector('[data-product-scan-timer]');
        let buffer = '';
        let lastKeyAt = 0;
        let closeTimer = null;
        let requestNumber = 0;

        const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;'
        } [character]));
        const close = () => {
            clearTimeout(closeTimer);
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
        };
        const show = product => {
            name.textContent = product.name || 'Producto';
            const availableSizes = Array.isArray(product.sizes) ? product.sizes.join(', ') : '';
            const availableColors = Array.isArray(product.colors) ? product.colors.join(', ') : '';
            const rows = [
                ['Código', product.code],
                ['Código de barras', product.barcode],
                ['Categoría', product.category],
                ['Marca', product.brand],
                ['Modelo', product.model],
                [product.size ? 'Talle escaneado' : 'Talles disponibles', product.size || availableSizes],
                [product.color ? 'Color escaneado' : 'Colores disponibles', product.color || availableColors]
            ].filter(([, value]) => value !== null && value !== undefined && String(value).trim() !== '');
            details.innerHTML = rows.map(([label, value]) => `<dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd>`).join('');
            price.textContent = `${product.currency || '$'} ${new Intl.NumberFormat('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2}).format(Number(product.price) || 0)}`;
            image.classList.toggle('is-visible', Boolean(product.image_url));
            imageEmpty.classList.toggle('d-none', Boolean(product.image_url));
            image.src = product.image_url || '';
            image.alt = product.image_url ? `Imagen de ${product.name || 'producto'}` : '';
            timerBar.style.animation = 'none';
            void timerBar.offsetWidth;
            timerBar.style.animation = '';
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
            clearTimeout(closeTimer);
            closeTimer = setTimeout(close, 7000);
        };
        const lookup = async barcode => {
            const currentRequest = ++requestNumber;
            try {
                const response = await fetch(`${window.APP_BASE_URL}/api/products/scan-preview?barcode=${encodeURIComponent(barcode)}`, {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                if (!response.ok) return;
                const product = await response.json();
                if (currentRequest === requestNumber) show(product);
            } catch (error) {
                console.warn('No se pudo consultar el producto escaneado.', error);
            }
        };

        document.addEventListener('keydown', event => {
            if (event.ctrlKey || event.altKey || event.metaKey) return;
            const activeElement = document.activeElement;
            const isEditing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement?.tagName)
                || activeElement?.isContentEditable;
            if (isEditing) {
                buffer = '';
                lastKeyAt = 0;
                return;
            }
            const now = performance.now();
            if (now - lastKeyAt > 120) buffer = '';

            if (event.key === 'Enter') {
                const code = buffer.trim();
                buffer = '';
                lastKeyAt = 0;
                if (code.length >= 3) lookup(code);
                return;
            }
            if (event.key.length === 1) {
                buffer += event.key;
                lastKeyAt = now;
            }
        }, true);

        overlay.querySelector('[data-product-scan-close]').addEventListener('click', close);
        overlay.addEventListener('click', event => {
            if (event.target === overlay) close();
        });
    });
</script>
