@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Vouchers</h4><small class="text-muted">Vouchers físicos y digitales de uso único</small>
    </div>
    <button class="btn btn-primary" onclick="openVoucher()"><i class="ri-add-line me-1"></i> Nuevo voucher</button>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de vouchers</h5><input id="search" class="form-control form-control-sm" style="max-width:320px" placeholder="Buscar por nombre o código">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th class="text-end">Beneficio</th>
                    <th>Formato</th>
                    <th>Canal</th>
                    <th>Vencimiento</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="rows">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Cargando vouchers...</td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<div class="modal fade" id="voucherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="voucherForm">
            <div class="modal-header">
                <h5 class="modal-title">Voucher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><input type="hidden" id="voucher_id">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label">Nombre</label><input id="name" class="form-control" required maxlength="255"></div>
                    <div class="col-md-5"><label class="form-label">Tipo de beneficio</label><select id="value_type" class="form-select">
                            <option value="fixed">Importe fijo</option>
                            <option value="percentage">Porcentaje</option>
                        </select></div>
                    <div class="col-md-6"><label class="form-label" id="amount_label">Importe</label><input id="amount" type="number" min="0.01" step="0.01" class="form-control" required></div>
                    <div class="col-md-6 d-none" id="maximum_discount_group"><label class="form-label">Tope máximo de descuento <span class="text-muted">(opcional)</span></label><input id="maximum_discount_amount" type="number" min="0.01" step="0.01" class="form-control"><small class="text-muted">Si queda vacío, no tendrá tope.</small></div>
                    <div class="col-md-6"><label class="form-label">Formato</label><select id="delivery_type" class="form-select">
                            <option value="physical">Físico</option>
                            <option value="digital">Digital</option>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Canal de venta</label><select id="sales_channel" class="form-select">
                            <option value="store">Local</option>
                            <option value="online">Online</option>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Válido hasta</label><input id="expires_at" type="date" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Mensaje</label><textarea id="message" class="form-control" rows="4" maxlength="2000"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
        </form>
    </div>
