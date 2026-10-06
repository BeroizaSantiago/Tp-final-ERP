import { apiRequest } from '../../lib/api'

/**
 * API del catálogo de libros.
 *
 * Habla con los endpoints de `Api\Products\Catalog\ProductController`. El
 * dominio del backend sigue llamándose "producto"; sólo cambia la etiqueta que
 * ve el usuario.
 */

/** Normaliza los parámetros del listado, omitiendo los vacíos. */
function buildQuery(params = {}) {
  const search = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined || value === '') {
      return
    }

    search.set(key, String(value))
  })

  const query = search.toString()

  return query ? `?${query}` : ''
}

export function listBooks(params) {
  return apiRequest(`/products${buildQuery(params)}`)
}

export function getBook(id) {
  return apiRequest(`/products/${id}`)
}

export function setBookStatus(id, isActive) {
  return apiRequest(`/products/${id}/status`, {
    method: 'PATCH',
    body: { is_active: isActive },
  })
}

/**
 * Convierte el formulario en el cuerpo que espera la API.
 *
 * Los selects vacíos se mandan como null para no guardar '' en un id de clave
 * foránea, y las listas se limpian de campos que nunca se rellenaron.
 */
export function toProductPayload(values, { imageUrls = [], removeImageIds = [] } = {}) {
  const optional = (value) => (value === '' || value === null ? null : value)

  return {
    code: optional(values.code),
    bar_code: values.has_variants ? null : optional(values.bar_code),
    reference_code: optional(values.reference_code),
    name: values.name?.trim(),
    web_title: optional(values.web_title),
    description: optional(values.description),
    notes: optional(values.notes),

    brand: optional(values.brand),
    model: optional(values.model),
    category: optional(values.category),
    unit_measure_name: optional(values.unit_measure_name),
    product_type_name: optional(values.product_type_name),
    principal_provider_name: optional(values.principal_provider_name),

    category_id: optional(values.category_id),
    brand_id: optional(values.brand_id),
    publisher_id: optional(values.publisher_id),
    product_model_id: optional(values.product_model_id),
    collection_id: optional(values.collection_id),
    author_id: optional(values.author_id),

    aliquot_name: values.aliquot_name || 'IVA 21%',
    currency_symbol: optional(values.currency_symbol),
    currency_name: optional(values.currency_name),

    has_variants: Boolean(values.has_variants),
    auto_calculate_tax: Boolean(values.auto_calculate_tax),

    cost_with_discount: optional(values.cost_with_discount),
    price_a: optional(values.price_a),
    price_b: optional(values.price_b),
    price_c: optional(values.price_c),
    price_d: optional(values.price_d),

    current_stock: optional(values.current_stock),
    min_stock: optional(values.min_stock),
    reposition_stock: optional(values.reposition_stock),

    weight: optional(values.weight),
    height: optional(values.height),
    width: optional(values.width),
    length: optional(values.length),

    is_active: values.is_active === undefined ? true : Boolean(values.is_active),

    image_urls: imageUrls,
    remove_image_ids: removeImageIds,
  }
}

/** Agrega al FormData sólo los archivos elegidos, bajo el nombre `images[]`. */
function appendImages(formData, files) {
  Array.from(files ?? []).forEach((file) => {
    formData.append('images[]', file)
  })
}

/**
 * Vuelca el payload en un FormData.
 *
 * En multipart todos los valores viajan como texto, y Laravel sólo acepta `1` o
 * `0` para los campos booleanos: mandar `true` o `false` hace fallar la
 * validación y el producto no se guarda. Los booleanos se convierten a `1` y el
 * `false` se omite para no mandarlo vacío.
 */
function toFormData(payload) {
  const formData = new FormData()

  const appendValue = (key, value) => {
    if (value === null || value === undefined) {
      return
    }

    if (typeof value === 'boolean') {
      formData.append(key, value ? '1' : '0')
      return
    }

    if (Array.isArray(value)) {
      value.forEach((item, index) => {
        const itemKey = typeof item === 'object' && item !== null
          ? `${key}[${index}]`
          : `${key}[]`
        appendValue(itemKey, item)
      })
      return
    }

    if (typeof value === 'object') {
      Object.entries(value).forEach(([field, fieldValue]) => {
        appendValue(`${key}[${field}]`, fieldValue)
      })
      return
    }

    formData.append(key, value)
  }

  Object.entries(payload).forEach(([key, value]) => appendValue(key, value))

  return formData
}

export function createBook(values, { files = [], imageUrls = [], variants = null } = {}) {
  const payload = toProductPayload(values, { imageUrls })

  // Si se proporcionan variantes, las incluimos en el payload
  if (variants !== null) {
    payload.variants = variants.map((v) => ({
      id: v.id || null,
      sku: v.sku || null,
      bar_code: v.bar_code || null,
      price_a_with_tax: v.price_a_with_tax || null,
      current_stock: v.current_stock ?? 0,
    }))
  }

  // Sin archivos se manda JSON; con archivos hace falta multipart.
  if (!files.length) {
    return apiRequest('/products', { method: 'POST', body: payload })
  }

  const formData = toFormData(payload)
  appendImages(formData, files)

  return apiRequest('/products', { method: 'POST', body: formData })
}

export function updateBook(id, values, { files = [], imageUrls = [], removeImageIds = [], variants = null } = {}) {
  const payload = toProductPayload(values, { imageUrls, removeImageIds })

  // Si se proporcionan variantes, las incluimos en el payload
  if (variants !== null) {
    payload.variants = variants.map((v) => ({
      id: v.id || null,
      sku: v.sku || null,
      bar_code: v.bar_code || null,
      price_a_with_tax: v.price_a_with_tax || null,
      current_stock: v.current_stock ?? 0,
    }))
  }

  if (!files.length) {
    return apiRequest(`/products/${id}`, { method: 'PUT', body: payload })
  }

  const formData = toFormData(payload)
  appendImages(formData, files)

  return apiRequest(`/products/${id}`, { method: 'PUT', body: formData })
}

/* ------------------------------------------------------------------ maestros */

export function listMaster(resource, { lookup = false } = {}) {
  const query = lookup ? '?lookup=1' : ''

  return apiRequest(`/${resource}${query}`)
}

export function createMasterItem(resource, values) {
  return apiRequest(`/${resource}`, { method: 'POST', body: values })
}

export function updateMasterItem(resource, id, values) {
  return apiRequest(`/${resource}/${id}`, { method: 'PUT', body: values })
}

export function deleteMasterItem(resource, id) {
  return apiRequest(`/${resource}/${id}`, { method: 'DELETE' })
}
