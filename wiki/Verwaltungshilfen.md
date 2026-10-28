# Verwaltungshilfen

[English](Administration-conveniences) · [Startseite](Home-de)

### Menüs weiterverwenden statt neu aufbauen.

Das Duplizieren unter Admin-Oberfläche aktivieren. Die Kopie behält native Linkeigenschaften, Hierarchie und SE-Sichtbarkeit, erhält aber keine Menüposition. Metadaten anderer Plugins werden nicht kopiert. Die Sichtbarkeit gilt nur für klassische Menüs und schützt keine Zielseiten. Ausgeblendete Elterneinträge verbergen auch ihre Untereinträge; Seiten-Caches müssen den Anmeldestatus unterscheiden.

### Benutzerinformationen mit klaren Grenzen.

Benutzer- und Inhalts-IDs bieten eine Kopiertaste; ersatzweise lässt sich der Text markieren. Die optionale Erfassung der letzten Anmeldung speichert je Benutzer und Website nur einen Zeitstempel einer erfolgreichen interaktiven Anmeldung. Bestehende Sitzungen aktualisieren ihn nicht. IP-Adressen und Historie werden nicht gespeichert. Nur Administratoren mit Berechtigung für die Benutzerliste sehen die Zeitstempel. WordPress-Datenschutzwerkzeuge können sie exportieren und löschen; Abschalten erhält vorhandene Werte, Deinstallation entfernt sie.

### Medien-Papierkorb: erst prüfen, dann konfigurieren.

Die optionale Statuskarte liest MEDIA_TRASH und EMPTY_TRASH_DAYS, ohne wp-config.php zu bearbeiten. Den nativen Medien-Papierkorb über die Serverkonfiguration vor dem WordPress-Start aktivieren; vorhandene Definitionen und Aufbewahrung beachten. Null Tage deaktivieren den Papierkorb. Dateien darin bleiben gespeichert und können direkt erreichbar bleiben; dies ist weder Backup noch Zugriffsschutz. Ausdrücklich endgültiges Löschen kann den Papierkorb umgehen. In Multisite gelten Konstanten normalerweise für die gesamte Installation.

### Speicherdiagnose ohne Live-Suche.

Die tägliche Diagnose des lokalen Upload-Speichers unter Mediathek aktivieren. Das gespeicherte Ergebnis steht unter Werkzeuge → Site Essentials: PHP. Die Anzeige durchsucht keine Verzeichnisse. Messungen enden spätestens an den Grenzen von 10.000 Einträgen, 512 Verzeichnissen oder zwei Sekunden. Unvollständige oder nicht verfügbare Ergebnisse zeigen keine Teilsumme. Eine manuell angeforderte Aktualisierung plant eine Aufgabe, höchstens alle 15 Minuten. WordPress-Cron kann abhängig vom Website-Traffic später laufen. Externe Medien, Datenbank und Hosting-Kontingent werden nicht gemessen.

### Eine Umgebungsanzeige.

Site Essentials zeigt die WordPress-Umgebung rechts in der Toolbar ausschließlich für Administratoren, ohne sie zu ändern. Stellt Brand Admin Schemes die Umgebungskennzeichnung bereit, übernimmt BAS die Darstellung und SE pausiert, auch bei ausgeblendeter BAS-Anzeige. SE behält seine Auswahl und setzt die Anzeige nach BAS-Deaktivierung fort. Der Einstellungsstatus erklärt den zuständigen Anbieter. BAS-Dateien und -Einstellungen bleiben unverändert.


## Beispiele aus der Verwaltung


![Benutzer-IDs kopieren und letzte Anmeldung anzeigen](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/110-users-de.png)



Die visuelle Profilvereinfachung blendet nur die ausgewählten nativen Felder aus. Gespeicherte Werte und Berechtigungen bleiben erhalten. Ein Zurücksetzen von Site Essentials entfernt Anmeldezeitpunkte und die Speicherdiagnose; Menü-Sichtbarkeitswerte bleiben als Inhalteigenschaften gespeichert und wirken bei ausgeschaltetem Modul nicht.


