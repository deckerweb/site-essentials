/* Fixed examples supplement native controls without changing their values or events. */
(function () {
 'use strict';
 const config = window.seFormatHelp;
 if (!config) return;
 const icon = document.querySelector('.site-icon-section td');
 if (icon && !document.getElementById('se-icon-help')) {
  const help = document.createElement('p'); help.id = 'se-icon-help'; help.className = 'description'; help.textContent = config.icon; icon.append(help);
 }
 if (!config.enabled) return;
 document.querySelectorAll('input[name="time_format"]').forEach((input, index) => {
  const text = config.time[input.value];
  if (!text) return;
  const help = document.createElement('span');
  help.id = 'se-time-help-' + index; help.className = 'description';
  help.classList.add('se-time-help');
  help.textContent = text;
  input.closest('label')?.append(help);
  input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' ' + help.id).trim());
 });
 const input = document.querySelector('input[name="date_format"]');
 if (input) {
  const help = document.createElement('dl'); help.id = 'se-date-help'; help.className = 'description se-date-help';
  config.date.forEach(item => {
   const row = document.createElement('div'), label = document.createElement('dt'), value = document.createElement('dd'), code = document.createElement('code');
   label.textContent = item.label; code.textContent = item.code; value.append(code, document.createTextNode(' → ' + item.example)); row.append(label, value); help.append(row);
  });
  input.closest('fieldset')?.append(help);
 }
})();
