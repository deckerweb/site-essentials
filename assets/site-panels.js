/* Separate site-local content drafts never enter preset/configuration payloads. */
(function () {
 'use strict';
 const cfg=window.seSitePanels, main=document.getElementById('se-settings');
 if(!cfg||!main)return;
 const target=new URLSearchParams(location.search).get('se_panel');
 const jump=target==='dashboard'?document.getElementById('se-dashboard-panel'):target==='avatar'?document.getElementById('se-default-avatar-panel'):null;
 function focusPanel(){if(jump&&!jump.closest('[hidden]')){const heading=jump.querySelector('h3')||jump;heading.tabIndex=-1;heading.focus({preventScroll:true});jump.scrollIntoView({block:'start'});}}
 if(document.readyState==='complete')requestAnimationFrame(focusPanel);else window.addEventListener('load',()=>requestAnimationFrame(focusPanel),{once:true});
 const ids=['se-widget-save-form','se-widget-import-form','se-default-avatar-form'];
 const snapshots=new Map(); let leaving=false;
 const editor=()=>window.tinymce?.get('se-widget-body');
 function state(id){return JSON.stringify([...document.querySelectorAll('[form="'+id+'"]')].filter(e=>['INPUT','TEXTAREA','SELECT'].includes(e.tagName)&&(e.type!=='checkbox'||e.checked)).map(e=>[e.name,e.type==='checkbox'?e.checked:e.type==='file'?[...e.files].map(f=>[f.name,f.size,f.lastModified]):e.value]));}
 ids.forEach(id=>snapshots.set(id,state(id)));
 function changed(id){return state(id)!==snapshots.get(id)||(id==='se-widget-save-form'&&Boolean(editor()?.isDirty()));}
 function message(id,text){document.getElementById(id==='se-default-avatar-form'?'se-default-avatar-status':'se-widget-status').textContent=text;}
 const originals=new Map(ids.map(id=>[id,[...document.querySelectorAll('[form="'+id+'"]')].filter(e=>['INPUT','TEXTAREA','SELECT'].includes(e.tagName)).map(e=>({element:e,value:e.value,checked:e.checked}))]));
 main.addEventListener('input',e=>{if(e.target.getAttribute('form')==='se-widget-save-form')message('',changed('se-widget-save-form')?cfg.unsaved:'');});
 function bindEditor(ed){if(ed.id==='se-widget-body')ed.on('change keyup undo redo',()=>message('',changed('se-widget-save-form')?cfg.unsaved:''));}
 if(editor())bindEditor(editor());window.tinymce?.on('AddEditor',event=>bindEditor(event.editor));
 const forms=[...document.querySelectorAll('form')];
 forms.forEach(form=>form.addEventListener('submit',event=>{
  const own=ids.includes(form.id)||form.id==='se-widget-export-form';
  const conflict=own?Boolean(window.siteEssentialsDraft?.has())||ids.some(id=>id!==form.id&&changed(id)):ids.some(changed);
  if(conflict){event.preventDefault();event.stopImmediatePropagation();message(form.id,cfg.draft);document.getElementById('se-status').textContent=cfg.draft;return;}
  if(form.id==='se-widget-save-form'&&editor()&&!editor().isHidden())editor().save();
  if(own&&form.id!=='se-widget-export-form')leaving=true;
 },true));
 window.addEventListener('beforeunload',e=>{if(!leaving&&ids.some(changed)){e.preventDefault();e.returnValue='';}});
 const preview=document.getElementById('se-default-avatar-preview'), attachment=document.getElementById('se-default-avatar-id'), mode=document.getElementById('se-default-avatar-mode'), file=document.getElementById('se-default-avatar-file'); let objectURL; const originalPreview=preview?.getAttribute('src');
 function release(){if(objectURL){URL.revokeObjectURL(objectURL);objectURL=null;}}
 document.getElementById('se-default-avatar-choose')?.addEventListener('click',()=>{
  if(!window.wp?.media)return;
  const frame=wp.media({title:cfg.choose,button:{text:cfg.use},multiple:false,library:{type:'image',se_local_avatar:'1'}});
  frame.on('select',()=>{const item=frame.state().get('selection').first()?.toJSON();
   let url;try{url=new URL(item.url);if(!cfg.origins.some(origin=>new URL(origin).origin===url.origin)||!['http:','https:'].includes(url.protocol)||!['image/jpeg','image/png','image/gif','image/webp','image/avif'].includes(item.mime)||item.seAvatarLocal===false)throw new Error('local');}catch(e){message('se-default-avatar-form',cfg.local);return;}
   release();attachment.value=String(item.id);mode.value='select';file.value='';preview.src=url.href;message('se-default-avatar-form',cfg.selected);
  });frame.open();
 });
 document.getElementById('se-default-avatar-reset')?.addEventListener('click',()=>{release();attachment.value='0';mode.value='remove';file.value='';preview.src=cfg.shipped;message('se-default-avatar-form',cfg.reset);});
 file?.addEventListener('change',()=>{release();if(file.files.length){mode.value='upload';objectURL=URL.createObjectURL(file.files[0]);preview.src=objectURL;message('se-default-avatar-form',cfg.selected);}else{mode.value='select';}});
 document.querySelectorAll('[data-se-local-discard]').forEach(button=>button.addEventListener('click',()=>{const id=button.dataset.seLocalDiscard;const baseline=new Set((originals.get(id)||[]).map(item=>item.element));document.querySelectorAll('[form="'+id+'"]').forEach(e=>{if(['INPUT','TEXTAREA','SELECT'].includes(e.tagName)&&!baseline.has(e)){const label=e.closest('label');if(label)label.remove();else e.remove();}});for(const item of originals.get(id)||[]){if(item.element.type==='file')item.element.value='';else{item.element.value=item.value;item.element.checked=item.checked;}}if(id==='se-widget-save-form'&&editor()){const body=originals.get(id).find(item=>item.element.id==='se-widget-body');editor().setContent(body?.value||'');editor().setDirty(false);}if(id==='se-default-avatar-form'){release();preview.src=originalPreview;}snapshots.set(id,state(id));message(id,'');main.dispatchEvent(new Event('input',{bubbles:true}));}));
 const search=document.getElementById('se-widget-user-search'),find=document.getElementById('se-widget-user-find'),results=document.getElementById('se-widget-user-results'),selected=document.getElementById('se-widget-users');let ticket=0;
 async function lookup(){const term=search.value.trim(),request=++ticket;results.replaceChildren();if(term.length<2){message('',cfg.short);return;}message('',cfg.searching);
  try{const response=await fetch(cfg.url,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'site_essentials_access_search',nonce:cfg.nonce,term})});const data=await response.json();if(request!==ticket)return;if(!response.ok||!data.success||!Array.isArray(data.data))throw new Error('lookup');message('',data.data.length?'':cfg.empty);
   data.data.forEach(user=>{if(!Number.isSafeInteger(user.id)||user.id<1||typeof user.label!=='string'||selected.querySelector('input[value="'+user.id+'"]'))return;const label=document.createElement('label'),input=document.createElement('input');label.className='se-choice';input.type='checkbox';input.value=String(user.id);input.name='widget_users[]';input.setAttribute('form','se-widget-save-form');label.append(input,document.createTextNode(' '+user.label));results.append(label);input.addEventListener('change',()=>selected.append(label));});
  }catch(e){if(request===ticket)message('',cfg.failed);}}
 find?.addEventListener('click',lookup);search?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();lookup();}});
}());
