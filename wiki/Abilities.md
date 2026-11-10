# WordPress abilities: explain and preview

[Deutsch](Abilities-de) · [Home](Home)

Site Essentials registers three native WordPress abilities. They explain configurations and return previews; they never save or activate settings.

| Ability | Purpose | Input |
| --- | --- | --- |
| `site-essentials/explain-settings` | Explain saved, proposed and effective values and known pause reasons | Optional `keys`: supported setting names |
| `site-essentials/compare-configurations` | Compare a validated partial configuration with saved values | `settings`: supported values |
| `site-essentials/preview-preset` | Preview changes from an available preset | `preset`: technical preset ID |

## Authorization and data scope

An authenticated user needs `manage_options` for the current site, normally an administrator. Authorization is checked at the API entry point and again before execution. An additional capability filter can tighten this baseline, not remove it. Native REST authentication remains required.

Responses contain the site ID, `readonly: true` and supported rules with explanations, values, known pause reasons and settings shortcuts. Free text, personal access exceptions, maintenance links and operational secrets are excluded. A preview is not a complete configuration export or a runtime/compatibility test.

Network administration is not exposed as an application target. Multisite requests use the permission context of the individual website.

## What else is installed?

No MCP adapter or external AI service. A client supporting the native Abilities API is required to use the integration. Applying changes through abilities is outside version 1.2.0.

[Choose deliberately](Deliberate-choices) · [User guide](User-guide)
