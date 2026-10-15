/* Vista: Detalle de Notas de Crédito. Muestra la información completa de un registro de Notas de Crédito. */
import { useState, useEffect } from 'react';
import { Swal } from 'sweetalert';

const CreditNoteShow = ({ invoiceId }) => {
  const [invoice, setInvoice] = useState(null);

  const money = (value) =>
    Number(value ?? 0).toLocaleString('es-AR', {
      style: 'currency',
      currency: 'ARS',
    });

  useEffect(() => {
    fetch(`${window.APP_BASE_URL}/api/credit-notes/${invoiceId}`)
      .then((r) => r.json())
      .then((data) => {
        setInvoice(data);
      })
      .catch((error) => {
        console.error('Error loading credit note:', error);
      });
  }, [invoiceId]);

  if (!invoice) {
    return <div id="invoiceBox">Cargando nota de crédito...</div>;
  }

  return (
    <div>
      <div className="mb-3">
        <button
          className="btn btn-secondary"
          onClick={() => window.location.href = '/demo/sales/credit-notes'}
        >
          <i className="ri-arrow-left-line" /> Volver
        </button>
        <div>{/* <x-action-button action="back" :href="url('/demo/sales/credit-notes')" /> */}</div>
      </div>

      <div id="invoiceBox">
        <div className="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h4 className="mb-1">
              Nota de Crédito
              <span className="badge bg-label-warning">
                {invoice.status_name ?? ''}
              </span>
            </h4>
            <small className="text-muted">
              {invoice.full_number ?? `${invoice.letter ?? ''}-${invoice.first_number ?? ''}`}
            </small>
          </div>

          <div className="d-flex gap-2">
            <button
              type="button"
              className="btn btn-warning btn-sm"
              id="authorizeArcaButton"
            >
              Autorizar ARCA
            </button>
            <button className="btn btn-success btn-sm">Descargar</button>
            <button className="btn btn-dark btn-sm">Reimprimir</button>
          </div>
        </div>

        <div className="card mb-4">
          <div className="card-body">
            <h5 className="mb-3">Datos del comprobante</h5>

            <div className="row">
              <div className="col-md-4 mb-3">
                <small className="text-muted">Cliente</small><br>
                <strong>{invoice.customer_name ?? '-'}</strong>
              </div>

              <div className="col-md-2 mb-3">
                <small className="text-muted">Comprobante</small><br>
                <strong>
                  {invoice.receipt_type_name ?? 'Nota de Crédito'}
                </strong>
              </div>

              <div className="col-md-2 mb-3">
                <small className="text-muted">Pto. Venta</small><br>
                <strong>{invoice.first_number ?? '-'}</strong>
              </div>

              <div className="col-md-2 mb-3">
                <small className="text-muted">Fecha emisión</small><br>
                <strong>{formatDateTime(invoice.issue_date)}</strong>
              </div>

              <div className="col-md-2 mb-3">
                <small className="text-muted">Relacionado</small><br>
                <strong>
                  {invoice.related_document_full_name ?? '-'}
                </strong>
              </div>

              <div className="col-md-3 mb-3">
                <small className="text-muted">Motivo</small><br>
                <strong>
                  {invoice.credit_note_reason?.name ?? invoice.credit_note_reason_name ?? '-'}
                </strong>
              </div>

              <div className="col-md-3 mb-3">
                <small className="text-muted">CAE</small><br>
                <strong>
                  {invoice.arca_cae ?? (invoice.is_internal_receipt ? 'No aplica' : 'Pendiente')}
                </strong>
              </div>
            </div>
          </div>
        </div>

        <div className="card mb-4">
          <div className="card-header">
            <h5 className="mb-0">Productos ajustados</h5>
          </div>

          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Producto</th>
                  <th className="text-end">Cant.</th>
                  <th className="text-end">P. Unit.</th>
                  <th className="text-end">Desc.</th>
                  <th className="text-end">IVA</th>
                  <th className="text-end">Total</th>
                </tr>
              </thead>

              <tbody>
                {(invoice.items ?? []).map((item) => (
                  <tr key={item.id}>
                    <td>{item.product_code ?? ''}</td>
                    <td>
                      <strong>{item.product_name ?? item.description ?? ''}</strong><br>
                      <small className="text-muted">
                        Talle: {item.variant?.size?.name ?? item.size_name ?? '-'}
                        · Color: {item.variant?.color?.name ?? item.color_name ?? '-'}
                      </small>
                    </td>
                    <td className="text-end">{item.quantity ?? ''}</td>
                    <td className="text-end">{money(item.unit_price_with_taxes)}</td>
                    <td className="text-end">{item.discount_percentage ?? 0}%</td>
                    <td className="text-end">{item.tax_aliquot_percentage ?? 0}%</td>
                    <td className="text-end"><strong>{money(item.total_amount)}</strong></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <div className="row justify-content-end">
          <div className="col-md-5">
            <div className="card">
              <div className="card-body">
                <div className="d-flex justify-content-between mb-2">
                  <span>Gravado</span>
                  <strong>{money(invoice.taxed_amount)}</strong>
                </div>

                <div className="d-flex justify-content-between mb-2">
                  <span>No gravado</span>
                  <strong>{money(invoice.non_taxed_amount)}</strong>
                </div>

                <div className="d-flex justify-content-between mb-3">
                  <span>IVA</span>
                  <strong>{money(invoice.tax_amount)}</strong>
                </div>

                <hr />

                <div className="d-flex justify-content-between align-items-center">
                  <h5 className="mb-0">Total</h5>
                  <h3 className="mb-0">{money(invoice.total_amount)}</h3>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <script>
        const formatDateTime = (date) => {
          if (!date) return '';
          const d = new Date(date);
          return `${d.getDay().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
        };

        document.getElementById('authorizeArcaButton')?.addEventListener('click', (event) => {
          const button = event.currentTarget;
          button.disabled = true;
          button.textContent = 'Autorizando...';

          fetch(`${window.APP_BASE_URL}/api/credit-notes/${invoiceId}/authorize`, {
            method: 'POST',
            headers: {
              Accept: 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                ? document.querySelector('meta[name="csrf-token"]').content
                : '',
            },
          })
            .then((r) => r.json())
            .then((data) => {
              if (!r.ok) throw new Error(data.message ?? 'ARCA rechazó la nota de crédito.');

              Swal.fire({
                icon: 'success',
                title: 'Nota autorizada',
                text: data.message,
              });
              window.location.reload();
            })
            .catch((error) => {
              Swal.fire({
                icon: 'error',
                title: 'No se pudo autorizar',
                text: error.message,
              });
              button.disabled = false;
              button.textContent = 'Autorizar ARCA';
            });
        });
      </script>
    </div>
  );
};

export default CreditNoteShow;