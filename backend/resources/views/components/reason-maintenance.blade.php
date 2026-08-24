@props(['title', 'subtitle', 'endpoint', 'singular' => 'motivo'])
{{-- Componente: mantenimiento inline de motivos con el patrón visual de los maestros de producto. --}}
<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="mb-1">{{ $title }}</h4><small class="text-muted">{{ $subtitle }}</small></div></div>

<div class="card mb-4">
    <div class="card-header"><h5 id="reasonFormTitle" class="mb-0">Nuevo {{ $singular }}</h5></div>
    <div class="card-body"><form id="reasonForm"><div class="row align-items-end">
        <div class="col-md-7 mb-3"><label class="form-label">Nombre *</label><input id="reasonName" name="name" class="form-control" maxlength="255" placeholder="Ingresá el nombre" required></div>
        <div class="col-md-2 mb-3"><label class="form-label">Activo</label><select id="reasonActive" name="is_active" class="form-select"><option value="1">Sí</option><option value="0">No</option></select></div>
        <div class="col-md-3 mb-3 d-flex justify-content-end gap-2"><button id="reasonCancel" type="button" class="btn btn-secondary d-none">Cancelar</button><button id="reasonSave" type="submit" class="btn btn-primary">Guardar <i class="ri-add-line"></i></button></div>
    </div></form></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3"><div><h5 class="mb-1">Listado</h5><small class="text-muted">{{ ucfirst($singular) }}s registrados en el sistema</small></div><input id="reasonSearch" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar {{ $singular }}..."></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nombre</th><th class="text-center">Estado</th><th class="text-end">Acciones</th></tr></thead><tbody id="reasonRows"><tr><td colspan="3" class="text-center text-muted py-4">Cargando motivos...</td></tr></tbody></table></div>
    @include('components.api-pagination')
</div>

<script>
(() => {
const endpoint=@js($endpoint),singular=@js($singular);let reasons=[],page=1,searchTimer;
const form=document.getElementById('reasonForm'),name=document.getElementById('reasonName'),active=document.getElementById('reasonActive'),save=document.getElementById('reasonSave'),cancel=document.getElementById('reasonCancel'),rows=document.getElementById('reasonRows'),search=document.getElementById('reasonSearch'),title=document.getElementById('reasonFormTitle');
const badge=value=>value?'<span class="badge bg-label-success">Activo</span>':'<span class="badge bg-label-secondary">Inactivo</span>';
function render(){if(!reasons.length){rows.innerHTML='<tr><td colspan="3" class="text-center text-muted py-4">No hay motivos registrados</td></tr>';return}rows.innerHTML=reasons.map(item=>`<tr><td><strong>${item.name??'-'}</strong></td><td class="text-center">${badge(item.is_active)}</td><td class="text-end"><button type="button" class="btn btn-sm btn-warning" data-edit="${item.id}">Editar <i class="ri-edit-line"></i></button></td></tr>`).join('');rows.querySelectorAll('[data-edit]').forEach(button=>button.onclick=()=>edit(button.dataset.edit))}
async function load(target=1){page=target;const params=new URLSearchParams({page,per_page:20});if(search.value.trim())params.set('search',search.value.trim());const response=await fetch(`${endpoint}?${params}`,{headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok)throw new Error(data.message||'No se pudieron cargar los motivos.');reasons=data.data??data;render();if(data.current_page)renderApiPagination(data,load)}
function edit(id){const item=reasons.find(row=>Number(row.id)===Number(id));if(!item)return;form.dataset.editId=item.id;name.value=item.name??'';active.value=item.is_active?'1':'0';title.textContent=`Editar ${singular}`;save.className='btn btn-warning';save.innerHTML='Actualizar <i class="ri-save-line"></i>';cancel.classList.remove('d-none');name.focus();window.scrollTo({top:0,behavior:'smooth'})}
function reset(){form.reset();delete form.dataset.editId;active.value='1';title.textContent=`Nuevo ${singular}`;save.className='btn btn-primary';save.innerHTML='Guardar <i class="ri-add-line"></i>';cancel.classList.add('d-none')}
form.onsubmit=async event=>{event.preventDefault();const editId=form.dataset.editId,payload={name:name.value.trim(),is_active:active.value==='1'};if(!payload.name){erpAlert(`Ingresá el nombre del ${singular}.`);return}save.disabled=true;save.innerHTML='Guardando... <span class="spinner-border spinner-border-sm"></span>';try{const response=await fetch(editId?`${endpoint}/${editId}`:endpoint,{method:editId?'PUT':'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(payload)});const data=await response.json();if(!response.ok){const errors=data.errors?Object.values(data.errors).flat().join('\n'):'';throw new Error([data.message,errors].filter(Boolean).join('\n')||'No se pudo guardar.')}reset();await load(page);erpAlert(editId?'Datos actualizados correctamente.':'Datos creados correctamente.',{icon:'success'})}catch(error){erpAlert(error.message)}finally{save.disabled=false;if(form.dataset.editId){save.className='btn btn-warning';save.innerHTML='Actualizar <i class="ri-save-line"></i>'}else{save.className='btn btn-primary';save.innerHTML='Guardar <i class="ri-add-line"></i>'}}};
cancel.onclick=reset;search.oninput=()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>load(1).catch(error=>erpAlert(error.message)),300)};load().catch(error=>{rows.innerHTML='<tr><td colspan="3" class="text-center text-danger py-4">Error al cargar los datos</td></tr>';erpAlert(error.message)});
})();
</script>