### Feed-Verweise und lesbare Feeds.

Automatische Feed-Verweise im Seitenquelltext lassen sich getrennt ausblenden: Blog, Kommentare, Kategorien/Schlagwörter, Autoren und Suche sowie Archive registrierter Inhaltstypen und Taxonomien. Die RSS-/Atom-Feeds bleiben erreichbar; deren Abschaltung ist eine eigene Option. Eine bisherige pauschale Ausblendung bleibt erhalten, einschließlich späterer Archive.

### Erfolgsmails zu automatischen Updates.

Erfolgsmeldungen lassen sich getrennt für WordPress, Plugins und Themes unterdrücken. Fehlermeldungen werden dadurch nicht unterdrückt. Eine bisherige pauschale Unterdrückung bleibt für alle drei Auswahlen erhalten, bis sie geändert wird.

### Derzeit unwirksame Einstellungen finden.

Ein Klick auf den Zähler derzeit unwirksamer Regeln zeigt die betroffenen Einstellungen mit bekanntem Grund und direktem Einstellungssprung. Eine vorhandene Suche wird dafür geleert, damit keine Treffer verborgen bleiben. Dies ändert nur die Ansicht.

Klassische Menüs, Duplizierung und Sichtbarkeit stehen gemeinsam in einem Container. Fehlt die klassische Verwaltung, bleiben ihre Einstellungen optisch ausgeblendet und gespeichert. Navigationen aus dem Navigationsblock sind davon getrennt. Klassische Widgets (Classic Widgets) und der klassische Editor (Classic Editor) werden jeweils kurz eingeordnet.


![Feed-Verweise getrennt ausblenden](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/110-feeds-de.png)

![Erfolgsmails getrennt für WordPress, Plugins und Themes steuern](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/110-update-mails-de.png)


### Medien- und Listenhilfen.

Erzeugte Bildgrößen optional im Medieneditor ansehen, ohne Dateien zu verändern. Neue Upload-Dateinamen lassen sich in Kleinbuchstaben umwandeln; bestehende Dateien bleiben unverändert. Inhaltslisten können eine sichtbare Auswahl für die Suche nur im Titel anbieten; Standard bleibt die vollständige Suche. Spalten für Beitragsbild, Änderungsdatum und das nur für Administratoren sichtbare Registrierungsdatum lassen sich einzeln aktivieren. Alle vier Hilfen sind zunächst ausgeschaltet.

### Klarer Status, datensparsame Diagnose.

Bekannte Gründe für pausierte Einstellungen werden in Regelübersicht, Übergabe und Website-Zustand einheitlich erklärt. Support-Diagnosen verwenden eine geprüfte Positivliste: Freitext, persönliche Zuordnungen sowie eigene Rollen- und Inhaltstypnamen fehlen; Listen zeigen nur Anzahlen. Die Speicherdiagnose unterscheidet noch nicht gemessen, unvollständig gemessen und nicht verfügbar.


![Mediathek: Optionen und Anleitung gemeinsam](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/v110-media-grouped-de.png)


### Ein Dashboard-Informationswidget.

Unter Dashboard lässt sich ein optionales Dashboard-Widget mit Titel, einfach formatiertem Text und einer Zielgruppe aus Rollen plus einzelnen Benutzern dieser Website einrichten. Eine passende Rolle ODER eine Benutzerfreigabe genügt; ohne Zielgruppe sieht es niemand. Benutzer können es über Ansicht anpassen ausblenden. Das Widget separat speichern. Allgemeine Einstellungs- und Preset-Exporte enthalten weder seinen Text noch persönliche Freigaben. Ein gesonderter, ausdrücklich bestätigter Textexport/-import überträgt nur gespeicherten Titel und Text, nie Aktivierung oder Zielgruppe. Abschalten und Deinstallation erhalten die lokale Konfiguration; das Zurücksetzen von Site Essentials entfernt sie.


![Ein natives Dashboard-Widget für die ausgewählte Zielgruppe; Beispielinhalt.](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/v110-dashboard-de.png)

