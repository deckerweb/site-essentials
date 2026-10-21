# Wartungsmodus

[English](Maintenance) · [Startseite](Home-de)

1. **Einstellungen → Site Essentials → Wartungsmodus** öffnen.
2. Eine Wartungsseite erstellen oder eine veröffentlichte Seite ohne Passwort auswählen.
3. Mit dem eingerichteten Block-Editor oder Classic Editor gestalten.
4. Die gespeicherte Seite als Administrator ansehen.
5. Wartung aktivieren und **Wartungsmodus speichern** verwenden. Anschließend anonym in einem privaten Browserfenster testen.

![Wartung mit eigener Seite](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/maintenance-de.png)

Eine veröffentlichte WordPress-Seite auswählen. Wartung verwendet HTTP 503 und wird separat gespeichert.

Besucher erhalten **HTTP 503**, `Retry-After` sowie No-Cache-/Noindex-Header. Administratoren behalten normalen Zugriff; der orange Toolbar-Eintrag kennzeichnet die aktive eigene Wartung. Login, Schnittstellen, Webhooks und Hintergrundaufgaben bleiben erreichbar. Dies ist keine Zugriffs- oder Datenschutzsperre.

## Wartung ist kein Coming Soon

Dieser Modus dient zeitweiligen Arbeiten an einer bestehenden Website. Eine längerfristig öffentliche Ankündigungsseite benötigt normalerweise HTTP 200 und eigene Entscheidungen zu Indexierung und Zugriff. Site Essentials bietet keinen Coming-Soon-Modus und keinen Statuscode-Schalter. Für diesen Ablauf ein geeignetes Theme, einen Builder oder ein spezialisiertes Plugin verwenden.

## Andere Anbieter und Caches

Aktive Wartung in Bricks, Elementor oder Breakdance erhält Vorrang. Site Essentials pausiert die eigene Antwort und verändert keine fremden Einstellungen. Nach einem Konflikt die eigene Wartung bewusst wieder aktivieren; sie startet nicht automatisch erneut.

Ein Seiten-Cache beim Hoster oder CDN kann eine gespeicherte Seite ausgeben, bevor WordPress ausgeführt wird – auch ohne Cache-Plugin. Bei Bedarf manuell leeren oder umgehen und die öffentliche Antwort nach dem Ein- und Ausschalten prüfen. Das Plugin konfiguriert keine fremden Caches um.

Wartungsaktivierung, Seitenzuordnung und erfasster Beginn sind Betriebsdaten dieser Website. Sie sind nicht in Presets, Importen, Exporten oder der Konfigurationswiederherstellung enthalten.
