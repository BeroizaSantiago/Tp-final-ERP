// Smoke test en DOM real (jsdom) del enrutado y del flujo de login.
// A diferencia de un render en servidor, acá sí corren los efectos, que es
// justamente lo que decide si las guardas dejan pasar al usuario.
import { JSDOM } from 'jsdom'

const dom = new JSDOM('<!doctype html><html><body></body></html>', {
  url: 'http://localhost:5173/',
  pretendToBeVisual: true,
})

const mediaStub = (query) => ({
  matches: false,
  media: query,
  onchange: null,
  addEventListener() {},
  removeEventListener() {},
  addListener() {},
  removeListener() {},
  dispatchEvent: () => false,
})

global.window = dom.window
global.document = dom.window.document
// En Node 22 `navigator` es de solo lectura, hay que redefinirlo.
Object.defineProperty(global, 'navigator', {
  value: dom.window.navigator,
  configurable: true,
  writable: true,
})
global.HTMLElement = dom.window.HTMLElement
global.localStorage = dom.window.localStorage
global.sessionStorage = dom.window.sessionStorage
global.Event = dom.window.Event
// La app construye FormData/File con los globales: se unifican con los de jsdom
// para que el mock pueda compararlos con instanceof.
global.FormData = dom.window.FormData
global.File = dom.window.File
global.Blob = dom.window.Blob
global.matchMedia = mediaStub
dom.window.matchMedia = mediaStub
global.IS_REACT_ACT_ENVIRONMENT = true

// --- API simulada -----------------------------------------------------------
const calls = []
const user = {
  id: 1,
  name: 'Ana Gómez',
  username: 'ana',
  email: 'ana@erp.test',
  role: 'Administrador',
  last_login_at: '2026-09-25T10:00:00-03:00',
}

// Libro de ejemplo que devuelve GET /api/products.
let products = [
  {
    id: 7,
    name: 'Cien años de soledad',
    bar_code: '9788497592228',
    code: 'LIB-0007',
    price_a: 20248,
    price_a_with_tax: 24500.5,
    current_stock: 12,
    available_stock: 10,
    min_stock: 2,
    is_active: true,
    has_variants: false,
    aliquot_name: 'IVA 21%',
    category_id: 3,
    category: { id: 3, name: 'Novela' },
    brand_id: 2,
    brand: { id: 2, name: 'Sudestudio' },
    description: 'La saga de los Buendia.',
    images: [{ id: 1, full_url: 'http://127.0.0.1:8000/media/products/7.png' }],
    image_full_url: 'http://127.0.0.1:8000/media/products/7.png',
    inventory_items: [{ id: 1, warehouse_name: 'Central' }],
    variants: [],
  },
]
let loginResponse = { ok: true, status: 200, body: { token: 'token-falso', user } }
let registerResponse = {
  ok: true,
  status: 201,
  body: { token: 'token-registro', user, initial_password: 'ERP123' },
}

global.fetch = async (url, options = {}) => {
  const target = String(url)

  calls.push({
    url: target,
    method: options.method ?? 'GET',
    headers: options.headers ?? {},
    // Con FormData no se puede hacer JSON.parse: se guarda el objeto tal cual
    // para poder inspeccionarlo.
    body:
      options.body instanceof dom.window.FormData
        ? options.body
        : options.body
          ? JSON.parse(options.body)
          : null,
  })

  if (target.endsWith('/api/login')) {
    return { ok: loginResponse.ok, status: loginResponse.status, json: async () => loginResponse.body }
  }

  if (target.endsWith('/api/register')) {
    return {
      ok: registerResponse.ok,
      status: registerResponse.status,
      json: async () => registerResponse.body,
    }
  }

  // /api/products y /api/products/{id}
  if (/\/api\/products\/\d+$/.test(target)) {
    const id = Number(target.split('/').pop())

    return {
      ok: true,
      status: 200,
      json: async () => products.find((item) => item.id === id) ?? products[0],
    }
  }

  if (target.includes('/api/products')) {
    return {
      ok: true,
      status: 200,
      json: async () => ({
        data: products,
        current_page: 1,
        last_page: 1,
        total: products.length,
        per_page: 20,
        from: 1,
        to: products.length,
      }),
    }
  }

  // Catálogos maestros: lookup plano o paginado.
  if (
    /^\/api\/(product-categories|brands|product-models|colors|publishers|collections)/.test(target)
  ) {
    const isLookup = target.includes('lookup=1')
    const row = { id: 3, name: 'Novela', is_active: true, web_order: 1, country: 'Argentina' }

    return {
      ok: true,
      status: 200,
      json: async () =>
        isLookup
          ? [row]
          : { data: [row], current_page: 1, last_page: 1, total: 1, per_page: 20, from: 1, to: 1 },
    }
  }

  return { ok: true, status: 200, json: async () => ({ user }) }
}

