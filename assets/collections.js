(function () {
 'use strict';
 const config = window.siteEssentialsCollections, base = window.siteEssentials;
 const source = document.getElementById('se-collection-json');
 if (!config || !base || !source) return;
 const file = document.getElementById('se-collection-file'), rows = document.getElementById('se-collection-rows'), error = document.getElementById('se-collection-error'), summary = document.getElementById('se-collection-summary'), commit = document.getElementById('se-collection-commit'), review = document.getElementById('se-collection-review');
 let pending = null, submitting = false, generation = 0;
 function resetReview() { generation++; pending = null; rows.replaceChildren(); summary.textContent = ''; commit.disabled = true; document.getElementById('se-collection-payload').value = ''; document.getElementById('se-collection-revision').value = ''; }
 function clear() { resetReview(); source.value = ''; file.value = ''; error.textContent = ''; }
 function showError(message) { document.querySelector('[data-section="collections"]').click(); error.textContent = message; error.focus(); }
 source.addEventListener('input', () => { resetReview(); error.textContent = ''; });
 document.getElementById('se-collection-clear').addEventListener('click', clear);
 document.getElementById('se-discard-draft').addEventListener('click', clear);
 file.addEventListener('change', async () => {
  resetReview(); source.value = ''; error.textContent = ''; const selected = file.files[0]; if (!selected) return;
  if (selected.size > config.maxBytes) { error.textContent = config.strings.limit; return; }
  const current = generation;
  try { const text = await selected.text(); if (current === generation) source.value = text; }
  catch (_) { error.textContent = config.strings.error; }
 });
 function update() {
  if (!pending) return;
  const used = new Set(pending.names.map(name => name.toLocaleLowerCase())), selected = [...rows.querySelectorAll('[data-pick]')].filter(input => input.checked);
  let valid = selected.length > 0 && selected.length <= pending.capacity;
  rows.querySelectorAll('[data-pick]').forEach(input => { rows.querySelector('[data-name="' + input.value + '"]').disabled = !input.checked; });
  selected.forEach(input => {
   const name = rows.querySelector('[data-name="' + input.value + '"]').value.trim(), folded = name.toLocaleLowerCase();
   if (!name || [...name].length > 80 || new TextEncoder().encode(name).length > 320 || used.has(folded)) valid = false;
   used.add(folded);
  });
  summary.textContent = config.strings.capacity.replace('%1$d', selected.length).replace('%2$d', pending.capacity);
  commit.disabled = !valid;
  error.textContent = selected.length && !valid ? (selected.length > pending.capacity ? config.strings.limit : config.strings.collision) : '';
 }
 function display(value) { if (typeof value === 'boolean') return base.strings[String(value)]; if (value && typeof value === 'object') return JSON.stringify(value); return String(value); }
 review.addEventListener('click', async () => {
  resetReview(); error.textContent = ''; const text = source.value, current = generation;
  if (new TextEncoder().encode(text).length > config.maxBytes) { error.textContent = config.strings.limit; return; }
  review.disabled = true;
  try {
   const response = await fetch(config.url, {method:'POST', credentials:'same-origin', body:new URLSearchParams({action:'site_essentials_collection_review', _wpnonce:config.nonce, collection:text})});
   const result = await response.json();
   if (current !== generation) return;
   if (!response.ok || !result.success) throw new Error(result.data?.message || config.strings.error);
   pending = result.data;
   document.getElementById('se-collection-payload').value = text;
   document.getElementById('se-collection-revision').value = pending.revision;
   pending.profiles.forEach(profile => {
    const row = document.createElement('div'); row.className = 'se-collection-row';
    const pickLabel = document.createElement('label'); pickLabel.className = 'se-choice';
    const pick = document.createElement('input'); pick.type = 'checkbox'; pick.dataset.pick = ''; pick.name = 'collection_pick[]'; pick.value = profile.index; pick.checked = profile.index < pending.capacity; pick.setAttribute('form', 'se-collection-import-form');
    const title = document.createElement('strong'); title.textContent = profile.source; pickLabel.append(pick, title); row.append(pickLabel);
    const note = document.createElement('p'); note.className = 'description'; note.textContent = profile.description; row.append(note);
    if (profile.renamed) { const hint = document.createElement('p'); hint.textContent = config.strings.renamed; row.append(hint); }
    const label = document.createElement('label'); label.htmlFor = 'se-collection-name-' + profile.index; label.textContent = config.strings.name;
    const name = document.createElement('input'); name.id = label.htmlFor; name.type = 'text'; name.maxLength = 80; name.dataset.name = profile.index; name.value = profile.label; name.name = 'collection_names[' + profile.index + ']'; name.setAttribute('form', 'se-collection-import-form'); name.required = true;
    row.append(label, name);
    const details = document.createElement('details'), heading = document.createElement('summary'), list = document.createElement('ul'); heading.textContent = config.strings.rules;
    Object.entries(profile.settings).forEach(([key,value]) => { const line = document.createElement('li'); line.textContent = base.schema[key].label + ': ' + display(value); list.append(line); });
    details.append(heading, list); row.append(details); rows.append(row);
   });
   update();
  } catch (exception) { if (current === generation) error.textContent = exception.message || config.strings.error; }
  finally { review.disabled = false; }
 });
 rows.addEventListener('input', update); rows.addEventListener('change', update);
 document.addEventListener('submit', event => {
  const form = event.target;
  if (form.id === 'se-collection-export-form') {
   if (!document.querySelector('[name="collection_ids[]"]:checked')) { event.preventDefault(); showError(config.strings.choose); } return;
  }
  if (form.id === 'se-collection-import-form') {
   update();
   if (!pending || commit.disabled || window.siteEssentialsDraft?.has()) { event.preventDefault(); event.stopImmediatePropagation(); showError(window.siteEssentialsDraft?.has() ? base.strings.draftFirst : config.strings.collision); return; }
   submitting = true; return;
  }
  if (source.value && (form.id === 'se-settings' || (form.matches('.se-preset-action') && form.id !== 'se-preset-export-form') || form.id === 'se-action-restore' || form.matches('.se-clear-action'))) {
   event.preventDefault(); event.stopImmediatePropagation(); showError(config.strings.draft);
  }
 }, true);
 window.addEventListener('beforeunload', event => { if (!submitting && source.value) { event.preventDefault(); event.returnValue = ''; } });
})();
