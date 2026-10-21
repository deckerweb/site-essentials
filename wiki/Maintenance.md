# Maintenance mode

[Deutsch](Wartungsmodus) · [Home](Home)

1. Open **Settings → Site Essentials → Maintenance mode**.
2. Create a maintenance page, or choose an existing published page without a password.
3. Design it with the configured Block Editor or Classic Editor.
4. Preview the saved page as an administrator.
5. Enable maintenance and use **Save maintenance mode**. Test anonymously in a private browser window.

![Maintenance with your own page](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/maintenance-en.png)

Select a published WordPress page. Maintenance uses HTTP 503 and has its own save action.

Visitors receive **HTTP 503**, `Retry-After` and no-cache/noindex headers. Administrators retain normal access; the orange toolbar entry identifies active own maintenance. Login, APIs, webhooks and background tasks remain available. This is not an access-control or privacy barrier.

## Maintenance is not Coming Soon

This mode is for temporary work on an existing website. A long-running public launch page normally needs HTTP 200 and separate indexing/access choices. Site Essentials has no Coming Soon mode or status-code switch. Use an appropriate theme, builder or specialist plugin for that workflow.

## Other providers and caches

Active maintenance in Bricks, Elementor or Breakdance takes precedence. Site Essentials pauses its own response without changing foreign settings. After a conflict, resume it deliberately rather than expecting automatic reactivation.

A hosting/CDN page cache can serve a previously saved page before WordPress runs, even without a cache plugin. Clear or bypass it manually where necessary, and check the public response after enabling and disabling maintenance. The plugin does not reconfigure foreign caches.

Maintenance activation, page assignment and recorded start time are operational data for this website. They are excluded from presets, imports, exports and configuration recovery.