dom.window.fetch = global.fetch

// Los módulos de la app deben importarse DESPUÉS de preparar el entorno.
const { act } = await import('react')
const { createRoot } = await import('react-dom/client')
const { MemoryRouter } = await import('react-router-dom')
const { default: App } = await import('../src/App.jsx')
const { AuthProvider } = await import('../src/context/AuthContext.jsx')
const { ToastProvider } = await import('../src/context/ToastContext.jsx')
const { ConfirmProvider } = await import('../src/context/ConfirmContext.jsx')

const checks = []
function check(name, condition) {
  checks.push([name, Boolean(condition)])
  console.log(`${condition ? 'OK  ' : 'FAIL'}  ${name}`)
}

// Sólo un árbol montado a la vez: si no, quedan ids duplicados en el documento
// y las consultas por id de jsdom devuelven el elemento del primer render.
let active = null

async function renderAt(path) {
  if (active) {
    await act(async () => active.root.unmount())
    active.container.remove()
  }

  const container = dom.window.document.createElement('div')
  dom.window.document.body.appendChild(container)
  const root = createRoot(container)
  active = { root, container }

  await act(async () => {
    root.render(
      <MemoryRouter initialEntries={[path]}>
        <AuthProvider>
          <ToastProvider>
            <ConfirmProvider>
              <App />
            </ConfirmProvider>
          </ToastProvider>
        </AuthProvider>
      </MemoryRouter>,
    )
  })

  return {
    container,
    text: () => container.textContent,
    // Busca el control por name, sea input, textarea o select.
    field: (name) => container.querySelector(`[name="${name}"]`),
    form: () => container.querySelector('form'),
  }
}

function setInputValue(input, value) {
  // El setter se toma del prototipo del propio elemento para no mezclar realms
  // de jsdom. Es el truco habitual para que React detecte el cambio en un input
  // controlado (si se asigna .value directo, React lo ignora).
  const win = input.ownerDocument.defaultView
  const setter = Object.getOwnPropertyDescriptor(Object.getPrototypeOf(input), 'value').set
  setter.call(input, value)
  input.dispatchEvent(new win.Event('input', { bubbles: true }))
}

function submit(form) {
  const win = form.ownerDocument.defaultView
  form.dispatchEvent(new win.Event('submit', { bubbles: true, cancelable: true }))
}

async function fill(page, values) {
  await act(async () => {
    Object.entries(values).forEach(([name, value]) => setInputValue(page.field(name), value))
  })
}

async function submitForm(page) {
  await act(async () => {
    submit(page.form())
  })
}

// --- Rutas con usuario invitado --------------------------------------------
const login = await renderAt('/login')
check('/login muestra el formulario', login.text().includes('¡Bienvenido nuevamente!'))
check('/login muestra el botón de acceso', login.text().includes('Iniciar sesión'))
check('/login enlaza el registro dentro de la SPA', Boolean(login.container.querySelector('a[href="/register"]')))
check(
  '/login no abandona el origen del frontend',
  ![...login.container.querySelectorAll('a')].some((a) => (a.getAttribute('href') ?? '').startsWith('http')),
)

const rootRoute = await renderAt('/')
check('/ sin sesión redirige al login', rootRoute.text().includes('¡Bienvenido nuevamente!'))

const booksGuest = await renderAt('/libros')
check('/libros sin sesión redirige al login', booksGuest.text().includes('¡Bienvenido nuevamente!'))
check('/libros sin sesión no filtra la tabla', !booksGuest.text().includes('Cien años de soledad'))

const oldHome = await renderAt('/dashboard')
check('la ruta vieja /dashboard sin sesión cae al login', oldHome.text().includes('¡Bienvenido nuevamente!'))

const register = await renderAt('/register')
check('/register responde en la SPA', register.text().includes('Creá tu usuario'))
check('/register pide nombre y apellido', register.text().includes('Nombre') && register.text().includes('Apellido'))
check('/register comparte el layout del login', Boolean(register.container.querySelector('.erp-login-layout')))
check('/register explica el rol asignado', register.text().includes('Vendedor'))
check('/register enlaza al login dentro de la SPA', Boolean(register.container.querySelector('a[href="/login"]')))
check(
  '/register no abandona el origen del frontend',
  ![...register.container.querySelectorAll('a')].some((a) => (a.getAttribute('href') ?? '').startsWith('http')),
)

