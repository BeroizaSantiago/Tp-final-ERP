@props([
    'id',
    'name',
    'label',
    'help' => null,
    'remoteUrl' => null,
    'minimumCharacters' => 3,
    'searchOnly' => false,
])
{{-- Componente: selector múltiple visual con etiquetas y acciones seleccionar/limpiar todo. --}}
<div class="erp-multiselect" data-erp-multiselect @if($searchOnly) data-multi-search-only="true" @endif>
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" multiple hidden
        @if($remoteUrl) data-multi-remote-url="{{ $remoteUrl }}" @endif
        data-multi-minimum-characters="{{ (int) $minimumCharacters }}">{{ $slot }}</select>
    <button class="erp-multiselect-control" type="button" aria-haspopup="listbox" aria-expanded="false">
        <span class="erp-multiselect-tags"><span class="erp-multiselect-placeholder">Seleccionar...</span></span>
        <i class="ri-arrow-down-s-line erp-multiselect-arrow"></i>
    </button>
    <div class="erp-multiselect-menu" role="listbox" aria-multiselectable="true">
        @if($remoteUrl)<div class="p-2 border-bottom"><input type="search" class="form-control form-control-sm" data-multi-search placeholder="Ingresá {{ (int) $minimumCharacters }} {{ (int) $minimumCharacters === 1 ? 'carácter' : 'caracteres' }}..."></div>@endif
        <div class="erp-multiselect-actions">
            @unless($searchOnly)<button type="button" data-multi-all>Seleccionar Todo</button>@endunless
            <button type="button" data-multi-clear>Limpiar</button>
        </div>
        <div class="erp-multiselect-options"></div>
    </div>
    @if($help)<small class="text-muted d-block mt-1">{{ $help }}</small>@endif
</div>

