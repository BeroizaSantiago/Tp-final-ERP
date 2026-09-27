// Genera src/components/icons.jsx a partir de la colección Remix Icon que ya
// usa el backend. Se ejecuta a mano: el resultado queda versionado en el repo
// para que la SPA no dependa de una librería de iconos.
const fs = require('node:fs')
const path = require('node:path')

const collection = require('../../backend/node_modules/@iconify/json/json/ri.json')

/** Nombre del icono en Remix -> clave camelCase usada en los componentes. */
const ICONS = {
  'shield-check-line': 'shieldCheck',
  'arrow-right-line': 'arrowRight',
  'moon-clear-line': 'moonClear',
  'sun-line': 'sun',
  'dashboard-3-line': 'dashboard',
  'book-2-line': 'book',
  'add-line': 'add',
  'folder-line': 'folder',
  'price-tag-3-line': 'priceTag',
  'stack-line': 'stack',
  'palette-line': 'palette',
  'ruler-2-line': 'rulerLarge',
  'ruler-line': 'ruler',
  'menu-2-line': 'menu',
  'search-line': 'search',
  'edit-line': 'edit',
  'delete-bin-6-line': 'trash',
  'eye-line': 'eyeFilled',
  'more-2-line': 'more',
  'close-line': 'close',
  'arrow-left-s-line': 'chevronLeft',
  'arrow-right-s-line': 'chevronRight',
  'settings-3-line': 'settings',
  'forbid-line': 'forbid',
  'checkbox-circle-line': 'checkCircle',
  'image-line': 'image',
  'upload-line': 'upload',
  'refresh-line': 'refresh',
  'box-3-line': 'box',
  'money-dollar-circle-line': 'money',
  'line-chart-line': 'chart',
  'receipt-line': 'receipt',
  'group-line': 'users',
  'alarm-warning-line': 'warning',
  'shopping-cart-line': 'cart',
  'filter-3-line': 'filter',
  'file-list-2-line': 'fileList',
  'bar-chart-box-line': 'barChart',
  'check-line': 'check',
  'information-line': 'info',
  'arrow-go-back-line': 'back',
  'save-3-line': 'save',
  'user-add-line': 'userAdd',
  'user-3-line': 'user',
  link: 'link',
  'building-2-line': 'building',
  'arrow-up-s-line': 'chevronUp',
  'arrow-down-s-line': 'chevronDown',
}

const extract = (body) =>
  body.replace(/^<path fill="currentColor" d="/, '').replace(/" \/>$/, '').replace(/"\/>$/, '')

const blocks = []
const missing = []

for (const [source, key] of Object.entries(ICONS)) {
  const icon = collection.icons[source]

  if (!icon) {
    missing.push(source)
    continue
  }

  blocks.push(`  // ri-${source}\n  ${key}:\n    '${extract(icon.body)}',`)
}

const template = `/**
 * Iconografía de la SPA.
 *
 * Los trazados filled provienen de la colección Remix Icon que ya usa el backend
 * y se copiaron con \`npm run icons\` (tools/extract-icons.cjs), para no sumar una
 * dependencia de iconos al frontend. Los de contorno (eye / logout) son SVG con
 * stroke, en el mismo estilo que usaba la vista Blade.
 */

/** Trazados de Remix Icon (viewBox 0 0 24 24). */
const FILLED_PATHS = {
${blocks.join('\n')}
}

/** Contornos con stroke (viewBox 0 0 24 24). */
const OUTLINED_SHAPES = {
  // Mostrar contraseña
  eye: (
    <>
      <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z" />
      <circle cx="12" cy="12" r="2.5" />
    </>
  ),
  // Ocultar contraseña
  eyeOff: (
    <>
      <path d="M3 3l18 18" />
      <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
      <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5.5 0 9 5 9 5a16.8 16.8 0 0 1-2.1 2.7" />
      <path d="M6.6 6.6C4.4 8.1 3 10 3 10s3.5 5 9 5c1.2 0 2.3-.2 3.3-.6" />
    </>
  ),
  // Salir del sistema
  logout: (
    <>
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
      <polyline points="16 17 21 12 16 7" />
      <line x1="21" y1="12" x2="9" y2="12" />
    </>
  ),
}

/** Icono relleno. \`size\` admite longitudes CSS; por defecto 1em. */
export function Icon({ name, size = '1em', className, ...rest }) {
  const d = FILLED_PATHS[name]

  if (!d) {
    return null
  }

  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      width={size}
      height={size}
      fill="currentColor"
      aria-hidden="true"
      focusable="false"
      {...rest}
    >
      <path d={d} />
    </svg>
  )
}

/** Icono de contorno (mismas formas SVG que la vista Blade). */
export function OutlinedIcon({ name, size = '1em', className, ...rest }) {
  const shapes = OUTLINED_SHAPES[name]

  if (!shapes) {
    return null
  }

  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      width={size}
      height={size}
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
      {...rest}
    >
      {shapes}
    </svg>
  )
}

/** Nombres disponibles, útiles para depurar desde la consola. */
export const ICON_NAMES = Object.keys(FILLED_PATHS)
`

const target = path.join(__dirname, '..', 'src', 'components', 'icons.jsx')
fs.writeFileSync(target, template, 'utf8')

console.log(`escrito ${target}`)
console.log(`iconos: ${blocks.length}`)
console.log(`faltantes: ${missing.length ? missing.join(', ') : 'ninguno'}`)
