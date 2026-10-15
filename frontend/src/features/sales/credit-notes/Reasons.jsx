/* Vista: Motivos de nota de crédito. Permite crear, editar y consultar motivos comerciales. */
import { useState, useEffect } from 'react';

const CreditNoteReasons = () => {
  const [reasons, setReasons] = useState([]);
  const [title, setTitle] = useState('Motivos de nota de crédito');
  const [subtitle, setSubtitle] = useState('Administración de los motivos disponibles para emitir notas de crédito');

  useEffect(() => {
    // Cargar motivos desde la API
    fetch(`${window.APP_BASE_URL}/api/credit-note-reasons`)
      .then((r) => r.json())
      .then((data) => {
        setReasons(data.data ?? data);
      })
      .catch((error) => {
        console.error('Error loading reasons:', error);
      });
  }, []);

  const handleCreate = () => {
    // Lógica para crear un nuevo motivo
    // Esto typically abriría un modal o navegaría a un formulario
    alert('Función de creación de motivo pending');
  };

  const handleEdit = (reasonId) => {
    // Lógica para editar un motivo
    // Esto típicamente navegaría a un formulario de edición
    alert(`Editar motivo ${reasonId} pending`);
  };

  const handleDelete = (reasonId) => {
    if (window.confirm('¿Seguro que querés eliminar este motivo?')) {
      fetch(`${window.APP_BASE_URL}/api/credit-note-reasons/${reasonId}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json' },
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            setReasons(data.reasons || []);
            alert('Motivo eliminado correctamente.');
          } else {
            alert(data.message || 'Error al eliminar el motivo.');
          }
        })
        .catch((error) => {
          alert(error.message || 'Error al eliminar el motivo.');
        });
    }
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4>{title}</h4>
          <small>{subtitle}</small>
        </div>
        {/* <x-action-button action="create" :href="url('/api/credit-note-reasons')" /> */}
      </div>

      <div className="card">
        <div className="card-body">
          {reasons.length === 0 ? (
            <p className="text-muted">
              No hay motivos de nota de crédito registrados.
            </p>
          ) : (
            <table className="table table-striped">
              <thead>
                <tr>
                  <th>Motivo</th>
                  <th>Activo</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {reasons.map((reason) => (
                  <tr key={reason.id}>
                    <td>{reason.name}</td>
                    <td>
                      {reason.is_active
                        ? <span className="badge bg-label-success">Sí</span>
                        : <span className="badge bg-label-danger">No</span>}
                    </td>
                    <td>
                      <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => handleEdit(reason.id)}
                      >
                        Editar
                      </button>
                      <button
                        className="btn btn-sm btn-outline-danger"
                        onClick={() => handleDelete(reason.id)}
                      >
                        Eliminar
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
};

export default CreditNoteReasons;