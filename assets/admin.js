(function () {
 'use strict';
 const config = window.siteEssentials;
 if (!config) return;
 const form = document.getElementById('se-settings');
 let opener = null;
 function openDialog(dialog, button) {
  opener = button || document.activeElement;
  dialog.showModal();
 }
 document.querySelectorAll('[data-dialog]').forEach(button => button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.dialog), button)));
 document.querySelectorAll('.se-dialog').forEach(dialog => {
  dialog.querySelector('.se-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => { if (opener && opener.isConnected) opener.focus(); });
 });
 if (!form) return;
 // Scope the fixed action bar to this page's actual content width, including folded menus.
 const root = form.closest('.se-root'), saveBar = form.querySelector('.se-save');
 /** Keep actions aligned and reserve space; temporarily use normal flow for a keyboard. @returns {void} */
 function placeSaveBar() {
  const bounds = root.getBoundingClientRect();
  root.style.setProperty('--se-save-left', bounds.left + 'px');
  root.style.setProperty('--se-save-width', bounds.width + 'px');
  root.style.setProperty('--se-save-height', saveBar.getBoundingClientRect().height + 'px');
  document.body.style.setProperty('--se-admin-save-height', saveBar.getBoundingClientRect().height + 'px');
  const keyboard = window.visualViewport && window.visualViewport.height < window.innerHeight * 0.65;
  root.classList.toggle('se-save-fixed', !keyboard);
 }
 placeSaveBar();
 if (window.ResizeObserver) { const resize = new ResizeObserver(placeSaveBar); resize.observe(root); resize.observe(saveBar); resize.observe(document.getElementById('wpcontent')); }
 window.addEventListener('resize', placeSaveBar);
 window.visualViewport?.addEventListener('resize', placeSaveBar);
 if (window.MutationObserver) { new MutationObserver(placeSaveBar).observe(document.body, {attributes:true,attributeFilter:['class']}); }
 const fields = config.schema;
 const panels = [...form.querySelectorAll('.se-panel')];
 const nav = [...form.querySelectorAll('[data-section]')];
 const status = document.getElementById('se-status');
 const error = document.getElementById('se-import-error');
 const preview = document.getElementById('se-preview');
 let pending = {};
 let pendingProfile = null;
 let submitting = false;
 let selectedSection = 'overview';
 const search = document.getElementById('se-search');
 const ruleFilter = document.getElementById('se-rule-filter');
 const ruleReference = document.getElementById('se-rule-reference');
 function selectSection(id, focus) {
  const exists = panels.some(panel => panel.id === 'se-' + id);
  if (!exists) id = 'overview';
  selectedSection = id;
  Object.keys(fields).forEach(key => form.querySelector('[data-setting="' + key + '"]').hidden = false);
  panels.forEach(panel => { panel.hidden = panel.id !== 'se-' + id; });
  nav.forEach(link => { if (link.dataset.section === id) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current'); });
  ruleView();
  if (focus) document.getElementById('se-title-' + id).focus();
 }
 nav.forEach(link => link.addEventListener('click', event => {
  event.preventDefault(); search.value = ''; ruleFilter.value = 'all'; document.getElementById('se-search-status').textContent = ''; selectSection(link.dataset.section, true);
  history.replaceState(null, '', link.hash);
 }));
 selectSection(location.hash.replace('#se-', ''), location.hash.startsWith('#se-'));
 window.addEventListener('hashchange', () => { search.value = ''; ruleFilter.value = 'all'; selectSection(location.hash.replace('#se-', ''), true); });
 function canonical(value) {
  if (Array.isArray(value)) return value.slice().sort();
  if (value && typeof value === 'object') return Object.fromEntries(Object.keys(value).sort().map(key => [key, canonical(value[key])]));
  return value;
 }
 function equal(a, b) { return JSON.stringify(canonical(a)) === JSON.stringify(canonical(b)); }
 function controls(key) { return [...form.querySelector('[data-setting="' + key + '"]').querySelectorAll('input,select')]; }
 function read() {
  const values = structuredClone(config.defaults);
  Object.entries(fields).forEach(([key, field]) => {
   const inputs = controls(key);
   if (field.type === 'bool') {
    const checkbox = inputs.find(input => input.type === 'checkbox');
    values[key] = checkbox.disabled ? config.local[key] : checkbox.checked;
   } else if (field.type === 'text' || field.type === 'int') {
    values[key] = field.type === 'int' ? Number(inputs[0].value) : inputs[0].value;
   } else if (field.type === 'list') {
    values[key] = inputs.filter(input => input.type === 'hidden' || input.checked).map(input => input.value);
   } else {
    values[key] = {};
    inputs.forEach(input => {
     const match = input.name.match(/^settings\[[^\]]+\]\[([^\]]+)\]$/);
     const number = Number(input.value);
     if (match && number !== (key === 'heartbeat_types' ? 0 : -1)) values[key][match[1]] = number;
    });
   }
  });
  return values;
 }

 const profileName = document.getElementById('se-preset-name');
 const profileNote = document.getElementById('se-preset-description');
 const editSelect = document.getElementById('se-preset-edit');
 let editSelected = editSelect?.value || '';
 let recovery = null;
 let draftAt = Date.now();
 const recoveryPanel = document.getElementById('se-profile-recovery');
 function showRecovery() {
  recoveryPanel.hidden = !recovery;
  document.querySelectorAll('[form="se-preset-edit-form"]').forEach(input => { input.disabled = Boolean(recovery); });
  document.getElementById('se-recovery-name').value = recovery?.name || '';
  document.getElementById('se-recovery-note').value = recovery?.description || '';
 }
 function fillEditor(id) {
  if (!editSelect || !Object.hasOwn(config.presets, id)) return;
  editSelect.value = id; editSelected = id;
  document.getElementById('se-preset-edit-name').value = config.presets[id].label;
  document.getElementById('se-preset-edit-description').value = config.presets[id].description || '';
 }
 fillEditor(editSelected);
 function profileDirty() { return Boolean(profileName.value || profileNote.value); }
 function editDirty() {
  return Boolean(editSelect && (document.getElementById('se-preset-edit-name').value !== config.presets[editSelected].label || document.getElementById('se-preset-edit-description').value !== (config.presets[editSelected].description || '')));
 }
 function removeProfileStorage() { try { sessionStorage.removeItem(config.profileStorageKey); } catch (_) {} }
 function retainProfile(action = '', renew = true, receipt = '') {
  try {
   if (!profileDirty() && !editDirty() && !recovery) { removeProfileStorage(); return true; }
   if (renew) draftAt = Date.now();
   const baseline = editSelect ? {name:config.presets[editSelected].label, description:config.presets[editSelected].description || ''} : null;
   const draft = {at:draftAt, action, receipt, recovery, creation:{name:profileName.value, description:profileNote.value}, editing:editDirty() ? {id:editSelected, name:document.getElementById('se-preset-edit-name').value, description:document.getElementById('se-preset-edit-description').value, baseline} : null};
   sessionStorage.setItem(config.profileStorageKey, JSON.stringify(draft)); return true;
  } catch (_) {
   const state = document.getElementById('se-profile-state'); state.textContent = config.strings.profileStorageError;
   search.value = ''; selectSection('overview', false); state.focus(); return false;
  }
 }
 try {
  const stored = JSON.parse(sessionStorage.getItem(config.profileStorageKey) || 'null');
  const validText = item => item && typeof item.name === 'string' && [...item.name].length <= 80 && typeof item.description === 'string' && [...item.description].length <= 500;
  if (stored && typeof stored.at === 'number' && Date.now()-stored.at >= 0 && Date.now()-stored.at < 1800000) {
   draftAt = stored.at;
   const query = new URLSearchParams(location.search);
   const result = query.get('se_result');
   const acknowledged = typeof stored.receipt === 'string' && /^[a-f0-9]{32}$/.test(stored.receipt) && stored.receipt === query.get('se_receipt');
   // A result URL acknowledges only the action retained for this submission.
   if (!(acknowledged && result === 'preset_saved' && stored.action === 'create') && validText(stored.creation)) { profileName.value = stored.creation.name; profileNote.value = stored.creation.description; }
   if (validText(stored.recovery)) recovery = {name:stored.recovery.name, description:stored.recovery.description};
   if (!(acknowledged && result === 'preset_edited' && stored.action === 'edit') && validText(stored.editing) && /^custom_[a-f0-9-]{36}$/.test(stored.editing.id)) {
    const current = config.presets[stored.editing.id];
    if (editSelect && current && validText(stored.editing.baseline) && equal(stored.editing.baseline, {name:current.label, description:current.description || ''})) {
     fillEditor(stored.editing.id); document.getElementById('se-preset-edit-name').value = stored.editing.name; document.getElementById('se-preset-edit-description').value = stored.editing.description;
    } else {
     // Never apply metadata to a changed or missing preset. Keep it available to copy.
     recovery = {name:stored.editing.name, description:stored.editing.description};
    }
   }
   retainProfile('', false);
  } else removeProfileStorage();
 } catch (_) { removeProfileStorage(); }
 showRecovery();
 document.getElementById('se-recovery-use').addEventListener('click', () => {
  if (!recovery || (profileDirty() && !window.confirm(config.strings.replaceProfile))) return;
  profileName.value = recovery.name; profileNote.value = recovery.description;
  recovery = null; showRecovery(); dirty(); retainProfile(); profileName.focus();
 });
 document.getElementById('se-recovery-discard').addEventListener('click', () => {
  if (!window.confirm(config.strings.discardRecovery)) return;
  recovery = null; showRecovery(); dirty(); retainProfile(); profileName.focus();
 });
 [profileName, profileNote, document.getElementById('se-preset-edit-name'), document.getElementById('se-preset-edit-description')].filter(Boolean).forEach(input => input.addEventListener('input', () => { dirty(); retainProfile(); }));
 editSelect?.addEventListener('change', () => {
  const next = editSelect.value;
  if(editDirty() && !window.confirm(config.strings.discardEdit)) { editSelect.value = editSelected; return; }
  fillEditor(next); dirty();
 });
 const initial = read();
 window.siteEssentialsDraft = {has: () => dirty() || profileDirty() || editDirty() || Boolean(recovery)};
 function dirty() {
  const changed = !equal(initial, read()) || Boolean(window.siteEssentialsAccessDirty?.());
  status.textContent = changed ? config.strings.unsaved : config.strings.saved;
  form.dataset.dirty = String(changed);
  document.getElementById('se-profile-state').textContent = profileDirty() || editDirty() || recovery ? config.strings.profileUnsaved : '';
  if(!changed && !profileDirty() && !editDirty() && !recovery) { document.getElementById('se-restore-draft-note').textContent = ''; document.getElementById('se-clear-draft-note').textContent = ''; }
  document.getElementById('se-preset-draft-note').textContent = changed ? config.strings.presetSaved : '';
  ruleView();
  return changed;
 }
 form.addEventListener('input', dirty);
 form.addEventListener('change', dirty);
 form.addEventListener('submit', event => { if(!retainProfile()) { event.preventDefault(); return; } submitting = true; });
 window.addEventListener('beforeunload', event => {
  if (!submitting && (dirty() || profileDirty() || editDirty() || recovery)) { event.preventDefault(); event.returnValue = ''; }
 });
 function write(key, value) {
  const field = fields[key]; const inputs = controls(key);
  if (field.type === 'bool') { const input = inputs.find(node => node.type === 'checkbox'); if (!input.disabled) input.checked = value; }
  else if (field.type === 'text' || field.type === 'int') inputs[0].value = String(value);
  else {
   const fieldset = form.querySelector('[data-setting="' + key + '"] fieldset');
   fieldset.querySelectorAll('input[type="hidden"]').forEach(input => input.remove());
   const represented = new Set();
   controls(key).forEach(input => {
    if (field.type === 'list') { input.checked = value.includes(input.value); represented.add(input.value); }
    else {
     const match = input.name.match(/^settings\[[^\]]+\]\[([^\]]+)\]$/);
     if (match) { represented.add(match[1]); input.value = String(value[match[1]] ?? (key === 'heartbeat_types' ? 0 : -1)); }
    }
   });
   const entries = field.type === 'list' ? value.map(item => [item, item]) : Object.entries(value);
   entries.forEach(([name, stored]) => {
    if (represented.has(name)) return;
    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.value = String(stored);
    hidden.name = field.type === 'list' ? 'settings[' + key + '][]' : 'settings[' + key + '][' + name + ']';
    fieldset.append(hidden);
   });
  }
 }
 function validate(values) {
  if (!values || Array.isArray(values) || typeof values !== 'object') throw new Error(config.strings.invalid);
  Object.entries(values).forEach(([key, value]) => {
   const field = fields[key]; if (!field) throw new Error(config.strings.invalid);
   let valid = true;
   if (field.type === 'bool') valid = typeof value === 'boolean';
   if (field.type === 'text') valid = typeof value === 'string' && value.length <= 500;
   if (field.type === 'int') valid = Number.isInteger(value) && value >= field.min && value <= field.max && (!key.startsWith('heartbeat_') || [0,15,30,60,120].includes(value));
   if (field.type === 'list') valid = Array.isArray(value) && value.length <= 100 && value.every(item => typeof item === 'string' && (field.dynamic === 'roles' ? item.length > 0 && item.length <= 191 && !/[\x00-\x1F\x7F]/.test(item) : /^[a-z0-9_-]{1,80}$/.test(item)) && (!field.choices || field.choices.includes(item)));
   if (field.type === 'map') valid = value && !Array.isArray(value) && typeof value === 'object' && Object.keys(value).length <= 100 && Object.entries(value).every(([name, number]) => /^[a-z0-9_-]{1,80}$/.test(name) && Number.isInteger(number) && (key === 'heartbeat_types' ? [0,15,30,60,120].includes(number) : number >= -1 && number <= 100));
   if (!valid) throw new Error(config.strings.invalid);
  });
  return values;
 }
 function display(value) {
  if (typeof value === 'boolean') return config.strings[String(value)];
  if (Array.isArray(value)) return value.join(', ') || '—';
  if (value && typeof value === 'object') return Object.entries(value).map(([key, number]) => key + ': ' + number).join(', ') || '—';
  return String(value) || '—';
 }
 function review(values, button, current = read(), mode = 'apply') {
  document.getElementById('se-preview-apply').hidden = mode === 'draft'; pendingProfile = null; pending = {}; const list = document.getElementById('se-preview-list'); list.replaceChildren();
  Object.entries(values).forEach(([key, value]) => {
   if (mode === 'apply' && key === 'login_language' && controls(key).some(input => input.disabled)) return;
   if (equal(current[key], value)) return;
   pending[key] = value;
   const label = document.createElement(mode === 'draft' ? 'div' : 'label'); label.className = 'se-diff';
   const check = document.createElement('input'); check.type = 'checkbox'; check.checked = true; check.dataset.key = key; check.hidden = mode === 'draft';
   const text = document.createElement('span'); const title = document.createElement('strong'); title.textContent = fields[key].label;
   const detail = document.createElement('span'); detail.textContent = config.strings.before + ': ' + display(current[key]) + ' → ' + config.strings.after + ': ' + display(value);
   text.append(title, detail); if(mode !== 'draft') label.append(check); label.append(text); list.append(label);
  });
  if (!Object.keys(pending).length) { const p = document.createElement('p'); p.textContent = config.strings.none; list.append(p); }
  document.getElementById('se-preview-apply').disabled = !Object.keys(pending).length;
  openDialog(preview, button);
 }
 document.getElementById('se-preset-preview').addEventListener('click', function () {
  const preset = config.presets[document.getElementById('se-preset').value];
  if (preset) review({...structuredClone(config.defaults), ...preset.settings}, this);
 });
 document.getElementById('se-preview-apply').addEventListener('click', () => {
  preview.querySelectorAll('[data-key]').forEach(input => { if (input.checked) write(input.dataset.key, pending[input.dataset.key]); });
  if (pendingProfile) { document.getElementById('se-preset-name').value = pendingProfile.name; document.getElementById('se-preset-description').value = pendingProfile.description; }
  preview.close(); dirty(); status.textContent = config.strings.changed;
 });
 document.getElementById('se-import-preview').addEventListener('click', function () {
  error.textContent = '';
  try {
   const text = document.getElementById('se-import').value;
   if (new TextEncoder().encode(text).length > 102400) throw new Error(config.strings.large);
   const data = JSON.parse(text);
   if (data.plugin !== 'site-essentials' || data.schema !== 1) throw new Error(config.strings.invalid);
   const values = validate(data.settings);
   review(document.getElementById('se-import-mode').value === 'replace' ? {...structuredClone(config.defaults), ...values} : values, this);
   if (data.profile && typeof data.profile === 'object' && typeof data.label === 'string' && [...data.label].length <= 80 && typeof data.profile.description === 'string' && [...data.profile.description].length <= 500) {
    pendingProfile = {name:data.label, description:data.profile.description};
    const profileRow = document.createElement('p'); profileRow.textContent = config.strings.profileName + ': ' + pendingProfile.name + '\n' + config.strings.profileNote + ': ' + pendingProfile.description; document.getElementById('se-preview-list').append(profileRow);
    document.getElementById('se-preview-apply').disabled = false;
   }
  } catch (exception) { error.textContent = exception instanceof SyntaxError ? config.strings.invalid : exception.message; }
 });
 document.getElementById('se-file').addEventListener('change', async function () {
  error.textContent = ''; const file = this.files[0]; if (!file) return;
  if (file.size > 102400) { error.textContent = config.strings.large; return; }
  try { document.getElementById('se-import').value = await file.text(); } catch (_) { error.textContent = config.strings.invalid; }
 });

 // Keep preset management separate from draft saving, including stale-form protection.
 document.querySelectorAll('.se-preset-action').forEach(presetForm => presetForm.addEventListener('submit', event => {
  if (presetForm.id === 'se-preset-export-form') return;
  if (dirty()) {
   event.preventDefault(); search.value = ''; selectSection('overview', false);
   document.getElementById('se-preset-draft-note').textContent = config.strings.presetSaved;
   document.getElementById('se-preset-draft-note').focus(); return;
  }
  if (presetForm.id === 'se-preset-remove-form' && !window.confirm(config.strings.removePreset)) { event.preventDefault(); return; }
  const needsCarry = Boolean(recovery) || (presetForm.id === 'se-preset-create-form' ? editDirty() : (presetForm.id === 'se-preset-edit-form' ? profileDirty() : profileDirty() || editDirty()));
  const action = presetForm.id === 'se-preset-create-form' ? 'create' : (presetForm.id === 'se-preset-edit-form' ? 'edit' : 'remove');
  const receipt = [...crypto.getRandomValues(new Uint8Array(16))].map(byte => byte.toString(16).padStart(2, '0')).join('');
  let receiptInput = presetForm.querySelector('[name="draft_receipt"]');
  if (!receiptInput) { receiptInput = document.createElement('input'); receiptInput.type = 'hidden'; receiptInput.name = 'draft_receipt'; presetForm.append(receiptInput); }
  receiptInput.value = receipt;
  if(!retainProfile(action, true, receipt) && needsCarry) { event.preventDefault(); return; }
  submitting = true;
 }));

 document.getElementById('se-review-draft').addEventListener('click', function () { review(read(), this, initial, 'draft'); });
 function presetNote() {
  const selected = config.presets[document.getElementById('se-preset').value];
  document.getElementById('se-preset-note').textContent = selected?.description || '';
 }
 document.getElementById('se-preset').addEventListener('change', presetNote); presetNote();
 document.querySelectorAll('.se-clear-action').forEach(clearForm => clearForm.addEventListener('submit', event => {
  if (dirty() || profileDirty() || editDirty() || recovery) {
   event.preventDefault(); search.value = ''; selectSection('data', false);
   const note = document.getElementById('se-clear-draft-note'); note.textContent = config.strings.draftFirst; note.focus(); return;
  }
  submitting = true;
 }));
 const generateReport = document.getElementById('se-report-generate');
 generateReport.addEventListener('click', async () => {
  generateReport.disabled = true;
  const output = document.getElementById('se-report-output'), reportStatus = document.getElementById('se-report-status'), copy = document.getElementById('se-report-copy');
  reportStatus.textContent = ''; copy.disabled = true; output.value = ''; output.hidden = true;
  try {
   const body = new URLSearchParams({action:'site_essentials_diagnostic', _wpnonce:config.reportNonce});
   const response = await fetch(config.reportUrl, {method:'POST', credentials:'same-origin', body});
   if (!response.ok) throw new Error();
   const payload = await response.json(); if(!payload.success) throw new Error();
   output.value = JSON.stringify(payload.data, null, 2); output.hidden = false; copy.disabled = false;
  } catch (_) { reportStatus.textContent = config.strings.reportError; }
  finally { generateReport.disabled = false; }
 });
 document.getElementById('se-report-copy').addEventListener('click', async () => {
  const output = document.getElementById('se-report-output'), status = document.getElementById('se-report-status');
  try { await navigator.clipboard.writeText(output.value); status.textContent = config.strings.copySuccess; }
  catch (_) { output.focus(); output.select(); status.textContent = config.strings.copyFallback; }
 });
 document.getElementById('se-handover-print').addEventListener('click', () => { document.body.classList.add('se-print-handover'); window.print(); });
 window.addEventListener('afterprint', () => document.body.classList.remove('se-print-handover'));

 document.getElementById('se-discard-draft').addEventListener('click', () => {
  Object.entries(initial).forEach(([key,value])=>write(key,value));
  profileName.value = ''; profileNote.value = ''; fillEditor(editSelected); recovery = null; showRecovery(); removeProfileStorage(); dirty();
  status.textContent = config.strings.draftDiscarded;
 });
 document.getElementById('se-restore-preview').addEventListener('click', function () { if(config.previous) review(config.previous, this, initial, 'draft'); });
 document.getElementById('se-action-restore').addEventListener('submit', event => {
  if(dirty() || profileDirty() || editDirty() || recovery) {
   event.preventDefault(); const note = document.getElementById('se-restore-draft-note'); note.textContent = config.strings.draftFirst; note.focus(); return;
  }
  submitting = true;
 });
 dirty();
 function compareSaved() {
  const preset = config.presets[document.getElementById('se-compare-preset').value];
  if (!preset) return;
  const baseline = {...structuredClone(config.defaults), ...preset.settings};
  const rows = document.getElementById('se-compare-rows'); rows.replaceChildren();
  let count = 0; const only = document.getElementById('se-compare-only-differences').checked;
  Object.entries(fields).forEach(([key, field]) => {
   const differs = !equal(config.local[key], baseline[key]); if (differs) count++;
   const locked = config.networkLocked && key === 'login_language';
   if (only && !differs && !locked) return;
   const row = document.createElement('tr');
   const local = config.local[key]; const effective = config.effective[key];
   const values = [field.label, display(local), display(effective), display(baseline[key]), locked ? config.strings.network : config.strings.localSource];
   values.forEach((value, index) => {
    const cell = document.createElement(index === 0 ? 'th' : 'td');
    if(index === 0) cell.scope = 'row'; cell.textContent = value; row.append(cell);
   });
   rows.append(row);
  });
  document.getElementById('se-compare-summary').textContent = count ? config.strings.differences.replace('%d', count) : config.strings.noDifferences;
 }
 document.getElementById('se-compare-preset').addEventListener('change', compareSaved);
 document.getElementById('se-compare-only-differences').addEventListener('change', compareSaved);
 compareSaved();
 function ruleDisplay(key,value) {
  if (/^access_(widgets|customizer|menus)_mode$/.test(key)) return config.strings.accessModes[value] || String(value);
  function choice(name) { const input=controls(key).find(item=>item.value===name || item.name.endsWith('['+name+']')); const label=input?.closest('label'); return label?.querySelector('span')?.textContent.trim() || label?.textContent.trim() || name; }
  if (Array.isArray(value)) return value.map(choice).join(', ') || '—';
  if (value && typeof value==='object') return Object.entries(value).map(([name,number])=>choice(name)+': '+number).join(', ') || '—';
  return display(value);
 }
 function ruleState(key, values) {
  const field = fields[key], local = values[key];
  const effective = key === 'login_language' && config.networkLocked ? true : local;
  const configured = typeof effective === 'boolean' ? effective : (field.type === 'int' ? !equal(effective, field.default) : typeof effective === 'string' ? effective.trim() !== '' : Object.keys(effective).length > 0);
  let reason = '';
  if (configured) {
   if (['targets','target_types'].includes(key)) {
    if (!values.targets || !values.target_types.some(type => config.availableTargetTypes.includes(type))) reason = config.strings.ruleTargetOff;
    if (config.targetProvider) reason = config.strings.ruleTargetProvider;
   }
   if (/^access_(widgets|customizer|menus)_roles$/.test(key) && values[key.replace('_roles','_mode')] !== 2) reason = config.strings.accessModes[values[key.replace('_roles','_mode')]];
   if (key === 'file_editors' && config.multisite) reason = config.strings.ruleSingle;
   if (values.comments_disabled && ['comment_types','hide_comments','comment_reply','self_pingbacks'].includes(key)) reason = config.strings.ruleComments;
   if (key === 'hide_comments' && !values.comment_types.length) reason = config.strings.ruleCommentTypes;
   if (key === 'footer' && values.footer_text.trim()) reason = config.strings.ruleFooter;
   if (['post_rename','post_plural','post_singular'].includes(key) && (!values.post_rename || !values.post_plural.trim() || !values.post_singular.trim())) reason = config.strings.ruleNames;
   if (field.dynamic && field.dynamic !== 'roles' && ['list','map'].includes(field.type)) {
    const types = field.type === 'list' ? local : Object.keys(local);
    if (types.length && !types.some(type => config.availableTypes.includes(type))) reason = config.strings.ruleTypes;
    if (key === 'classic_editor_types' && values.blog_admin && types.length && types.every(type => type === 'post')) reason = config.strings.ruleBlog;
   }
  }
  return {local,effective,active:Boolean(configured && !reason),dormant:Boolean(configured && reason),reason,source:key === 'login_language' && config.networkLocked ? config.strings.ruleNetwork : config.strings.ruleLocal,pending:!equal(local,config.local[key])};
 }
 function ruleView() {
  const values = read(), baseline = {...config.defaults,...(config.presets[ruleReference.value]?.settings || {})}, query = search.value.trim().toLocaleLowerCase(), mode = ruleFilter.value;
  const all = Object.entries(fields).filter(([key]) => !['widgets_admin','customizer_admin'].includes(key)).map(([key,field]) => ({key,field,...ruleState(key,values)}));
  const matches = all.filter(row => (row.field.label + ' ' + row.field.help).toLocaleLowerCase().includes(query) && (mode === 'all' || mode === 'active' && row.active || mode === 'different' && !equal(row.local,baseline[row.key]) || mode === 'saved-different' && !equal(config.local[row.key],baseline[row.key]) || mode === 'pending' && row.pending || mode === 'dormant' && row.dormant));
  const keys = new Set(matches.map(row => row.key));
  Object.keys(fields).forEach(key => form.querySelector('[data-setting="'+key+'"]').hidden = !keys.has(key));
  form.querySelectorAll('.se-field-group').forEach(group => { group.hidden = !group.querySelector('[data-setting]:not([hidden])'); });
  const focusedCard = document.activeElement?.closest('[data-setting]');
  const rename = document.getElementById('se-field-post_rename'), names = document.getElementById('se-post-names');
  const renameCard = form.querySelector('[data-setting="post_rename"]'), renameGroup = form.querySelector('.se-rename-group');
  const nameCards = [...names.querySelectorAll('[data-setting]')];
  names.hidden = !rename.checked;
  rename.setAttribute('aria-controls', names.id); rename.setAttribute('aria-expanded', String(rename.checked));
  names.querySelectorAll('input').forEach(input => { input.readOnly = !rename.checked; });
  if (nameCards.some(card => !card.hidden)) renameCard.hidden = false;
  renameGroup.hidden = renameCard.hidden && nameCards.every(card => card.hidden);
  if (focusedCard?.hidden) ruleFilter.focus();
  if (query || mode !== 'all') {
   nav.forEach(link => link.removeAttribute('aria-current'));
   const groups = new Set(matches.map(row => row.field.group));
   panels.forEach(panel => panel.hidden = panel.id !== 'se-overview' && !groups.has(panel.id.replace('se-','')));
  } else {
   panels.forEach(panel => panel.hidden = panel.id !== 'se-'+selectedSection);
   nav.forEach(link => { if(link.dataset.section === selectedSection) link.setAttribute('aria-current','page'); else link.removeAttribute('aria-current'); });
  }
  document.getElementById('se-search-status').textContent = query || mode !== 'all' ? (matches.length ? config.strings.ruleMatches.replace('%d',matches.length) : config.strings.searchNone) : '';
  document.getElementById('se-rule-summary').textContent = config.strings.ruleCounts.replace('%1$d',all.filter(row=>row.active).length).replace('%2$d',all.filter(row=>row.dormant).length).replace('%3$d',all.filter(row=>row.pending).length);
  if (query || mode !== 'all') document.getElementById('se-rule-details').open = true;
  const body = document.getElementById('se-rule-rows'); body.replaceChildren();
  matches.forEach(row => {
   const tr = document.createElement('tr'); tr.dataset.rule = row.key;
   const td = document.createElement('td'), button = document.createElement('button');button.type = 'button';button.className = 'button-link';button.textContent = row.field.label;button.title = config.strings.ruleJump;
   button.addEventListener('click',()=>{search.value='';ruleFilter.value='all';selectSection(row.field.group,false);history.replaceState(null,'','#se-'+row.field.group);const card=form.querySelector('[data-setting="'+row.key+'"]');if (['post_plural','post_singular'].includes(row.key) && !document.getElementById('se-field-post_rename').checked) { document.getElementById('se-field-post_rename').focus(); } else { card.tabIndex=-1;card.focus(); }});td.append(button);tr.append(td);
   [config.groups[row.field.group],ruleDisplay(row.key,config.local[row.key]),ruleDisplay(row.key,row.local),ruleDisplay(row.key,row.effective),ruleDisplay(row.key,baseline[row.key]),(row.active?config.strings.ruleActive:row.dormant?config.strings.ruleDormant:config.strings.ruleOff)+' · '+row.source+(row.reason?' · '+row.reason:''),row.pending?config.strings.ruleDraft:config.strings.ruleSaved].forEach(value=>{const cell=document.createElement('td');cell.textContent=value;tr.append(cell);});body.append(tr);
  });
 }
 search.addEventListener('input',ruleView);
 ruleFilter.addEventListener('change',ruleView);
 ruleReference.addEventListener('change',ruleView);
 search.addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();search.value='';ruleFilter.value='all';ruleView();}});
 ruleView();
})();
