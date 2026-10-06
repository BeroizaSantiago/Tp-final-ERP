import { Link, useNavigate, useParams } from 'react-router-dom'

import { ActiveBadge, StockBadge } from '../../components/Badge'
import { BackLink } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useConfirm } from '../../context/ConfirmContext'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { dateTime, money, number } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { setBookStatus } from './api'

/** Fila de dato para las fichas de detalle. */
function Detail({ label, value, wide }) {
  return (
    <div className={`detail-item${wide ? ' detail-item--wide' : ''}`}>
      <dt className="detail-label">{label}</dt>
      <dd className="detail-value">{value ?? '—'}</dd>
    </div>
  )
}

/** Portada del libro con selector si tiene varias imágenes. */
function CoverGallery({ product }) {
  const images = product.images ?? []
  const main = product.image_full_url ?? images[0]?.full_url ?? null

  if (!main) {
    return (
      <div className="detail-cover detail-cover--empty">
        <Icon name="book" size={40} />
        <span>Sin portada</span>
      </div>
    )
  }

  return (
    <div className="detail-gallery">
      <div className="detail-cover">
        <img src={main} alt="" />
      </div>

      {images.length > 1 ? (
        <div className="detail-thumbs">
          {images.map((image, index) => (
            <a
              key={image.id ?? index}
              href={image.full_url}
              target="_blank"
              rel="noreferrer"
              className="detail-thumb"
              title={`Abrir imagen ${index + 1}`}
            >
              <img src={image.full_url} alt="" loading="lazy" />
            </a>
          ))}
        </div>
      ) : null}
    </div>
  )
}

/**
 * Administración de un libro.
 *
 * Réplica de la vista `products/catalog/show.blade.php`: misma ficha con los
 * datos de edición, precios, stock y variantes, más las acciones de estado.
 */
