import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

import { ActiveBadge, StockBadge } from '../../components/Badge'
import { BackLink, Field, FormSection, Select, Switch, TextInput } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { money, number, toInput } from '../../lib/format'
import { useApiResource } from '../../lib/useApiResource'
import { useMasterOptions } from '../masters/useMasterOptions'
import { createBook, getBook, updateBook } from './api'

/** Campos que definen una variante de producto. */
const VARIANT_FIELDS = ['category_id', 'brand_id', 'publisher_id', 'product_model_id', 'collection_id']

/** Crea una variante vacía con todos los campos en blanco. */
function emptyVariant() {
  return {
    category_id: '',
    brand_id: '',
    publisher_id: '',
    product_model_id: '',
    collection_id: '',
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

/**
 * Administrador de variantes de producto.
 *
 * Permite crear, editar y eliminar variantes. Cada variante es una combinación
 * única de atributos maestros (categoría, marca, editorial, modelo, colección)
 * con su propio stock, SKU y código de barras.
 */
function VariantManager({ variants, onChange, masterOptions, errors }) {
  const toast = useToast()
  const [showForm, setShowForm] = useState(false)
  const [editingIndex, setEditingIndex] = useState(null)
  const [formData, setFormData] = useState(emptyVariant())

  const categories = masterOptions.categories
  const brands = masterOptions.brands
  const publishers = masterOptions.publishers
  const models = masterOptions.models
  const collections = masterOptions.collections

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
      toast.error('Variante incompleta', 'Seleccioná al menos un atributo para la variante.')
      return
    }

    // Verificar duplicados
    const isDuplicate = variants.some((v, index) => {
      if (index === editingIndex) return false
      return VARIANT_FIELDS.every((field) => (v[field] || '') === (formData[field] || ''))
    })

    if (isDuplicate) {
      toast.error('Variante duplicada', 'Ya existe una variante con esa combinación de atributos.')
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
          Cada combinación de atributos crea una variante única con su propio stock.
        </p>
      </div>

      <div className="form-section-body">
        {variants.length > 0 && !showForm ? (
          <div className="table-scroll">
            <table className="erp-table">
              <thead>
                <tr>
                  <th>Atributos</th>
                  <th>SKU</th>
                  <th>Stock</th>
                  <th className="is-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {variants.map((variant, index) => (
                  <tr key={index}>
                    <td>
                      <div className="variant-attributes">
                        {variant.category_id && (
                          <span className="erp-badge">{categories.find(c => c.value === variant.category_id)?.label || 'Categoría'}</span>
                        )}
                        {variant.brand_id && (
                          <span className="erp-badge">{brands.find(b => b.value === variant.brand_id)?.label || 'Marca'}</span>
                        )}
                        {variant.publisher_id && (
                          <span className="erp-badge">{publishers.find(p => p.value === variant.publisher_id)?.label || 'Editorial'}</span>
                        )}
                        {variant.product_model_id && (
                          <span className="erp-badge">{models.find(m => m.value === variant.product_model_id)?.label || 'Modelo'}</span>
                        )}
                        {variant.collection_id && (
                          <span className="erp-badge">{collections.find(c => c.value === variant.collection_id)?.label || 'Colección'}</span>
                        )}
                      </div>
                    </td>
                    <td>{variant.sku || '—'}</td>
                    <td>{number(Number(variant.current_stock) || 0)}</td>
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
              <Select
                name="category_id"
                label="Categoría"
                value={formData.category_id}
                onChange={(e) => handleFieldChange('category_id', e.target.value)}
                options={categories}
                placeholder="Sin categoría"
              />
              <Select
                name="brand_id"
                label="Marca"
                value={formData.brand_id}
                onChange={(e) => handleFieldChange('brand_id', e.target.value)}
                options={brands}
                placeholder="Sin marca"
              />
              <Select
                name="publisher_id"
                label="Editorial"
                value={formData.publisher_id}
                onChange={(e) => handleFieldChange('publisher_id', e.target.value)}
                options={publishers}
                placeholder="Sin editorial"
              />
              <Select
                name="product_model_id"
                label="Modelo"
                value={formData.product_model_id}
                onChange={(e) => handleFieldChange('product_model_id', e.target.value)}
                options={models}
                placeholder="Sin modelo"
              />
              <Select
                name="collection_id"
                label="Colección"
                value={formData.collection_id}
                onChange={(e) => handleFieldChange('collection_id', e.target.value)}
                options={collections}
                placeholder="Sin colección"
              />

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
              <button type="button" className="erp-btn erp-btn-outline" onClick={handleCancel}>
                Cancelar
              </button>
              <button type="button" className="erp-btn erp-btn-primary" onClick={handleSave}>
                <Icon name="check" size={16} />
                {editingIndex !== null ? 'Actualizar variante' : 'Agregar variante'}
              </button>
            </div>
          </div>
        ) : (
          <button type="button" className="erp-btn erp-btn-outline" onClick={openCreate}>
            <Icon name="plus" size={16} />
            Agregar variante
          </button>
        )}
      </div>
    </div>
  )
}

/** Alíquotas que el backend traduce a porcentaje en calculateTaxInclusivePrices(). */
const TAX_OPTIONS = [
  { value: 'IVA 21%', label: 'IVA 21%' },
  { value: 'IVA 10,5%', label: 'IVA 10,5%' },
  { value: 'IVA 27%', label: 'IVA 27%' },
  { value: 'Exento', label: 'Exento' },
]

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
          className="visually-hidden"
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
export function BookFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const isEditing = Boolean(id)

  const [values, setValues] = useState(EMPTY_FORM)
  const [images, setImages] = useState([])
  const [newFiles, setNewFiles] = useState([])
  const [removedImageIds, setRemovedImageIds] = useState([])
  const [variants, setVariants] = useState([])
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(isEditing)
  const [saving, setSaving] = useState(false)

  const { options: categories } = useMasterOptions('product-categories')
  const { options: brands } = useMasterOptions('brands')
  const { options: publishers } = useMasterOptions('publishers')
  const { options: models } = useMasterOptions('product-models')
  const { options: collections } = useMasterOptions('collections')
  const masterOptions = { categories, brands, publishers, models, collections }

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
        category_id: v.category_id || '',
        brand_id: v.brand_id || '',
        publisher_id: v.publisher_id || '',
        product_model_id: v.product_model_id || '',
        collection_id: v.collection_id || '',
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
    setValues((current) => ({ ...current, [field]: type === 'checkbox' ? checked : value }))
    setErrors((current) => ({ ...current, [field]: undefined }))
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

  const total = Number(values.current_stock ?? 0)
  const lowStock = values.min_stock !== '' && total > 0 && total < Number(values.min_stock)

  const previewPrice = useMemo(() => withTax(values.price_a, values.aliquot_name), [values.price_a, values.aliquot_name])

  if (isEditing && (loading || loadError)) {
    return (
      <div className="page-head">
        <BackLink to="/libros">Volver a libros</BackLink>

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

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="page-head">
        <div>
          <BackLink to="/libros">Volver a libros</BackLink>
          <h1 className="page-title">{isEditing ? 'Editar libro' : 'Nuevo libro'}</h1>
          <p className="page-subtitle">
            {isEditing
              ? 'Modificá los datos del catálogo. Los cambios impactan en el stock y las ventas.'
              : 'Cargá un libro al catálogo con sus precios, stock y datos de edición.'}
          </p>
        </div>

        <div className="page-actions">
          <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/libros')} disabled={saving}>
            Cancelar
          </button>
          <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
            <Icon name="save" size={18} />
            {saving ? 'Guardando…' : 'Guardar libro'}
          </button>
        </div>
      </div>

      {errors.bar_code ? (
        <div className="erp-alert" role="alert">
          {errors.bar_code}
        </div>
      ) : null}

      <FormSection title="Datos de edición" description="Información principal del libro.">
        <div className="form-grid">
          <TextInput
            className="form-span-2"
            name="name"
            label="Título"
            required
            value={values.name}
            onChange={change('name')}
            error={errors.name}
            placeholder="Cien años de soledad"
            autoFocus={!isEditing}
          />

          <TextInput
            name="bar_code"
            label="ISBN"
            value={values.bar_code}
            onChange={change('bar_code')}
            error={errors.bar_code}
            placeholder="9788497592228"
            hint="Se carga en el código de barras del producto."
          />

          <TextInput
            name="code"
            label="Código interno"
            value={values.code}
            onChange={change('code')}
            error={errors.code}
            placeholder="LIB-0001"
          />

          <TextInput
            name="reference_code"
            label="Código de referencia"
            value={values.reference_code}
            onChange={change('reference_code')}
            error={errors.reference_code}
          />

          <TextInput
            name="web_title"
            label="Título para la web"
            value={values.web_title}
            onChange={change('web_title')}
            error={errors.web_title}
            hint="Opcional, para la ficha pública."
          />

          <Field
            className="form-span-2"
            label="Sinopsis"
            error={errors.description}
            htmlFor="description"
            hint="Descripción del libro. Es el campo `description` del producto."
          >
            <textarea
              id="description"
              name="description"
              className={`erp-control erp-textarea${errors.description ? ' is-invalid' : ''}`}
              rows={5}
              value={values.description}
              onChange={change('description')}
              placeholder="Resumen de la obra…"
            />
          </Field>

          <Field
            className="form-span-2"
            label="Notas internas"
            error={errors.notes}
            htmlFor="notes"
          >
            <textarea
              id="notes"
              name="notes"
              className={`erp-control erp-textarea${errors.notes ? ' is-invalid' : ''}`}
              rows={3}
              value={values.notes}
              onChange={change('notes')}
            />
          </Field>
        </div>
      </FormSection>

      <FormSection title="Clasificación" description="Dónde está publicado el libro.">
        <div className="form-grid">
          <Select
            name="category_id"
            label="Categoría"
            value={values.category_id}
            onChange={change('category_id')}
            error={errors.category_id}
            options={categories}
            placeholder="Sin categoría"
          />

          <Select
            name="brand_id"
            label="Marca / Editorial"
            value={values.brand_id}
            onChange={change('brand_id')}
            error={errors.brand_id}
            options={brands}
            placeholder="Sin marca"
          />

          <Select
            name="publisher_id"
            label="Editorial"
            value={values.publisher_id}
            onChange={change('publisher_id')}
            error={errors.publisher_id}
            options={publishers}
            placeholder="Sin editorial"
          />

          <Select
            name="product_model_id"
            label="Modelo"
            value={values.product_model_id}
            onChange={change('product_model_id')}
            error={errors.product_model_id}
            options={models}
            placeholder="Sin modelo"
          />

          <Select
            name="collection_id"
            label="Colección"
            value={values.collection_id}
            onChange={change('collection_id')}
            error={errors.collection_id}
            options={collections}
            placeholder="Sin colección"
          />

          <TextInput
            name="unit_measure_name"
            label="Unidad de medida"
            value={values.unit_measure_name}
            onChange={change('unit_measure_name')}
            error={errors.unit_measure_name}
          />
        </div>
      </FormSection>

      <FormSection
        title="Precios"
        description="Cargá los precios netos. Si activás el cálculo automático, el backend deriva los precios con impuesto."
      >
        <div className="form-grid">
          <Select
            name="aliquot_name"
            label="Alícuota de IVA"
            value={values.aliquot_name}
            onChange={change('aliquot_name')}
            error={errors.aliquot_name}
            options={TAX_OPTIONS}
            placeholder="IVA 21%"
          />

          <TextInput
            name="cost_with_discount"
            label="Costo con descuento"
            type="number"
            step="0.01"
            value={values.cost_with_discount}
            onChange={change('cost_with_discount')}
            error={errors.cost_with_discount}
          />

          {PRICE_LISTS.map((list) => (
            <TextInput
              key={list.key}
              name={list.field}
              label={`${list.label} (neto)`}
              type="number"
              step="0.01"
              value={values[list.field]}
              onChange={change(list.field)}
              error={errors[list.field]}
            />
          ))}

          <div className="erp-field-block form-span-2">
            <div className="price-preview">
              <Icon name="money" size={18} />
              <span>
                Precio final A: <strong>{previewPrice === null ? '—' : money(previewPrice)}</strong>
              </span>
              <small>Calculado en el backend con la alícuota elegida.</small>
            </div>
          </div>

          <div className="form-span-2">
            <Switch
              label="Calcular precios con impuesto automáticamente"
              checked={values.auto_calculate_tax}
              onChange={change('auto_calculate_tax')}
              hint="El backend recalcula los precios con impuesto a partir de los netos."
            />
          </div>

          <div className="form-span-2">
            <Switch
              label="Maneja variantes"
              checked={values.has_variants}
              onChange={change('has_variants')}
              hint="Si lo activás, el stock se administra por variante en lugar del libro."
            />
          </div>
        </div>
      </FormSection>

      <FormSection title="Stock" description="Existencias iniciales y puntos de control.">
        <div className="form-grid">
          <TextInput
            name="current_stock"
            label="Stock actual"
            type="number"
            step="1"
            value={values.current_stock}
            onChange={change('current_stock')}
            error={errors.current_stock}
            disabled={values.has_variants}
            hint={values.has_variants ? 'Con variantes el stock se define por cada una.' : undefined}
          />

          <TextInput
            name="min_stock"
            label="Stock mínimo"
            type="number"
            step="1"
            value={values.min_stock}
            onChange={change('min_stock')}
            error={errors.min_stock}
          />

          <TextInput
            name="reposition_stock"
            label="Stock de reposición"
            type="number"
            step="1"
            value={values.reposition_stock}
            onChange={change('reposition_stock')}
            error={errors.reposition_stock}
          />

          <div className="erp-field-block">
            <span className="erp-label">Estado del stock</span>
            <div className="stock-preview">
              <StockBadge value={total} />
              {lowStock ? <span className="erp-badge erp-badge--warning">Por debajo del mínimo</span> : null}
              <small className="erp-hint">{number(total)} unidades</small>
            </div>
          </div>

          <div className="form-span-2">
            <Switch
              label="Libro habilitado"
              checked={values.is_active}
              onChange={change('is_active')}
              hint="Un libro inhabilitado no aparece en las búsquedas de venta."
            />
          </div>
        </div>
      </FormSection>

      {values.has_variants ? (
        <VariantManager
          variants={variants}
          onChange={setVariants}
          masterOptions={masterOptions}
          errors={errors}
        />
      ) : null}

      <FormSection title="Medidas" description="Dimensiones y peso físico, en centímetros y kilos.">
        <div className="form-grid">
          <TextInput
            name="weight"
            label="Peso"
            type="number"
            step="0.01"
            value={values.weight}
            onChange={change('weight')}
            error={errors.weight}
          />
          <TextInput
            name="height"
            label="Alto"
            type="number"
            step="0.01"
            value={values.height}
            onChange={change('height')}
            error={errors.height}
          />
          <TextInput
            name="width"
            label="Ancho"
            type="number"
            step="0.01"
            value={values.width}
            onChange={change('width')}
            error={errors.width}
          />
          <TextInput
            name="length"
            label="Largo"
            type="number"
            step="0.01"
            value={values.length}
            onChange={change('length')}
            error={errors.length}
          />
        </div>
      </FormSection>

      <ImageManager
        images={images.filter((image) => !removedImageIds.includes(image.id))}
        newFiles={newFiles}
        disabled={saving}
        onAddFiles={(files) =>
          setNewFiles((current) => [...current, ...files].slice(0, 6 - images.length))
        }
        onRemoveExisting={(image) =>
          setRemovedImageIds((current) => (image.id ? [...current, image.id] : current))
        }
        onRemoveNew={(index) => setNewFiles((current) => current.filter((_, i) => i !== index))}
      />

      <div className="form-actions">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/libros')} disabled={saving}>
          Cancelar
        </button>
        <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
          <Icon name="save" size={18} />
          {saving ? 'Guardando…' : 'Guardar libro'}
        </button>
      </div>
    </form>
  )
}