</div>
<script>
    let currentPage = 1,
        vouchers = [];
    const modal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('voucherModal'));
    const money = v => new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(Number(v || 0));
    const benefit = v => v.value_type === 'percentage' ? `${Number(v.amount||0).toLocaleString('es-AR')}%${v.maximum_discount_amount?` · Tope ${money(v.maximum_discount_amount)}`:''}` : money(v.amount);
    const statusLabel = s => ({
        available: 'Disponible',
        reserved: 'Reservado',
        used: 'Usado',
        disabled: 'Inhabilitado',
        expired: 'Vencido'
    } [s] || s);
    const dateValue = value => {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[1]}-${match[2]}-${match[3]}` : '';
    };
    const displayDate = value => {
        const normalized = dateValue(value);
        if (!normalized) return '-';
        const [year, month, day] = normalized.split('-');
        return `${day}/${month}/${year}`;
    };
    async function load(page = 1) {
        currentPage = page;
        const rowsBox = document.getElementById('rows');
        const q = encodeURIComponent(document.getElementById('search').value.trim());
        rowsBox.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Cargando vouchers...</td></tr>';
        try {
            const r = await fetch(`${window.APP_BASE_URL}/api/vouchers?page=${page}&search=${q}`, {
                headers: {
                    Accept: 'application/json'
                }
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.message || 'No se pudieron cargar los vouchers.');
            vouchers = d.data || [];
            rowsBox.innerHTML = vouchers.length ? vouchers.map(v => {
                const s = v.effective_status || v.status;
                const locked = ['used', 'reserved'].includes(v.status);
                return `<tr>
                <td><strong>${v.code}</strong></td>
                <td>${v.name}</td>
                <td class="text-end fw-semibold">${benefit(v)}</td>
                <td>${v.delivery_type==='digital'?'Digital':'Físico'}</td>
                <td>${v.sales_channel==='online'?'Online':'Local'}</td>
                <td>${displayDate(v.expires_at)}</td>
                <td class="text-center"><span class="badge bg-label-${s==='available'?'success':s==='used'?'secondary':s==='reserved'?'warning':'danger'}">${statusLabel(s)}</span></td>
                <td class="text-end"><div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="Acciones del voucher"><i class="icon-base ri ri-more-2-line"></i></button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="${window.APP_BASE_URL}/demo/vouchers/${v.id}"><i class="icon-base ri ri-eye-line me-2"></i>Ver detalle</a>
                        <button class="dropdown-item" type="button" onclick="editVoucher(${v.id})" ${v.status==='used'?'disabled':''}><i class="icon-base ri ri-edit-line me-2"></i>Editar</button>
                        <a class="dropdown-item" target="_blank" href="${window.APP_BASE_URL}/demo/vouchers/${v.id}/print"><i class="icon-base ri ri-printer-line me-2"></i>Imprimir</a>
                        <a class="dropdown-item" target="_blank" href="${window.APP_BASE_URL}/demo/vouchers/${v.id}/download-image"><i class="icon-base ri ri-image-line me-2"></i>Descargar JPG</a>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item ${v.status==='disabled'?'text-success':'text-danger'}" type="button" onclick="toggleVoucher(${v.id})" ${locked?'disabled':''}><i class="icon-base ri ${v.status==='disabled'?'ri-checkbox-circle-line':'ri-forbid-line'} me-2"></i>${v.status==='disabled'?'Habilitar':'Inhabilitar'}</button>
                    </div>
                </div></td>
            </tr>`;
            }).join('') : '<tr><td colspan="8" class="text-center text-muted py-5">No hay vouchers para mostrar.</td></tr>';
            renderApiPagination(d, load);
        } catch (error) {
            rowsBox.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">${error.message}</td></tr>`;
            renderApiPagination({
                current_page: 1,
                last_page: 1,
                total: 0
            }, load);
        }
    }

    function voucherField(id) {
        return document.getElementById(id)
    }

    function updateVoucherValueType() {
        const percentage = voucherField('value_type').value === 'percentage';
        voucherField('amount_label').textContent = percentage ? 'Porcentaje' : 'Importe';
        voucherField('amount').max = percentage ? '100' : '';
        voucherField('maximum_discount_group').classList.toggle('d-none', !percentage);
        if (!percentage) voucherField('maximum_discount_amount').value = ''
    }

    function openVoucher() {
        voucherField('voucherForm').reset();
        voucherField('voucher_id').value = '';
        voucherField('value_type').value = 'fixed';
        updateVoucherValueType();
        modal().show()
    }

    function editVoucher(id) {
        const v = vouchers.find(x => x.id === id);
        if (!v) return;
        voucherField('voucher_id').value = v.id;
        voucherField('name').value = v.name;
        voucherField('value_type').value = v.value_type || 'fixed';
        voucherField('amount').value = v.amount;
        voucherField('maximum_discount_amount').value = v.maximum_discount_amount || '';
        voucherField('message').value = v.message || '';
        voucherField('delivery_type').value = v.delivery_type;
        voucherField('sales_channel').value = v.sales_channel;
        voucherField('expires_at').value = dateValue(v.expires_at);
        updateVoucherValueType();
        modal().show()
    }
    voucherField('value_type').addEventListener('change', updateVoucherValueType);
    voucherField('voucherForm').addEventListener('submit', async e => {
        e.preventDefault();
        const id = voucherField('voucher_id').value;
        const payload = {
            name: voucherField('name').value.trim(),
            value_type: voucherField('value_type').value,
            amount: voucherField('amount').value,
            maximum_discount_amount: voucherField('maximum_discount_amount').value || null,
            message: voucherField('message').value,
            delivery_type: voucherField('delivery_type').value,
            sales_channel: voucherField('sales_channel').value,
            expires_at: voucherField('expires_at').value || null
        };
        const r = await fetch(`${window.APP_BASE_URL}/api/vouchers${id?'/'+id:''}`, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (!r.ok) {
            alert(d.message || 'No se pudo guardar.');
            return
        }
        modal().hide();
        load(currentPage)
    });
    async function toggleVoucher(id) {
        if (!await window.erpConfirm('¿Confirmás el cambio de estado del voucher?')) return;
        const r = await fetch(`${window.APP_BASE_URL}/api/vouchers/${id}/toggle`, {
            method: 'POST',
            headers: {
                Accept: 'application/json'
            }
        });
        const d = await r.json();
        if (!r.ok) alert(d.message);
        load(currentPage)
    }
    let timer;
    document.getElementById('search').addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => load(1), 300)
    });
    load();
</script>
@endsection
