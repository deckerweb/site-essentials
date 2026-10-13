# Fragen nach Themen

[English](FAQ)

## Einstieg und Konfiguration

### Verändert die Aktivierung sofort WordPress?

Die ausgelieferte Konfiguration aktiviert drei Website-Hinweise nur für Administratoren: Indexierung, Datenschutzseite und Impressum. Bereinigungen und verhaltensändernde Module bleiben aus, bis sie gewählt oder über ein Preset angewendet werden. Ein ausdrücklich konfiguriertes Startpreset ist die Ausnahme.

### Wie funktionieren Presets und Importe?

Mitgelieferte und eigene lokale Presets nutzen dieselbe Vorschau. Eigene Presets sichern nur gespeicherte lokale Einstellungen und lassen sich einzeln exportieren. Unterschiede prüfen, Änderungen auswählen und in den Entwurf übernehmen. Zum Anwenden speichern. Ergänzen behält nicht gelieferte Entwurfswerte; Ersetzen verwendet dafür Standardwerte.

### Wie funktionieren Preset-Sammlungen?

Ausgewählte eigene Profile exportieren und die Sammlung auf der Zielwebsite prüfen. Kopien und eindeutige Namen wählen. Nach vollständiger Validierung wird der Preset-Speicher einmal geprüft gespeichert; vorhandene Presets, aktive Einstellungen und Konfigurationshistorie bleiben erhalten. Bis zu 20 Profile und 256 KiB pro Sammlung, bis zu 20 eigene Presets je Website. Avatare und Medienzuordnungen werden nicht übertragen.

### Was zeigen die Regelfilter?

Die Übersicht zeigt gespeicherte lokale Werte, lokale Entwürfe, wirksame Entwurfswerte und Referenzwerte mit Status/Quelle. Aktiv umfasst eingeschaltete Standardhinweise; gespeichert, aber nicht angewendet bezeichnet eine bekannte Abhängigkeit oder Site-Einschränkung, keine automatische Konfliktdiagnose. Gespeicherte oder Entwurfswerte mit Plugin-Defaults oder eingebautem/eigenem Preset vergleichen. Suche und Statusfilter wirken gemeinsam über Bereiche; Bereichsnavigation oder Einstellungssprung setzt Suche/Statusfilter zurück. Referenzen und Filter ändern keine Einstellungen und werden nicht gespeichert. Ohne JavaScript bleiben alle Einstellungen verfügbar.

## Login, Editor und Core-Verhalten

### Was ändern die Login-Logo- und Favicon-Optionen?

Der Logo-Link kann auf die Startseite dieser Website zeigen, sein Linktext den Websitenamen verwenden. Logo-Bild und Login-Weiterleitungen bleiben unverändert. Favicon-Normalisierung entfernt nur die genauen blauen/grauen Core-Fallbacks mit dem W und erhält konfigurierte Website-Icons und andere Adressen. Die Core-Favicon-Anfrage erhält leeres HTTP 204, wenn kein Icon übrig bleibt. Physische Favicon-Dateien, Theme-Markup und Browser-Caches werden nicht entfernt.

### Bleiben eigene Patterns erhalten?

Ja. Mitgelieferte als Core markierte Patterns und externe Verzeichnisvorschläge haben getrennte Schalter. Lokal registrierte Benutzer-, Theme- und Plugin-Patterns bleiben verfügbar. Externe theme.json-Verzeichnisvorschläge werden ebenfalls vom Remote-Schalter erfasst.

### Konvertiert der klassische Editor vorhandene Blöcke?

Nein. Die Auswahl ändert nur die Editoroberfläche. Gespeichertes Block-Markup bleibt erhalten; Block-Inhalte erhalten auf der klassischen Seite einen Warnhinweis. Komplexe Blöcke vor dem Speichern prüfen.

### Wird Heartbeat abgeschaltet?

Nein. Anfängliche Intervalle wählen oder bestehendes Verhalten erhalten; optional mit Inhaltstyp-Regeln im Beitragseditor.

