document.addEventListener('submit', function (event) {
 'use strict';
 if (!event.target.id.startsWith('se-check-') || !window.siteEssentialsDraft?.has()) return;
 event.preventDefault(); event.stopImmediatePropagation();
 const message = document.getElementById('se-check-draft-message');
 if (message) { message.textContent = window.siteEssentialsChecks.draft; message.focus(); }
}, true);
