import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'

import { BackLink, FormSection, Select, TextInput, Textarea } from '../../components/fields'
import { Icon } from '../../components/icons'
import { useToast } from '../../context/ToastContext'
import { ApiError } from '../../lib/api'
import { RemoteSearchSelect, useRemoteSearch } from './RemoteSearchSelect'
import { createMiscExpense, listExpenseTypes, listStockLocations, searchProviders } from './api'

const RECEIPT_TYPES = ['Factura', 'Ticket', 'Recibo', 'Nota de Crédito', 'Nota de Débito']
const CURRENCIES = ['Pesos', 'Dólares']
const IVA_OPTIONS = [
  { value: '21', label: '21%' },
  { value: '10.5', label: '10.5%' },
  { value: '0', label: 'Exento' },
]

/** Alta de gasto vario, réplica de `misc-expenses/create.blade.php`. */
export function ExpenseFormPage() {
  const navigate = useNavigate()
  const toast = useToast()

  const [values, setValues] = useState({
    provider_id: '',
    expense_type_id: '',
    issue_date: new Date().toISOString().substring(0, 10),
    receipt_type_name: 'Factura',
    receipt_number: '',
    branch_name: '',
    currency_name: 'Pesos',
    description: '',
    net_amount: '0',
    discount_amount: '0',
    surcharge_amount: '0',
    iva: '21',
    notes: '',
  })
  const [provider, setProvider] = useState(null)
  const [expenseTypes, setExpenseTypes] = useState([])
  const [stockLocations, setStockLocations] = useState([])
  const [saving, setSaving] = useState(false)

  const providerSearch = useRemoteSearch(async (q) => {
    const data = await searchProviders(q)

    return (data?.data ?? data ?? []).map((p) => ({
      value: String(p.id),
      label: p.name,
      hint: [p.code, p.identification_number].filter(Boolean).join(' · '),
    }))
  })

  useEffect(() => {
    listExpenseTypes()
      .then((data) => setExpenseTypes(data?.data ?? data ?? []))
      .catch(() => toast.error('No se pudieron cargar los tipos de gasto.'))

    listStockLocations()
      .then((locations) => {
        const list = Array.isArray(locations) ? locations : []

        setStockLocations(list)

        if (list[0]) {
          setValues((current) => ({ ...current, branch_name: list[0].name }))
        }
      })
      .catch(() => toast.error('No se pudieron cargar las sucursales.'))
  }, [])

  const change = (field) => (event) => setValues((current) => ({ ...current, [field]: event.target.value }))

  // Misma fórmula que la vista Blade: neto − descuento + recargo + IVA.
  const tax = useMemo(() => Number(values.net_amount || 0) * (Number(values.iva || 0) / 100), [values.net_amount, values.iva])
  const total = useMemo(
    () => Number(values.net_amount || 0) - Number(values.discount_amount || 0) + Number(values.surcharge_amount || 0) + tax,
    [values.net_amount, values.discount_amount, values.surcharge_amount, tax],
  )

  const handleSubmit = async (event) => {
    event.preventDefault()
    setSaving(true)

    try {
      const expense = await createMiscExpense({
        provider_id: provider ? Number(provider.value) : null,
        expense_type_id: values.expense_type_id ? Number(values.expense_type_id) : null,
        issue_date: values.issue_date,
        receipt_type_name: values.receipt_type_name,
        receipt_number: values.receipt_number || null,
        branch_name: values.branch_name || null,
        currency_name: values.currency_name,
        description: values.description || null,
        net_amount: Number(values.net_amount || 0),
        discount_amount: Number(values.discount_amount || 0),
        surcharge_amount: Number(values.surcharge_amount || 0),
        tax_amount: Number(tax.toFixed(2)),
        notes: values.notes || null,
      })

      toast.success('Gasto registrado', `Comprobante ${expense.receipt_number ?? `#${expense.id}`} creado.`)
      navigate(`/gastos/${expense.id}/pago`, { replace: true })
    } catch (error) {
      toast.error('No se pudo guardar el gasto', error instanceof ApiError ? error.message : 'Ocurrió un error inesperado.')
      setSaving(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="page-head">
        <div>
          <BackLink to="/gastos">Volver a gastos</BackLink>
          <h1 className="page-title">Nuevo gasto vario</h1>
          <p className="page-subtitle">Registrá un gasto por comprobante.</p>
        </div>

        <div className="page-actions">
          <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/gastos')} disabled={saving}>
            Cancelar
          </button>
          <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
            <Icon name="save" size={18} />
            {saving ? 'Guardando…' : 'Continuar'}
          </button>
        </div>
      </div>

      <FormSection title="Comprobante" description="Datos del encabezado del gasto.">
        <div className="form-grid">
          <RemoteSearchSelect
            label="Proveedor"
            placeholder="Buscar proveedor por nombre, código o identificación…"
            search={providerSearch.query}
            onSearch={providerSearch.setQuery}
            results={providerSearch.results}
            loading={providerSearch.loading}
            selected={provider ? { label: provider.label } : null}
            onSelect={setProvider}
          />

          <Select
            name="receipt_type_name"
            label="Tipo comprobante"
            value={values.receipt_type_name}
            onChange={change('receipt_type_name')}
            options={RECEIPT_TYPES.map((value) => ({ value, label: value }))}
          />

          <TextInput name="receipt_number" label="Número" value={values.receipt_number} onChange={change('receipt_number')} />

          <TextInput name="issue_date" label="Fecha" type="date" value={values.issue_date} onChange={change('issue_date')} />

          <Select
            name="branch_name"
            label="Sucursal"
            value={values.branch_name}
            onChange={change('branch_name')}
            options={stockLocations.map((branch) => ({ value: branch.name, label: branch.name }))}
          />

          <Select
            name="currency_name"
            label="Moneda"
            value={values.currency_name}
            onChange={change('currency_name')}
            options={CURRENCIES.map((value) => ({ value, label: value }))}
          />
        </div>
      </FormSection>

      <FormSection title="Detalle del gasto" description="Importes y clasificación del gasto.">
        <div className="form-grid">
          <Select
            name="expense_type_id"
            label="Tipo de gasto"
            value={values.expense_type_id}
            onChange={change('expense_type_id')}
            options={expenseTypes.map((type) => ({ value: String(type.id), label: type.name }))}
            placeholder="Seleccionar…"
          />

          <TextInput
            className="form-span-2"
            name="description"
            label="Descripción"
            value={values.description}
            onChange={change('description')}
          />

          <TextInput name="net_amount" label="Importe neto" type="number" step="0.01" value={values.net_amount} onChange={change('net_amount')} />

          <Select name="iva" label="% IVA" value={values.iva} onChange={change('iva')} options={IVA_OPTIONS} />

          <TextInput name="discount_amount" label="Descuento" type="number" step="0.01" value={values.discount_amount} onChange={change('discount_amount')} />

          <TextInput name="surcharge_amount" label="Recargo" type="number" step="0.01" value={values.surcharge_amount} onChange={change('surcharge_amount')} />

          <TextInput
            name="tax"
            label="IVA"
            value={tax.toFixed(2)}
            readOnly
          />

          <TextInput name="total" label="Total" value={total.toFixed(2)} readOnly />
        </div>
      </FormSection>

      <FormSection title="Observaciones" description="Notas internas del gasto.">
        <Textarea name="notes" label="Observaciones" rows={4} value={values.notes} onChange={change('notes')} />
      </FormSection>

      <div className="form-actions">
        <button className="erp-btn erp-btn-outline" type="button" onClick={() => navigate('/gastos')} disabled={saving}>
          Cancelar
        </button>
        <button className="erp-btn erp-btn-primary" type="submit" disabled={saving}>
          <Icon name="save" size={18} />
          {saving ? 'Guardando…' : 'Continuar'}
        </button>
      </div>
    </form>
  )
}