### Welche automatischen Einbettungen werden deaktiviert?

Nur automatische URL-Umwandlung in Inhalten und Text-Widgets ohne Block-Markup. Alle Inhalte mit Blöcken behalten ihr bisheriges Embed-Verhalten; ausdrückliche Embed-Shortcodes bleiben aktiv. Externe Anfragen werden nicht allgemein gesperrt.

### Was bewirkt die Sperre eigener Einbettungen?

Core-Embed-Seiten und öffentliche oEmbed-Antworten dieser Website erhalten HTTP 404, auch für Einbettungen innerhalb derselben Website. Normale Beitragslinks bleiben öffentlich. Bereits extern zwischengespeicherte Einbettungen können fortbestehen; manuelle Iframes und Kopieren werden nicht verhindert.

### Bleiben Dashicons-Abhängigkeiten erhalten?

Nur die direkte Einbindung für abgemeldete öffentliche Besucher entfällt. Dashicons bleibt registriert und wird bei benötigten Stylesheet-Abhängigkeiten geladen. Login, Admin, Customizer und angemeldete Sitzungen behalten es.

### Was umfasst die Abschaltung von Suche und Autorenarchiven?

Native öffentliche Suchseiten, Such-Feeds, Autorenarchive und Autoren-Feeds liefern HTTP 404. Core-Suchformulare und -Blöcke werden verborgen. Native REST-Suche liefert für Besucher 404; angemeldete Benutzer mit edit_posts behalten die Suche im Editor. Admin-Suche und normale Beiträge bleiben verfügbar. Autorenarchiv-Adressen und die native Benutzer-Sitemap entfallen. Eigene Formulare, andere REST-Inhaltslisten, fest gesetzte Links und zwischengespeicherte Seiten gesondert prüfen.

### Kann das große Willkommens-Panel im Dashboard abgeschaltet werden?

Ja. Unter den Core-Dashboard-Widgets das Willkommens-Panel auswählen. Der native Callback entfällt ohne Änderung der persönlichen Einstellung. Eigene Drittanbieter-Willkommens-Callbacks sind nicht betroffen.

## Kommentare und Avatare

### Löscht die vollständige Kommentarabschaltung vorhandene Kommentare?

Nein. Die Option unterdrückt öffentliche Core-Kommentarfunktionen, Verwaltungsseiten, Templates, Blöcke, Feeds, gewöhnliche REST-/XML-RPC-Zugänge und eigene Assets. Einzelregeln bleiben gespeichert. Private Redaktionsnotizen behalten ihre native Berechtigungsprüfung; gemeinsam genutzte WordPress-Styles bleiben verwendbar. Eigene Datenbankabfragen und fremde Kommentaroberflächen gesondert prüfen.

### Was schützt der lokale Avatar-Modus?

Core-Avatar-HTML und -Adressen verwenden lokal ausgelieferte Profilbilder oder einen lokalen Ersatz. Gravatar-HTTP-Anfragen und Verbindungshinweise werden gesperrt, ebenso andere externe Anfragen während der Avatar-Filter. Unabhängiger Update-Verkehr bleibt unverändert. Zuordnungen gelten je Website und werden nicht exportiert. Bereits zwischengespeicherte Seiten und eigenes Avatar-HTML ohne Core-API gesondert prüfen.

### Können Abonnenten eigene Avatare hochladen?

Ja, über das getrennt berechtigte lokale Avatar-Formular im eigenen WordPress-Profil. Allgemeine Medien-Upload-Rechte werden nicht erteilt. Direkte Uploads: JPEG, PNG, GIF, WebP und AVIF bis 4 MiB und 4096 mal 4096 Pixel; strengere native Upload-Grenzen und erlaubte Dateitypen gelten zusätzlich. Vorhandene Bilder werden nach normalen Medienberechtigungen gewählt. Bilder bleiben öffentliche WordPress-Medien; das Entfernen einer Zuordnung löscht das Bild nicht.

