/**
 * Rutas compartidas.
 *
 * Viven aparte de `App.jsx` para que las páginas puedan usar el destino por
 * defecto sin importar el componente de rutas, que las importa a ellas y
 * generaría un ciclo.
 */

/** Destino al entrar y tras iniciar sesión: el catálogo de libros. */
export const HOME_PATH = '/libros'
