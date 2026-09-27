/**
 * Configuración de la aplicación.
 *
 * La SPA es autónoma: todas las vistas (incluido el login) se sirven desde
 * React. Del backend sólo se consumen datos mediante su API, por lo que la
 * única configuración necesaria es la URL de esa API.
 */

const trimTrailingSlash = (value) => String(value ?? '').replace(/\/+$/, '')

export const API_URL = trimTrailingSlash(import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api')

/**
 * Ilustración del panel visual del login, servida por el propio frontend desde
 * `public/` para no depender de los assets del backend.
 */
export const HERO_IMAGE_URL = '/assets/img/auth/erp-login-hero.png'