## Blog, Menüs und Design-Zugriffe

### Löscht die Abschaltung der Beitragsverwaltung Inhalte?

Nein. Native Beitragsmenüs, Neu hinzufügen, Dashboard-Angebote, direkte Seiten, objektbezogene Bearbeitung/Löschung und native REST-Verwaltung werden eingeschränkt. Seiten/Medien und andere Inhaltstypen behalten ihre Rechte, einschließlich gemeinsam genutzter Taxonomie-Seiten. XML-RPC-Verwaltung und eigene serverseitige Schreibwege gehören nicht zu dieser Admin-/REST-Steuerung; bei Bedarf die separate XML-RPC-Sperre nutzen.

### Was bewirkt die Abschaltung der öffentlichen Blog-Ausgabe?

Native Beiträge aus normalen Frontend-Abfragen, öffentlichen nativen REST-Lese-/Suchzugängen, Kommentaren, eigenen Embeds und Beitrags-Sitemap entfernen. Reine Blog-Zugänge und -Feeds liefern HTTP404. Gemeinsam genutzte Taxonomiearchive können andere Inhaltstypen weiter anzeigen. Eine Startseite mit neuesten Beiträgen wird404: vorher eine statische Seite wählen. Berechtigte native Vorschauen und Editor-REST-Verwaltung bleiben bei aktiver Verwaltung verfügbar. Unabhängige Feed-Einstellungen bleiben unverändert. Eigene feste Abfragen/Ausgaben, fremde APIs und Caches gesondert prüfen.

### Wie funktionieren eigene Beitragsnamen?

Umbenennung einschalten und Mehrzahl/Einzahl angeben (je bis80Zeichen). Native Beschriftungen, klassische Ergebnismeldungen und Dashboard-Zähler nutzen diese Namen. Technischer Inhaltstyp post, URLs, IDs und Taxonomie-Namen bleiben unverändert. Feste Drittanbieter-Texte werden nicht übersetzt. Namen bleiben nach erneuter Freigabe der Verwaltung verfügbar. Der Schalter und beide Eingabefelder stehen gemeinsam in einer Karte. Namen erscheinen nur bei aktiver Umbenennung; beim Abschalten bleiben gespeicherte Namen erhalten, ohne angewendet zu werden.

### Was passiert mit deaktivierten klassischen Widgets?

Ausgewählte klassische Core-Widgets sind nicht verfügbar und werden nicht ausgegeben. Site Essentials löscht ihre gespeicherten Instanzen und Sidebar-Zuordnungen nicht. Erneutes Aktivieren stellt die Ausgabe wieder her; nach Änderungen an Widget-Layouts Zuordnungen prüfen. Block-Entsprechungen und Drittanbieter-Widgets sind nicht betroffen.

### Was umfassen die Widgets- und Customizer-Sperren?

Native Adminseiten werden ausgeblendet und direkte Zugriffe liefern HTTP 403. Beim Customizer entfallen auch der Toolbar-Link sowie native Header-/Hintergrund-Links. Gespeicherte Widgets, Sidebar-Zuordnungen und Theme-Einstellungen bleiben erhalten. Frontend-Ausgabe bleibt bestehen, sofern keine separaten Widget-Regeln aktiv sind. Drittanbieter-Editoren und REST-Verwaltung sind nicht umfasst. Der klassische Widget-Editor ändert nur die Oberfläche; ausgewählte Core-Widgets werden separat gesteuert. Bei klassischem Bricks-Parent-/Child-Theme entfallen native Site-Editor-Menülinks automatisch, einschließlich Varianten mit Query-Parametern. Bricks-Templates und andere Pluginmenüs bleiben verfügbar.

### Wie funktionieren Zugriffsregeln und persönliche Ausnahmen?

