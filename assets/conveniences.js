(function () {
 'use strict';
 document.getElementById('se-menu-copy-form')?.addEventListener('submit', function (event) { if (window.siteEssentialsDraft?.has()) { event.preventDefault(); window.alert(seConveniences.draft); } });
 document.addEventListener('click', async function (event) {
  const button = event.target.closest('[data-se-copy], .se-copy-code');
  if (!button) return;
  const value = button.dataset.seCopy || button.parentElement.querySelector('[data-se-copy-text]')?.textContent;
  if (!value) return;
  let status = document.getElementById('se-copy-status');
  if (!status) { status = document.createElement('p'); status.id = 'se-copy-status'; status.className = 'screen-reader-text'; status.setAttribute('role', 'status'); document.body.append(status); }
  try { await navigator.clipboard.writeText(value); status.textContent = seConveniences.copied; button.textContent = seConveniences.copied; }
  catch (_) { status.textContent = seConveniences.failed; const text = button.parentElement.querySelector('.se-id-value, [data-se-copy-text]'); if (text) { const range = document.createRange(); range.selectNodeContents(text); const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(range); } button.title = seConveniences.failed; }
 });
}());