export function BookDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const confirm = useConfirm()

  const { data: product, loading, error, reload } = useApiResource(`/products/${id}`, {
    deps: [id],
  })

  const handleToggleStatus = async () => {
    const nextActive = !product.is_active

    const confirmed = await confirm({
      title: nextActive ? 'Habilitar libro' : 'Dar de baja el libro',
      message: `¿Confirmás que querés ${nextActive ? 'habilitar' : 'dar de baja'} "${product.name}"?`,
      confirmText: nextActive ? 'Sí, habilitar' : 'Sí, dar de baja',
      tone: nextActive ? 'primary' : 'danger',
    })

    if (!confirmed) {
      return
    }

    try {
      const response = await setBookStatus(product.id, nextActive)
      toast.success(nextActive ? 'Libro habilitado' : 'Libro dado de baja', response.message)
      reload()
    } catch (err) {
      toast.error(
        'No se pudo actualizar',
        err instanceof ApiError ? err.message : 'Ocurrió un error inesperado.',
      )
    }
  }

  if (loading) {
    return (
      <div className="table-state">
        <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
        <span>Cargando libro…</span>
      </div>
    )
  }

  if (error) {
    return (
      <>
        <BackLink to="/libros">Volver a libros</BackLink>
        <div className="erp-alert" role="alert">
          {error.message}
        </div>
      </>
    )
  }

  const relationName = (relation, raw) => relation?.name ?? (raw || '—')
  const inventory = product.inventory_items ?? []
  const variants = product.variants ?? []

  return (
    <>
      <div className="page-head">
        <div>
          <BackLink to="/libros">Volver a libros</BackLink>
          <h1 className="page-title">{product.name}</h1>
          <p className="page-subtitle">
            {relationName(product.author, product.author)} · {relationName(product.category, product.category)}
          </p>
        </div>

        <div className="page-actions">
          <Link className="erp-btn erp-btn-primary" to={`/libros/${product.id}/editar`}>
            <Icon name="edit" size={18} />
            Editar
          </Link>
          <button
            className={`erp-btn ${product.is_active ? 'erp-btn-outline-danger' : 'erp-btn-outline'}`}
            type="button"
            onClick={handleToggleStatus}
          >
            <Icon name={product.is_active ? 'forbid' : 'checkCircle'} size={18} />
            {product.is_active ? 'Dar de baja' : 'Habilitar'}
          </button>
        </div>
      </div>

      <div className="detail-layout">
        <aside className="detail-aside">
          <CoverGallery product={product} />

          <div className="detail-badges">
            <ActiveBadge active={product.is_active} />
            <StockBadge value={product.current_stock} />
          </div>

          <dl className="detail-list">
            <Detail label="Precio final A" value={money(product.price_a_with_tax)} />
            <Detail label="Precio neto A" value={money(product.price_a)} />
            <Detail
              label="Ganancia estimada"
              value={
                product.price_a_with_tax !== null && product.cost_with_discount !== null
                  ? money(Number(product.price_a_with_tax) - Number(product.cost_with_discount))
                  : null
              }
            />
            <Detail label="Stock disponible" value={number(product.available_stock)} />
            <Detail label="Stock mínimo" value={number(product.min_stock)} />
            <Detail label="Último acceso" value={dateTime(product.last_login_at)} />
          </dl>
        </aside>

        <div className="detail-main">
          <section className="erp-card">
            <div className="erp-card-head">
              <h2 className="erp-card-title">Datos de edición</h2>
            </div>

            <dl className="detail-list detail-list--grid">
              <Detail label="ISBN" value={product.bar_code} />
              <Detail label="Código interno" value={product.code} />
              <Detail label="Código de referencia" value={product.reference_code} />
              <Detail label="Alícuota" value={product.aliquot_name} />
              <Detail label="Categoría" value={relationName(product.category, product.category)} />
              <Detail label="Marca" value={relationName(product.brand, product.brand)} />
              {/* Editorial y colección no tienen columna de texto: sólo la relación. */}
              <Detail label="Editorial" value={relationName(product.publisher)} />
              <Detail label="Modelo" value={relationName(product.model, product.model)} />
              <Detail label="Colección" value={relationName(product.collection)} />
              <Detail label="Unidad de medida" value={product.unit_measure_name} />
              <Detail label="Variantes" value={product.has_variants ? 'Sí' : 'No'} />
              <Detail label="Costo con descuento" value={money(product.cost_with_discount)} />
            </dl>
          </section>

          {product.description ? (
            <section className="erp-card">
              <div className="erp-card-head">
                <h2 className="erp-card-title">Sinopsis</h2>
              </div>
              <p className="detail-text">{product.description}</p>
            </section>
          ) : null}

          {product.notes ? (
            <section className="erp-card">
              <div className="erp-card-head">
                <h2 className="erp-card-title">Notas internas</h2>
              </div>
              <p className="detail-text">{product.notes}</p>
            </section>
          ) : null}

          <section className="erp-card">
            <div className="erp-card-head">
              <h2 className="erp-card-title">Listas de precio</h2>
            </div>

            <div className="table-scroll">
              <table className="erp-table">
                <thead>
                  <tr>
                    <th>Lista</th>
                    <th className="is-end">Neto</th>
                    <th className="is-end">Con impuesto</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    ['A', product.price_a, product.price_a_with_tax],
                    ['B', product.price_b, product.price_b_with_tax],
                    ['C', product.price_c, product.price_c_with_tax],
                    ['D', product.price_d, product.price_d_with_tax],
                  ].map(([label, net, gross]) => (
                    <tr key={label}>
                      <td>Precio {label}</td>
                      <td className="is-end">{net === null ? '—' : money(net)}</td>
                      <td className="is-end">{gross === null ? '—' : money(gross)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          {variants.length ? (
            <section className="erp-card">
              <div className="erp-card-head">
                <h2 className="erp-card-title">Variantes</h2>
                <span className="erp-card-meta">{variants.length} variantes</span>
              </div>

              <div className="table-scroll">
                <table className="erp-table">
                  <thead>
                    <tr>
                      <th>SKU</th>
                      <th>Código de barras</th>
                      <th className="is-center">Stock</th>
                    </tr>
                  </thead>
                  <tbody>
                    {variants.map((variant) => (
                      <tr key={variant.id}>
                        <td>{variant.sku || '—'}</td>
                        <td>{variant.bar_code || '—'}</td>
                        <td className="is-center">{number(variant.current_stock)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          ) : null}

          <section className="erp-card">
            <div className="erp-card-head">
              <h2 className="erp-card-title">Depósitos</h2>
            </div>

            {inventory.length ? (
              <dl className="detail-list detail-list--grid">
                {inventory.map((item) => (
                  <Detail
                    key={item.id}
                    label={item.warehouse_name || 'Depósito'}
                    value={`${number(item.current_stock)} unidades`}
                  />
                ))}
              </dl>
            ) : (
              <p className="detail-text detail-text--muted">Este libro todavía no tiene stock en depósitos.</p>
            )}
          </section>

          <div className="detail-footer">
            <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/libros')}>
              Volver al listado
            </button>
          </div>
        </div>
      </div>
    </>
  )
}
