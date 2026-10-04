/**
 * Catálogos maestros del ERP.
 *
 * Cada entrada describe un endpoint de `routes/api.php`, sus columnas y sus
 * campos de formulario. Se usa para tres cosas: el CRUD genérico, los selects
 * del formulario de libros y el menú lateral, de modo que agregar un maestro
 * nuevo sea sólo agregar una entrada acá.
 *
 * Los talles y los tipos de talle quedaron afuera: son de indumentaria y no
 * aplican al catálogo de libros, aunque los endpoints sigan existiendo en el
 * backend por si algún producto los llegara a usar.
 */

export const MASTERS = {

  autores: {
    key: 'autores',
    path: '/maestros/autores',
    resource: 'authors',
    label: 'Autores',
    singular: 'autor',
    icon: 'user',
    description: 'Autores de los libros del catálogo.',
    columns: ['name', 'biography', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Gabriel García Márquez' },
      { name: 'biography', label: 'Biografía', type: 'text', placeholder: ' ' },
      { name: 'external_code', label: 'Código externo', type: 'text' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },
  
  categorias: {
    key: 'categorias',
    path: '/maestros/categorias',
    resource: 'product-categories',
    label: 'Categorías',
    singular: 'categoría',
    icon: 'folder',
    description: 'Agrupan el catálogo y pueden anidarse en subcategorías.',
    columns: ['name', 'web_order', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Novela' },
      { name: 'external_code', label: 'Código externo', type: 'text' },
      { name: 'parent_id', label: 'Categoría padre', type: 'master', resource: 'categorias' },
      { name: 'web_order', label: 'Orden', type: 'number', placeholder: '0' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },

  marcas: {
    key: 'marcas',
    path: '/maestros/marcas',
    resource: 'brands',
    label: 'Marcas',
    singular: 'marca',
    icon: 'priceTag',
    description: 'Marca o sello comercial asociado al libro.',
    columns: ['name', 'external_code', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Sudestudio' },
      { name: 'external_code', label: 'Código externo', type: 'text' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },

  editoriales: {
    key: 'editoriales',
    path: '/maestros/editoriales',
    resource: 'publishers',
    label: 'Editoriales',
    singular: 'editorial',
    icon: 'building',
    description: 'Casas editoras que publican los libros del catálogo.',
    columns: ['name', 'country', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Sudestudio Editores' },
      { name: 'external_code', label: 'Código externo', type: 'text' },
      { name: 'country', label: 'País', type: 'text', placeholder: 'Argentina' },
      { name: 'website', label: 'Sitio web', type: 'text', placeholder: 'https://…' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },

  colecciones: {
    key: 'colecciones',
    path: '/maestros/colecciones',
    resource: 'collections',
    label: 'Colecciones',
    singular: 'colección',
    icon: 'stack',
    description: 'Series o colecciones de una misma editorial.',
    columns: ['name', 'publisher_id', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Colección Clásico' },
      { name: 'external_code', label: 'Código externo', type: 'text' },
      { name: 'publisher_id', label: 'Editorial', type: 'master', resource: 'publishers' },
      { name: 'web_order', label: 'Orden', type: 'number', placeholder: '0' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },

  modelos: {
    key: 'modelos',
    path: '/maestros/modelos',
    resource: 'product-models',
    label: 'Modelos',
    singular: 'modelo',
    icon: 'box',
    description: 'Modelos o ediciones, opcionalmente ligados a una marca.',
    columns: ['name', 'brand_id', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Edición ilustrada' },
      { name: 'external_code', label: 'Código externo', type: 'text' },
      { name: 'brand_id', label: 'Marca', type: 'master', resource: 'brands' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },

  colores: {
    key: 'colores',
    path: '/maestros/colores',
    resource: 'colors',
    label: 'Colores',
    singular: 'color',
    icon: 'palette',
    description: 'Color de la tapa o de la tinta.',
    columns: ['name', 'hex_code', 'is_active'],
    fields: [
      { name: 'name', label: 'Nombre', type: 'text', required: true, placeholder: 'Tinta' },
      { name: 'hex_code', label: 'Código hexadecimal', type: 'text', placeholder: '#1b1b1f' },
      { name: 'web_order', label: 'Orden', type: 'number', placeholder: '0' },
      { name: 'is_active', label: 'Activo', type: 'switch', default: true },
    ],
  },
}

export const MASTER_LIST = Object.values(MASTERS)

/**
 * Orden en que aparecen los maestros dentro del desplegable "Productos".
 * El menú lateral usa esta lista, así que el orden es explícito y no depende
 * del orden del objeto.
 */
export const MENU_ORDER = [
  'autores',
  'categorias',
  'marcas',
  'editoriales',
  'modelos',
  'colecciones',
  'colores',
]

/** Entradas del desplegable, en el orden pedido. */
export function menuMasters() {
  return MENU_ORDER.map((key) => MASTERS[key]).filter(Boolean)
}

/** Valores por defecto de un maestro, a partir de la definición de sus campos. */
export function masterDefaults(config) {
  return config.fields.reduce((acc, field) => {
    acc[field.name] = field.type === 'switch' ? (field.default ?? true) : ''
    return acc
  }, {})
}
