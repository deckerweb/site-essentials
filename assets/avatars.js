jQuery(function () {
 'use strict';
 const config = window.siteEssentialsAvatar, form = document.getElementById('se-avatar-form'), main = document.getElementById('your-profile');
 if (!config || !form || !main) return;
 const id = document.getElementById('se-avatar-id'), mode = document.getElementById('se-avatar-mode'), file = document.getElementById('se-avatar-file'), image = document.getElementById('se-avatar-preview'), message = document.getElementById('se-avatar-message');
 const original = id.value, originalImage = image.src;
 let submitting = false;
 const read = () => JSON.stringify([...new FormData(main).entries()].map(([key,value]) => [key,typeof value === 'string' ? value : '']).sort());
 const baseline = read();
 const dirty = () => mode.value === 'upload' || (mode.value === 'remove' && original !== '0') || id.value !== original;
 document.getElementById('se-avatar-select')?.addEventListener('click', () => {
  const picker = wp.media({title:config.strings.choose, button:{text:config.strings.use}, library:{type:'image',se_local_avatar:1}, multiple:false, uploader:false});
  picker.state('library').set({content:'browse',contentUserSetting:false});
  picker.on('router:render:browse', router => router.unset('upload'));
  picker.on('select', () => { const selected = picker.state().get('selection').first().toJSON();
   const url = selected.sizes?.thumbnail?.url || selected.url;
   const origins = config.origins.map(value => new URL(value).origin);
   if (!selected.seAvatarLocal || !origins.includes(new URL(url, location.href).origin)) { message.textContent = config.strings.local; message.focus(); return; }
   id.value = selected.id; mode.value = 'select'; file.value = ''; image.src = selected.sizes?.thumbnail?.url || selected.url; message.textContent = ''; }); picker.open();
 });
 file.addEventListener('change', () => { mode.value = file.files.length ? 'upload' : 'select'; });
 document.getElementById('se-avatar-remove').addEventListener('click', () => { id.value = '0'; mode.value = 'remove'; file.value = ''; image.src = config.fallback; });
 document.getElementById('se-avatar-discard').addEventListener('click', () => { id.value = original; mode.value = 'select'; file.value = ''; image.src = originalImage; message.textContent = ''; });
 form.addEventListener('submit', event => { if (read() !== baseline) { event.preventDefault(); message.textContent = config.strings.profileDraft; message.focus(); return; } submitting = true; });
 main.addEventListener('submit', event => { if (dirty()) { event.preventDefault(); event.stopImmediatePropagation(); message.textContent = config.strings.avatarDraft; message.focus(); return; } submitting = true; }, true);
 window.addEventListener('beforeunload', event => { if (!submitting && dirty()) { event.preventDefault(); event.returnValue = ''; } });
});
