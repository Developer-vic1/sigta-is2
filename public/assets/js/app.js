'use strict';
// SIGTA: solo mejora progresiva de la experiencia. Toda validación relevante ocurre en servidor.
(() => {
 const menu=document.querySelector('[data-menu]'); const sidebar=document.querySelector('#sidebar');
 menu?.addEventListener('click',()=>{const open=sidebar?.classList.toggle('open');menu.setAttribute('aria-expanded',open?'true':'false');});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){sidebar?.classList.remove('open');menu?.setAttribute('aria-expanded','false');}});
 document.querySelectorAll('[data-confirm]').forEach(b=>{b.addEventListener('click',e=>{if(!window.confirm(b.getAttribute('data-confirm')))e.preventDefault();});});
 document.querySelectorAll('[data-table-filter]').forEach(inp=>{inp.addEventListener('input',()=>{const t=document.querySelector(inp.dataset.tableFilter);if(!t)return;const val=inp.value.trim().toLocaleLowerCase('es');t.querySelectorAll('tbody tr').forEach(tr=>{tr.hidden=!tr.textContent.toLocaleLowerCase('es').includes(val);});});});
 const call=document.querySelector('[data-call-select]');const modes=document.querySelector('[data-modalities-select]');
 if(call&&modes){const all=[...modes.querySelectorAll('option[data-call]')].map(o=>({v:o.value,label:o.textContent,call:o.dataset.call}));const render=()=>{const selected=call.value;modes.replaceChildren(new Option('Selecciona modalidad...',''));all.filter(o=>o.call===selected).forEach(o=>modes.add(new Option(o.label,o.v)));};call.addEventListener('change',render);render();}
})();
