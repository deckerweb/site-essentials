/* Progressive controls for our native destination box and selected migration form. */
(function () {
 'use strict';
 function init() {
  const box = document.getElementById('se-alternative-target');
  if (box && !box.dataset.seReady) {
   box.dataset.seReady = '1';
   const fields = box.querySelector('#se-target-fields');
   function showFields() { if (fields) fields.hidden = box.querySelector('[name="se_target_mode"]:checked')?.value !== 'custom'; }
   box.querySelectorAll('[name="se_target_mode"]').forEach(input => input.addEventListener('change', showFields));
   showFields();
  }
  const form = document.getElementById('se-target-import');
  if (form && !form.dataset.seReady) {
   form.dataset.seReady = '1';
   const choices = Array.from(form.querySelectorAll('input[name^="selected["]'));
   const all = form.querySelector('#se-target-select-page'), submit = form.querySelector('[type="submit"]');
   function state() {
    const count = choices.filter(input => input.checked).length;
    if (all) { all.disabled = !choices.length; all.checked = choices.length > 0 && count === choices.length; all.indeterminate = count > 0 && count < choices.length; }
    if (submit) submit.disabled = count === 0;
   }
   if (all) all.addEventListener('change', () => { choices.forEach(input => { input.checked = all.checked; }); state(); });
   choices.forEach(input => input.addEventListener('change', state));
   state();
  }
 }
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
 // Core moves legacy meta boxes into the block editor after initializing React.
 if (!document.getElementById('se-target-import') && !document.getElementById('se-alternative-target')?.dataset.seReady) {
  const observer = new MutationObserver(() => { init(); if (document.getElementById('se-alternative-target')?.dataset.seReady) observer.disconnect(); });
  observer.observe(document.body, {childList:true,subtree:true});
 }
 if (window.wp?.apiFetch?.use) {
  wp.apiFetch.use((options, next) => next(options).then(async response => {
   if (options.parse === false && options.method === 'POST' && options.url === window._wpMetaBoxUrl && response?.ok && document.querySelector('[name="se_target_revision"]')) {
    // Native meta-box saves return the re-rendered editor HTML. Read only our fresh hash;
    // never execute or inject its markup, and never fetch another endpoint.
    try {
     const html = await response.clone().text();
     const match = html.match(/name="se_target_revision" value="([a-f0-9]{64})"/);
     if (match) document.querySelector('[name="se_target_revision"]').value = match[1];
    } catch (_) { /* A missing response hash retains stale-write protection. Reload remains available. */ }
   }
   return response;
  }));
 }
}());
