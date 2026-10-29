# Site Essentials

![Site Essentials](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/banner-de-github-1280x640.png)

[English](README.md)

## Kurzvorstellung

Dein WordPress. Deine Einstellungen. Site Essentials bündelt gezielte Einstellungen und praktische Werkzeuge für eine passend eingerichtete WordPress-Website. Vereinfache die Verwaltung, steuere WordPress-Funktionen und übertrage bewährte Konfigurationen auf weitere Websites. Entwickelt für die tägliche Betreuung und die Übergabe an Kunden, mit Unterstützung für Einzelinstallationen und einzelne Websites einer Multisite.

**1.1.1 · WordPress 7.1+ · PHP 8.2+**

[Plugin-Website](https://deckerwebplugins.com/de/plugins/site-essentials/) · [Dokumentation](https://deckerweb.github.io/site-essentials/de.html) · [Fragen nach Themen](FAQ-de.md)

## Inhalt

- [Auf einen Blick](#auf-einen-blick)
- [Installation](#installation)
- [Feature-Highlights](#feature-highlights)
- [WordPress-Funktionen](#wordpress-funktionen)
- [Konfiguration](#konfiguration)
- [Verhalten und Grenzen](#verhalten-und-grenzen)
- [Multisite](#multisite)
- [Vorkonfigurierte Installation](#vorkonfigurierte-installation)
- [Gespeicherte Daten](#gespeicherte-daten)
- [Komponenten und Verbindungen](#komponenten-und-verbindungen)
- [Verwaltungshilfen](#verwaltungshilfen)
- [Screenshots](#screenshots)
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

[Alle 68 Featurepunkte nach Aufgaben ansehen](https://github.com/deckerweb/site-essentials/wiki/Funktionen) – mit Voraussetzungen und Funktionsgrenzen.

## Installation

ZIP unter Plugins → Installieren → Plugin hochladen installieren und aktivieren. Einstellungen → Site Essentials öffnen. Preset wählen, Änderungen prüfen und alle Einstellungen speichern. Standardmäßig ist keine Bereinigung aktiviert.

## Feature-Highlights

### Dein WordPress. Deine Einstellungen.

Richte WordPress so ein, wie du es für deine Website brauchst. Verständliche Optionen und vorbereitete Presets erleichtern dir die Konfiguration. Du entscheidest, welche Einstellungen gelten und welche Funktionen aktiv sind. Prüfe ein Preset vor dem Anwenden, speichere deine Auswahl und verwende bewährte Konfigurationen über Import und Export erneut.

### Bewährte Einstellungen mitnehmen.

Du nutzt bereits Unbloater, Falcon oder beide zusammen? Die einmalige geführte Übernahme zeigt passende Einstellungen und macht Konflikte sichtbar, bevor du Änderungen bestätigst. Die Konfigurationswiederherstellung bleibt verfügbar, bis ein späteres Speichern die vorherige Sicherung ersetzt. Die bisherigen Plugins deaktivierst und entfernst du selbst. Die geführte Einstellungsübernahme gilt ausdrücklich für Falcon und Unbloater.

### Eine Verwaltung, mit der Kunden arbeiten können.

Blende selten benötigte Bereiche aus und mache wichtige Einstellungen verständlicher. Steuere den Zugang zu Menüs, Widgets und Customizer nach Benutzerrolle, mit lokalen Ausnahmen für einzelne Benutzer. Diese persönlichen Ausnahmen bleiben auf der Website und sind von Exporten ausgeschlossen.

### Wartung mit deiner eigenen WordPress-Seite.

Gestalte deine Wartungsseite im eingerichteten Block- oder Classic Editor. Besucher erhalten die Wartungsseite mit HTTP 503, Administratoren behalten Zugriff. Prüfe die Seite vor der Aktivierung in der Vorschau; ein orangefarbener Toolbar-Hinweis erinnert Administratoren an den aktiven Modus. Aktive Wartungsmodi von Bricks, Elementor und Breakdance haben Vorrang. Seiten-Caches müssen gegebenenfalls geleert oder umgangen werden.

### Lokale Avatare statt externer Gravatar-Aufrufe.

Verwende eigene Benutzerbilder aus der Mediathek. Das aktivierte lokale Avatar-Modul unterbindet Gravatar-Aufrufe für die Avatar-Ausgabe. Optional erscheinen die Bilder in der Benutzerübersicht. Stellt ein unterstützter Anbieter bereits lokale Avatare bereit, blendet Site Essentials sein eigenes Avatar-Modul aus und pausiert es.

### Wichtige Website-Einstellungen im Blick behalten.

Erhalte als Administrator einen Hinweis bei gesperrter Suchmaschinenindexierung und bestätige eine bewusste Entscheidung. Prüfe außerdem auf fehlende, unveröffentlichte oder erkennbar leere Datenschutz- und Impressumsseiten. Diese Prüfungen bewerten weder die Rechtskonformität noch die Qualität rechtlicher Texte.

### Mehrere Aufgaben. Ein gemeinsamer Platz.

Site Essentials kann – abhängig von den benötigten Funktionen – zusätzliche Einzelplugins ersetzen:

| Aufgabe | In Site Essentials |
| --- | --- |
| Disable Comments oder ähnliche Plugins | Kommentare vollständig deaktivieren oder gezielt einschränken |
| One User Avatar oder ähnliche Plugins | Lokale Benutzerbilder verwenden und Gravatar-Aufrufe unterbinden |
| Page Links To | Alternative Beitragsziele festlegen; vorhandene Page-Links-To-Ziele kontrolliert übernehmen |
| Einfache Maintenance-Mode-Plugins | Eine eigene WordPress-Seite als Wartungsseite verwenden |

Das verspricht keine vollständige Funktionsgleichheit. Die geführte Einstellungsübernahme gilt ausdrücklich für Falcon und Unbloater. Die Übernahme von Page-Links-To-Zielen ist davon getrennt; Daten anderer Plugins werden nicht automatisch migriert.

## WordPress-Funktionen

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
- Vier Navigationsbefehle für Einstellungen, Berichte, Website-Prüfungen und Wartung in der nativen Befehlspalette; Website-Zustandsinformationen für Administratoren einschließlich Wartungsstatus und bekannter übersteuerter Regeln.
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
- Einmalige Einstellungsübernahme von Falcon und Unbloater mit ausdrücklicher Quellenauswahl, Konfliktprüfung und Konfigurationswiederherstellung.
- Optionaler Wartungsmodus je Website mit normaler WordPress-Seite, Administrator-Vorschau, HTTP 503 und Cache-Hinweisen.
- Optionale Spalten für lokale Avatare und Benutzer-IDs, Datums-/Zeitbeispiele und ein breiterer CSS-Editor im Customizer.
- Klarere Navigationsgruppen und Optionstexte, Änderungspunkt im Speichern-Button und getrennte Menüoptionen für Blockvorlagen und Schriften.
- Testlinks für Administratoren zu fehlenden Seiten und Suchen ohne Treffer, mit Hinweisen zum jeweiligen Theme.
- Öffentliche Links zu robots.txt, Sitemap und Feeds sowie eine ausdrücklich gestartete anonyme Prüfung der Startseitenantwort.

## Konfiguration

Presets und Konfigurationsdatei-Importe verändern den Entwurf, nicht die Website. Erst Alle Einstellungen speichern wendet die Konfiguration an. Export lädt gespeicherte lokale Einstellungen herunter, keine ungespeicherten Änderungen. Der WordPress-Standard steht als Preset zur Prüfung bereit. Systemverhalten bleibt ohne ausdrückliche Einstellung unverändert. Die gesonderte Falcon-/Unbloater-Übernahme speichert ausdrücklich ausgewählte Einstellungen nach Bestätigung.

## Verhalten und Grenzen

Template-Bearbeitung wird gesperrt; vorhandene Templates werden weiter ausgegeben. jQuery Migrate wird nur für abgemeldete Frontend-Besucher entfernt. Heartbeat-Intervalle gelten als anfängliche Abrufeinstellung; WordPress oder andere Scripts können sie später verändern. Revisionsgrenzen führen keine sofortige Bereinigung aus; Core kann beim späteren Speichern ältere Revisionen entfernen. Vorhandene Kommentare werden in gewöhnlichen Frontend-Kommentar-Templates ausgeblendet, nicht in beliebigen eigenen Datenbankabfragen. XML-RPC wird nach dem Laden von WordPress gesperrt; das ersetzt keine Webserver-Firewall.

## Multisite

Auf einer Website oder netzwerkweit aktivierbar. Websites behalten eigene Optionen, auch nach Aktivierung neu angelegte Sites. Netzwerkverwaltung → Einstellungen → Site Essentials kann die Login-Sprachauswahl überall ausblenden oder den Websites überlassen. Weitere Netzwerkvorgaben werden nicht erzwungen.

## Vorkonfigurierte Installation

Für eine bewusst vorkonfigurierte Installation startup-preset.json vorab auf {"preset":"builder"}, "comfort", "head", "classic" oder "defaults" setzen. Die gewählte Konfiguration initialisiert jede noch unkonfigurierte Website bei erster Nutzung. Bestehende Einstellungen bleiben erhalten. Die ausgelieferte Datei enthält null.

## Gespeicherte Daten

Lokale Optionen: site_essentials_settings, site_essentials_previous und site_essentials_presets. Benutzer-Metadaten je Website: `site_essentials_avatar_SITE_ID` mit lokalen Attachment-IDs. Avatar-Medien bleiben normale WordPress-Uploads. Netzwerkoption: site_essentials_network. Deaktivierung und Deinstallation erhalten diese Werte. Die eingebettete Library entfernt eigene temporäre Daten nur gemäß ihrer Letzt-Host-Bereinigung; gemeinsame Einstellungen bleiben standardmäßig erhalten. Inhalte und Daten anderer Plugins werden nicht gelöscht. Die lokale Option site_essentials_checks erhält Impressumszuordnung und Inhalts-/Indexierungsbestätigungen. Sie wird nicht mit Konfigurationen exportiert; lokales RESET entfernt sie ohne Änderung nativer Sichtbarkeit oder Datenschutzzuordnung. Der optionale Mediensortierungs-Index speichert nur abgeleitete Byte-Zahlen (oder eine Nicht-verfügbar-Markierung) in Website-lokalen Medien-Metadaten _site_essentials_file_size. Lokale Plugin-Datenbereinigung entfernt den Index, ohne Medien oder Dateien anzutasten. Website-lokale Option site_essentials_access_users enthält persönliche Design-Ausnahmen. Sie ist aus allen Exporten, Preset-Snapshots und Diagnose-Ausgaben ausgeschlossen. Beitragsmetadaten _site_essentials_target speichern ausdrücklich gewählte Zieladressen und Neuer-Tab-/nofollow-Vorgaben. Konfigurationsbereinigung und Deinstallation erhalten sie; Konfigurations-/Preset-Exporte enthalten sie nicht. Die lokale Option site_essentials_maintenance speichert Aktivierung, Pause und ausgewählte Seite. Sie ist von allen Konfigurationstransfers und der Wiederherstellung ausgeschlossen, bleibt bei Deinstallation erhalten und wird durch ausdrückliches lokales RESET entfernt. Die Seite selbst bleibt als Inhalt erhalten.

## Komponenten und Verbindungen

Enthält deckerweb Library 1.1.0 und deckerweb GitHub Release Updater 2.2.1, GPL-2.0-or-later, von David Decker. Der Online-Katalog der Library ist optional und zunächst ausgeschaltet. Der Updater fragt das konfigurierte GitHub-Repository im normalen WordPress-Updateprozess ab; dabei werden Anfragedaten wie die Server-IP an GitHub übertragen. Keine Analysefunktionen oder externen Schriften. Dieses Direktvertriebspaket ist kein WordPress.org-Build.

## Verwaltungshilfen

### Ein Dashboard-Informationswidget.

Unter Dashboard lässt sich ein optionales Dashboard-Widget mit Titel, einfach formatiertem Text und einer Zielgruppe aus Rollen plus einzelnen Benutzern dieser Website einrichten. Eine passende Rolle ODER eine Benutzerfreigabe genügt; ohne Zielgruppe sieht es niemand. Benutzer können es über Ansicht anpassen ausblenden. Das Widget separat speichern. Allgemeine Einstellungs- und Preset-Exporte enthalten weder seinen Text noch persönliche Freigaben. Ein gesonderter, ausdrücklich bestätigter Textexport/-import überträgt nur gespeicherten Titel und Text, nie Aktivierung oder Zielgruppe. Abschalten und Deinstallation erhalten die lokale Konfiguration; das Zurücksetzen von Site Essentials entfernt sie.

### Ein lokaler Standard-Avatar.

Zuerst lokale Avatare aktivieren. Darunter erscheinen dann die optionale Benutzerlisten-Spalte und ein eigener Container für das Standardbild. Er zeigt das aktuelle Standardbild und bietet lokale Medienauswahl, Bild-Upload oder die Rückkehr zum mitgelieferten SE-Bild. Das Bild separat speichern. Bei fehlender, gelöschter, privater oder extern bereitgestellter Auswahl erscheint das SE-Bild. Beim Zurücksetzen der Auswahl werden keine Mediendateien kopiert, gesperrt oder gelöscht. Persönliche Profilbilder haben weiterhin Vorrang. Bild-IDs gelten je Website und fehlen in Einstellungs-, Preset- und Supportexporten.



### Medien- und Listenhilfen.

Erzeugte Bildgrößen optional im Medieneditor ansehen, ohne Dateien zu verändern. Neue Upload-Dateinamen lassen sich in Kleinbuchstaben umwandeln; bestehende Dateien bleiben unverändert. Inhaltslisten können eine sichtbare Auswahl für die Suche nur im Titel anbieten; Standard bleibt die vollständige Suche. Spalten für Beitragsbild, Änderungsdatum und das nur für Administratoren sichtbare Registrierungsdatum lassen sich einzeln aktivieren. Alle vier Hilfen sind zunächst ausgeschaltet.

### Klarer Status, datensparsame Diagnose.

Bekannte Gründe für pausierte Einstellungen werden in Regelübersicht, Übergabe und Website-Zustand einheitlich erklärt. Support-Diagnosen verwenden eine geprüfte Positivliste: Freitext, persönliche Zuordnungen sowie eigene Rollen- und Inhaltstypnamen fehlen; Listen zeigen nur Anzahlen. Die Speicherdiagnose unterscheidet noch nicht gemessen, unvollständig gemessen und nicht verfügbar.



### Menüs weiterverwenden statt neu aufbauen.

Das Duplizieren unter Admin-Oberfläche aktivieren. Die Kopie behält native Linkeigenschaften, Hierarchie und SE-Sichtbarkeit, erhält aber keine Menüposition. Metadaten anderer Plugins werden nicht kopiert. Die Sichtbarkeit gilt nur für klassische Menüs und schützt keine Zielseiten. Ausgeblendete Elterneinträge verbergen auch ihre Untereinträge; Seiten-Caches müssen den Anmeldestatus unterscheiden.

### Benutzerinformationen mit klaren Grenzen.

Benutzer- und Inhalts-IDs bieten eine Kopiertaste; ersatzweise lässt sich der Text markieren. Die optionale Erfassung der letzten Anmeldung speichert je Benutzer und Website nur einen Zeitstempel einer erfolgreichen interaktiven Anmeldung. Bestehende Sitzungen aktualisieren ihn nicht. IP-Adressen und Historie werden nicht gespeichert. Nur Administratoren mit Berechtigung für die Benutzerliste sehen die Zeitstempel. WordPress-Datenschutzwerkzeuge können sie exportieren und löschen; Abschalten erhält vorhandene Werte, Deinstallation entfernt sie.

### Medien-Papierkorb: erst prüfen, dann konfigurieren.

Die optionale Statuskarte liest MEDIA_TRASH und EMPTY_TRASH_DAYS, ohne wp-config.php zu bearbeiten. Den nativen Medien-Papierkorb über die Serverkonfiguration vor dem WordPress-Start aktivieren; vorhandene Definitionen und Aufbewahrung beachten. Null Tage deaktivieren den Papierkorb. Dateien darin bleiben gespeichert und können direkt erreichbar bleiben; dies ist weder Backup noch Zugriffsschutz. Ausdrücklich endgültiges Löschen kann den Papierkorb umgehen. In Multisite gelten Konstanten normalerweise für die gesamte Installation.

### Speicherdiagnose ohne Live-Suche.

Die tägliche Diagnose des lokalen Upload-Speichers unter Mediathek aktivieren. Das gespeicherte Ergebnis steht unter Werkzeuge → Systeminformationen und Speicher. Die Anzeige durchsucht keine Verzeichnisse. Messungen enden spätestens an den Grenzen von 10.000 Einträgen, 512 Verzeichnissen oder zwei Sekunden. Unvollständige oder nicht verfügbare Ergebnisse zeigen keine Teilsumme. Eine manuell angeforderte Aktualisierung plant eine Aufgabe, höchstens alle 15 Minuten. WordPress-Cron kann abhängig vom Website-Traffic später laufen. Externe Medien, Datenbank und Hosting-Kontingent werden nicht gemessen.

### Eine Umgebungsanzeige.

Site Essentials zeigt die WordPress-Umgebung rechts in der Toolbar ausschließlich für Administratoren, ohne sie zu ändern. Stellt eine aktive Brand-Admin-Schemes-Implementierung ihren Umgebungs-Callback bereit, übernimmt BAS die Darstellung und SE pausiert, auch bei ausgeblendeter BAS-Anzeige. SE behält seine Auswahl und setzt die Anzeige nach BAS-Deaktivierung fort. Der Einstellungsstatus erklärt den zuständigen Anbieter. BAS-Dateien und -Einstellungen bleiben unverändert.

[Neue Verwaltungshilfen mit Beispielen](wiki/Verwaltungshilfen.md)

## Screenshots

### Presets und Einstellungen

![Presets und Einstellungen](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/settings-de.png)

Presets prüfen, Einstellungen filtern und die Konfiguration bewusst speichern.

### Wartung mit eigener Seite

![Wartung mit eigener Seite](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/maintenance-de.png)

Eine veröffentlichte WordPress-Seite auswählen. Wartung verwendet HTTP 503 und wird separat gespeichert.

### Website-Hinweise

![Website-Hinweise](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/checks-de.png)

Administrator-Hinweise prüfen Sichtbarkeit und erkennbare Seiteninhalte; sie bewerten keine Rechtskonformität.

Bebilderte Anleitungen: [Dashboard](https://github.com/deckerweb/site-essentials/wiki/Dashboard-de), [Website-Icon und Datumsformate](https://github.com/deckerweb/site-essentials/wiki/Allgemeine-Einstellungen), [Listenspalten](https://github.com/deckerweb/site-essentials/wiki/Verwaltungshilfen) und [Diagnose](https://github.com/deckerweb/site-essentials/wiki/Diagnose).

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

### 1.1.1 · 2026-10-29

- **Verbessert:** Gegliederte Übersicht zu Systeminformationen und Speicher mit Sprungnavigation, verständlichen Beschriftungen und Hinweis bei deaktivierter Upload-Diagnose.
- **Behoben:** Die Kommentarbearbeitung bricht nicht mehr ab, wenn WordPress dem Metabox-Hook für alternative Beitragsziele ein Kommentarobjekt übergibt.
- **Behoben:** Die vereinfachte Toolbar-Begrüßung berücksichtigt auch maskierte Anzeigenamen unter WordPress 7.1.4.

### 1.1.0 · 2026-10-28

- **Neu:** Optionale Video-Upload-Sperre, Anleitung zum nativen Medien-Papierkorb und gespeicherte tägliche Upload-Speicherdiagnose.
- **Neu:** Klassische Menüs duplizieren und Menüeinträge für angemeldete oder abgemeldete Besucher steuern.
- **Neu:** Kopierbare Inhalts- und Benutzer-IDs, gezielte Profilvereinfachung und letzte Anmeldung je Website.
- **Neu:** Optionale Anzeige der WordPress-Umgebung mit Vorrang für Brand Admin Schemes.
- **Neu:** Optionale Bildgrößenübersicht, neue Upload-Dateinamen in Kleinbuchstaben, sichtbare Titelsuche im Admin und gezielte Listenspalten.
- **Neu:** Optionales Dashboard-Informationswidget mit websitebezogener Zielgruppe und ausdrücklicher Textübertragung; eigenes lokales Standard-Avatarbild mit dokumentiertem Entwickler-Filter.
- **Verbessert:** Getrennte Feed-Verweise und Erfolgsmails für WordPress, Plugins und Themes bei erhaltenem Verhalten; verständlichere Gruppen und direkter Zugriff auf derzeit unwirksame Regeln.
- **Verbessert:** Gebündelte Medienoptionen und Anleitungen, Umgebungsanzeige rechts nur für Administratoren, verständlichere Avatar-Hilfe und einheitliche datensparsame Diagnosen.
- **Verbessert:** Schmale zweizeilige und sortierbare ID-Spalten; sortierbares Änderungsdatum im nativen WordPress-Format; klarere Avatar-Abhängigkeiten, Abstände bei Update-E-Mails und Änderungspunkt. Website-Zustand und Befehlspalette berücksichtigen die neuen Module.
- **Verbessert:** Eigene Dashboard-Rubrik, übersichtlichere Editor-Listen und Datumsbeispiele, Favicon-Erklärung und nach Wichtigkeit geordnete Angaben im Website-Zustand.

### 1.0.1 · 2026-10-22

- **Behoben:** Aktualisierte Plugins verwenden ihren mitgelieferten Updater auch dann, wenn ein anderes Plugin zuvor eine ältere Kopie geladen hat.

### 1.0.0 · 2026-10-21

- **Neu:** Optionaler Wartungsmodus je Website mit normaler WordPress-Seite, Administrator-Vorschau, HTTP 503 und Cache-Hinweisen.
- **Neu:** Optionale Spalten für lokale Avatare und Benutzer-IDs, Datums-/Zeitbeispiele und ein breiterer CSS-Editor im Customizer.
- **Neu:** Geprüfte einmalige Übernahme unterstützter Falcon- und Unbloater-Einstellungen mit Konfliktauswahl und Wiederherstellung.
- **Neu:** Website-Adressen rein lesend, öffentliche robots.txt-, Sitemap- und Feed-Links sowie eine manuell gestartete Prüfung der Startseitenantwort.
- **Neu:** Vier Navigationsbefehle und erweiterte Angaben im Website-Zustand zu Wartung und wirksamen Einstellungen.
- **Verbessert:** Klarere Navigationsgruppen und Optionstexte, Änderungspunkt im Speichern-Button und getrennte Menüoptionen für Blockvorlagen und Schriften.
- **Verbessert:** Enthält deckerweb Library 1.1.0 mit aktuellem lokalem Katalog, persönlicher Sortierung und robusten Dialogen.
- **Behoben:** Debugmeldungen beim Start und vorzeitige Einstellungsabfragen beim Anlegen neuer Multisite-Websites vermeiden.
- **Behoben:** Der Avatar-Vorrang berücksichtigt auch kompatible lokal geladene Anbieter mit automatischem Laden ihrer Klassen.
- **Sonstiges:** Erfordert WordPress 7.1 oder höher und PHP 8.2 oder höher.

### 0.9.0 · 2026-10-13

- **Neu:** Gezielte WordPress-Core-Einstellungen mit wiederverwendbaren Presets, geprüften Importen und unabhängiger Website-Konfiguration.
- **Neu:** Optionale Kommentarabschaltung, lokale Avatare, Design-Zugriffe, Medienwerkzeuge und alternative Beitragsziele.
- **Verbessert:** Lokale Avatare überlassen einem kompatiblen aktiven Anbieter den Vorrang; Bedienelemente und Profilformulare werden ausgeblendet, gespeicherte Einstellungen und Zuordnungen bleiben erhalten.
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
