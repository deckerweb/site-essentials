# FAQ by topic

[Deutsch](FAQ-de.html)

## Getting started and configuration

### Does activation immediately change WordPress?

The shipped configuration enables three administrator-only website reminders: indexing, privacy page and imprint. Cleanup and behavior-changing modules remain off until selected or a preset is applied. An explicitly configured startup preset is an exception.

### How do presets and imports work?

Built-in and named local presets use the same review flow. Custom presets snapshot saved local settings only and can be exported individually. Review the differences, select changes and transfer them into the draft. Save to apply. Merge keeps unspecified draft values; replace uses defaults for unspecified settings.

### How do preset collections work?

Export selected custom profiles, then review the collection on a target site. Choose copies and their unique names. The import uses one confirmed preset-store write after full validation; existing presets, active settings and configuration history are retained. Collections allow up to 20 profiles and 256 KiB; each site stores up to 20 custom presets. Avatars and media assignments are not transferred.

### What do the rule filters show?

The overview displays saved local, draft local, effective draft and reference values with state/source. Active includes enabled default reminders; stored but not applied means a known dependency or site-context restriction, not an automatic conflict diagnosis. Compare either saved or draft values with plugin defaults or a selected builtin/custom preset. Search and state filters combine across sections; section navigation or a setting shortcut clears the search/state filter. References and filters never change settings and are not persisted. All settings remain available without JavaScript.

## Login, editor and core behavior

### What do the login logo and favicon options change?

The logo link can point to this site’s home URL, and its link text can use the site name. The logo image and login redirects are unchanged. Favicon normalization removes only exact core blue/gray W fallbacks and preserves configured site icons and other URLs. The core favicon request returns empty HTTP 204 if no icon remains. Physical favicon files, theme markup and browser caches are not removed.

### Can I keep my own patterns?

Yes. Bundled patterns marked as core and remote directory suggestions have separate controls. Locally registered user, theme and plugin patterns remain available. Remote theme.json directory suggestions are also affected by the remote-pattern switch.

### Does the classic editor convert existing blocks?

No. Selection changes the editor interface only. Existing block markup stays stored, and block content receives a warning on the classic screen. Complex blocks should be reviewed before saving.

### Does this disable Heartbeat?

No. Select initial intervals or keep existing behavior, with optional content-type rules in the post editor.

### Which automatic embeddings are disabled?

Only automatic URL conversion in content and text widgets without block markup. Any content containing blocks keeps existing embedding behavior; explicit embed shortcodes remain enabled. This is not a general external-request blocker.

### What does disabling own embeddings do?

Core embed pages and public oEmbed responses for this site return HTTP 404, including embeds within the same site. Ordinary post links remain public. Previously cached external embeds may persist; manual iframes and copying are not prevented.

### Does Dashicons cleanup break stylesheet dependencies?

Only the direct enqueue for signed-out public visitors is removed. Dashicons remains registered and loads when another stylesheet depends on it. Login, admin, Customizer and signed-in sessions retain it.

### What does public search and author archive shutdown cover?

Native frontend search pages, search feeds, author archives and author feeds return HTTP 404. Core search forms and blocks are hidden. Native REST search returns 404 to visitors; authenticated users with edit_posts retain editor lookup. Admin search and normal posts remain available. Author archive URLs and the native users sitemap are suppressed. Custom forms, other REST listings, hard-coded links and cached pages need separate review.

### Can the large dashboard welcome panel be disabled?

Yes. Select Welcome panel under the core dashboard widgets. The native callback is removed without rewriting the user preference. Custom third-party welcome callbacks remain outside this core option.

## Comments and avatars

### Does complete comment shutdown delete existing comments?

No. The option suppresses core public comment features, management screens, templates, blocks, feeds, ordinary REST/XML-RPC access and dedicated assets. The narrower comment settings remain stored. Private editor notes retain native authorization, and shared WordPress styles remain usable. Direct custom queries and third-party comment interfaces need verification.

### What does local avatar mode protect?

