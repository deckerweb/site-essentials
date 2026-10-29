# Configuration information and shortcuts

[Deutsch](Diagnose) · [Home](Home)

Open **Tools → Site Health → Info → Site Essentials**. Version, scope and configuration validity appear first, followed by network policy and operational information such as maintenance. Convenience features appear later.

![Site Essentials configuration information in Site Health](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/release110-health-en.png)

These are configuration states, not runtime tests or a security score. The section omits customer text, preset names and notes, selected users and image URLs/IDs. Copying the native Site Health report also copies information from WordPress and other plugins; review the complete report before sharing it.

## Open settings through the command palette

Where WordPress already provides its command palette, administrators can search for **Site Essentials**. Commands open settings, reports, website checks, maintenance, dashboard information, system information and storage, and, while SE local avatars are active, the default-avatar controls. They navigate only; they never activate a feature or save changes.

## Find stored settings without an effect

In the rule overview, select the **stored but not applied** count when it is nonzero. The overview opens the affected rules with reasons and setting shortcuts. A retained setting can be intentionally paused by another provider or a more comprehensive rule.

[Website checks](Website-checks) contains the manual public-response checks. Those checks are separate from Site Health configuration information.


## System information and storage

Under **Tools → System information and storage**, separate sections cover WordPress/PHP, memory and execution time, uploads and forms, upload file storage, and technical details. Section links provide direct navigation. Technical keys remain visible below readable labels. Upload diagnosis is optional: when disabled, a link opens the media setting; opening the page never starts a measurement. When enabled, the saved result includes its measurement time. The internal page address remains unchanged.


Suhosin remains expandable under further technical details even when the extension is not detected. Unavailable values are labeled accordingly. The native Site Health link appears at the top and again at the bottom.

![System information and storage with section links and grouped diagnostics](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/release111-system-en.png)
