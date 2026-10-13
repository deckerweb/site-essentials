/* Fixed display policies; native value inputs and menu saving remain untouched. */
(function () {
 'use strict';
 var cfg = window.siteEssentialsMenuInterface;
 if (!cfg) { return; }
 Object.entries(cfg.rules).forEach(function (entry) {
  var name = entry[0], show = entry[1], checkbox = document.querySelector('.hide-column-tog[value="' + name + '"]');
  if (!checkbox) { return; }
  checkbox.checked = show; checkbox.disabled = true; checkbox.title = cfg.fixed;
  var label = checkbox.closest('label');
  if (label) { label.title = cfg.fixed; }
  document.querySelectorAll('#menu-to-edit .field-' + name).forEach(function (field) { field.classList.toggle('hidden-field', !show); });
 });
 var controls = document.querySelector('#adv-settings');
 if (controls) {
  var note = document.createElement('p'); note.className = 'description'; note.textContent = cfg.note;
  controls.append(note);
 }
}());
