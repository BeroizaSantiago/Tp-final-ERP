/* Vista: Nueva Nota de Crédito. Muestra el formulario para crear una Nota de Crédito. */
import { useState, useEffect, useRef } from 'react';
import { Swal } from 'sweetalert';

const CreditNoteCreate = () => {
  const [sourceInvoice, setSourceInvoice] = useState(null);
  const [idempotencyKey] = useState(() =>
    crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`
  );
  const [alertBox, setAlertBox] = useState(document.createElement('div'));
  const [money] = useState(() => (value) =>
    Number(value || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })
  );

  const esc = (value) => {
    const el = document.createElement('div');
    el.textContent = value ?? '';
    return el.innerHTML;
  };

  const showAlert = (message, type = 'danger') => {
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
      alertBox.innerHTML = `<div class="alert alert-${type}">${esc(message)}</div>`;
    }
  };

  const moneyFormat = (value) =>
    Number(value || 0).toLocaleString('es-AR', {
      style: 'currency',
      currency: 'ARS',
    });

  const variantText = (item) => {
    const size = item.variant?.size?.name || item.size_name;
    const color = item.variant?.color?.name || item.color_name;
    return [size, color].filter(Boolean).join(' · ') || 'Sin variante';
  };

  const lineValues = (item, quantity) => {
    const gross = quantity * Number(item.unit_price_with_taxes || 0);
    const total = gross * (1 - Number(item.discount_percentage || 0) / 100);
    const rate = Number(item.tax_aliquot_percentage || 0);
    const net = rate > 0 ? total / (1 + rate / 100) : total;
    return { net, tax: total - net, total };
  };

  const renderItems = () => {
    const itemsRows = document.getElementById('itemsRows');
    if (!itemsRows) return;

    const items = sourceInvoice?.items || [];
    itemsRows.innerHTML = items
      .map((item) => {
        const max = Number(item.remaining_credit_quantity || 0);
        const disabled = max <= 0;
        return `
          <tr class="${max <= 0 ? 'opacity-50' : ''}">
            <td>
              <strong>${esc(item.product_name || item.description)}</strong>
              <br>
              <small class="text-muted">${esc(item.product_code || item.product_barcode || '')}</small>
            </td>
            <td>${esc(variantText(item))}</td>
            <td class="text-end">${Number(item.quantity)}</td>
            <td class="text-end"><strong>${max}</strong></td>
            <td>
              <input
                className="form-control credit-qty"
                data-item-id="${item.id}"
                type="number"
                min="0"
                max="${max}"
                step="0.01"
                value="0"
                ${disabled ? 'disabled' : ''}
              />
            </td>
            <td class="text-end">${moneyFormat(item.unit_price_with_taxes)}</td>
            <td class="text-end fw-semibold" id="line-total-${item.id}">
              ${moneyFormat(0)}
            </td>
          </tr>
        `;
      })
      .join('') ||
      '<tr><td colspan="7" class="text-center text-muted py-5">La factura no tiene productos acreditables.</td></tr>';

    // Recalcular totales
    document.querySelectorAll('.credit-qty').forEach((input) => {
      input.addEventListener('input', recalculate);
    });
    recalculate();
  };

  const recalculate = () => {
    let net = 0;
    let tax = 0;
    let total = 0;

    document.querySelectorAll('.credit-qty').forEach((input) => {
      const itemId = input.dataset.itemId;
      const inputValue = Number(input.value || 0);
      const item = sourceInvoice?.items?.find((i) => i.id === Number(itemId));

      if (!item) return;

      const qty = Math.min(inputValue, Number(item.remaining_credit_quantity || 0));
      const values = lineValues(item, qty);

      net += values.net;
      tax += values.tax;
      total += values.total;

      const lineTotalEl = document.getElementById(`line-total-${itemId}`);
      if (lineTotalEl) {
        lineTotalEl.replaceChildren(document.createTextNode(moneyFormat(values.total)));
      }
    });

    const netTotal = document.getElementById('netTotal');
    const taxTotal = document.getElementById('taxTotal');
    const grandTotal = document.getElementById('grandTotal');

    if (netTotal) netTotal.textContent = moneyFormat(net);
    if (taxTotal) taxTotal.textContent = moneyFormat(tax);
    if (grandTotal) grandTotal.textContent = moneyFormat(total);
  };

  useEffect(() => {
    issue_date.value = new Date()
      .toLocaleDateString('en-CA');
    loadReasons();
    // erpEnhanceRemoteSelect(source_invoice_id, { minimum: 1 });
  }, []);

  const loadReasons = async () => {
    try {
      const response = await fetch(
        `${window.APP_BASE_URL}/api/credit-note-reasons?per_page=100`,
        { headers: { Accept: 'application/json' } }
      );
      const data = await response.json();
      const reasonSelect = document.getElementById('reason_id');
      if (reasonSelect) {
        reasonSelect.innerHTML = '<option value="">Seleccionar...</option>' +
          (data.data || data)
            .filter((reason) => reason.is_active !== false)
            .map((reason) => `<option value="${reason.id}">${esc(reason.name)}</option>`)
            .join('');
      }
    } catch (error) {
      console.error('Error loading reasons:', error);
    }
  };

  const loadInvoice = async (id) => {
    const itemsRows = document.getElementById('itemsRows');
    if (!itemsRows) return;

    itemsRows.innerHTML = '<tr><td colspan="7" class="text-center py-5"><span className="spinner-border spinner-border-sm me-2"></span>Cargando factura...</span></tr>';

    try {
      const response = await fetch(
        `${window.APP_BASE_URL}/api/credit-notes/source-invoices/${id}`,
        { headers: { Accept: 'application/json' } }
      );
      const data = await response.json().catch(() => ({}));

      if (!response.ok) throw new Error(data.message || 'No se pudo cargar la factura.');

      setSourceInvoice(data);
      const invoiceSummary = document.getElementById('invoiceSummary');
      if (invoiceSummary) invoiceSummary.classList.remove('d-none');

      const selectAllButton = document.getElementById('selectAllButton');
      const saveButton = document.getElementById('saveButton');
      if (selectAllButton) selectAllButton.disabled = false;
      if (saveButton) saveButton.disabled = false;

      const customerName = document.getElementById('customerName');
      const invoiceNumber = document.getElementById('invoiceNumber');
      const invoiceDate = document.getElementById('invoiceDate');
      const originalTotal = document.getElementById('originalTotal');

      if (customerName) customerName.textContent =
        data.customer_name || data.client?.name || '—';
      if (invoiceNumber) invoiceNumber.textContent =
        data.display_number || data.full_number || `Venta #${data.id}`;
      if (invoiceDate) invoiceDate.textContent = formatDateTime(data.issue_date);
      if (originalTotal) originalTotal.textContent = moneyFormat(data.total_amount);

      renderItems();
    } catch (error) {
      showAlert(error.message);
    }
  };

  const formatDateTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return `${d.getDay().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
  };

  const handleSave = async (event) => {
    event.preventDefault();

    const items = [...document.querySelectorAll('.credit-qty')]
      .map((input) => ({
        original_invoice_item_id: Number(input.dataset.itemId),
        quantity: Number(input.value || 0),
      }))
      .filter((item) => item.quantity > 0);

    if (!sourceInvoice) return showAlert('Seleccioná la factura original.', 'warning');
    if (!document.getElementById('reason_id').value) return showAlert('Seleccioná un motivo.', 'warning');
    if (!items.length) return showAlert('Ingresá una cantidad para al menos un producto.', 'warning');

    const saveButton = document.getElementById('saveButton');
    if (saveButton) {
      saveButton.disabled = true;
      saveButton.innerHTML = '<span className="spinner-border spinner-border-sm me-2"></span>Emitiendo...';
    }

    try {
      const response = await fetch(
        `${window.APP_BASE_URL}/api/credit-notes`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify({
            related_invoice_id: Number(sourceInvoice.id),
            issue_date: document.getElementById('issue_date').value,
            credit_note_reason_id: Number(document.getElementById('reason_id').value),
            idempotency_key: idempotencyKey,
            items,
          }),
        }
      );
      const data = await response.json().catch(() => ({}));

      if (!response.ok) throw new Error(
        data.errors ? Object.values(data.errors).flat()[0] : data.message || 'No se pudo emitir.'
      );

      window.location.href = `${window.APP_BASE_URL}/demo/sales/credit-notes/${data.id}`;
    } catch (error) {
      showAlert(error.message);
    } finally {
      if (saveButton) {
        saveButton.disabled = false;
        saveButton.innerHTML = '<i className="ri-save-line me-1"></i> Emitir Nota de Crédito';
      }
    }
  };

  useEffect(() => {
    const issueDate = document.getElementById('issue_date');
    if (issueDate) {
      issueDate.value = new Date().toLocaleDateString('en-CA');
    }

    const source_invoice_id = document.getElementById('source_invoice_id');
    if (source_invoice_id) {
      // erpEnhanceRemoteSelect(source_invoice_id, { minimum: 1 });
      source_invoice_id.addEventListener('change', async () => {
        if (!source_invoice_id.value) return;
        try {
          await loadInvoice(source_invoice_id.value);
        } catch (error) {
          showAlert(error.message);
        }
      });
    }
  }, []);

  const selectAllButtonHandler = () => {
    document.querySelectorAll('.credit-qty').forEach((input) => {
      if (!input.disabled) input.value = input.max;
    });
    recalculate();
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
          <h4 className="mb-1">Nueva Nota de Crédito</h4>
          <small className="text-muted">
            Devolución total o parcial vinculada a una factura finalizada
          </small>
        </div>
        <button
          className="btn btn-outline-primary btn-sm"
          onClick={() => window.location.href = '/demo/sales/credit-notes'}
        >
          <i className="ri-arrow-left-line me-1" /> Volver
        </button>
      </div>

      <div id="alertBox"></div>

      <form id="creditNoteForm">
        <div className="card mb-4">
          <div className="card-header">
            <h5 className="mb-1">Comprobante original</h5>
            <small className="text-muted">
              Buscá por número de factura o cliente.
            </small>
          </div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-lg-6">
                <label className="form-label" for="source_invoice_id">Factura</label>
                <select
                  className="form-select"
                  id="source_invoice_id"
                  required
                  data-remote-url="/api/credit-notes/source-invoices"
                  data-remote-placeholder="Buscar factura o cliente..."
                  data-remote-minimum="1"
                >
                  <option value="">Cargando...</option>
                </select>
              </div>

              <div className="col-md-3">
                <label className="form-label" for="issue_date">Fecha</label>
                <input
                  type="date"
                  className="form-control"
                  id="issue_date"
                  required
                />
              </div>

              <div className="col-md-3">
                <label className="form-label" for="reason_id">Motivo</label>
                <select
                  className="form-select"
                  id="reason_id"
                  required
                >
                  <option value="">Seleccionar...</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div id="invoiceSummary" className="card mb-4 d-none">
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <small className="text-muted d-block">Cliente</small>
                <strong id="customerName">—</strong>
              </div>
              <div className="col-md-3">
                <small className="text-muted d-block">Factura</small>
                <strong id="invoiceNumber">—</strong>
              </div>
              <div className="col-md-2">
                <small className="text-muted d-block">Fecha</small>
                <strong id="invoiceDate">—</strong>
              </div>
              <div className="col-md-3 text-md-end">
                <small className="text-muted d-block">Total original</small>
                <strong id="originalTotal" className="fs-5">—</strong>
              </div>
            </div>
          </div>
        </div>

        <div className="card mb-4">
          <div
            className="card-header d-flex justify-content-between align-items-center"
          >
            <div>
              <h5 className="mb-1">Productos a acreditar</h5>
              <small className="text-muted">
                Ingresá únicamente la cantidad que vuelve. El máximo descuenta devoluciones anteriores.
              </small>
            </div>
            <button
              id="selectAllButton"
              type="button"
              className="btn btn-outline-primary btn-sm"
              disabled
              onClick={selectAllButtonHandler}
            >
              <i className="ri-checkbox-multiple-line me-1" /> Devolver todo
            </button>
          </div>

          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Variante</th>
                  <th className="text-end">Facturado</th>
                  <th className="text-end">Disponible</th>
                  <th styleName="width:150px">A devolver</th>
                  <th className="text-end">Precio</th>
                  <th className="text-end">Total</th>
                </tr>
              </thead>
              <tbody id="itemsRows">
                <tr>
                  <td colSpan="7" className="text-center text-muted py-5">
                    Seleccioná una factura para cargar sus productos.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div className="row justify-content-end">
          <div className="col-lg-4">
            <div className="card">
              <div className="card-body">
                <div className="d-flex justify-content-between mb-2">
                  <span>Neto</span>
                  <strong id="netTotal">${moneyFormat(0)}</strong>
                </div>
                <div className="d-flex justify-content-between mb-3">
                  <span>IVA</span>
                  <strong id="taxTotal">${moneyFormat(0)}</strong>
                </div>
                <hr />
                <div
                  className="d-flex justify-content-between align-items-center mb-3"
                >
                  <h5 className="mb-0">Total NC</h5>
                  <h3 id="grandTotal" className="mb-0">${moneyFormat(0)}</h3>
                </div>
                <button
                  id="saveButton"
                  className="btn btn-success w-100"
                  disabled
                >
                  <i className="ri-save-line me-1" /> Emitir Nota de Crédito
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
};

export default CreditNoteCreate;