Core avatar HTML and URLs use locally served profile images or a local placeholder. Gravatar HTTP requests and connection hints are blocked; other remote requests during avatar filters are blocked as well. Independent update traffic is unchanged. User assignments are per site and are not exported. Previously cached pages and custom avatar HTML that bypasses core need verification.

### Can subscribers upload their avatars?

Yes, through the separately authorized local-avatar form in their own WordPress profile. This does not grant general media-library upload permissions. Direct uploads accept JPEG, PNG, GIF, WebP and AVIF up to 4 MiB and 4096 by 4096 pixels, subject to stricter native upload limits and permitted file types. Ordinary media capabilities govern existing image selection. Images remain public WordPress media; removing an assignment does not delete the image.

## Blog, menus and appearance access

### Does disabling post management delete content?

No. Native post menus, New Post, dashboard offerings, direct screens, object editing/deletion and native REST management are restricted. Pages/media and other content types retain their rights, including shared taxonomy screens. Remote XML-RPC management and custom server-side writers are outside this administrative UI/native REST control; use the separate XML-RPC control if needed.

### What does public blog shutdown do?

Exclude native posts from ordinary frontend queries and public native REST reads/searches, comments, own embeds and the posts sitemap. Blog-only routes and feeds return HTTP404. Shared taxonomy archives can still show other content types. A latest-posts homepage becomes404: select a static page first. Authenticated native previews and permitted editor REST management remain available when management is enabled. Independent feed settings remain unchanged. Hard-coded custom queries/output, foreign APIs and caches need separate review.

### How do custom post names work?

Enable renaming and supply plural and singular text (up to80characters each). Native labels, classic result messages and dashboard counts use these names. Technical post type post, URLs, IDs and taxonomy names remain unchanged. Fixed third-party text is not translated. Stored names remain available after re-enabling post management. The naming checkbox and both fields share one card. Names appear only while renaming is enabled; turning it off retains the stored names without applying them.

### What happens to disabled classic widgets?

Selected native classic widgets are unavailable and do not render. Their saved instances and sidebar assignments are not deleted by Site Essentials. Re-enable them to restore output; check assignments after changing widget layouts. Block equivalents and third-party widgets are outside this option.

### What do the Widgets and Customizer restrictions cover?

Their native admin screens are hidden and direct access returns HTTP 403. The Customizer toolbar shortcut and native Header/Background links are also removed. Saved widgets, sidebar assignments and theme settings remain intact, and frontend output is unchanged unless separate widget controls are enabled. Third-party editors and REST management are outside these screen restrictions. The classic-widget-editor switch changes the interface only; selected Core widgets are a separate behavior control. A classic Bricks parent/child theme automatically loses native Site Editor menu links, including query-string variants. Bricks templates and other plugin menus remain available.

### How do appearance access policies and personal exceptions work?

Choose native permissions, block everyone, or select allowed roles. In selected-role mode an explicit personal block wins, followed by a personal allow, then any matching role. Multisite super administrators remain eligible unless personally blocked. Widgets and Customizer retain native capabilities; the policy only narrows access. Explicit classic-menu delegation permits the native menu screen and its known menu AJAX handlers without granting general Theme, Widgets, Customizer or Site Editor permissions. Native object/nonce checks and protected-content lookup gates still apply. Excluded widget/menu areas are protected in native Customizer settings and editing REST routes; public navigation reads remain unchanged. Third-party editors are outside scope. All rules are site-local. Role policies travel with settings/presets; personal exceptions are stored separately, excluded from every export and retained across preset/import/rollback operations. Save all settings saves both role rules and local exceptions after validation and stale-state checks. Local own-data clearing removes exceptions. Lookup and export notices are administrator-only. For comprehensive capability management, use a dedicated plugin such as Members.

### How do fixed menu-field policies work?

Choose each field independently: show link target, CSS classes or XFN; hide title attribute or description. All policies are off by default. They override native screen-option visibility for all menu editors while retaining their underlying personal choices, including when other screen options are saved. Hidden menu value inputs still submit unchanged values. Turning policies off restores personal choices. Bricks navigation cleanup is separate and optional; templates, template tags and bundles disappear from native menu/Customizer offers while existing links and template administration remain intact.

