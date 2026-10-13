# Site Essentials

![Site Essentials](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/banner-de-github-1280x640.png)

[English](README.md)

## Kurzvorstellung

Konfiguriert WordPress-Core-Verhalten mit gezielten optionalen Einstellungen. Presets und Importe vor dem Speichern prüfen; jede Website behält ihre eigene Konfiguration.

**0.9.0 · WordPress 7.1.2+ · PHP 8.2+**

[Dokumentation](https://deckerweb.github.io/site-essentials/de.html) · [Fragen nach Themen](FAQ-de.md)

## Inhalt

- [Auf einen Blick](#auf-einen-blick)
- [Installation](#installation)
- [Core-Einstellungen](#core-einstellungen)
- [Konfiguration](#konfiguration)
- [Verhalten und Grenzen](#verhalten-und-grenzen)
- [Multisite](#multisite)
- [Vorkonfigurierte Installation](#vorkonfigurierte-installation)
- [Gespeicherte Daten](#gespeicherte-daten)
- [Komponenten und Verbindungen](#komponenten-und-verbindungen)
- [FAQ](#faq)
- [Änderungsverlauf](#änderungsverlauf)
- [Projekt](#projekt)
- [Fehler und Sicherheit](#fehler-und-sicherheit)
- [Unterstützung](#unterstützung)
- [Lizenz und Herkunft](#lizenz-und-herkunft)

## Auf einen Blick

- Optionale Einstellungen für Login, Editoren, Admin-Seiten, Header und Feeds.
- Kommentare, Avatare, Heartbeat und Revisionen je Website steuern.
- Dateigrößen, Sortierung und Dokumenttyp-Filter in der Mediathek.
- Gezielte Design-Zugriffe nach Rolle mit lokalen persönlichen Ausnahmen.
- Alternative Beitragsziele und ausgewählte Page-Links-To-Übernahme.
- Presets, Entwurfsprüfung, Import/Export und Konfigurationswiederherstellung.
- Website-Hinweise für Administratoren, PHP-Informationen und Site-Health-Angaben.
- Single-Site und Multisite; Englisch und beide deutschen Sprachfassungen.

## Installation

ZIP unter Plugins → Installieren → Plugin hochladen installieren und aktivieren. Einstellungen → Site Essentials öffnen. Preset wählen, Änderungen prüfen und alle Einstellungen speichern. Standardmäßig ist keine Bereinigung aktiviert.

## Core-Einstellungen

- Sprachauswahl beim Login, Administrator-E-Mail-Abfrage und optionaler eigener Login-Logo-Link/-Text.
- Klassischer Editor nach Inhaltstyp, klassische Widget-Oberfläche und Hinweis bei vorhandenen Block-Inhalten.
- Getrennte Steuerung automatischer URL-Einbettungen und des eigenen oEmbed-Angebots; ausdrückliche Einbettungen und normale Beitragslinks bleiben verfügbar.
- Vollbild, Begrüßung, Block-Verzeichnis, mitgelieferte Core-Patterns, externe Pattern-Vorschläge und Template-Bearbeitung.
- Ausgewählte Core-Dashboard-Widgets, Toolbar-Einträge und Admin-Footer als Text.
- Core-Fallback-Favicon entfernen, eigene Website-Icons erhalten.
- Generator, RSD, WLW, Shortlinks und Feed-Verweise; Feed-Endpunkte können HTTP 404 liefern.
- Vollständige Core-Kommentarabschaltung einschließlich Admin, Templates, Blöcken, Feeds, REST/XML-RPC und eigener Assets; private Notizen erhalten.
- Lokale Avatare mit Zuordnung je Website und begrenzten berechtigten Profil-Uploads; kein Gravatar-Fallback.
- Portable Sammlungen ausgewählter eigener Presets; geschützte Prüfung, neue lokale Kopien und konfliktfreie Namen.
- Kommentare nach Inhaltstyp, optionales Ausblenden vorhandener Frontend-Kommentare, bedarfsgerechtes Antwortscript und Self-Pingbacks.
- Emoji-Assets, jQuery-Migrate-Entfernung für öffentliche Besucher und XML-RPC-Sperre mit HTTP 403.
- Heartbeat-Intervalle nach Bereich und Inhaltstyp, Revisionsgrenzen und ausgewählte Mitteilungen.
- Ein Navigationsbefehl zu Berichten in der nativen Befehlspalette; datensparsame Site-Health-Informationen für Administratoren.
- Getrennte REST- und oEmbed-Verweise; optionaler Berechtigungsschutz der Core-Dateieditoren auf Single-Site.
- Datensparsame Diagnose auf Abruf, druckbare Leseansicht zur Übergabe und getrennte lokale/Netzwerk-Bereinigung.
- Dokumentierte Presets enthalten eingegebene Namen und Notizen sowie eine neutrale Exportkennung. Keine privaten Daten eingeben.
- Fünf mitgelieferte Presets, bis zu 20 benannte lokale Presets, Statusabgleich, Einstellungssuche, selektive Änderungsvorschau, JSON-Import mit Ergänzen/Ersetzen, Export und Rücknahme einer Konfiguration.
- Optionale Dashicons-Bereinigung, Abschaltung neuer PDF-Vorschauen, ursprüngliche WordPress-Schreibweise, vereinfachte Toolbar-Begrüßung, öffentliche Suche und Autorenarchive, berechtigungsabhängiger Core-Update-Hinweis, Kommentar-URL-Verlinkung und ausgewählte klassische Core-Widgets.
- Website-Prüfungen nur für Administratoren: unerwünschte Indexierungs-Abschaltung, zugeordnete Datenschutzseite und ausdrücklich zugeordnetes oder erkanntes Impressum. Drei Hinweisschalter sind standardmäßig aktiv; Sichtbarkeit und Seitenveröffentlichung werden nie automatisch geändert.
- Getrennte Abschaltung nativer Beitragsverwaltung und öffentlicher Blog-Ausgabe; optionale Mehrzahl-/Einzahl-Bezeichnungen bei erhaltenen Inhalten und technischem Inhaltstyp.
- Nur für Administratoren: PHP-Informationen unter Werkzeuge, ausklappbare Cache-/Erweiterungs-/Suhosin-Unterstützung und optionaler PHP-/Speicher-Footer.
- Optionale Dateigrößen-Anzeige und -Sortierung in der nativen Medienliste mit begrenzter lokaler Indexierung; PDF-, Word-, Excel-, PowerPoint-, ZIP-, OpenDocument-, CSV- und Textfilter in Liste und Raster.
- Optionale Sperre der nativen Widgets- und Customizer-Adminseiten bei erhaltenen Theme-/Sidebar-Einstellungen und Frontend-Ausgabe. Klassische Bricks-Themes blenden native Site-Editor-Menülinks automatisch aus.
- Rollenbasierte Zugriffsregeln für native Widgets, Customizer und klassische Menüs mit lokalen persönlichen Freigaben/Sperren. Menüfreigaben nutzen begrenzte native Seiten-/AJAX-Berechtigungen ohne gespeicherte Rollenrechte zu ändern.
- Ausgewählte Core-Werkzeug-Menülinks optional ausblenden; direkte Zugänge, Website-Zustand, Datenschutz-Werkzeuge und Pluginseiten erhalten.
- Optionale Core-KI-Abschaltung mit Konnektoren-Seitensperre; Zugangsdaten erhalten, unabhängige Plugin-KI nicht umfasst.
- E-Mail-Publizierungs- und Update-Dienst-Einstellungen getrennt ausblenden; gespeicherte Werte und Laufzeitverhalten erhalten.
- Optionale alternative Beitragsziele für ausgewählte öffentliche Inhaltstypen; HTTP(S)- und interne Pfad-Ziele, reversible HTTP-302-Weiterleitungen und erhaltene Editor-/Vorschauadressen.
- Paginierte Page-Links-To-Vorschau nur für Administratoren mit ausdrücklicher Auswahl; Originaldaten erhalten, belegte, veraltete, unberechtigte, unsichere oder zyklische Ziele überspringen.
- Optionale feste Website-Vorgaben für native Menüfelder und Bricks-Vorlagen/Taxonomien aus Navigationsangeboten entfernen; persönliche Ansichtsoptionen und vorhandene Linkwerte erhalten.

## Konfiguration

Presets und Importe verändern den Entwurf, nicht die Website. Erst Alle Einstellungen speichern wendet die Konfiguration an. Export lädt gespeicherte lokale Einstellungen herunter, keine ungespeicherten Änderungen. Der WordPress-Standard steht als Preset zur Prüfung bereit. Systemverhalten bleibt ohne ausdrückliche Einstellung unverändert.

## Verhalten und Grenzen

Template-Bearbeitung wird gesperrt; vorhandene Templates werden weiter ausgegeben. jQuery Migrate wird nur für abgemeldete Frontend-Besucher entfernt. Heartbeat-Intervalle gelten als anfängliche Abrufeinstellung; WordPress oder andere Scripts können sie später verändern. Revisionsgrenzen führen keine sofortige Bereinigung aus; Core kann beim späteren Speichern ältere Revisionen entfernen. Vorhandene Kommentare werden in gewöhnlichen Frontend-Kommentar-Templates ausgeblendet, nicht in beliebigen eigenen Datenbankabfragen. XML-RPC wird nach dem Laden von WordPress gesperrt; das ersetzt keine Webserver-Firewall.

## Multisite

Auf einer Website oder netzwerkweit aktivierbar. Websites behalten eigene Optionen, auch nach Aktivierung neu angelegte Sites. Netzwerkverwaltung → Einstellungen → Site Essentials kann die Login-Sprachauswahl überall ausblenden oder den Websites überlassen. Weitere Netzwerkvorgaben werden nicht erzwungen.

## Vorkonfigurierte Installation

Für eine bewusst vorkonfigurierte Installation startup-preset.json vorab auf {"preset":"builder"}, "comfort", "head", "classic" oder "defaults" setzen. Die gewählte Konfiguration initialisiert jede noch unkonfigurierte Website bei erster Nutzung. Bestehende Einstellungen bleiben erhalten. Die ausgelieferte Datei enthält null.

## Gespeicherte Daten

Lokale Optionen: site_essentials_settings, site_essentials_previous und site_essentials_presets. Benutzer-Metadaten je Website: `site_essentials_avatar_SITE_ID` mit lokalen Attachment-IDs. Avatar-Medien bleiben normale WordPress-Uploads. Netzwerkoption: site_essentials_network. Deaktivierung und Deinstallation erhalten diese Werte. Die eingebettete Library entfernt eigene temporäre Daten nur gemäß ihrer Letzt-Host-Bereinigung; gemeinsame Einstellungen bleiben standardmäßig erhalten. Inhalte und Daten anderer Plugins werden nicht gelöscht. Die lokale Option site_essentials_checks erhält Impressumszuordnung und Inhalts-/Indexierungsbestätigungen. Sie wird nicht mit Konfigurationen exportiert; lokales RESET entfernt sie ohne Änderung nativer Sichtbarkeit oder Datenschutzzuordnung. Der optionale Mediensortierungs-Index speichert nur abgeleitete Byte-Zahlen (oder eine Nicht-verfügbar-Markierung) in Website-lokalen Medien-Metadaten _site_essentials_file_size. Lokale Plugin-Datenbereinigung entfernt den Index, ohne Medien oder Dateien anzutasten. Website-lokale Option site_essentials_access_users enthält persönliche Design-Ausnahmen. Sie ist aus allen Exporten, Preset-Snapshots und Diagnose-Ausgaben ausgeschlossen. Beitragsmetadaten _site_essentials_target speichern ausdrücklich gewählte Zieladressen und Neuer-Tab-/nofollow-Vorgaben. Konfigurationsbereinigung und Deinstallation erhalten sie; Konfigurations-/Preset-Exporte enthalten sie nicht.

## Komponenten und Verbindungen

Enthält deckerweb Library 0.9.0 und deckerweb GitHub Release Updater 2.1.0, GPL-2.0-or-later, von David Decker. Der Online-Katalog der Library ist optional und zunächst ausgeschaltet. Der Updater fragt das konfigurierte GitHub-Repository im normalen WordPress-Updateprozess ab; dabei werden Anfragedaten wie die Server-IP an GitHub übertragen. Keine Analysefunktionen oder externen Schriften. Dieses Direktvertriebspaket ist kein WordPress.org-Build.

## FAQ

### Verändert die Aktivierung sofort WordPress?

Die ausgelieferte Konfiguration aktiviert drei Website-Hinweise nur für Administratoren: Indexierung, Datenschutzseite und Impressum. Bereinigungen und verhaltensändernde Module bleiben aus, bis sie gewählt oder über ein Preset angewendet werden. Ein ausdrücklich konfiguriertes Startpreset ist die Ausnahme.

### Wie funktionieren Presets und Importe?

Mitgelieferte und eigene lokale Presets nutzen dieselbe Vorschau. Eigene Presets sichern nur gespeicherte lokale Einstellungen und lassen sich einzeln exportieren. Unterschiede prüfen, Änderungen auswählen und in den Entwurf übernehmen. Zum Anwenden speichern. Ergänzen behält nicht gelieferte Entwurfswerte; Ersetzen verwendet dafür Standardwerte.

### Wie funktionieren Preset-Sammlungen?

Ausgewählte eigene Profile exportieren und die Sammlung auf der Zielwebsite prüfen. Kopien und eindeutige Namen wählen. Nach vollständiger Validierung wird der Preset-Speicher einmal geprüft gespeichert; vorhandene Presets, aktive Einstellungen und Konfigurationshistorie bleiben erhalten. Bis zu 20 Profile und 256 KiB pro Sammlung, bis zu 20 eigene Presets je Website. Avatare und Medienzuordnungen werden nicht übertragen.

### Löscht die vollständige Kommentarabschaltung vorhandene Kommentare?

Nein. Die Option unterdrückt öffentliche Core-Kommentarfunktionen, Verwaltungsseiten, Templates, Blöcke, Feeds, gewöhnliche REST-/XML-RPC-Zugänge und eigene Assets. Einzelregeln bleiben gespeichert. Private Redaktionsnotizen behalten ihre native Berechtigungsprüfung; gemeinsam genutzte WordPress-Styles bleiben verwendbar. Eigene Datenbankabfragen und fremde Kommentaroberflächen gesondert prüfen.

### Was schützt der lokale Avatar-Modus?

Core-Avatar-HTML und -Adressen verwenden lokal ausgelieferte Profilbilder oder einen lokalen Ersatz. Gravatar-HTTP-Anfragen und Verbindungshinweise werden gesperrt, ebenso andere externe Anfragen während der Avatar-Filter. Unabhängiger Update-Verkehr bleibt unverändert. Zuordnungen gelten je Website und werden nicht exportiert. Bereits zwischengespeicherte Seiten und eigenes Avatar-HTML ohne Core-API gesondert prüfen.

### Wird Multisite unterstützt?

Ja. Einzel- und Netzwerkaktivierung, unabhängige Site-Einstellungen und eine Netzwerkvorgabe zur Login-Sprachauswahl werden unterstützt.

### Was passiert bei der Deinstallation?

Eigene Einstellungen, benannte lokale Presets, vorherige Konfiguration und Netzwerkvorgabe bleiben gespeichert. Laufzeiteingriffe enden bei inaktivem Plugin.

[Fragen nach Themen](FAQ-de.md)

## Änderungsverlauf

### 0.9.0 · 2026-10-13

- **Neu:** Gezielte WordPress-Core-Einstellungen mit wiederverwendbaren Presets, geprüften Importen und unabhängiger Website-Konfiguration.
- **Neu:** Optionale Kommentarabschaltung, lokale Avatare, Design-Zugriffe, Medienwerkzeuge und alternative Beitragsziele.
- **Verbessert:** Lokale Avatare überlassen einem wirksamen Leitstand-Anbieter den Vorrang; Bedienelemente und Profilformulare werden ausgeblendet, gespeicherte Einstellungen und Zuordnungen bleiben erhalten.
- **Behoben:** Updatepakete werden vor dem Ersetzen auf Plugin-Identität, angebotene Version und WordPress-/PHP-Anforderungen geprüft.

### 0.1.0–0.8.0 · 2026-10-09–2026-10-12

- **Sonstiges:** Core-Einstellungen, Admin-Oberfläche, Berechtigungen und Multisite-Unterstützung aufgebaut und verfeinert.



## Projekt

Von David Decker – DECKERWEB. Site Essentials konzentriert sich auf WordPress-Core-Verhalten und ausgewählte Werkzeuge für den Admin-Alltag.

## Fehler und Sicherheit

Normale Fehler in [Issues](https://github.com/deckerweb/site-essentials/issues) melden; Fragen in [Discussions](https://github.com/deckerweb/site-essentials/discussions) stellen.

Sicherheitslücken vertraulich über [GitHubs private Sicherheitsmeldungen](https://github.com/deckerweb/site-essentials/security/advisories/new) melden. Plugin- und Komponenten-Versionen, WordPress-/PHP-Versionen, Schritte zur Reproduktion und Auswirkungen angeben. Keine Zugangsdaten übermitteln oder Exploit-Details in Issues veröffentlichen. Meldungen werden vertraulich geprüft; Korrektur und Offenlegung werden mit dem Melder abgestimmt.

## Unterstützung

[Ko-fi](https://ko-fi.com/deckerweb) · [Buy Me a Coffee](https://buymeacoffee.com/daveshine) · [PayPal](https://paypal.me/deckerweb)

## Lizenz und Herkunft

Copyright © 2026 David Decker – DECKERWEB. [GPL-2.0-or-later](LICENSE.txt). Eingebettete deckerweb Library und Updater: David Decker, GPL-2.0-or-later. Die Grafiken verwenden DejaVu Sans; die Distributionslizenz liegt als [FONT-LICENSE-DejaVu.txt](assets/images/FONT-LICENSE-DejaVu.txt) bei. Referenzfunktionen sind eigenständig umgesetzt; kein Premium-Plugin-Code enthalten.
