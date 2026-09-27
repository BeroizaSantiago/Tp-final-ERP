import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

import { AuthFooterLink, AuthLayout } from '../components/AuthLayout'
import { Icon } from '../components/icons'
import { useAuth } from '../context/AuthContext'
import { ApiError } from '../lib/api'
import { setNotice } from '../lib/notice'
import { HOME_PATH } from '../lib/routes'

const EMPTY_FORM = { first_name: '', last_name: '', email: '' }

/**
 * Alta de usuario.
 *
 * Comparte el layout y los estilos del login. A diferencia del alta Blade,
 * pide nombre y apellido por separado (que es como los guarda el modelo) y
 * mantiene la regla del ERP de asignar la contraseña inicial.
 */
export function RegisterPage() {
  const { register } = useAuth()
  const navigate = useNavigate()

  const [form, setForm] = useState(EMPTY_FORM)
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

    if (errors[field] || alert) {
      clearFeedback()
    }
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    setSubmitting(true)
    clearFeedback()

    try {
      const response = await register({
        firstName: form.first_name.trim(),
        lastName: form.last_name.trim(),
        email: form.email.trim(),
      })

      // La contraseña inicial se muestra una sola vez, en el aviso del layout.
      setNotice({
        type: 'info',
        title: 'Tu cuenta fue creada',
        message: `Tu contraseña inicial es ${response.initial_password}. Cámbiala desde tu perfil.`,
      })

      navigate(HOME_PATH, { replace: true })
    } catch (error) {
      const fieldErrors =
        error instanceof ApiError
          ? {
              first_name: error.fieldError('first_name'),
              last_name: error.fieldError('last_name'),
              email: error.fieldError('email'),
            }
          : {}

      setErrors(fieldErrors)

      if (!fieldErrors.first_name && !fieldErrors.last_name && !fieldErrors.email) {
        setAlert(
          error instanceof ApiError
            ? error.message
            : 'Ocurrió un error inesperado. Intentá nuevamente.',
        )
      }

      setSubmitting(false)
    }
  }

  return (
    <AuthLayout
      kicker={{ icon: 'userAdd', text: 'Alta de usuario' }}
      title="Creá tu usuario"
      subtitle="Completá los datos para empezar a gestionar tu negocio."
      footer={
        <AuthFooterLink
          question="¿Ya tenés usuario?"
          action="Iniciá sesión"
          to="/login"
        />
      }
    >
      <div className="erp-login-hint">
        <Icon name="shieldCheck" />
        <span>
          Tu cuenta se crea activa con el rol <strong>Vendedor</strong> y la contraseña
          inicial que define el ERP.
        </span>
      </div>

      {alert ? (
        <div className="erp-alert" role="alert">
          {alert}
        </div>
      ) : null}

      <form onSubmit={handleSubmit} noValidate>
        <div className="erp-field-row">
          <div className="erp-field">
            <label className="erp-label" htmlFor="first_name">
              Nombre
            </label>
            <input
              className={`erp-control erp-login-input${errors.first_name ? ' is-invalid' : ''}`}
              id="first_name"
              name="first_name"
              type="text"
              value={form.first_name}
              onChange={handleChange('first_name')}
              placeholder="Juan"
              autoComplete="given-name"
              autoFocus
              required
            />
            {errors.first_name ? (
              <div className="erp-invalid-feedback">{errors.first_name}</div>
            ) : null}
          </div>

          <div className="erp-field">
            <label className="erp-label" htmlFor="last_name">
              Apellido
            </label>
            <input
              className={`erp-control erp-login-input${errors.last_name ? ' is-invalid' : ''}`}
              id="last_name"
              name="last_name"
              type="text"
              value={form.last_name}
              onChange={handleChange('last_name')}
              placeholder="Pérez"
              autoComplete="family-name"
              required
            />
            {errors.last_name ? (
              <div className="erp-invalid-feedback">{errors.last_name}</div>
            ) : null}
          </div>
        </div>

        <div className="erp-field">
          <label className="erp-label" htmlFor="email">
            Correo electrónico
          </label>
          <input
            className={`erp-control erp-login-input${errors.email ? ' is-invalid' : ''}`}
            id="email"
            name="email"
            type="email"
            value={form.email}
            onChange={handleChange('email')}
            placeholder="jperez@empresa.com"
            autoComplete="email"
            required
          />
          {errors.email ? <div className="erp-invalid-feedback">{errors.email}</div> : null}
        </div>

        <button
          className="erp-btn erp-btn-primary erp-login-submit"
          type="submit"
          disabled={submitting}
        >
          {submitting ? 'Creando usuario…' : 'Crear usuario'}
          {submitting ? null : <Icon name="arrowRight" className="erp-login-submit-icon" />}
        </button>
      </form>
    </AuthLayout>
  )
}