const notFound = await renderAt('/ruta-inexistente')
check('ruta desconocida muestra 404', notFound.text().includes('Página no encontrada'))

// --- Sesión restaurada desde localStorage -----------------------------------
global.localStorage.setItem('erp.token', 'token-previo')
const restored = await renderAt('/libros')
check('token guardado restaura la sesión', restored.text().includes('Cien años de soledad'))
check('el menú lateral se monta con sesión', Boolean(restored.container.querySelector('.app-sidebar')))
check('el sidebar agrupa todo bajo Productos', Boolean(restored.container.querySelector('.app-nav-toggle')))
check('el sidebar ofrece el menú de usuario', Boolean(restored.container.querySelector('.sidebar-user-button')))
check('el sidebar ya no tiene Inicio', !restored.text().includes('Inicio'))
check('el sidebar ya no ofrece Talles', !restored.text().includes('Talles'))
check('el sidebar ya no ofrece Tipos de talle', !restored.text().includes('Tipos de talle'))

const navLabels = [...restored.container.querySelectorAll('.app-nav-sublist .app-nav-link')].map(
  (a) => a.textContent.trim(),
)
check('el desplegable abre con Libros', navLabels[0] === 'Libros')
check('el desplegable sigue con Cargar libro', navLabels[1] === 'Cargar libro')
check(
  'el desplegable tiene los 6 maestros en orden',
  JSON.stringify(navLabels.slice(2)) ===
    JSON.stringify(['Categorías', 'Marcas', 'Editoriales', 'Modelos', 'Colecciones', 'Colores']),
)

const navHrefs = [...restored.container.querySelectorAll('.app-nav-sublink, .app-nav-link--child')].map(
  (a) => a.getAttribute('href'),
)
check('el desplegable enlaza a editoriales', navHrefs.includes('/maestros/editoriales'))
check('el desplegable enlaza a colecciones', navHrefs.includes('/maestros/colecciones'))
check('el desplegable no enlaza a talles', !navHrefs.includes('/maestros/talles'))

// El desplegable se puede plegar y recuerda el estado.
const toggle = restored.container.querySelector('.app-nav-toggle')
check('el desplegable arranca desplegado', toggle.getAttribute('aria-expanded') === 'true')

await act(async () => {
  toggle.dispatchEvent(new dom.window.Event('click', { bubbles: true }))
})
check('el desplegable se pliega al click', restored.container.querySelector('.app-nav-toggle').getAttribute('aria-expanded') === 'false')
check('el desplegable recuerda estar plegado', global.localStorage.getItem('erp.nav.productos') === '0')

await act(async () => {
  restored.container.querySelector('.app-nav-toggle').dispatchEvent(
    new dom.window.Event('click', { bubbles: true }),
  )
})
check('el desplegable vuelve a abrirse', restored.container.querySelector('.app-nav-toggle').getAttribute('aria-expanded') === 'true')
const restoreCall = calls.find((c) => c.url.endsWith('/api/user'))
check('valida el token previo contra GET /api/user', Boolean(restoreCall))
check('envía el token previo como Bearer', restoreCall?.headers?.Authorization === 'Bearer token-previo')
check(
  'con sesión activa /login expulsa al catálogo',
  (await renderAt('/login')).text().includes('Cien años de soledad'),
)
check(
  'con sesión activa /dashboard redirige al catálogo',
  (await renderAt('/dashboard')).text().includes('Cien años de soledad'),
)
check(
  'la raíz redirige al catálogo',
  (await renderAt('/')).text().includes('Cien años de soledad'),
)

// --- Login exitoso ----------------------------------------------------------
global.localStorage.removeItem('erp.token')
calls.length = 0

const flow = await renderAt('/login')
await fill(flow, { email: 'ana@erp.test', password: 'secreta' })

check('el input controlado refleja lo tipeado', flow.field('email').value === 'ana@erp.test')

await submitForm(flow)

check('guarda el token de Sanctum', global.localStorage.getItem('erp.token') === 'token-falso')
check('navega al catálogo sin salir de la SPA', flow.text().includes('Cien años de soledad'))
check('deja de mostrar el login', !flow.text().includes('¡Bienvenido nuevamente!'))

