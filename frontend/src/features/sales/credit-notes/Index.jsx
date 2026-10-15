/* Vista: Listado de Notas de Crédito. Muestra la consulta principal y las acciones disponibles de Notas de Crédito. */
import { useState, useEffect } from 'react';

const CreditNoteIndex = () => {
  const [creditNotes, setCreditNotes] = useState([]);
  const [search, setSearch] = useState('');

  const money = (value) =>
    Number(value ?? 0).toLocaleString('es-AR', {
      style: 'currency',
      currency: 'ARS',
    });

  const statusBadge = (status) => {
    const text = String(status ?? '').toLowerCase();

    if (text.includes('cob') || text.includes('pag')) {
      return <span className="badge bg-label-success">{status}</span>;
    }

    if (text.includes('pend')) {
      return <span className="badge bg-label-warning">{status}</span>;
    }

    if (text.includes('anu') || text.includes('cancel')) {
      return <span className="badge bg-label-danger">{status}</span>;
    }

    return <span className="bg-label-secondary">{status ?? '-'}</span>;
  };

  const renderInvoices = () => {
    const rows = document.getElementById('rows');
    if (!rows) return;

    if (!creditNotes.length) {
      rows.innerHTML = `
        <tr>
          <td colspan="7" class="text-center py-4 text-muted">
            No hay notas de crédito registradas
          </td>
        </tr>
      `;
      return;
    }

    rows.innerHTML = creditNotes
      .map((invoice) => `
        <tr>
          <td className="text-nowrap">${formatDateTime(invoice.issue_date)}</td>

          <td>
            <strong>${invoice.full_number ?? `${invoice.letter ?? ''}-${invoice.first_number ?? ''}`}</strong>
            <br>
            <small className="text-muted">
              ${invoice.receipt_type_name ?? 'Nota de Crédito'}
            </small>
          </td>

          <td>${invoice.customer_name ?? '-'}</td>

          <td>${invoice.currency_name ?? 'Pesos'}</td>

          <td className="text-end">
            <strong>${money(invoice.total_amount)}</strong>
          </td>

          <td>${statusBadge(invoice.status_name)}</td>

          <td className="text-end">
            <a
              href="${window.APP_BASE_URL}/demo/sales/credit-notes/${invoice.id}"
              className="btn btn-sm btn-primary"
            >
              Ver
            </a>
          </td>
        </tr>
      `).join('');
  };

  const formatDateTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return `${d.getDay().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
  };

  useEffect(() => {
    const searchInput = document.getElementById('search');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase();
        const filtered = creditNotes.filter((invoice) =>
          String(invoice.full_number ?? '').toLowerCase().includes(q) ||
          String(invoice.customer_name ?? '').toLowerCase().includes(q) ||
          String(invoice.receipt_type_name ?? '').toLowerCase().includes(q) ||
          String(invoice.status_name ?? '').toLowerCase().includes(q)
        );
        renderInvoices(filtered);
      });
    }

    const loadInvoices = async () => {
      try {
        const response = await fetch(
          `${window.APP_BASE_URL}/api/credit-notes`
        );
        const data = await response.json();
        setCreditNotes(data.data ?? data);
        renderInvoices();
      } catch (error) {
        console.error(error);
        const rows = document.getElementById('rows');
        if (rows) {
          rows.innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-4 text-danger">
                Error al cargar notas de crédito
              </td>
            </tr>
          `;
        }
      }
    };

    loadInvoices();
  }, [search]);

  return (
    <div>
      <div
        className="d-flex justify-content-between align-items-center mb-4"
      >
        <div>
          <h4 className="mb-1">Notas de Crédito</h4>
          <small className="text-muted">
            Comprobantes emitidos a clientes
          </small>
        </div>

        <a
          href="/demo/sales/credit-notes/create"
          className="btn btn-primary"
        >
          <i className="ri-add-line me-1" /> Nueva nota de crédito
        </a>
      </div>

      <div className="card">
        <div className="card-header d-flex justify-content-between align-items-center">
          <h5 className="mb-0">Listado</h5>

          <input
            type="text"
            id="search"
            className="form-control form-control-sm"
            style="max-width:280px"
            placeholder="Buscar nota de débito..."
            value={search}
          />
        </div>

        <div className="table-responsive">
          <table className="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Comprobante</th>
                <th>Cliente</th>
                <th>Moneda</th>
                <th className="text-end">Total</th>
                <th>Estado</th>
                <th className="text-end">Acciones</th>
              </tr>
            </thead>

            <tbody id="rows">
              {/* Items rendered by renderInvoices */}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default CreditNoteIndex;