WordPress-Berechtigungen beibehalten, alle sperren oder erlaubte Rollen auswählen. Bei ausgewählten Rollen gilt: persönliche Sperre vor persönlicher Freigabe vor passender Rolle. Multisite-Super-Administratoren bleiben zulässig, sofern nicht persönlich gesperrt. Widgets und Customizer behalten native Berechtigungen; die Regeln schränken den Zugriff zusätzlich ein. Gezielte klassische Menüfreigabe erlaubt die native Menüseite und bekannte Menü-AJAX-Aktionen, ohne allgemeine Theme-, Widgets-, Customizer- oder Site-Editor-Rechte zu erteilen. Native Objekt-/Nonce-Prüfungen und Schutz bei Inhaltsabfragen bleiben wirksam. Ausgeschlossene Widget-/Menübereiche werden auch in nativen Customizer-Einstellungen und bearbeitenden REST-Routen geschützt; öffentliche Navigationsabfragen bleiben unverändert. Drittanbieter-Editoren sind nicht umfasst. Regeln gelten je Website. Rollenregeln werden mit Einstellungen/Presets übertragen; persönliche Ausnahmen liegen separat, fehlen in allen Exporten und bleiben bei Preset-/Import-/Rollback-Vorgängen erhalten. Alle Einstellungen speichern übernimmt Rollenregeln und lokale Ausnahmen nach Validierung und Zustandsprüfung. Lokale eigene Datenbereinigung entfernt Ausnahmen. Benutzersuche und Exporthinweise erscheinen nur für Administratoren. Für umfassende Rechteverwaltung eignet sich ein eigenes Plugin wie Members.

### Wie funktionieren feste Menüfeld-Vorgaben?

Jedes Feld einzeln wählen: Linkziel, CSS-Klassen oder XFN anzeigen; Title-Attribut oder Beschreibung ausblenden. Alle Vorgaben sind anfangs aus. Sie übersteuern die nativen Ansichtsoptionen für alle Menüeditoren, erhalten aber deren persönliche Vorgaben, auch beim Speichern anderer Ansichtsoptionen. Versteckte Menüfelder übermitteln ihre Werte unverändert. Nach Abschalten der Vorgaben gelten wieder die persönlichen Einstellungen. Die Bricks-Bereinigung ist getrennt optional: Vorlagen, Template-Tags und Bundles entfallen aus nativen Menü-/Customizer-Angeboten; vorhandene Links und Vorlagenverwaltung bleiben erhalten.

## Medien und Beitragsziele

### Werden vorhandene PDF-Vorschauen oder Inhalte gelöscht?

Nein. Nur neue PDF-Fallback-Vorschauen werden unterbunden. Textoptionen entfernen native Ausgabefilter ohne gespeicherte Inhalte umzuschreiben. Die Kommentaroption erhält ausdrücklich gesetzte Links.

### Wie funktioniert die Dateigrößen-Sortierung?

Die optionalen Schalter unter Mediathek aktivieren. Anzeige und Sortierung wirken in der nativen Listenansicht; Dateitypfilter ergänzen Liste und Raster. Sortierung blendet die Spalte ebenfalls ein. Der lokale Zahlenindex entsteht in Schritten mit höchstens 50 Medien, solange ein berechtigter Benutzer die Mediathek geöffnet hat. Indexierung kann pausiert werden; erneutes Öffnen setzt sie fort. Nach Abschluss die Liste neu laden. Fehlende, externe oder nur remote vorhandene Dateien bleiben sichtbar und werden zuletzt sortiert. Gemessen werden lokale Mediendateien im Upload-Verzeichnis dieser Website, ohne HTTP-Zugriffe und ohne Vorschaubilder zu addieren. Native Datei-/Metadatenänderungen aktualisieren den Index bei aktiver Sortierung. Direkte Dateisystemänderungen ohne native Medienaktualisierung können alte Indexwerte hinterlassen. Zusätzliche Upload-Typen werden nicht freigeschaltet. Dateiersetzung ist nicht enthalten.

### Wie funktionieren alternative Beitragsziele?

