import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

import { ActiveBadge, StockBadge, Badge } from '../../components/Badge'
import { BackLink, Field, FormSection, Select, Switch, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money, number, toInput, normalizePaginator } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { useMasterOptions } from '../masters/useMasterOptions'
import { listBooks, createBook, updateBook } from './api'
import { DataTable, Pagination } from '../../components/DataTable'

/** Identificadores comerciales propios de una variante. */
const VARIANT_FIELDS = ['sku', 'bar_code']

/** Crea una variante vacía con todos los campos en blanco. */
function emptyVariant() {
  return {
    id: null,
    sku: '',
    bar_code: '',
    price_a_with_tax: '',
    current_stock: '0',
  }
}

/** Verifica si una variante tiene al menos un atributo definido. */
function hasAnyAttribute(variant) {
  return VARIANT_FIELDS.some((field) => variant[field] !== '' && variant[field] !== null)
}

/** Etiqueta de un maestro o el valor de texto plano que guarda el producto. */
function relatedName(relation, raw) {
  return relation?.name ?? (raw || null) ?? '—'
}

/**
 * Administrador de variantes de producto.
 *
 * Los datos bibliográficos pertenecen al libro. Cada variante conserva sus
 * propios SKU, ISBN, precio y stock.
 */
function VariantManager({ variants, onChange }) {
  const toast = useToast()
  const [showForm, setShowForm] = useState(false)
  const [editingIndex, setEditingIndex] = useState(null)
  const [formData, setFormData] = useState(emptyVariant())

  const openCreate = () => {
    setFormData(emptyVariant())
    setEditingIndex(null)
    setShowForm(true)
  }

  const openEdit = (index) => {
    setFormData({ ...variants[index] })
    setEditingIndex(index)
    setShowForm(true)
  }

  const handleFieldChange = (field, value) => {
    setFormData((current) => ({ ...current, [field]: value }))
  }

  const handleSave = () => {
    if (!hasAnyAttribute(formData)) {
      toast.error('Variante incompleta', 'Ingresá un SKU o un ISBN para identificar la variante.')
      return
    }

    // Verificar duplicados
    const isDuplicate = variants.some((v, index) => {
      if (index === editingIndex) return false
      return VARIANT_FIELDS.some((field) => {
        const value = String(formData[field] || '').trim()
        return value !== '' && String(v[field] || '').trim() === value
      })
    })

    if (isDuplicate) {
      toast.error('Variante duplicada', 'Ya existe una variante con el mismo SKU o ISBN.')
      return
    }

    const newVariants = [...variants]
    if (editingIndex !== null) {
      newVariants[editingIndex] = { ...formData }
    } else {
      newVariants.push({ ...formData })
    }

    onChange(newVariants)
    setShowForm(false)
    setFormData(emptyVariant())
    setEditingIndex(null)
    toast.success('Variante guardada', 'La variante fue agregada correctamente.')
  }

  const handleDelete = (index) => {
    const newVariants = variants.filter((_, i) => i !== index)
    onChange(newVariants)
    toast.success('Variante eliminada', 'La variante fue eliminada correctamente.')
  }

  const handleCancel = () => {
    setShowForm(false)
    setFormData(emptyVariant())
    setEditingIndex(null)
  }

  return (
    <div className="form-section">
      <div className="form-section-head">
        <h3 className="form-section-title">Variantes</h3>
        <p className="form-section-text">
          Cada variante tiene su propio SKU o ISBN, precio y stock. Los datos bibliográficos pertenecen al libro.
        </p>
      </div>

      <div className="form-section-body">
        {variants.length > 0 && !showForm ? (
          <div className="table-scroll">
            <table className="erp-table">
              <thead>
                <tr>
                  <th>Identificación</th>
                  <th>SKU</th>
                  <th>Stock</th>
                  <th className="is-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {variants.map((variant, index) => (
                  <tr key={index}>
                    <td>
                      {variant.bar_code || variant.sku || '—'}
                    </td>
                    <td>{variant.sku || '—'}</td>
                    <td>{Number(variant.current_stock) || 0}</td>
                    <td className="is-end">
                      <button
                        type="button"
                        className="erp-btn erp-btn-outline erp-btn-sm"
                        onClick={() => openEdit(index)}
                      >
                        <Icon name="edit" size={14} />
                      </button>
                      <button
                        type="button"
                        className="erp-btn erp-btn-outline-danger erp-btn-sm"
                        onClick={() => handleDelete(index)}
                      >
                        <Icon name="close" size={14} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : null}

        {showForm ? (
          <div className="variant-form">
            <div className="form-grid">
              <TextInput
                name="sku"
                label="SKU"
                value={formData.sku}
                onChange={(e) => handleFieldChange('sku', e.target.value)}
                placeholder="SKU-001"
              />

              <TextInput
                name="bar_code"
                label="Código de barras"
                value={formData.bar_code}
                onChange={(e) => handleFieldChange('bar_code', e.target.value)}
                placeholder="9788497592228"
              />

              <TextInput
                name="price_a_with_tax"
                label="Precio final A"
                type="number"
                step="0.01"
                value={formData.price_a_with_tax}
                onChange={(e) => handleFieldChange('price_a_with_tax', e.target.value)}
              />

              <TextInput
                name="current_stock"
                label="Stock inicial"
                type="number"
                step="1"
                value={formData.current_stock}
                onChange={(e) => handleFieldChange('current_stock', e.target.value)}
              />
            </div>

            <div className="variant-form-actions">
              <button type="button" className="erp-btn erp-btn-primary" onClick={handleSave}>
                <Icon name="check" size={16} />
                {editingIndex !== null ? 'Actualizar variante' : 'Agregar variante'}
              </button>
            </div>
          </div>
        ) : (
          <button type="button" className="erp-btn erp-outline" onClick={openCreate}>
            <Icon name="add" size={16} />
            Agregar variante
          </button>
        )}
      </div>
    </div>
  )
}

/** Listas de precio que maneja el ERP. */
const PRICE_LISTS = [
  { key: 'a', label: 'Precio A', field: 'price_a' },
  { key: 'b', label: 'Precio B', field: 'price_b' },
  { key: 'c', label: 'Precio C', field: 'price_c' },
  { key: 'd', label: 'Precio D', field: 'price_d' },
]

const EMPTY_FORM = {
  name: '',
  bar_code: '',
  code: '',
  reference_code: '',
  web_title: '',
  description: '',
  notes: '',
  author_id: '',
  category_id: '',
  brand_id: '',
  publisher_id: '',
  product_model_id: '',
  collection_id: '',
  category: '',
  brand: '',
  model: '',
  unit_measure_name: 'Unidad',
  product_type_name: '',
  principal_provider_name: '',
  aliquot_name: 'IVA 21%',
  currency_symbol: '$',
  currency_name: 'Pesos',
  has_variants: false,
  auto_calculate_tax: true,
  cost_with_discount: '',
  price_a: '',
  price_b: '',
  price_c: '',
  price_d: '',
  current_stock: '0',
  min_stock: '',
  reposition_stock: '',
  weight: '',
  height: '',
  width: '',
  length: '',
  is_active: true,
}

/** Calcula el precio con impuesto a partir del neto y la alícuota elegida. */
function taxRate(aliquotName) {
  switch (aliquotName) {
    case 'IVA 10,5%':
      return 10.5
    case 'IVA 27%':
      return 27
    case 'Exento':
      return 0
    default:
      return 21
  }
}

function withTax(value, aliquotName) {
  const net = Number(value)

  if (value === '' || value === null || Number.isNaN(net)) {
    return null
  }

  return net * (1 + taxRate(aliquotName) / 100)
}

/** Managed de imágenes: previsualización, altas por archivo y borrado. */
function ImageManager({ images, newFiles, onAddFiles, onRemoveExisting, onRemoveNew, disabled }) {
  const fileInputRef = useRef(null)
  const [previews, setPreviews] = useState([])

  useEffect(() => {
    const urls = Array.from(newFiles).map((file) => URL.createObjectURL(file))
    setPreviews(urls)

    return () => urls.forEach((url) => URL.revokeObjectURL(url))
  }, [newFiles])

  return (
    <div className="form-section">
      <div className="form-section-head">
        <h3 className="form-section-title">Portadas</h3>
        <p className="form-section-text">
          Hasta 6 imágenes. La primera queda como portada del libro. Máximo 2 MB por archivo.
        </p>
      </div>

      <div className="form-section-body">
        <div className="image-manager">
          {images.map((image, index) => (
            <div className="image-thumb" key={image.id ?? image.full_url}>
              <img src={image.full_url} alt="" />
              {index === 0 ? <span className="image-thumb-badge">Portada</span> : null}
              <button
                className="image-thumb-remove"
                type="button"
                onClick={() => onRemoveExisting(image)}
                disabled={disabled}
                aria-label="Quitar imagen"
              >
                <Icon name="close" size={14} />
              </button>
            </div>
          ))}

          {previews.map((url, index) => (
            <div className="image-thumb" key={url}>
              <img src={url} alt="" />
              <span className="image-thumb-badge">Nueva</span>
              <button
                className="image-thumb-remove"
                type="button"
                onClick={() => onRemoveNew(index)}
                disabled={disabled}
                aria-label="Quitar imagen"
              >
                <Icon name="close" size={14} />
              </button>
            </div>
          ))}

          {images.length + newFiles.length < 6 ? (
            <button
              className="image-thumb image-thumb--add"
              type="button"
              onClick={() => fileInputRef.current?.click()}
              disabled={disabled}
            >
              <Icon name="upload" size={20} />
              <span>Agregar</span>
            </button>
          ) : null}
        </div>

        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          multiple
          className="visuallyhidden"
          onChange={(event) => {
            onAddFiles(Array.from(event.target.files ?? []))
            event.target.value = ''
          }}
        />
      </div>
    </div>
  )
}

/**
 * Alta y edición de libros.
 *
 * Misma pantalla para los dos casos: si llega un id carga el producto y completa
 * el formulario, si no arranca vacío. El guardado va a POST /api/products o a
 * PUT /api/products/{id} y devuelve al listado.
 */
export function StockSearch() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const isEditing = Boolean(id)
  const [search, setSearch] = useState('')

  const [values, setValues] = useState(EMPTY_FORM)
  const [images, setImages] = useState([])
  const [newFiles, setNewFiles] = useState([])
  const [removedImageIds, setRemovedImageIds] = useState([])
  const [variants, setVariants] = useState([])
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(isEditing)
  const [saving, setSaving] = useState(false)
  const [searchResults, setSearchResults] = useState(null)
  const [searching, setSearching] = useState(false)
  const [page, setPage] = useState(1)

  const { options: categories } = useMasterOptions('product-categories')
  const { options: brands } = useMasterOptions('brands')
  const { options: publishers } = useMasterOptions('publishers')
  const { options: models } = useMasterOptions('product-models')
  const { options: collections } = useMasterOptions('collections')
  const { options: authors } = useMasterOptions('authors')
  const masterOptions = { categories, brands, publishers, models, collections, authors }

  const { data: product, error: loadError } = useApiResource(
    isEditing ? `/products/${id}` : null,
    { enabled: isEditing, deps: [id] },
  )

  // Completa el formulario con el producto cargado.
  useEffect(() => {
    if (!product) {
      return
    }

    setValues(
      Object.keys(EMPTY_FORM).reduce((acc, key) => {
        const raw = product[key]

        // Los booleanos deben seguir siendo booleanos: si se castean a string,
        // "false" es truthy y los interruptores quedarían en el estado errado.
        acc[key] = typeof EMPTY_FORM[key] === 'boolean' ? Boolean(raw) : toInput(raw)

        return acc
      }, { ...EMPTY_FORM }),
    )
    setImages(product.images ?? [])

    // Cargar variantes existentes
    if (product.variants && product.variants.length > 0) {
      setVariants(product.variants.map((v) => ({
        id: v.id,
        sku: v.sku || '',
        bar_code: v.bar_code || '',
        price_a_with_tax: v.price_a_with_tax ? String(v.price_a_with_tax) : '',
        current_stock: v.current_stock ? String(v.current_stock) : '0',
      })))
    }

    setLoading(false)
  }, [product])

  const change = (field) => (event) => {
    const { value, checked, type } = event.target
    setValues((current) => ({ ...current, [field]: type === 'checkbox' ?
      checked : value }))
    setErrors((current) => ({ ...current, [field]: undefined }))
    // Auto-search when product field has 3+ characters
    // Clear results when field is empty or has less than 3 chars
    if (field === 'name') {
      if (value.trim().length >= 3) {
        setSearch(value)
      } else {
        setSearchResults(null)
      }
    }
  }

  const searchBooks = async () => {
    setSearching(true)
    setSearchResults(null)

    try {
      // Construir params de búsqueda: incluyen el texto y los filtros de selects
      // Los selects vacíos se envían como undefined (buildQuery los omite automáticamente)
      const searchParams = {
        search,
        author_id: values.author_id || undefined,
        category_id: values.category_id || undefined,
        publisher_id: values.publisher_id || undefined,
        page,
        per_page: 20,
      }
      const result = await listBooks(searchParams)
      setSearchResults(normalizePaginator(result))
    } catch (error) {
      toast.error('Error al buscar', 'Ocurrió un error al consultar el stock.')
      setSearchResults(null)
    } finally {
      setSearching(false)
    }
  }

  const clearForm = () => {
    setValues(EMPTY_FORM)
    setSearchResults(null)
    setSearching(false)
    setSearch('')
    setPage(1)
  }

  const total = Number(values.current_stock ?? 0)
  const lowStock = values.min_stock !== '' && total > 0 && total < Number(values.min_stock)

  const previewPrice = useMemo(() => withTax(values.price_a, values.aliquot_name), [values.price_a, values.aliquot_name])

  if (isEditing && (loading || loadError)) {
    return (
      <div className="page-head">
        {loadError ? (
          <div className="erp-alert" role="alert">
            {loadError.message}
          </div>
        ) : (
          <div className="table-state">
            <span className="app-loader-spinner app-loader-spinner--sm" aria-hidden="true" />
            <span>Cargando libro…</span>
          </div>
        )}
      </div>
    )
  }

  // Formatea el stock para mostrar el badge de valorización
  const stockStatus = (stock) => {
    const s = Number(stock ?? 0)
    if (s <= 0) {
      return <Badge tone="danger">Sin stock</Badge>
    }
    if (s <= 3) {
      return <Badge tone="warning">{s}</Badge>
    }
    return <Badge tone="success">{s}</Badge>
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setSaving(true)
    setErrors({})

    try {
      if (isEditing) {
        await updateBook(id, values, {
          files: newFiles,
          removeImageIds: removedImageIds,
          variants: values.has_variants ? variants : null,
        })
        toast.success('Libro actualizado', `Se guardaron los cambios de "${values.name}".`)
      } else {
        await createBook(values, {
          files: newFiles,
          variants: values.has_variants ? variants : null,
        })
        toast.success('Libro creado', `"${values.name}" quedó cargado en el catálogo.`)
      }

      navigate('/libros', { replace: true })
    } catch (error) {
      if (error instanceof ApiError) {
        const fieldErrors = error.errors ?? {}

        if (Object.keys(fieldErrors).length) {
          setErrors(fieldErrors)
          toast.error('Revisá los datos', 'Hay campos que necesitan corrección.')
        } else {
          toast.error('No se pudo guardar', error.message)
        }
      } else {
        toast.error('No se pudo guardar', 'Ocurrió un error inesperado.')
      }

      setSaving(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="page-head">
        <div>
          <h1 className="page-title">{isEditing ? 'Editar libro' : 'Stock'}</h1>
          <p className="page-subtitle">
            Existencias actuales valorizadas por sucursal, depósito y variante
          </p>
        </div>
      </div>

      {errors.bar_code ? (
        <div className="erp-alert" role="alert">
          {errors.bar_code}
        </div>
      ) : null}

      <FormSection title="Buscar Stock" description="Información principal del libro.">
        <div className="form-grid">
          <TextInput
            name="name"
            label="Producto"
            value={values.name}
            onChange={change('name')}
            error={errors.name}
            placeholder="ISBN, Nombre o Código (mín 3 caracteres)"
          />

          <Select
            name="author_id"
            label="Autor"
            value={values.author_id}
            onChange={change('author_id')}
            error={errors.author_id}
            options={authors}
            placeholder="Todos"
          />

          <Select
            name="category_id"
            label="Categoría"
            value={values.category_id}
            onChange={change('category_id')}
            error={errors.category_id}
            options={categories}
            placeholder="Todos"
          />

          <Select
            name="publisher_id"
            label="Editorial"
            value={values.publisher_id}
            onChange={change('publisher_id')}
            error={errors.publisher_id}
            options={publishers}
            placeholder="Todos"
          />
        </div>
        <div className="form-actions">
          <button className="erp-btn erp-btn-primary" type="button" onClick={clearForm} disabled={searching}>
            <Icon name="close" size={18} />
            Limpiar
          </button>
          <button className="erp-btn erp-btn-primary" type="button" onClick={searchBooks} disabled={searching}>
            <Icon name="search" size={18} />
            {searching ? 'Buscando…' : 'Buscar'}
          </button>
        </div>
      </FormSection>

      <FormSection title="Stock Valorizado" description="Resultados de la búsqueda">
        {searchResults ? (
          <DataTable
            columns={[
              {
                key: 'image_full_url',
                header: '',
                render: (row) => {
                  return row.image_full_url ? (
                    <img src={row.image_full_url} alt={row.name} style={{ width: '100px', height: 'auto' }} />
                  ) : (
                    <span><Icon name="book" size={16} /></span>
                  )
                },
              },
              {
                key: 'name',
                header: 'Libro',
                render: (row) => (
                  <div style={{ fontSize: '20px', fontWeight: 'bold' }}>
                    {row.name || '—'} <br></br>
                    <small>{relatedName(row.author, row.author)}</small>
                  </div>
                ),
              },
              {
                key: 'current_stock',
                header: 'Stock',
                render: (row) => {
                  const stock = stockStatus(row.current_stock)
                  return (
                    <div style={{ fontSize: '20px'}}>
                      {stock}
                    </div>
                  )
                },
              },
              {
                key: 'price_a_with_tax',
                header: 'Valorización',
                align: 'end',
                render: (row) => {
                  const totalStockValue = row.price_a_with_tax * row.current_stock
                  return (
                    <div style={{ fontSize: '20px', fontWeight: 'bold' }}>
                      {money(totalStockValue)}
                    </div>
                  )
                },
              },
            ]}
            rows={searchResults.items ?? []}
            loading={searching}
            error={null}
            emptyText="No hay libros para mostrar"
            footer={<Pagination meta={searchResults.meta} onChange={setPage} />}
          />
        ) : (
          <p className="text-center">
            La tabla se mostrará después de ejecutar la búsqueda.
          </p>
        )}
      </FormSection>
    </form>
  )
}