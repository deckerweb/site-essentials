# Site Essentials

![Site Essentials](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/banner-en-github-1280x640.png)

[Deutsch](Home-de)

**Your WordPress. Your settings.**

## About

Configure WordPress core behavior with focused, optional controls. Review presets and imports before saving; keep settings independent on each site.

**0.9.0 · WordPress 7.1.2+ · PHP 8.2+**

[Documentation](https://deckerweb.github.io/site-essentials/) · [FAQ by topic](FAQ)

## Contents

- [At a glance](#at-a-glance)
- [Installation](#installation)
- [Core controls](#core-controls)
- [Configuration](#configuration)
- [Behavior and limits](#behavior-and-limits)
- [Multisite](#multisite)
- [Preconfigured installation](#preconfigured-installation)
- [Stored data](#stored-data)
- [Components and connections](#components-and-connections)
- [FAQ](#faq)
- [Changelog](#changelog)
- [Project](#project)
- [Issues and security](#issues-and-security)
- [Support](#support)
- [License and credits](#license-and-credits)

## At a glance

- Optional controls for login, editors, admin screens, headers and feeds.
- Comments, avatars, Heartbeat and revisions configured per site.
- Media file sizes, sorting and document-type filters.
- Focused appearance access by role with local personal exceptions.
- Alternative post destinations and selected Page Links To migration.
- Presets, draft review, import/export and configuration recovery.
- Administrator website reminders, PHP information and Site Health details.
- Single-site and Multisite; English and both German locales.

## Installation

Upload the ZIP through Plugins → Add Plugin → Upload Plugin and activate it. Open Settings → Site Essentials. Choose a preset, review the changes and save all settings. No cleanup is enabled by default; three administrator-only website reminders are enabled.

## Core controls

- Login language selector, administrator email confirmation and optional site-specific login logo link/text.
- Classic editor by content type, classic widget interface and a warning for existing block content.
- Separate automatic URL conversion and own oEmbed provider controls, preserving authored embeds and ordinary post links.
- Post editor fullscreen, welcome guide, block directory, bundled core patterns, remote pattern suggestions and template editing.
- Selected core dashboard widgets, toolbar entries and plain-text admin footer.
- Core fallback favicon removal preserves custom site icons.
- Generator, RSD, WLW, shortlinks and feed discovery; feed endpoints can return HTTP 404.
- Complete core comment shutdown with admin, templates, blocks, feeds, REST/XML-RPC and dedicated assets covered; private notes retained.
- Local avatars with site-specific assignments and bounded authorized profile uploads; no Gravatar fallback.
- Portable collections of selected custom presets; protected review, fresh local copies and collision-safe names.
- Comments by content type, optional hiding of existing frontend comments, conditional reply script and self-pingbacks.
- Emoji assets, public-visitor jQuery Migrate removal and XML-RPC HTTP 403.
- Heartbeat intervals by area and content type, revision limits and selective notifications.
- One reports navigation shortcut in the native command palette; a data-minimal Site Health Info section for administrators.
- Separate REST and oEmbed discovery controls; optional single-site file-editor permission protection.
- On-demand data-minimal diagnostics, read-only printable handover and separate local/network clearing.
- Documented presets contain explicitly entered names and notes plus an opaque export identifier. Do not enter private data.
- Five built-in presets, up to 20 named local presets, saved-state comparison, settings search, selective change previews, JSON merge/replace, export and one-step configuration rollback.
- Optional public Dashicons cleanup, PDF preview suppression, original WordPress spelling, simplified toolbar greeting, public search and author archive restrictions, capability-based core update notices, comment URL auto-link control and selected classic core widgets.
- Administrator-only website checks for discouraged indexing, assigned privacy pages and explicit or automatically detected imprint pages. Three reminder switches are enabled by default; they never change visibility or publish pages automatically.
- Separate native post management and public blog shutdown; optional plural/singular post names, with stored content and technical post type retained.
- Read-only administrator PHP information under Tools, expandable cache/extension/Suhosin support and optional PHP/memory footer.
- Optional native media list file-size display and sorting, with bounded site-local indexing; PDF, Word, Excel, PowerPoint, ZIP, OpenDocument, CSV and text filters in list and grid views.
- Optional native Widgets and Customizer admin-screen restrictions, preserving saved theme/sidebar settings and frontend output. Classic Bricks themes automatically hide native Site Editor menu links.
- Role-based native Widgets, Customizer and classic-menu access policies with local personal allow/block exceptions. Classic-menu delegation uses scoped native screen/AJAX permissions without changing stored role capabilities.
- Optionally hide selected core Tools menu links; direct access, Site Health, privacy tools and plugin pages remain available.
- Optional core AI opt-out with Connectors page restriction; credentials retained and independent plugin AI outside scope.
- Hide post-by-email and update-service settings independently, retaining configured values and their runtime behavior.
- Optional alternative post destinations for selected public content types; HTTP(S) and root-relative targets, reversible HTTP 302 redirects and retained editor/preview addresses.
- Administrator-only, paginated Page Links To destination preview and selected copying, preserving source metadata and skipping occupied, stale, unauthorized, unsafe or looping records.
- Optional website-wide native menu-field policies and removal of Bricks templates/taxonomies from navigation offers, retaining underlying user preferences and existing link values.

## Configuration

Presets and imports modify the draft, not the site. Only Save all settings applies the configuration. Export downloads saved local settings, not unsaved changes. WordPress defaults can be reviewed as a preset. System behavior remains unchanged unless explicitly configured. Manual changes have an optional read-only review before saving. Preset names and notes are explicitly entered and included in exports; no site domains or user data are gathered automatically. Diagnostics omit preset notes and custom footer text. Clearing local data removes local avatar assignments, presets and rollback history while keeping neutral defaults to prevent startup presets from returning; network clearing releases only the current network policy.

## Behavior and limits

Template editing is blocked, but existing templates still render. jQuery Migrate removal applies only to signed-out frontend visitors. Heartbeat intervals are initial polling settings; WordPress or another script can subsequently adjust them. Revision limits do not run an immediate cleanup; core may prune older revisions on later saves. Existing comment hiding applies to ordinary frontend comment templates, not arbitrary custom database queries. XML-RPC blocking occurs after WordPress loads; it is not a web-server firewall.

## Multisite

Activate on one site or network-wide. Sites retain independent options, including sites created after activation. Network Admin → Settings → Site Essentials can hide the login language selector on every site or leave it to local settings. No other network policy is imposed.

## Preconfigured installation

For a deliberately preconfigured deployment, set startup-preset.json to {"preset":"builder"}, "comfort", "head", "classic" or "defaults" before installation. The selected configuration initializes each unconfigured site on first use. Existing configurations are retained. The shipped file uses null.

## Stored data

Local options: site_essentials_settings, site_essentials_previous and site_essentials_presets. Site-scoped user metadata: `site_essentials_avatar_SITE_ID`, containing local attachment IDs. Avatar media remain normal WordPress uploads. Network option: site_essentials_network. Deactivation and uninstall retain these values. The embedded Library removes its own temporary data only under its final-host cleanup contract; shared settings are retained by default. No content or other plugin data is deleted. Local option site_essentials_checks retains local imprint assignment and content/indexing confirmations. It is not part of configuration exports; explicit local RESET removes it without changing native visibility/privacy assignments. The optional media sorting index stores only derived byte counts (or an unavailable marker) in site-local attachment metadata _site_essentials_file_size. Local plugin-data clearing removes the index without touching attachments or files. Site-local option site_essentials_access_users contains personal appearance exceptions. It is excluded from all exports, preset snapshots and diagnostic output. Content metadata _site_essentials_target stores explicit destination URL and new-tab/nofollow flags. It is retained by configuration clearing and plugin uninstall and excluded from configuration/preset exports.

## Components and connections

Includes deckerweb Library 0.9.0 and deckerweb GitHub Release Updater 2.1.0, GPL-2.0-or-later, by David Decker. Library online catalog access is optional and initially off. The updater checks the configured GitHub repository through normal WordPress update checks; request metadata such as your server IP is sent to GitHub. No analytics or remote fonts are included. This direct-distribution package is not a WordPress.org build.

## FAQ

### Does activation immediately change WordPress?

The shipped configuration enables three administrator-only website reminders: indexing, privacy page and imprint. Cleanup and behavior-changing modules remain off until selected or a preset is applied. An explicitly configured startup preset is an exception.

### How do presets and imports work?

Built-in and named local presets use the same review flow. Custom presets snapshot saved local settings only and can be exported individually. Review the differences, select changes and transfer them into the draft. Save to apply. Merge keeps unspecified draft values; replace uses defaults for unspecified settings.

### How do preset collections work?

Export selected custom profiles, then review the collection on a target site. Choose copies and their unique names. The import uses one confirmed preset-store write after full validation; existing presets, active settings and configuration history are retained. Collections allow up to 20 profiles and 256 KiB; each site stores up to 20 custom presets. Avatars and media assignments are not transferred.

### Does complete comment shutdown delete existing comments?

No. The option suppresses core public comment features, management screens, templates, blocks, feeds, ordinary REST/XML-RPC access and dedicated assets. The narrower comment settings remain stored. Private editor notes retain native authorization, and shared WordPress styles remain usable. Direct custom queries and third-party comment interfaces need verification.

### What does local avatar mode protect?

Core avatar HTML and URLs use locally served profile images or a local placeholder. Gravatar HTTP requests and connection hints are blocked; other remote requests during avatar filters are blocked as well. Independent update traffic is unchanged. User assignments are per site and are not exported. Previously cached pages and custom avatar HTML that bypasses core need verification.

### Does it support Multisite?

Yes. Local activation, network activation, independent site settings and a network login-language policy are supported.

### What happens when I uninstall it?

Own settings, named local presets, previous configuration and network policy remain stored. Runtime changes stop when the plugin is inactive.

[FAQ by topic](FAQ)

## Changelog

### 0.9.0 · 2026-10-13

- **New:** Focused WordPress core controls with reusable presets, reviewed imports and independent site settings.
- **New:** Optional comment shutdown, local avatars, appearance access, media tools and alternative post destinations.
- **Fixed:** Update packages are checked against the plugin identity, offered version and WordPress/PHP requirements before replacement.

### 0.1.0–0.8.0 · 2026-10-09–2026-10-12

- **Misc:** Core controls, administration layout, permissions and Multisite support developed and refined.



## Project

By David Decker – DECKERWEB. Site Essentials focuses on WordPress core behavior and a small set of everyday administration tools.

## Issues and security

Report ordinary bugs in [Issues](https://github.com/deckerweb/site-essentials/issues); ask questions in [Discussions](https://github.com/deckerweb/site-essentials/discussions).

Report vulnerabilities privately through [GitHub private vulnerability reporting](https://github.com/deckerweb/site-essentials/security/advisories/new). Include the plugin and component versions, WordPress/PHP versions, reproduction steps and impact. Do not include credentials or publish exploit details in issues. Reports are reviewed privately and fixes and disclosure are coordinated with the reporter.

## Support

[Ko-fi](https://ko-fi.com/deckerweb) · [Buy Me a Coffee](https://buymeacoffee.com/daveshine) · [PayPal](https://paypal.me/deckerweb)

## License and credits

Copyright © 2026 David Decker – DECKERWEB. [GPL-2.0-or-later](https://github.com/deckerweb/site-essentials/blob/main/LICENSE.txt). Embedded deckerweb Library and Updater: David Decker, GPL-2.0-or-later. Artwork uses DejaVu Sans; its redistribution license is included in [FONT-LICENSE-DejaVu.txt](https://github.com/deckerweb/site-essentials/blob/main/assets/images/FONT-LICENSE-DejaVu.txt). Feature references are independently implemented; no premium plugin code is included.
