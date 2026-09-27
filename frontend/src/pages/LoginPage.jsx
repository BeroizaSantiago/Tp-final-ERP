import { useRef, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'

import { AuthFooterLink, AuthLayout } from '../components/AuthLayout'
import { Icon, OutlinedIcon } from '../components/icons'
import { HOME_PATH } from '../lib/routes'
import { useAuth } from '../context/AuthContext'
import { ApiError } from '../lib/api'

const EMPTY_FORM = { email: '', password: '' }

/**
 * Pantalla de inicio de sesión.
 *
 * Replica la vista `auth/login.blade.php` y delega la autenticación en
 * POST /api/login, que devuelve un token de Sanctum y los datos del usuario.
 */
export function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const passwordRef = useRef(null)

  const [form, setForm] = useState(EMPTY_FORM)
  const [showPassword, setShowPassword] = useState(false)
  const [remember, setRemember] = useState(false)
  const [errors, setErrors] = useState({})
  const [alert, setAlert] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const clearFeedback = () => {
    setErrors({})
    setAlert('')
  }

  const handleChange = (field) => (event) => {
    const { value } = event.target
    setForm((current) => ({ ...current, [field]: value }))

    // Se borra el error del campo apenas el usuario corrige.
    if (errors[field] || alert) {
      clearFeedback()
    }
  }

  const handleTogglePassword = () => {
    const next = !showPassword
    setShowPassword(next)
    // Igual que en el backend, el foco vuelve al input para no cortar la escritura.
    window.setTimeout(() => passwordRef.current?.focus(), 0)
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    setSubmitting(true)
    clearFeedback()

    try {
      await login({
        email: form.email.trim(),
        password: form.password,
        remember,
      })

      // Navegación interna: la SPA nunca abandona su propio origen.
      navigate(location.state?.from ?? HOME_PATH, { replace: true })
    } catch (error) {
      const fieldErrors =
        error instanceof ApiError
          ? { email: error.fieldError('email'), password: error.fieldError('password') }
          : {}

      setErrors(fieldErrors)

      // Si el backend no señala un campo concreto (red caída, 500) se muestra arriba.
      if (!fieldErrors.email && !fieldErrors.password) {
        setAlert(
          error instanceof ApiError
            ? error.message
            : 'Ocurrió un error inesperado. Intentá nuevamente.',
        )
      }

      setSubmitting(false)
    }
  }

  const passwordLabel = showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'

  return (
    <AuthLayout
      kicker={{ icon: 'shieldCheck', text: 'Acceso seguro' }}
      title="¡Bienvenido nuevamente!"
      subtitle="Ingresá a tu cuenta para continuar gestionando tu negocio."
      footer={
        <AuthFooterLink
          question="¿Todavía no tenés usuario?"
          action="Crear usuario"
          to="/register"
        />
      }
    >
      {alert ? (
        <div className="erp-alert" role="alert">
          {alert}
        </div>
      ) : null}

      <form onSubmit={handleSubmit} noValidate>
        <div className="erp-field">
          <label className="erp-label" htmlFor="email">
            Usuario o correo electrónico
          </label>
          <input
            className={`erp-control erp-login-input${errors.email ? ' is-invalid' : ''}`}
            id="email"
            name="email"
            type="text"
            value={form.email}
            onChange={handleChange('email')}
            placeholder="Ingresá tu usuario o correo"
            autoComplete="username"
            autoFocus
            required
          />
          {errors.email ? <div className="erp-invalid-feedback">{errors.email}</div> : null}
        </div>

        <div className="erp-field erp-field--tight">
          <label className="erp-label" htmlFor="password">
            Contraseña
          </label>
          <div className="erp-password-field">
            <input
              className={`erp-control erp-login-input${errors.password ? ' is-invalid' : ''}`}
              ref={passwordRef}
              id="password"
              name="password"
              type={showPassword ? 'text' : 'password'}
              value={form.password}
              onChange={handleChange('password')}
              placeholder="Ingresá tu contraseña"
              autoComplete="current-password"
              required
            />
            <button
              className="erp-password-toggle"
              type="button"
              onClick={handleTogglePassword}
              aria-label={passwordLabel}
              title={passwordLabel}
            >
              <OutlinedIcon name={showPassword ? 'eye' : 'eyeOff'} size={20} />
            </button>
          </div>
          {errors.password ? <div className="erp-invalid-feedback">{errors.password}</div> : null}
        </div>

        <div className="erp-check-row">
          <input
            className="erp-check-input"
            id="remember"
            name="remember"
            type="checkbox"
            checked={remember}
            onChange={(event) => setRemember(event.target.checked)}
          />
          <label className="erp-check-label" htmlFor="remember">
            Mantener la sesión iniciada
          </label>
        </div>

        <button
          className="erp-btn erp-btn-primary erp-login-submit"
          type="submit"
          disabled={submitting}
        >
          {submitting ? 'Ingresando…' : 'Iniciar sesión'}
          {submitting ? null : <Icon name="arrowRight" className="erp-login-submit-icon" />}
        </button>
      </form>
    </AuthLayout>
  )
}