Unter Alternative Beitragsziele das Modul aktivieren und öffentliche Inhaltstypen auswählen. Der jeweilige Inhaltseditor erhält eine native Meta-Box (im Blockeditor Meta-Boxen öffnen). Normale Adresse oder HTTP(S)-Adresse/internen Pfad auswählen. Ursprüngliche öffentliche GET-/HEAD-Einzeladressen leiten temporär mit HTTP 302 weiter; Vorschau, Embeds, Feeds, REST und Bearbeitung werden nicht weitergeleitet. Native öffentliche Permalinks verwenden das Ziel; Entwurfs-/private Links bleiben nativ. Die Auswahl Normale WordPress-Adresse entfernt den eigenen Ziel-Datensatz dieses Inhalts. Modul/Inhaltstyp abschalten erhält Datensätze und stellt natives Verhalten wieder her. Keine allgemeine Weiterleitungsverwaltung oder URL-Historie. Zielfelder erscheinen erst nach Auswahl der alternativen Adresse. Veraltete Zielformulare werden abgewiesen; neu laden zeigt den aktuellen Wert.

### Wie wird die Page-Links-To-Übernahme kontrolliert?

Inhaltstypen auswählen und speichern, dann Page-Links-To-Ziele prüfen oder Werkzeuge → Alternative Beitragsziele öffnen. Vorschau mit höchstens 25 Einträgen je Seite; nichts ist vorausgewählt. Gewünschte Datensätze auswählen. Seit der Vorschau geänderte Quellen, vorhandene Site-Essentials-Ziele, unberechtigte Objekte, unsichere URLs und lokale Schleifen werden übersprungen. Aktuelle Metaschlüssel mit Unterstrich und alte Schlüssel vor Version 3 werden unterstützt. Die Neuer-Tab-Vorgabe wird übernommen, nofollow nicht hinzugefügt. Originaldaten werden nicht gelöscht. Solange Page Links To geladen ist, pausieren unsere Verarbeitung und Bearbeitung. Page Links To vor dem Wechsel selbst deaktivieren; kein Plugin wird automatisch umgeschaltet. Alle verfügbaren Ziele dieser Seite auswählen ist optional und anfangs aus. Leere oder abgeschnittene Auswahlen übernehmen keine Daten. Inhalte ohne Titel erhalten eine ID-Beschriftung.

### Welche Grenzen gelten für Linkattribute und Sitemaps?

Neuer Tab/noopener und optionales nofollow gelten für native Navigationsmenü- und wp_list_pages-Links. Eigene Theme-, Builder- und Query-Loop-Ausgaben benötigen eigene Attribute; kein globales Frontend-Skript schreibt Links um. Inhalte mit Beitragszielen entfallen bei aktivem Modul aus der nativen WordPress-Beitrags-Sitemap. Drittanbieter-Sitemaps/Canonical-Ausgaben und gecachte Seiten sind je Website zu prüfen. Das Modul prüft externe Zieladressen nicht durch HTTP-Anfragen.

### Werden Beitragsziele mit Einstellungen exportiert oder gelöscht?

Konfigurations-/Preset-Exporte enthalten nur Modulaktivierung und Inhaltstypen, niemals einzelne Zieladressen. Die Beitragsmetadaten _site_essentials_target gehören zum Inhalt. Konfigurations-Rollback/-Bereinigung, Deaktivierung und Deinstallation erhalten sie; eine Konfigurationsbereinigung deaktiviert die Verarbeitung. Ziele direkt im Inhaltseditor ändern/entfernen. WordPress-Inhaltsexporte oder Backups können diese Metadaten selbstverständlich enthalten.

## Website-Prüfungen und Diagnose

### Warum erscheinen Hinweise sofort?

Indexierungs-, Datenschutz- und Impressumsprüfung sind standardmäßig aktiv. Sie zeigen nur lokalen Administratoren Hinweise und veröffentlichen weder Seiten noch ändern sie die Sichtbarkeit. Jede Hinweiskategorie lässt sich unter Website-Prüfungen abschalten. Andere Core-Einstellungen behalten ihre bisherigen Defaults.