Ein natives Dashboard-Widget für die ausgewählte Zielgruppe; Beispielinhalt.

### Ein lokaler Standard-Avatar.

Zuerst lokale Avatare aktivieren. Darunter erscheinen dann die optionale Benutzerlisten-Spalte und ein eigener Container für das Standardbild. Er zeigt das aktuelle Standardbild und bietet lokale Medienauswahl, Bild-Upload oder die Rückkehr zum mitgelieferten SE-Bild. Das Bild separat speichern. Bei fehlender, gelöschter, privater oder extern bereitgestellter Auswahl erscheint das SE-Bild. Beim Zurücksetzen der Auswahl werden keine Mediendateien kopiert, gesperrt oder gelöscht. Persönliche Profilbilder haben weiterhin Vorrang. Bild-IDs gelten je Website und fehlen in Einstellungs-, Preset- und Supportexporten.


![Standardbild mit lokaler Auswahl, Upload und Rücksetzen.](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/v110-avatar-de.png)

Standardbild mit lokaler Auswahl, Upload und Rücksetzen.

### Zustände und Sprünge ohne Kundendaten.

Der Bericht im Website-Zustand nennt den Aktivierungszustand des Dashboard-Widgets und ob das Standard-Avatarbild von SE, einer lokalen Auswahl oder einem Entwickler-Filter kommt. Widget-Texte, Kontaktdaten, ausgewählte Benutzer und Bild-IDs/URLs fehlen. Administratoren können über die Befehlspalette die Dashboard-Informationen und bei aktiven lokalen SE-Avataren das Standardbild öffnen. Die Befehle navigieren nur; sie speichern oder aktivieren nichts.

### Standardbild per Site-Functionality-Plugin vorgeben

Entwickler-Filter: site_essentials_local_avatar_default_id liefert eine positive ganzzahlige Attachment-ID der aktuellen Website oder 0 für keine Vorgabe. Nur ein verfügbares, öffentliches, lokal bereitgestelltes Rasterbild wird akzeptiert. Eine gültige Vorgabe hat Vorrang vor der gespeicherten Auswahl; andernfalls folgen gespeichertes Bild und SE-Standardbild. Die Oberfläche erklärt eine gültige Vorgabe und pausiert die Auswahl, ohne sie zu löschen. Persönliche Bilder haben Vorrang; Force-default-Aufrufe verwenden das wirksame Standardbild.

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $id ): int {
    if ( get_current_blog_id() !== 1 ) {
        return $id;
    }
    return 123;
} );
```

`1` durch die passende Website-ID und `123` durch die lokale Bild-ID ersetzen. IDs sind nicht zwischen Websites portabel. Kein wp-config-Eintrag erforderlich. Der Filter umgeht keine Bildprüfung und aktiviert das Modul nicht.


## Inhalts-IDs, Titelsuche und Listenspalten

Unter **Admin-Oberfläche** die Inhalts-IDs und bei Bedarf **Zusätzliche Listenspalten → Zuletzt geändert** aktivieren. In Beitrags-, Seiten- und Medienlisten steht die ID über der Kopieraktion. Die Spaltenüberschrift sortiert numerisch; „Zuletzt geändert“ sortiert nach Änderungszeitpunkt und verwendet das native WordPress-Datumsformat.

Die optionale Titelsuche ergänzt neben den Listenfiltern „Nur Titel“. Einen Suchbegriff eingeben, die Suchart auswählen und filtern bzw. suchen. Die Standardsuche bleibt „Titel und Inhalt“; die öffentliche Website-Suche wird nicht verändert.

![Titelsuche und schmale ID-Spalte mit Kopieraktion](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/release110-lists-de.png)

Beispiel einer gefilterten Seitenliste; Sortierung über die jeweiligen Spaltenüberschriften.

Schrittweise Anleitungen: [Dashboard](Dashboard-de), [Website-Icon und Datumsformate](Allgemeine-Einstellungen), [Diagnose und Befehlspalette](Diagnose).
