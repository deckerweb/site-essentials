=== Site Essentials ===
Contributors: daveshine
Tags: admin, settings, multisite, cleanup
Requires at least: 7.1.2
Tested up to: 7.1.2
Requires PHP: 8.2
Stable tag: 0.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configure WordPress core behavior with optional controls and reusable presets.

== Description ==
Configure WordPress core behavior with focused, optional controls. Review presets and imports before saving; keep settings independent on each site.

* Login language selector, administrator email confirmation and optional site-specific login logo link/text.
* Classic editor by content type, classic widget interface and a warning for existing block content.
* Separate automatic URL conversion and own oEmbed provider controls, preserving authored embeds and ordinary post links.
* Post editor fullscreen, welcome guide, block directory, bundled core patterns, remote pattern suggestions and template editing.
* Selected core dashboard widgets, toolbar entries and plain-text admin footer.
* Core fallback favicon removal preserves custom site icons.
* Generator, RSD, WLW, shortlinks and feed discovery; feed endpoints can return HTTP 404.
* Complete core comment shutdown with admin, templates, blocks, feeds, REST/XML-RPC and dedicated assets covered; private notes retained.
* Local avatars with site-specific assignments and bounded authorized profile uploads; no Gravatar fallback.
* Portable collections of selected custom presets; protected review, fresh local copies and collision-safe names.
* Comments by content type, optional hiding of existing frontend comments, conditional reply script and self-pingbacks.
* Emoji assets, public-visitor jQuery Migrate removal and XML-RPC HTTP 403.
* Heartbeat intervals by area and content type, revision limits and selective notifications.
* One reports navigation shortcut in the native command palette; a data-minimal Site Health Info section for administrators.
* Separate REST and oEmbed discovery controls; optional single-site file-editor permission protection.
* On-demand data-minimal diagnostics, read-only printable handover and separate local/network clearing.
* Documented presets contain explicitly entered names and notes plus an opaque export identifier. Do not enter private data.
* Five built-in presets, up to 20 named local presets, saved-state comparison, settings search, selective change previews, JSON merge/replace, export and one-step configuration rollback.
* Optional public Dashicons cleanup, PDF preview suppression, original WordPress spelling, simplified toolbar greeting, public search and author archive restrictions, capability-based core update notices, comment URL auto-link control and selected classic core widgets.
* Administrator-only website checks for discouraged indexing, assigned privacy pages and explicit or automatically detected imprint pages. Three reminder switches are enabled by default; they never change visibility or publish pages automatically.
* Separate native post management and public blog shutdown; optional plural/singular post names, with stored content and technical post type retained.
* Read-only administrator PHP information under Tools, expandable cache/extension/Suhosin support and optional PHP/memory footer.
* Optional native media list file-size display and sorting, with bounded site-local indexing; PDF, Word, Excel, PowerPoint, ZIP, OpenDocument, CSV and text filters in list and grid views.
* Optional native Widgets and Customizer admin-screen restrictions, preserving saved theme/sidebar settings and frontend output. Classic Bricks themes automatically hide native Site Editor menu links.
* Role-based native Widgets, Customizer and classic-menu access policies with local personal allow/block exceptions. Classic-menu delegation uses scoped native screen/AJAX permissions without changing stored role capabilities.
* Optionally hide selected core Tools menu links; direct access, Site Health, privacy tools and plugin pages remain available.
* Optional core AI opt-out with Connectors page restriction; credentials retained and independent plugin AI outside scope.
* Hide post-by-email and update-service settings independently, retaining configured values and their runtime behavior.
* Optional alternative post destinations for selected public content types; HTTP(S) and root-relative targets, reversible HTTP 302 redirects and retained editor/preview addresses.
* Administrator-only, paginated Page Links To destination preview and selected copying, preserving source metadata and skipping occupied, stale, unauthorized, unsafe or looping records.
* Optional website-wide native menu-field policies and removal of Bricks templates/taxonomies from navigation offers, retaining underlying user preferences and existing link values.

Includes deckerweb Library 0.9.0 and deckerweb GitHub Release Updater 2.1.0, GPL-2.0-or-later, by David Decker. Library online catalog access is optional and initially off. The updater checks the configured GitHub repository through normal WordPress update checks; request metadata such as your server IP is sent to GitHub. No analytics or remote fonts are included. This direct-distribution package is not a WordPress.org build.

== Installation ==
Upload the ZIP through Plugins → Add Plugin → Upload Plugin and activate it. Open Settings → Site Essentials. Choose a preset, review the changes and save all settings. No cleanup is enabled by default; three administrator-only website reminders are enabled.

== Frequently Asked Questions ==

= Does activation immediately change WordPress? =
The shipped configuration enables three administrator-only website reminders: indexing, privacy page and imprint. Cleanup and behavior-changing modules remain off until selected or a preset is applied. An explicitly configured startup preset is an exception.

= How do presets and imports work? =
Built-in and named local presets use the same review flow. Custom presets snapshot saved local settings only and can be exported individually. Review the differences, select changes and transfer them into the draft. Save to apply. Merge keeps unspecified draft values; replace uses defaults for unspecified settings.

= How do preset collections work? =
Export selected custom profiles, then review the collection on a target site. Choose copies and their unique names. The import uses one confirmed preset-store write after full validation; existing presets, active settings and configuration history are retained. Collections allow up to 20 profiles and 256 KiB; each site stores up to 20 custom presets. Avatars and media assignments are not transferred.

= Does complete comment shutdown delete existing comments? =
No. The option suppresses core public comment features, management screens, templates, blocks, feeds, ordinary REST/XML-RPC access and dedicated assets. The narrower comment settings remain stored. Private editor notes retain native authorization, and shared WordPress styles remain usable. Direct custom queries and third-party comment interfaces need verification.

= What does local avatar mode protect? =
Core avatar HTML and URLs use locally served profile images or a local placeholder. Gravatar HTTP requests and connection hints are blocked; other remote requests during avatar filters are blocked as well. Independent update traffic is unchanged. User assignments are per site and are not exported. Previously cached pages and custom avatar HTML that bypasses core need verification.

= Does it support Multisite? =
Yes. Local activation, network activation, independent site settings and a network login-language policy are supported.

= What happens when I uninstall it? =
Own settings, named local presets, previous configuration and network policy remain stored. Runtime changes stop when the plugin is inactive.

Full FAQ: https://github.com/deckerweb/site-essentials/blob/main/FAQ.md

== Changelog ==

= 0.9.0 =
* New: Focused WordPress core controls with reusable presets, reviewed imports and independent site settings.
* New: Optional comment shutdown, local avatars, appearance access, media tools and alternative post destinations.
* Fixed: Update packages are checked against the plugin identity, offered version and WordPress/PHP requirements before replacement.

= 0.1.0–0.8.0 =
* Misc: Core controls, administration layout, permissions and Multisite support developed and refined.
