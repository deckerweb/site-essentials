# User guide

[Deutsch](Anleitung) · [Home](Home)

## Find the right setting

The left navigation separates views and presets, WordPress settings, and plugin tools. Search and state filters affect only what you see. The reference profile is a comparison aid; it does not apply settings. The rule overview distinguishes active settings from saved choices that currently have no effect.

## Save, export and restore

A dot in the save button and the existing status identify unsaved changes. Review the draft before applying it. Export uses saved settings. Recovery restores the previous configuration snapshot; a later settings save replaces that snapshot. Maintenance controls save separately.

## Focused controls

- **Comments:** complete shutdown hides native management and public output without deleting stored comments. Granular controls remain saved.
- **Local avatars:** use local Media Library images, with no Gravatar fallback from the module. Supported external avatar providers take precedence.
- **Media:** optionally display file sizes, sort by size and filter document types. Indexing uses bounded batches; existing files are not replaced. SVG sanitization is not provided.
- **Appearance access:** choose allowed roles for classic menus, widgets or the Customizer; local user allow/block exceptions are available. Administrators alone can manage these exceptions; they are excluded from exports. This is not a full role-management system.
- **Alternative post destinations:** enable selected content types, then edit the destination in the post editor. HTTP 302 redirects preserve the normal editing address. Page Links To copying is a separate reviewed operation; no automatic deletion of source data.
- **Editor helpers:** optional date/time examples explain existing formats; the Customizer CSS editor can be widened. Stored formats, CSS and native publishing behavior remain unchanged.

## Multisite

Activate on one site or network-wide. Each site keeps its own configuration. The limited network setting governs the login language selector; other sites are not silently reconfigured. Personal exceptions and media assignments remain website-local.

Continue with [maintenance](Maintenance), [website checks](Website-checks), [settings takeover](Takeover), or [the complete FAQ](FAQ).
