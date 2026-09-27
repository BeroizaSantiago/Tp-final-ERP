import { useId } from 'react'
import { Link } from 'react-router-dom'

import { Icon } from './icons'

/**
 * Campo de formulario.
 *
 * Envuelve label + control + mensaje de error y conecta los atributos ARIA,
 * de modo que cada control del formulario de libros comparta el mismo markup.
 */
export function Field({ label, error, hint, required, children, htmlFor, className = '' }) {
  return (
    <div className={`erp-field-block ${className}`.trim()}>
      {label ? (
        <label className="erp-label" htmlFor={htmlFor}>
          {label}
          {required ? <span className="erp-required" aria-hidden="true"> *</span> : null}
        </label>
      ) : null}

      {children}

      {error ? <div className="erp-invalid-feedback">{error}</div> : null}
      {!error && hint ? <small className="erp-hint">{hint}</small> : null}
    </div>
  )
}

export function TextInput({ label, error, hint, required, className = '', ...props }) {
  const generatedId = useId()
  const id = props.id ?? generatedId

  return (
    <Field label={label} error={error} hint={hint} required={required} htmlFor={id} className={className}>
      <input
        {...props}
        id={id}
        className={`erp-control ${error ? 'is-invalid' : ''} ${props.className ?? ''}`.trim()}
      />
    </Field>
  )
}

export function Textarea({ label, error, hint, required, rows = 4, className = '', ...props }) {
  const generatedId = useId()
  const id = props.id ?? generatedId

  return (
    <Field label={label} error={error} hint={hint} required={required} htmlFor={id} className={className}>
      <textarea
        {...props}
        id={id}
        rows={rows}
        className={`erp-control erp-textarea ${error ? 'is-invalid' : ''} ${props.className ?? ''}`.trim()}
      />
    </Field>
  )
}

export function Select({ label, error, hint, required, options = [], placeholder, className = '', ...props }) {
  const generatedId = useId()
  const id = props.id ?? generatedId

  return (
    <Field label={label} error={error} hint={hint} required={required} htmlFor={id} className={className}>
      <select
        {...props}
        id={id}
        className={`erp-control erp-select ${error ? 'is-invalid' : ''} ${props.className ?? ''}`.trim()}
      >
        <option value="">{placeholder ?? 'Seleccionar…'}</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </Field>
  )
}

/**
 * Interruptor.
 *
 * Reemplaza el `form-check` de Bootstrap: el input nativo queda oculto y se
 * dibuja una pista con el estado, para que también funcione en mobile.
 */
export function Switch({ label, checked, onChange, hint, disabled, id: providedId }) {
  const generatedId = useId()
  const id = providedId ?? generatedId

  return (
    <div className="erp-switch-row">
      <label className="erp-switch" htmlFor={id}>
        <input
          id={id}
          type="checkbox"
          role="switch"
          checked={checked}
          onChange={onChange}
          disabled={disabled}
        />
        <span className="erp-switch-track" aria-hidden="true">
          <span className="erp-switch-thumb" />
        </span>
        <span className="erp-switch-label">{label}</span>
      </label>

      {hint ? <small className="erp-hint">{hint}</small> : null}
    </div>
  )
}

/** Grupo de campos en dos columnas que colapsa en pantallas angostas. */
export function FieldRow({ children, columns = 2 }) {
  return (
    <div
      className="erp-field-row"
      style={{ '--erp-field-columns': columns }}
    >
      {children}
    </div>
  )
}

/** Encabezado de sección del formulario. */
export function FormSection({ title, description, children }) {
  return (
    <section className="form-section">
      <div className="form-section-head">
        <h3 className="form-section-title">{title}</h3>
        {description ? <p className="form-section-text">{description}</p> : null}
      </div>
      <div className="form-section-body">{children}</div>
    </section>
  )
}

/** Botón de acción con icono, para la cabecera de las pantallas. */
export function PageHeader({ title, subtitle, actions }) {
  return (
    <div className="page-head">
      <div>
        <h1 className="page-title">{title}</h1>
        {subtitle ? <p className="page-subtitle">{subtitle}</p> : null}
      </div>

      {actions ? <div className="page-actions">{actions}</div> : null}
    </div>
  )
}

/** Enlace con icono hacia atrás, usado en detalle y edición. */
export function BackLink({ to, children }) {
  return (
    <Link className="back-link" to={to}>
      <Icon name="back" size={16} />
      {children}
    </Link>
  )
}