const loginCall = calls.find((c) => c.url.endsWith('/api/login'))
check('el login se consume contra la API del backend', Boolean(loginCall))
check('el login envía usuario y contraseña', loginCall?.body?.email === 'ana@erp.test' && loginCall?.body?.password === 'secreta')
check('el login envía remember en 0 por defecto', loginCall?.body?.remember === 0)

// --- Login fallido ----------------------------------------------------------
loginResponse = {
  ok: false,
  status: 422,
  body: {
    message: 'El correo o la contraseña no son correctos.',
    errors: { email: ['El correo o la contraseña no son correctos.'] },
  },
}

// El test anterior dejó una sesión activa: hay que salir de ella para poder
// volver a mostrar el formulario de login.
global.localStorage.removeItem('erp.token')

const failed = await renderAt('/login')
await fill(failed, { email: 'ana@erp.test', password: 'mala' })
await submitForm(failed)

check('muestra el error de la API', failed.text().includes('El correo o la contraseña no son correctos.'))
check('marca el campo con is-invalid', Boolean(failed.container.querySelector('.is-invalid')))
check('no guarda token si el login falla', global.localStorage.getItem('erp.token') === null)
check('permanece en el login', failed.text().includes('¡Bienvenido nuevamente!'))

// --- Logout -----------------------------------------------------------------
global.localStorage.setItem('erp.token', 'token-falso')
const session = await renderAt('/libros')

// El cierre de sesión vive en el menú de usuario del sidebar: hay que abrirlo.
await act(async () => {
  session.container.querySelector('.sidebar-user-button').dispatchEvent(
    new dom.window.Event('click', { bubbles: true }),
  )
})

check('el menú de usuario se abre', session.text().includes('Cerrar sesión'))

await act(async () => {
  const button = [...session.container.querySelectorAll('button')].find((b) =>
    b.textContent.includes('Cerrar sesión'),
  )
  button.dispatchEvent(new dom.window.Event('click', { bubbles: true }))
})

check('el logout borra el token local', global.localStorage.getItem('erp.token') === null)
check('el logout vuelve al login', session.text().includes('¡Bienvenido nuevamente!'))

// --- Alta de usuario --------------------------------------------------------
global.localStorage.clear()
global.sessionStorage.clear()
calls.length = 0

const alta = await renderAt('/register')
await fill(alta, { first_name: 'Juan', last_name: 'Pérez', email: 'jperez@empresa.com' })
await submitForm(alta)

const registerCall = calls.find((c) => c.url.endsWith('/api/register'))
check('el alta se consume contra POST /api/register', Boolean(registerCall))
check(
  'el alta envía nombre, apellido y correo',
  registerCall?.body?.first_name === 'Juan' &&
    registerCall?.body?.last_name === 'Pérez' &&
    registerCall?.body?.email === 'jperez@empresa.com',
)
check('el alta deja la sesión iniciada', global.localStorage.getItem('erp.token') === 'token-registro')
check('el alta navega al catálogo', alta.text().includes('Cien años de soledad'))
check('el alta avisa la contraseña inicial', alta.text().includes('ERP123'))
check('el alta no vuelve a mostrar el formulario', !alta.text().includes('Creá tu usuario'))
check('el aviso se consume una sola vez', global.sessionStorage.getItem('erp.notice') === null)

// El aviso ya se consumió: recargar no debe volver a mostrarlo.
const afterReload = await renderAt('/libros')
check('el aviso no reaparece al recargar', !afterReload.text().includes('ERP123'))

// --- Alta duplicada ---------------------------------------------------------
global.localStorage.clear()
global.sessionStorage.clear()

registerResponse = {
  ok: false,
  status: 422,
  body: {
    message: 'Ya existe un usuario con ese correo electrónico.',
    errors: { email: ['Ya existe un usuario con ese correo electrónico.'] },
  },
}

const duplicate = await renderAt('/register')
await fill(duplicate, { first_name: 'Juan', last_name: 'Pérez', email: 'jperez@empresa.com' })
await submitForm(duplicate)

check('el alta duplicada muestra el error del backend', duplicate.text().includes('Ya existe un usuario con ese correo electrónico.'))
check('el alta duplicada marca el correo como inválido', Boolean(duplicate.container.querySelector('input[name="email"].is-invalid')))
check('el alta duplicada no guarda token', global.localStorage.getItem('erp.token') === null)
check('el alta duplicada permanece en el formulario', duplicate.text().includes('Creá tu usuario'))

// --- Catalogo de libros -----------------------------------------------------
global.localStorage.clear()
global.localStorage.setItem('erp.token', 'token-falso')
calls.length = 0

