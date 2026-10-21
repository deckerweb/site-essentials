/* Reuse the native editor, setting and publish flow. */
(function () {
 'use strict';
 wp.customize.bind('ready', function () {
  const section = wp.customize.section('custom_css');
  if (!section) return;
  const controls = section.contentContainer?.[0];
  if (!controls || document.getElementById('se-css-width')) return;
  const group = document.createElement('li'); group.id = 'se-css-width'; group.className = 'customize-control';
  const button = document.createElement('button'); button.type = 'button'; button.className = 'button'; button.textContent = seCssWidth.expand; button.setAttribute('aria-expanded', 'false');
  const label = document.createElement('label'); label.textContent = seCssWidth.width; label.hidden = true;
  const slider = document.createElement('input'); slider.type = 'range'; slider.min = '30'; slider.max = '70'; slider.step = '5'; slider.value = '50'; label.append(slider);
  group.append(button, label); if (controls.firstElementChild) controls.firstElementChild.after(group); else controls.append(group);
  let expanded = false;
  function layout() {
   document.body.classList.toggle('se-css-expanded', expanded && section.expanded.get());
   document.body.style.setProperty('--se-css-width', slider.value + 'vw');
   button.textContent = expanded ? seCssWidth.normal : seCssWidth.expand;
   button.setAttribute('aria-expanded', String(expanded)); label.hidden = !expanded;
   requestAnimationFrame(() => { wp.customize.control.each(control => { if (control.params.section === 'custom_css') control.editor?.codemirror?.refresh(); }); });
  }
  button.addEventListener('click', () => { expanded = !expanded; layout(); });
  slider.addEventListener('input', layout);
  section.expanded.bind(layout);
 });
})();