## Media and post destinations

### Are existing PDF thumbnails or content deleted?

No. PDF preview suppression applies to new fallback preview generation. Text options remove native output filters without rewriting stored content. Comment auto-link suppression leaves explicitly authored links intact.

### How does media file-size sorting work?

Enable the optional controls in Media library. Size display and sorting apply to the native list view; document-type filters extend both list and grid. Sorting implies column visibility. A local numeric index is prepared in batches of at most 50 attachments while an authorized user has the media screen open. Indexing can be paused; reopening resumes it. Reload the list after completion. Missing, external or remote-only files remain in the list and sort last. Only local attached files inside this site’s uploads are measured, without HTTP requests or thumbnail totals. Native metadata/file updates refresh the index while sorting is enabled. Direct filesystem changes without native attachment updates can leave old index values. No upload types are enabled. File replacement is not provided.

### How do alternative post destinations work?

Enable the module and select public content types under Alternative post destinations. Each supported content editor gains a native meta box (in the block editor, open Meta Boxes). Choose the normal address or an HTTP(S)/root-relative destination. Original public GET/HEAD singular URLs use temporary HTTP 302 redirects without redirecting previews, embeds, feeds, REST or editing. Native public permalinks use the target. Draft/private links stay native. Returning a content item to Normal WordPress address removes its own destination record. Disabling the module/type retains records and restores native behavior. No general redirect manager or URL history is provided. Destination fields appear only after selecting the alternative address. Stale destination forms are rejected; reload to see the current value.

### How is Page Links To migration controlled?

Save selected content types, then open Review Page Links To destinations or Tools → Alternative post destinations. Review pages of at most 25 records; nothing is preselected. Select the records to copy. Sources changed after preview, existing Site Essentials targets, unauthorized objects, unsafe URLs and local loops are skipped. Current underscore metadata and pre-v3 legacy keys are supported. New-tab intent is copied; nofollow is not invented. Original provider metadata is never deleted. While Page Links To is loaded, Site Essentials processing and editing pause. Deactivate Page Links To manually before using the replacement; no plugin is switched automatically. Select all available destinations on this page is optional and starts unchecked. Empty or truncated selections cannot copy data. Untitled content receives an ID label.

### What are the link and sitemap limits?

New-tab/noopener and optional nofollow attributes apply to native navigation-menu and wp_list_pages links. Arbitrary theme, builder and query-loop link renderers need their own attributes; no global frontend script rewrites links. Destination-only items are excluded from the native WordPress posts sitemap while enabled. Third-party sitemap/canonical behavior and cached pages require site-specific verification. The module makes no HTTP requests to check remote destinations.

### Are destinations exported or deleted with settings?

Configuration/preset exports contain only module activation and selected content types, never per-content URLs. The _site_essentials_target post metadata is part of the content itself. Configuration rollback/clearing, deactivation and uninstall retain it; clearing configuration disables processing. Edit the content destination directly to change/remove it. WordPress content export or backups may naturally contain this metadata.

## Website checks and diagnostics

### Why do reminders appear immediately?

Indexing, privacy and imprint checks are enabled by default. They show local administrator-only reminders, without publishing pages or changing visibility. Each reminder category can be disabled under Website checks. Other core behavior controls retain their existing defaults.

### How do indexing decisions work?

When blog_public discourages indexing, keep and acknowledge that decision or explicitly confirm allowing indexing. Acknowledgements belong to this site and are invalidated when native visibility changes. Reset confirmations to review again. This setting is a request to search engines, not an access block.

### How are legal pages checked?

Use the native WordPress privacy page assignment. An imprint is detected by exact Impressum/Imprint title or slug, unless explicitly assigned. Ambiguous results require assignment. Missing, unpublished, password-protected and empty pages produce reminders. Stored content is inspected without execution or external requests; no legal text quality assessment is performed.

### What about builder pages?