const books = await renderAt('/libros')
check('el listado muestra el menu lateral', Boolean(books.container.querySelector('.app-sidebar')))
check('el listado muestra el titulo del libro', books.text().includes('Cien años de soledad'))
check('el listado formatea el precio', books.text().includes('$'))
check('el listado muestra el deposito', books.text().includes('Central'))
// Las acciones son botones cuadrados de sólo ícono: el texto va en title/aria-label.
const actionButtons = [...books.container.querySelectorAll('.row-actions .erp-btn')]
check('el listado tiene 3 acciones por libro', actionButtons.length === 3)
check('el listado ofrece administrar', books.container.querySelector('[aria-label^="Administrar"]') !== null)
check('el listado ofrece editar', books.container.querySelector('[aria-label^="Editar"]') !== null)
check('el listado ofrece dar de baja', books.container.querySelector('[aria-label^="Dar de baja"]') !== null)
check('los botones de acción son cuadrados', Boolean(books.container.querySelector('.erp-btn--icon')))
check(
  'el botón de actualizar es cuadrado y sólo ícono',
  Boolean(books.container.querySelector('.page-actions .erp-btn--icon')),
)
check('el listado filtra por categoria', books.text().includes('Todas las categorías'))
check('el listado filtra por marca', books.text().includes('Todas las marcas'))
check('el listado filtra por estado', books.text().includes('Todos los estados'))

const listCall = calls.find((c) => c.url.includes('/api/products?'))
check('el listado consulta GET /api/products', Boolean(listCall))
check('el listado pide paginacion de 20', listCall?.url.includes('per_page=20'))

const detail = await renderAt('/libros/7')
check('el detalle muestra la sinopsis', detail.text().includes('La saga de los Buendia.'))
check('el detalle resuelve la categoria', detail.text().includes('Novela'))
check('el detalle muestra las listas de precio', detail.text().includes('Precio A'))
check('el detalle ofrece editar', detail.text().includes('Editar'))

const form = await renderAt('/libros/nuevo')
check('el formulario pide el titulo', form.text().includes('Título'))
check('el formulario pide el ISBN', form.text().includes('ISBN'))
check('el formulario pide la sinopsis', form.text().includes('Sinopsis'))
check('el formulario pide la aliquota', form.text().includes('Alícuota de IVA'))
check('el formulario pide la editorial', form.text().includes('Editorial'))
check('el formulario pide la colección', form.text().includes('Colección'))
check('el formulario pide el stock', form.text().includes('Stock actual'))
check('el formulario muestra el precio calculado', form.text().includes('Precio final A'))

await fill(form, { name: 'El amor en los tiempos del colera' })
check('el titulo se refleja en el input', form.field('name').value === 'El amor en los tiempos del colera')

const edit = await renderAt('/libros/7/editar')
check('la edicion precarga el titulo', edit.field('name').value === 'Cien años de soledad')
check('la edicion precarga el ISBN', edit.field('bar_code').value === '9788497592228')
check('la edicion precarga la sinopsis', edit.text().includes('La saga de los Buendia.'))

const masters = await renderAt('/maestros/categorias')
check('el CRUD de maestros se monta', masters.text().includes('Categorías'))
check('el CRUD ofrece crear', masters.text().includes('Nueva categoría'))
check('el CRUD lista la columna Estado', masters.text().includes('Estado'))

const publishers = await renderAt('/maestros/editoriales')
check('el CRUD de editoriales se monta', publishers.text().includes('Editoriales'))
check('el CRUD de editoriales ofrece crear', publishers.text().includes('Nueva editorial'))

const collections = await renderAt('/maestros/colecciones')
check('el CRUD de colecciones se monta', collections.text().includes('Colecciones'))
check('el CRUD de colecciones ofrece crear', collections.text().includes('Nueva colección'))

// --- El formulario de maestro no manda null a columnas NOT NULL --------------
// web_order es NOT NULL DEFAULT 0: mandarlo en null hace fallar el insert.
const { createMasterItem } = await import('../src/features/books/api.js')
calls.length = 0

await act(async () => {
  collections.container.querySelector('.page-actions button').dispatchEvent(
    new dom.window.Event('click', { bubbles: true }),
  )
})

const modal = collections.container.querySelector('.modal-card--form')
check('se abre el modal de alta de colección', Boolean(modal))

const nameInput = modal.querySelector('input[name="name"]')
const setter = Object.getOwnPropertyDescriptor(
  Object.getPrototypeOf(nameInput),
  'value',
).set

