# Website-Prüfungen

[English](Website-checks) · [Startseite](Home-de)

**Einstellungen → Site Essentials → Website-Prüfungen** öffnen. Die drei Hinweise betreffen unerwünscht abgeschaltete Suchmaschinenindexierung, die zugewiesene native Datenschutzseite und eine gewählte oder erkannte Impressumsseite. Geprüft werden Veröffentlichung und erkennbare Inhalte, nicht die Qualität rechtlicher Texte. Bei absichtlich gesperrter Indexierung die aktuelle Entscheidung bestätigen; eine tatsächliche Änderung der Sichtbarkeit hebt diese Bestätigung auf.

![Website-Hinweise](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/checks-de.png)

Administrator-Hinweise prüfen Sichtbarkeit und erkennbare Seiteninhalte; sie bewerten keine Rechtskonformität.

## Adressen und öffentliche Dateien

Die Website-Adresse bezeichnet den öffentlichen Auftritt; die WordPress-Adresse die Installationsdateien. Beide sind nur lesend und kopierbar. Der Link zum Website-Zustand öffnet das vorhandene WordPress-Werkzeug.

![Öffentliche Website-Dateien](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/public-resources-de.png)

robots.txt, Sitemap- und Feed-Adressen unverändert öffnen; eine Antwortprüfung nur bei Bedarf starten.

robots.txt am Host-Stamm, verfügbare Sitemap-Links und Feed-Links öffnen. Site Essentials verändert diese Dateien nicht. Liefert ein anderes Plugin die Sitemap, kann die native WordPress-Sitemap fehlen; Sitemap-Angaben in einer abgerufenen robots.txt können weitere Links liefern. Feed-Einschränkungen können die angezeigten Feeds absichtlich unerreichbar machen.

## Manuelle Prüfung der öffentlichen Antwort

Die Prüfung startet erst durch einen Administrator. Startseite und robots.txt werden anonym über die sichere WordPress-HTTP-API abgerufen, ohne Login-Cookies. Angezeigt werden Antwortstatus, Weiterleitungen, Inhaltstyp, Indexierungsheader/-Meta und erkennbare Canonical-Angaben. Fremde Inhalte erscheinen als Text und werden nicht ausgeführt. Ergebnisse werden nicht dauerhaft gespeichert und lassen sich kompakt kopieren. Es gibt weder Hintergrundüberwachung noch einen externen Analysedienst.

Hosting-Beschränkungen, Loopback-Zugriff und Caches können Ergebnisse beeinflussen; eine Antwort beweist keine Suchmaschinenindexierung. Aktive Wartung kann absichtlich HTTP 503 liefern.

## Besucherführung testen

Zwei Testaktionen öffnen eine nicht vorhandene Adresse und eine Suche ohne erwarteten Treffer. Theme oder Builder bestimmen die Gestaltung. Site Essentials ordnet keine eigene 404-Seite zu und ersetzt kein Suchtemplate.
