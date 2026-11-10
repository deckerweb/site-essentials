# WordPress-Abilities: erklären und vorab prüfen

[English](Abilities) · [Startseite](Home-de)

Site Essentials registriert drei native WordPress-Abilities. Sie erklären Konfigurationen und zeigen Vorschauen; sie speichern oder aktivieren nichts.

| Ability | Zweck | Eingabe |
| --- | --- | --- |
| `site-essentials/explain-settings` | Gespeicherte, vorgeschlagene und wirksame Werte sowie bekannte Pausengründe erklären | Optional `keys`: ausgewählte unterstützte Einstellungsnamen |
| `site-essentials/compare-configurations` | Eine validierte Teilkonfiguration mit gespeicherten Werten vergleichen | `settings`: unterstützte Einstellungswerte |
| `site-essentials/preview-preset` | Änderungen eines verfügbaren Presets vorab ansehen | `preset`: technische Preset-Kennung |

## Berechtigung und Datenumfang

Es ist eine angemeldete Person mit `manage_options` für die aktuelle Website erforderlich, im Normalfall ein Administrator. Die Prüfung erfolgt sowohl am API-Einstieg als auch vor der Ausführung. Ein zusätzlicher Berechtigungsfilter kann diese Mindestanforderung verschärfen, nicht aufheben. Native REST-Authentifizierung bleibt erforderlich.

Die Antwort enthält die Website-ID, `readonly: true` und unterstützte Regeln mit Beschreibung, Werten, bekanntem Pausengrund und Einstellungssprung. Freitexte, persönliche Zugriffsausnahmen, Wartungslinks und betriebliche Geheimnisse sind ausgeschlossen. Die Vorschau ist kein vollständiger Konfigurationsexport und kein Laufzeit- oder Kompatibilitätstest.

Die Netzwerkverwaltung wird nicht als Anwendungsziel angeboten. In Multisite gilt immer der Berechtigungskontext der jeweiligen Website.

## Was wird zusätzlich installiert?

Kein MCP-Adapter und kein externer KI-Dienst. Zur Verwendung ist ein passender Client für die native Abilities API nötig. Das Anwenden von Änderungen über Abilities gehört nicht zu Version 1.2.0.

[Konfiguration bewusst wählen](Bewusste-Entscheidungen) · [Anleitung](Anleitung)