@once
<style>
.erp-multiselect{position:relative}.erp-multiselect-control{width:100%;min-height:44px;border:1px solid var(--bs-border-color);border-radius:.375rem;background:var(--bs-body-bg);color:var(--bs-body-color);padding:.35rem 2.25rem .35rem .45rem;display:flex;align-items:center;text-align:left;position:relative}.erp-multiselect.is-open .erp-multiselect-control{border-color:#8c57ff;box-shadow:0 0 0 .2rem rgba(140,87,255,.16)}.erp-multiselect-tags{display:flex;flex-wrap:wrap;gap:.3rem;min-width:0}.erp-multiselect-tag{display:inline-flex;align-items:center;gap:.2rem;background:rgba(140,87,255,.12);color:#7048c8;border-radius:.3rem;padding:.2rem .45rem;font-size:.8125rem}.erp-multiselect-tag button{border:0;background:transparent;color:inherit;padding:0;line-height:1}.erp-multiselect-placeholder{color:var(--bs-secondary-color)}.erp-multiselect-arrow{position:absolute;right:.65rem;top:50%;transform:translateY(-50%);font-size:1.25rem}.erp-multiselect.is-open .erp-multiselect-arrow{transform:translateY(-50%) rotate(180deg)}.erp-multiselect-menu{display:none;position:absolute;z-index:1090;top:calc(100% - 1px);left:0;right:0;background:var(--bs-body-bg);border:1px solid #8c57ff;border-radius:0 0 .45rem .45rem;box-shadow:0 .5rem 1.25rem rgba(47,43,61,.18);max-height:270px;overflow:hidden}.erp-multiselect.is-open .erp-multiselect-menu{display:block}.erp-multiselect-actions{display:flex;justify-content:flex-end;gap:.4rem;padding:.45rem;border-bottom:1px solid var(--bs-border-color)}.erp-multiselect-actions button{border:0;background:transparent;color:#8c57ff;font-size:.78rem;font-weight:600}.erp-multiselect-options{max-height:220px;overflow:auto;padding:.45rem}.erp-multiselect-option{display:block;width:100%;border:0;border-radius:.35rem;background:transparent;color:var(--bs-body-color);padding:.55rem .7rem;text-align:left}.erp-multiselect-option:hover{background:rgba(140,87,255,.09)}.erp-multiselect-option.is-selected{background:#8c57ff;color:#fff}.erp-multiselect-option+.erp-multiselect-option{margin-top:.15rem}[data-bs-theme="dark"] .erp-multiselect-tag{background:rgba(166,120,255,.22);color:#c6a7ff}
</style>
<script>
window.erpEnhanceMultiSelect=window.erpEnhanceMultiSelect||function(select){
 const root=select.closest('[data-erp-multiselect]');if(!root||root._multi)return root?root._multi:null;
 const control=root.querySelector('.erp-multiselect-control'),tags=root.querySelector('.erp-multiselect-tags'),optionsBox=root.querySelector('.erp-multiselect-options');
 const api={refresh(){optionsBox.innerHTML='';[...select.options].forEach(option=>{const button=document.createElement('button');button.type='button';button.className='erp-multiselect-option'+(option.selected?' is-selected':'');button.textContent=option.text;button.addEventListener('click',()=>{option.selected=!option.selected;select.dispatchEvent(new Event('change',{bubbles:true}));api.render()});optionsBox.appendChild(button)});api.render()},render(){tags.innerHTML='';const selected=[...select.selectedOptions];if(!selected.length){tags.innerHTML='<span class="erp-multiselect-placeholder">Seleccionar...</span>'}else selected.forEach(option=>{const tag=document.createElement('span');tag.className='erp-multiselect-tag';tag.append(document.createTextNode(option.text));const remove=document.createElement('button');remove.type='button';remove.innerHTML='&times;';remove.setAttribute('aria-label','Quitar '+option.text);remove.addEventListener('click',e=>{e.stopPropagation();option.selected=false;select.dispatchEvent(new Event('change',{bubbles:true}));api.refresh()});tag.appendChild(remove);tags.appendChild(tag)});[...optionsBox.children].forEach((button,index)=>button.classList.toggle('is-selected',select.options[index]?.selected))},setAll(value){[...select.options].forEach(o=>o.selected=value);select.dispatchEvent(new Event('change',{bubbles:true}));api.refresh()},close(){root.classList.remove('is-open');control.setAttribute('aria-expanded','false')}};
 control.addEventListener('click',()=>{const open=!root.classList.contains('is-open');document.querySelectorAll('[data-erp-multiselect].is-open').forEach(el=>el!==root&&el._multi?.close());root.classList.toggle('is-open',open);control.setAttribute('aria-expanded',String(open));if(open)root.querySelector('[data-multi-search]')?.focus()});root.querySelector('[data-multi-all]')?.addEventListener('click',()=>api.setAll(true));root.querySelector('[data-multi-clear]').addEventListener('click',()=>api.setAll(false));document.addEventListener('click',e=>{if(!root.contains(e.target))api.close()});
 const remoteSearch=root.querySelector('[data-multi-search]'),minimum=Math.max(1,Number(select.dataset.multiMinimumCharacters||3)),searchOnly=root.dataset.multiSearchOnly==='true';let remoteTimer,remoteController;if(searchOnly)optionsBox.innerHTML=`<div class="erp-remote-message">Ingresá al menos ${minimum} ${minimum===1?'carácter':'caracteres'}.</div>`;if(remoteSearch)remoteSearch.addEventListener('input',()=>{clearTimeout(remoteTimer);const term=remoteSearch.value.trim();if(term.length<minimum){optionsBox.innerHTML=`<div class="erp-remote-message">Ingresá al menos ${minimum} ${minimum===1?'carácter':'caracteres'}.</div>`;return}remoteTimer=setTimeout(async()=>{remoteController?.abort();remoteController=new AbortController();optionsBox.innerHTML='<div class="erp-remote-message">Buscando...</div>';try{const url=new URL(select.dataset.multiRemoteUrl,location.origin);url.searchParams.set('search',term);url.searchParams.set('lookup','1');url.searchParams.set('per_page','20');const response=await fetch(url,{headers:{Accept:'application/json'},signal:remoteController.signal});const data=await response.json();const selected=[...select.selectedOptions].map(option=>({id:option.value,label:option.text,rawName:option.dataset.rawName||option.text}));select.innerHTML='';selected.forEach(item=>{const option=new Option(item.label,item.id,true,true);option.dataset.rawName=item.rawName;select.add(option)});(data.data??data).forEach(item=>{if(selected.some(value=>String(value.id)===String(item.id)))return;const typeName=item.size_type?.name??item.sizeType?.name;const baseLabel=[item.code,item.name,item.bar_code].filter(Boolean).join(' · ');const option=new Option(typeName?`${baseLabel} · ${typeName}`:baseLabel,item.id);option.dataset.rawName=item.name??baseLabel;select.add(option)});api.refresh()}catch(error){if(error.name!=='AbortError')optionsBox.innerHTML='<div class="erp-remote-message text-danger">No se pudo buscar.</div>'}},300)});
 root._multi=api;if(searchOnly)api.render();else api.refresh();return api;
};
document.addEventListener('DOMContentLoaded',()=>document.querySelectorAll('[data-erp-multiselect] select').forEach(window.erpEnhanceMultiSelect));
</script>
@endonce
