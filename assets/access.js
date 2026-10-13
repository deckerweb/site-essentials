/* Local-only personal exceptions; never part of the portable configuration object. */
(function () {
 'use strict';
 var cfg = window.siteEssentialsAccess, form = document.getElementById('se-settings');
 if (!cfg || !form) { return; }
 function personalState() { return [...form.querySelectorAll('input[name^="exceptions["]')].filter(node => node.type === 'checkbox' && node.checked).map(node => node.name + ':' + node.value).sort(); }
 var initial = personalState();
 window.siteEssentialsAccessDirty = function () { return JSON.stringify(personalState()) !== JSON.stringify(initial); };
 document.getElementById('se-discard-draft').addEventListener('click', function () {
  form.querySelectorAll('input[name^="exceptions["]').forEach(function (node) { if (node.type === 'checkbox') { node.checked = initial.includes(node.name + ':' + node.value); } });
  visibility(); form.dispatchEvent(new Event('input', {bubbles:true}));
 });
 function visibility() {
  form.querySelectorAll('[data-access-mode]').forEach(function (mode) {
   var area = mode.dataset.accessMode, roles = form.querySelector('[data-setting="access_' + area + '_roles"]'), personal = form.querySelector('[data-access-personal="' + area + '"]');
   if (roles) { roles.classList.toggle('se-access-inactive', mode.value !== '2'); }
   if (personal) { personal.hidden = mode.value !== '2'; }
   var legacy = document.getElementById('se-field-' + area + '_admin');
   if (legacy && legacy.checked !== (mode.value === '1')) { legacy.checked = mode.value === '1'; legacy.dispatchEvent(new Event('change', {bubbles:true})); }
  });
 }
 form.addEventListener('change', visibility);
 new MutationObserver(visibility).observe(form, {childList:true,subtree:true});
 visibility();
 form.querySelectorAll('.se-access-picker').forEach(function (picker) {
  var input = picker.querySelector('.se-access-search'), button = picker.querySelector('.se-access-find'), results = picker.querySelector('.se-access-results'), status = picker.querySelector('.se-access-status'), ticket = 0;
  async function find() {
   var term = input.value.trim(), current = ++ticket;
   results.replaceChildren();
   if (term.length < 2) { status.textContent = cfg.short; return; }
   status.textContent = cfg.searching;
   try {
    var response = await fetch(cfg.url, {method:'POST', credentials:'same-origin', body:new URLSearchParams({action:'site_essentials_access_search',nonce:cfg.nonce,term:term})});
    var data = await response.json(); if (current !== ticket) { return; }
    if (!response.ok || !data.success || !Array.isArray(data.data)) { throw new Error('Lookup'); }
    status.textContent = data.data.length ? '' : cfg.empty;
    data.data.forEach(function (user) {
     if (!Number.isSafeInteger(user.id) || user.id < 1 || typeof user.label !== 'string') { return; }
     var selected = picker.querySelector('.se-access-selected input[value="' + user.id + '"]');
     if (selected) { return; }
     var label = document.createElement('label'), checkbox = document.createElement('input');
     label.className = 'se-choice'; checkbox.type = 'checkbox'; checkbox.value = String(user.id);
     label.append(checkbox, document.createTextNode(' ' + user.label)); results.append(label);
     checkbox.addEventListener('change', function () {
      checkbox.name = 'exceptions[' + picker.dataset.area + '][' + picker.dataset.kind + '][]';
      picker.querySelector('.se-access-selected').append(label);
     });
    });
   } catch (error) { if (current === ticket) { status.textContent = cfg.failed; } }
  }
  button.addEventListener('click',find);
  input.addEventListener('keydown',function (event) { if (event.key === 'Enter') { event.preventDefault(); find(); } });
 });
}());
