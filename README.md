# Site Essentials

![Site Essentials](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/banner-en-github-1280x640.png)

[Deutsch](README-de.md)

## About

Your WordPress. Your settings. Site Essentials brings focused settings and practical tools together for a WordPress website that fits your needs. Simplify administration, control WordPress features and reuse proven configurations across websites. Built for everyday site care and client handover, with support for single installations and individual sites in a Multisite network.

**1.1.0 · WordPress 7.1+ · PHP 8.2+**

[Plugin website](https://deckerwebplugins.com/en/plugins/site-essentials/) · [Documentation](https://deckerweb.github.io/site-essentials/) · [FAQ by topic](FAQ.md)

## Contents

- [At a glance](#at-a-glance)
- [Installation](#installation)
- [Feature highlights](#feature-highlights)
- [WordPress features](#wordpress-features)
- [Configuration](#configuration)
- [Behavior and limits](#behavior-and-limits)
- [Multisite](#multisite)
- [Preconfigured installation](#preconfigured-installation)
- [Stored data](#stored-data)
- [Components and connections](#components-and-connections)
- [Administration conveniences](#administration-conveniences)
- [Screenshots](#screenshots)
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

[Browse all 68 feature points by task](https://github.com/deckerweb/site-essentials/wiki/Features), including requirements and limitations.

## Installation

Upload the ZIP through Plugins → Add Plugin → Upload Plugin and activate it. Open Settings → Site Essentials. Choose a preset, review the changes and save all settings. No cleanup is enabled by default; three administrator-only website reminders are enabled.

## Feature highlights

### Your WordPress. Your settings.

Set up WordPress to suit your website. Clear options and prepared presets make configuration easier. You decide which settings apply and which features are active. Review a preset before applying it, save your choices and reuse proven configurations through import and export.

### Bring your existing settings along.

Already using Unbloater, Falcon or both? The guided one-time takeover previews matching settings and highlights conflicts before you confirm changes. Configuration recovery remains available until a later settings save replaces the previous snapshot. You deactivate and remove the old plugins yourself. Guided settings takeover is provided specifically for Falcon and Unbloater.

### Administration your clients can work with.

Hide rarely needed areas and make important settings easier to understand. Control access to menus, widgets and the Customizer by role, with local exceptions for individual users. These personal exceptions stay on the website and are excluded from exports.

### Maintenance with your own WordPress page.

Design your maintenance page in the configured Block or Classic Editor. Visitors receive the maintenance page with HTTP 503 while administrators retain access. Preview the page before enabling maintenance; an orange toolbar indicator reminds administrators when it is active. Active Bricks, Elementor and Breakdance maintenance modes take precedence. Full-page caches may need to be cleared or bypassed.

### Local avatars instead of external Gravatar requests.

Use your own user images from the Media Library. When enabled, the local avatar module prevents Gravatar requests for avatar output. Optionally show the images in the user table. If a supported provider already supplies local avatars, Site Essentials hides and pauses its own avatar module.

### Keep important website settings in view.

Get administrator reminders when search indexing is discouraged, and confirm an intentional decision. Also check for missing, unpublished or detectably empty privacy and imprint pages. These checks do not assess legal compliance or the quality of legal text.

### Several tasks. One shared place.

Depending on the functions you need, Site Essentials can replace additional individual plugins:

| Task | In Site Essentials |
| --- | --- |
| Disable Comments or similar plugins | Disable comments completely or restrict them selectively |
| One User Avatar or similar plugins | Use local user images and prevent Gravatar requests |
| Page Links To | Set alternative post destinations; selectively copy existing Page Links To targets |
| Simple maintenance-mode plugins | Use your own WordPress page as a maintenance page |

This does not promise full feature parity. Guided settings takeover applies specifically to Falcon and Unbloater. Page Links To target copying is separate; data from other plugins is not automatically migrated.

## WordPress features

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
- Four native command-palette shortcuts for settings, reports, website checks and maintenance; administrator Site Health information includes maintenance state and known overridden rules.
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
- One-time Falcon and Unbloater settings takeover with explicit source choices, conflict review and configuration recovery.
- Optional per-site maintenance with a native WordPress page, administrator preview, HTTP 503 and cache guidance.
- Optional local-avatar and user-ID columns, date/time examples and a wider Customizer CSS editor.
- Clearer navigation groups, option descriptions, save-button change indicator and separate pattern/font menu controls.
- Administrator test links for missing pages and searches without results, with theme-specific guidance.
- Read-only public robots.txt, sitemap and feed links, plus an explicit anonymous homepage-response check.

## Configuration

Presets and configuration-file imports modify the draft, not the site. Save all settings applies that draft. The separate Falcon/Unbloater takeover saves explicitly selected settings after confirmation. Export downloads saved local settings, not unsaved changes. WordPress defaults can be reviewed as a preset. System behavior remains unchanged unless explicitly configured. Manual changes have an optional read-only review before saving. Preset names and notes are explicitly entered and included in exports; no site domains or user data are gathered automatically. Diagnostics omit preset notes and custom footer text. Clearing local data removes local avatar assignments, presets and rollback history while keeping neutral defaults to prevent startup presets from returning; network clearing releases only the current network policy.

## Behavior and limits

Template editing is blocked, but existing templates still render. jQuery Migrate removal applies only to signed-out frontend visitors. Heartbeat intervals are initial polling settings; WordPress or another script can subsequently adjust them. Revision limits do not run an immediate cleanup; core may prune older revisions on later saves. Existing comment hiding applies to ordinary frontend comment templates, not arbitrary custom database queries. XML-RPC blocking occurs after WordPress loads; it is not a web-server firewall.

## Multisite

Activate on one site or network-wide. Sites retain independent options, including sites created after activation. Network Admin → Settings → Site Essentials can hide the login language selector on every site or leave it to local settings. No other network policy is imposed.

## Preconfigured installation

For a deliberately preconfigured deployment, set startup-preset.json to {"preset":"builder"}, "comfort", "head", "classic" or "defaults" before installation. The selected configuration initializes each unconfigured site on first use. Existing configurations are retained. The shipped file uses null.

## Stored data

Local options: site_essentials_settings, site_essentials_previous and site_essentials_presets. Site-scoped user metadata: `site_essentials_avatar_SITE_ID`, containing local attachment IDs. Avatar media remain normal WordPress uploads. Network option: site_essentials_network. Deactivation and uninstall retain these values. The embedded Library removes its own temporary data only under its final-host cleanup contract; shared settings are retained by default. No content or other plugin data is deleted. Local option site_essentials_checks retains local imprint assignment and content/indexing confirmations. It is not part of configuration exports; explicit local RESET removes it without changing native visibility/privacy assignments. The optional media sorting index stores only derived byte counts (or an unavailable marker) in site-local attachment metadata _site_essentials_file_size. Local plugin-data clearing removes the index without touching attachments or files. Site-local option site_essentials_access_users contains personal appearance exceptions. It is excluded from all exports, preset snapshots and diagnostic output. Content metadata _site_essentials_target stores explicit destination URL and new-tab/nofollow flags. It is retained by configuration clearing and plugin uninstall and excluded from configuration/preset exports. Local option site_essentials_maintenance stores activation, pause state and the selected page. It is excluded from all configuration transfers and recovery, preserved at uninstall, and removed by explicit local RESET. The page itself remains content.

## Components and connections

Includes deckerweb Library 1.1.0 and deckerweb GitHub Release Updater 2.2.1, GPL-2.0-or-later, by David Decker. Library online catalog access is optional and initially off. The updater checks the configured GitHub repository through normal WordPress update checks; request metadata such as your server IP is sent to GitHub. No analytics or remote fonts are included. This direct-distribution package is not a WordPress.org build.

## Administration conveniences

### One dashboard information widget.

Under Dashboard, configure one optional dashboard widget with a title, simple formatted text and an audience of roles plus additional site users. A matching role OR a selected user grants visibility; an empty audience grants nobody access. Users can hide it in Screen Options. Save this widget separately. Generic configuration and preset exports never include its text or personal grants. A separate, explicitly confirmed text export/import transfers only the saved title and text; it never transfers activation or audience. Disabling or uninstalling preserves the local configuration; resetting Site Essentials clears it.

### A local default avatar.

Enable local avatars first. The optional user-list column and a separate default-image card then appear below the master setting. The card previews the current default and offers local media selection, raster upload or return to the shipped SE image. Save the image separately. A missing, deleted, private or externally served selection falls back to the shipped image. No media files are copied, locked or deleted when resetting the selection. Personal profile images still take priority. Image IDs are local to each website and are excluded from configuration, preset and support exports.



### Media and list conveniences.

Optionally inspect generated image sizes in the attachment editor without changing files. New upload filenames can be converted to lowercase; existing files stay unchanged. Native content lists can offer a visible title-only search choice, with full search as the default. Featured-image, modified-date and administrator-only user-registration columns can be enabled separately. All four helpers start off.

### Clear status, minimal diagnostics.

Known reasons for paused settings are shared across the rule overview, handover and Site Health. Support diagnostics use a reviewed allowlist: custom text, personal assignments and custom role or content-type names are omitted; lists disclose counts only. Storage diagnosis distinguishes no measurement, an incomplete measurement and an unavailable result.



### Menus, without rebuilding them.

Enable menu duplication under Admin interface. The copy keeps native link properties, hierarchy and Site Essentials visibility, but receives no menu location. Third-party metadata is not copied. Visibility choices apply only to classic menus and do not protect target pages. Hidden parents also hide their descendants; page caches must distinguish login status.

### User information, kept small.

User and content IDs offer a copy button with selectable-text fallback. Optional last-login collection stores only one successful interactive-login timestamp per user and website. Existing sessions do not update it. No IP address or history is stored. Only administrators with user-list permission see timestamps. WordPress personal-data tools can export and erase them; disabling collection retains existing timestamps, uninstall removes them.

### Media trash: inspect before changing configuration.

The optional status card reads MEDIA_TRASH and EMPTY_TRASH_DAYS without editing wp-config.php. Enable native media trash only through server configuration before WordPress loads, respecting existing definitions and retention. A zero retention disables trash. Files in trash remain stored and may remain directly accessible; this is not a backup or privacy barrier. Explicit permanent deletion can bypass trash. On Multisite, constants normally apply across the installation.

### Storage diagnosis without a live scan.

Enable daily local upload-storage diagnosis under Media library and view the cached result in Tools → Site Essentials: PHP. Requests never scan directories while displaying the result. Measurements stop at 10,000 entries, 512 directories or two seconds. An incomplete or unavailable result is labeled without a partial total. Manual refresh queues a task, limited to fifteen-minute intervals. Traffic-dependent WordPress cron may run late. Remote media, the database and hosting quota are not measured.

### One environment indicator.

Site Essentials shows the WordPress environment on the right of the toolbar for administrators only, without changing it. When an active Brand Admin Schemes implementation provides its environment callback, BAS owns presentation and Site Essentials pauses, even if BAS hides its own badge. Site Essentials keeps its choice and resumes after BAS deactivation. The settings status explains which provider is responsible. No BAS files or settings are changed.

[New administration conveniences with examples](wiki/Administration-conveniences.md)

## Screenshots

### Presets and settings

![Presets and settings](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/settings-en.png)

Review presets, filter settings and save the configuration deliberately.

### Maintenance with your own page

![Maintenance with your own page](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/maintenance-en.png)

Select a published WordPress page. Maintenance uses HTTP 503 and has its own save action.

### Website reminders

![Website reminders](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/checks-en.png)

Administrator reminders check visibility and recognizable page content; they do not assess legal compliance.

Illustrated guides: [Dashboard](https://github.com/deckerweb/site-essentials/wiki/Dashboard), [Site icon and date formats](https://github.com/deckerweb/site-essentials/wiki/General-settings), [List columns](https://github.com/deckerweb/site-essentials/wiki/Administration-conveniences) and [Diagnostics](https://github.com/deckerweb/site-essentials/wiki/Diagnostics).

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

[FAQ by topic](FAQ.md)

## Changelog

### 1.1.0 · 2026-10-28

- **New:** Optional video upload restrictions, native media trash guidance and cached daily upload-storage diagnosis.
- **New:** Classic menu duplication and menu visibility for logged-in or logged-out visitors.
- **New:** Copyable content and user IDs, selected profile simplification and site-specific last-login timestamps.
- **New:** Optional WordPress environment indicator with priority for Brand Admin Schemes.
- **New:** Optional image-size overview, lowercase new upload filenames, visible title-only admin search and selected list columns.
- **New:** Optional dashboard information widget with site-local audience and explicit text transfer; configurable local default avatar with a documented developer filter.
- **Improved:** Separate feed discovery controls and WordPress/plugin/theme success-email choices, preserving existing behavior; clearer grouped settings and a direct shortcut to unapplied rules.
- **Improved:** Grouped media controls and guidance, administrator-only environment indicator on the right, clearer local-avatar help and consistent privacy-conscious diagnostics.
- **Improved:** Narrow two-line, sortable ID columns; sortable modified dates in the native WordPress date format; clearer avatar dependencies, update-email spacing and pending-change marker. Site Health and command-palette navigation cover the new modules.
- **Improved:** Dedicated Dashboard section, clearer editor lists and date-format examples, Favicon guidance and prioritized Site Health information.

### 1.0.1 · 2026-10-22

- **Fixed:** Updated plugins use their bundled updater even when another plugin loaded an older copy first.

### 1.0.0 · 2026-10-21

- **New:** Optional per-site maintenance with a native WordPress page, administrator preview, HTTP 503 and cache guidance.
- **New:** Optional local-avatar and user-ID columns, date/time examples and a wider Customizer CSS editor.
- **New:** Reviewed one-time takeover of supported Falcon and Unbloater settings with conflict selection and recovery.
- **New:** Read-only website addresses, public robots.txt, sitemap and feed links, plus an on-demand homepage response check.
- **New:** Four navigation commands and expanded Site Health details for maintenance and effective settings.
- **Improved:** Clearer navigation groups, option descriptions, save-button change indicator and separate pattern/font menu controls.
- **Improved:** Includes deckerweb Library 1.1.0 with the current local catalog, personal sorting and robust dialogs.
- **Fixed:** Avoid debug notices during startup and premature settings reads while new Multisite sites are being created.
- **Fixed:** Avatar handoff also recognizes compatible locally loaded providers with automatic class loading.
- **Misc:** Requires WordPress 7.1 or later and PHP 8.2 or later.

### 0.9.0 · 2026-10-13

- **New:** Focused WordPress core controls with reusable presets, reviewed imports and independent site settings.
- **New:** Optional comment shutdown, local avatars, appearance access, media tools and alternative post destinations.
- **Improved:** Local avatars defer to a compatible active provider; controls and profile forms are hidden while saved settings and assignments are retained.
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

Copyright © 2026 David Decker – DECKERWEB. [GPL-2.0-or-later](LICENSE.txt). Embedded deckerweb Library and Updater: David Decker, GPL-2.0-or-later. Artwork uses DejaVu Sans; its redistribution license is included in [FONT-LICENSE-DejaVu.txt](assets/images/FONT-LICENSE-DejaVu.txt). Feature references are independently implemented; no premium plugin code is included.
