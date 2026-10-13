/* Site-local, bounded indexing. No uploads, replacement or remote requests. */
(function () {
 'use strict';
 var cfg = window.siteEssentialsMedia;
 if (!cfg) { return; }
 var root = document.querySelector('.wrap'), stopped = false, processed = 0, controller = new AbortController();
 if (!root) { return; }
 var box = document.createElement('div'), text = document.createElement('p'), status = document.createElement('span'), button = document.createElement('button');
 box.className = 'notice notice-info'; box.id = 'se-media-index-status'; status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
 status.textContent = cfg.working; button.type = 'button'; button.className = 'button'; button.textContent = cfg.stop; button.style.marginInlineStart = '12px';
 text.append(status, button); box.append(text); root.append(box);
 button.addEventListener('click', function () { stopped = true; controller.abort(); status.textContent = cfg.failed; button.remove(); });
 window.addEventListener('pagehide', function () { stopped = true; controller.abort(); });
 async function batch() {
  if (stopped) { return; }
  try {
   var body = new URLSearchParams({action: 'site_essentials_media_index', nonce: cfg.nonce});
   var response = await fetch(cfg.url, {method: 'POST', credentials: 'same-origin', body: body, signal: controller.signal});
   if (!response.ok) { throw new Error('Index request failed'); }
   var result = await response.json();
   if (!result.success || !result.data || typeof result.data.processed !== 'number') { throw new Error('Invalid index response'); }
   processed += result.data.processed;
   if (result.data.done) { status.textContent = processed ? cfg.ready : ''; button.remove(); if (!processed) { box.remove(); } return; }
   status.textContent = cfg.working + ' ' + processed; window.setTimeout(batch, 250);
  } catch (error) { if (!stopped) { status.textContent = cfg.failed; button.remove(); } }
 }
 batch();
}());
