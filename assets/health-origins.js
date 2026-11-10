/** Make proven Site Health origins visible on closed headings, without changing reports. */
(function () {
 'use strict';
 function badge(button, text) {
  if (!button || button.querySelector('.se-health-source-badge')) return;
  var node = document.createElement('span');
  node.className = 'se-health-source-badge';
  node.textContent = text;
  node.setAttribute('aria-label', text);
  var title = button.querySelector('.title');
  (title || button).appendChild(node);
 }
 function decorate(root) {
  root.querySelectorAll('.se-health-origin').forEach(function (origin) {
   var panel = origin.closest('.health-check-accordion-panel');
   var heading = panel && panel.previousElementSibling;
   badge(heading && heading.querySelector('button'), origin.dataset.seSource || origin.textContent.trim());
  });
  Object.entries(window.seHealthSources || {}).forEach(function (entry) {
   badge(document.getElementById('health-check-section-' + entry[0]), entry[1]);
  });
 }
 function start() {
  var root = document.querySelector('.health-check-body');
  if (!root) return;
  decorate(root);
  new MutationObserver(function () { decorate(root); }).observe(root, {childList: true, subtree: true});
 }
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
 else start();
}());