Known stored builder data, shortcodes and unsupported dynamic blocks are marked for manual review rather than declared empty. An authorized editor may confirm content for a published page. The confirmation is bound to the page/content fingerprint and is invalidated when relevant data changes. Template-only content or other builders may require separate checking.

### What can the PHP page reveal?

Only selected safe values for the current web request: PHP version, memory targets/current limit, upload/input/time limits, useful extensions, cache support and exposed Suhosin directives. No phpinfo, environment dump, paths, credentials or external connections. Configured targets are not reserved/available RAM. Missing directives are unavailable, not unlimited. Cache extension presence is not proof of connection or working page caching. The optional admin footer links to this page.

## Multisite and stored data

### Does it support Multisite?

Yes. Local activation, network activation, independent site settings and a network login-language policy are supported.

### How are overlapping settings handled?

Site Essentials preserves comment restrictions from earlier filters and stricter incoming revision limits. It does not rewrite other providers’ network policies. If a supported provider already supplies local avatars, Site Essentials pauses its own avatar output, profile controls and uploads. Saved settings and image assignments remain available when that provider no longer supplies the function.

### What happens when I uninstall it?

Own settings, named local presets, previous configuration and network policy remain stored. Runtime changes stop when the plugin is inactive.

## Maintenance and editor helpers

### How does maintenance work?

Maintenance uses a published, non-password-protected WordPress page with its configured Classic or Block Editor. Save maintenance separately; activation and page selection never travel with presets or configuration recovery. Active Bricks, Elementor or Breakdance modes pause the own response until explicitly enabled again. APIs and webhooks remain accessible. Clear or bypass full-page caches and verify while signed out.

Site Essentials is designed for brief maintenance on an existing website. Its maintenance response uses HTTP 503 (temporarily unavailable) with Retry-After. This is not a Coming Soon mode for a launch page that should remain publicly available over a longer period. A public launch page normally uses HTTP 200; its indexing and access settings need separate decisions. Site Essentials does not offer a Coming Soon mode or a status-code switch. For a launch page, use a suitable theme, builder or dedicated plugin. A launch-page workflow is outside the scope of this focused maintenance tool.

Maintenance shows its activation time when recorded, using the website time zone. Existing activations without a recorded time are explicitly marked. A normal save retains the time; disabling clears it and explicitly resuming after a provider pause starts a new period. Public checks show when they ran and distinguish failed requests from missing declarations or uninspected HTML. Their compact copyable summary omits remote bodies and URL query strings. Save or discard unsaved configuration changes before separate maintenance or public-check actions.

### Do editor helpers change my CSS or formats?

No. Format examples are read-only explanations. The width control resizes the existing Customizer editor; its CSS data, live preview, drafts and publishing remain native.

## Replacing Falcon and Unbloater

### Can I replace Falcon or Unbloater with Site Essentials?

Open Settings → Site Essentials → Import and export → Review Falcon and Unbloater settings. Installed Falcon 2.12.0 and Unbloater 1.7.0 are detected even when inactive. Every setting starts with Keep current; choose a source explicitly. The preview shows current values, both proposals, conflicts and behavior differences. Active functions without a safe match are listed separately, including Falcon’s Heartbeat shutdown. Other source versions and nonstandard plugin directories are not imported. No source code is loaded and no source plugin is changed or removed.

### How can I undo a settings takeover?

The confirmed takeover retains the full preceding Site Essentials configuration in its existing single previous-configuration snapshot. Review and restore that snapshot on the main settings page. A later settings save replaces the snapshot; export first for a lasting copy. Unselected settings, per-content-type overrides, personal exceptions and content are retained. Source or local changes since the preview require a new review. This does not synchronize plugins; after verification, deactivate and remove the source plugins yourself.

### Does a takeover change every site in Multisite?

No. Falcon settings are read from the current site. Network-active Unbloater uses its network option; only a network administrator can review that source, and selected settings are applied solely to the current website. Network policy is unchanged. File-editor restrictions are not imported into Multisite, and a network-locked login language setting is not offered for local takeover. Existing source plugins can continue controlling overlapping behavior until deactivated.