await act(async () => {
  setter.call(nameInput, 'Probe desde el test')
  nameInput.dispatchEvent(new dom.window.Event('input', { bubbles: true }))
})

// Se deja el campo Orden vacio a proposito.
const orderInput = modal.querySelector('input[name="web_order"]')
check('el campo Orden arranca vacio', orderInput.value === '')

await act(async () => {
  modal.querySelector('form').dispatchEvent(
    new dom.window.Event('submit', { bubbles: true, cancelable: true }),
  )
})

const masterCall = calls.find((c) => c.url.endsWith('/api/collections') && c.method === 'POST')
check('el alta de colección llega a la API', Boolean(masterCall))
check('la colección manda su nombre', masterCall?.body?.name === 'Probe desde el test')
check('el campo vacío NO se manda como null (rompería el insert)', masterCall?.body?.web_order === 0)
check('los selects vacíos sí se mandan como null', masterCall?.body?.publisher_id === null)
check('el switch llega como booleano', masterCall?.body?.is_active === true)

// --- El formulario envía el payload que espera la API ----------------------
global.localStorage.clear()
global.localStorage.setItem('erp.token', 'token-falso')
calls.length = 0

const newBook = await renderAt('/libros/nuevo')
await fill(newBook, {
  name: 'Rayuela',
  bar_code: '9788437604572',
  description: 'Novela de Cortazar.',
  price_a: '20',
  current_stock: '7',
})
await submitForm(newBook)

const createCall = calls.find((c) => c.url.endsWith('/api/products') && c.method === 'POST')
check('el alta de libro va a POST /api/products', Boolean(createCall))
check('el alta manda el nombre', createCall?.body?.name === 'Rayuela')
check('el alta manda el ISBN en bar_code', createCall?.body?.bar_code === '9788437604572')
check('el alta manda la sinopsis en description', createCall?.body?.description === 'Novela de Cortazar.')
check('el alta manda el stock', createCall?.body?.current_stock === '7')
check('el alta activa el calculo de impuestos', createCall?.body?.auto_calculate_tax === true)
check('el alta manda la alicuota por defecto', createCall?.body?.aliquot_name === 'IVA 21%')
check('el alta manda is_active', createCall?.body?.is_active === true)
check('el alta manda image_urls como array', Array.isArray(createCall?.body?.image_urls))
check('los selects vacíos se mandan como null', createCall?.body?.category_id === null)
check('el payload no manda size_id (los libros no tienen talle)', createCall?.body?.size_id === undefined)

// --- Serializacion multipart (subida de portadas) --------------------------
const { createBook, updateBook } = await import('../src/features/books/api.js')
calls.length = 0

const formValues = {
  name: 'Libro con portada',
  bar_code: '111',
  has_variants: false,
  auto_calculate_tax: true,
  is_active: true,
  current_stock: '5',
}

// Se simula el archivo que devuelve el input[type=file].
const fakeFile = new dom.window.File(['bytes'], 'portada.png', { type: 'image/png' })

await createBook(formValues, { files: [fakeFile] })

const multipartCall = calls.find((c) => c.url.endsWith('/api/products') && c.method === 'POST')
const sent = multipartCall?.body

check('con portada la peticion va en multipart', sent instanceof dom.window.FormData)
check('multipart manda el archivo como images[]', sent?.getAll('images[]').length === 1)
// Laravel solo acepta 1/0 para booleanos: "true"/"false" rompe la validacion.
check('multipart manda has_variants como 0', sent?.get('has_variants') === '0')
check('multipart manda auto_calculate_tax como 1', sent?.get('auto_calculate_tax') === '1')
check('multipart manda is_active como 1', sent?.get('is_active') === '1')
check('multipart manda el nombre', sent?.get('name') === 'Libro con portada')
check('multipart manda el stock', sent?.get('current_stock') === '5')
check(
  'multipart no manda el Content-Type (lo define el boundary)',
  !multipartCall?.headers?.['Content-Type'],
)

await updateBook(9, formValues, { files: [fakeFile] })
const multipartUpdate = calls.find((c) => c.url.endsWith('/api/products/9') && c.method === 'PUT')
check('la edicion con portada tambien va en multipart', multipartUpdate?.body instanceof dom.window.FormData)

const failedCount = checks.filter(([, ok]) => !ok).length
console.log(`\n${checks.length - failedCount}/${checks.length} verificaciones correctas`)
process.exit(failedCount === 0 ? 0 : 1)