### Wie funktionieren Indexierungsentscheidungen?

Bei abgeschalteter Indexierung die Entscheidung bewusst bestätigen und beibehalten oder die Freigabe ausdrücklich bestätigen. Bestätigungen gehören zu dieser Website und verfallen bei Änderung der nativen Sichtbarkeit. Zur erneuten Prüfung Bestätigungen zurücksetzen. Die Einstellung ist eine Aufforderung an Suchmaschinen, keine Zugriffssperre.

### Wie werden Datenschutzseite und Impressum geprüft?

Die Datenschutzseite nutzt die native WordPress-Zuordnung. Das Impressum wird anhand des exakten Titels oder Slugs Impressum/Imprint erkannt, sofern nicht ausdrücklich zugeordnet. Mehrdeutige Treffer erfordern eine Zuordnung. Fehlende, unveröffentlichte, passwortgeschützte und leere Seiten erzeugen Hinweise. Gespeicherte Inhalte werden ohne Ausführung oder externe Anfragen geprüft; keine Bewertung der rechtlichen Textqualität.

### Was gilt für Builder-Seiten?

Bekannte gespeicherte Builder-Daten, Shortcodes und nicht sicher prüfbare dynamische Blöcke werden zur manuellen Prüfung vorgemerkt statt als leer bezeichnet. Berechtigte Bearbeiter können Inhalt veröffentlichter Seiten bestätigen. Die Bestätigung gilt für den Seiten-/Inhaltsfingerabdruck und verfällt bei relevanten Änderungen. Nur über Templates ausgegebene Inhalte oder andere Builder gesondert prüfen.

### Welche Daten zeigt die PHP-Seite?

Nur ausgewählte sichere Werte des aktuellen Web-Aufrufs: PHP-Version, Speicherziele/aktuelles Limit, Upload-/Eingabe-/Zeitlimits, nützliche Erweiterungen, Cache-Unterstützung und verfügbare Suhosin-Werte. Kein phpinfo, Umgebungsdump, Pfade, Zugangsdaten oder externe Verbindungen. Konfigurierte Ziele sind kein reservierter/verfügbarer RAM. Fehlende Werte sind nicht verfügbar, nicht unbegrenzt. Eine Cache-Erweiterung belegt keine Verbindung oder funktionierende Seiten-Caches. Der optionale Admin-Footer verlinkt die Seite.

## Multisite und gespeicherte Daten

### Wird Multisite unterstützt?

Ja. Einzel- und Netzwerkaktivierung, unabhängige Site-Einstellungen und eine Netzwerkvorgabe zur Login-Sprachauswahl werden unterstützt.

### Wie funktioniert das Zusammenspiel mit Leitstand?

Site Essentials öffnet keine durch frühere Filter gesperrten Kommentare und erhält strengere eingehende Revisionsgrenzen. Leitstand-Netzwerkregeln werden nicht umgeschrieben. Stellt der geladene Leitstand-Avatar-Renderer lokale Avatare auf der aktuellen Website wirksam bereit, pausiert Site Essentials seine eigene Ausgabe, Avatar-HTTP-Eingriffe, Profil-Bedienelemente und Uploads und blendet den Avatar-Einstellungsbereich aus. Gespeicherte Auswahl und Bildzuordnungen bleiben erhalten. Erlaubte Website-Ausnahmen werden beachtet; eine verpflichtende Netzwerkvorgabe hat Vorrang. Stellt Leitstand die Funktion nicht mehr bereit, wird die gespeicherte Site-Essentials-Auswahl wieder verfügbar. Ein veraltetes Avatar-Formular kann während des Leitstand-Vorrangs keine Zuordnungen verändern.

### Was passiert bei der Deinstallation?

Eigene Einstellungen, benannte lokale Presets, vorherige Konfiguration und Netzwerkvorgabe bleiben gespeichert. Laufzeiteingriffe enden bei inaktivem Plugin.
