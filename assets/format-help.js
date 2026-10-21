/* Fixed examples supplement native controls without changing their values or events. */
(function () {
 'use strict';
 const config = window.seFormatHelp;
 if (!config) return;
 document.querySelectorAll('input[name="time_format"]').forEach((input, index) => {
  const text = config.time[input.value];
  if (!text) return;
  const help = document.createElement('span');
  help.id = 'se-time-help-' + index; help.className = 'description';
  help.style.display = 'block'; help.style.marginInlineStart = '25px';
  help.textContent = text;
  input.closest('label')?.append(help);
  input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' ' + help.id).trim());
 });
 const input = document.querySelector('input[name="date_format"]');
 if (input) {
  const help = document.createElement('p'); help.id = 'se-date-help'; help.className = 'description'; help.textContent = config.date;
  input.closest('fieldset')?.append(help);
 }
})();
