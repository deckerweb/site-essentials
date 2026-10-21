# Website checks

[Deutsch](Website-Pruefungen) · [Home](Home)

Open **Settings → Site Essentials → Website checks**. The three reminders cover discouraged search indexing, the assigned native privacy page and a selected or recognized imprint page. They inspect publication and recognizable content, not legal text quality. If indexing is intentionally discouraged, confirm the current decision; an actual visibility change invalidates that acknowledgement.

![Website reminders](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/checks-en.png)

Administrator reminders check visibility and recognizable page content; they do not assess legal compliance.

## Addresses and public resources

The Site Address identifies the public website; the WordPress Address identifies the installation files. Both are read-only and copyable. The Site Health link opens the existing WordPress tool.

![Public website resources](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/public-resources-en.png)

Open robots.txt, sitemap and feed addresses without changing them; start a response check only when needed.

Open robots.txt at the host root, available sitemap links and feed links. Site Essentials does not edit these files. If a sitemap is provided by another plugin, the native WordPress sitemap may be unavailable; sitemap declarations found in an inspected robots.txt can provide further links. Feed restrictions can intentionally make listed feeds unavailable.

## Manual public response check

The check starts only after an administrator submits it. It retrieves the homepage and robots.txt anonymously through the safe WordPress HTTP API, without login cookies. It reports response status, redirects, content type, indexing headers/meta and canonical information where recognizable. Remote content is displayed as text, not executed. Results are not stored permanently and can be copied as a compact summary. There is no background monitoring or external analysis service.

Hosting restrictions, loopback access and caches may change results; one response is not proof of search-engine indexing. Maintenance may deliberately produce HTTP 503.

## Visitor navigation tests

The two test actions open a nonexistent URL and a search with no expected match. The theme or builder determines their design. Site Essentials does not assign its own 404 page or replace the search template.
