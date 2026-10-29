# Konfigurationsinformationen und schnelle Einstiege

[English](Diagnostics) · [Startseite](Home-de)

**Werkzeuge → Website-Zustand → Bericht → Site Essentials** öffnen. Version, Geltungsbereich und Gültigkeit der Konfiguration stehen zuerst. Danach folgen Netzwerkvorgaben und Betriebsinformationen wie der Wartungsmodus; Komfortfunktionen stehen weiter unten.

![Konfigurationsinformationen von Site Essentials im Website-Zustand](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/release110-health-de.png)

Das sind Konfigurationszustände, keine Laufzeittests und keine Sicherheitsbewertung. Kundentexte, Preset-Namen und Notizen, ausgewählte Benutzer sowie Bild-IDs und -Adressen bleiben ausgespart. Der native Bericht zum Website-Zustand enthält zusätzlich Angaben von WordPress und anderen Plugins; vor einer Weitergabe den gesamten Bericht prüfen.

## Einstellungen über die Befehlspalette öffnen

Wo WordPress seine Befehlspalette bereits bereitstellt, können Administratoren nach **Site Essentials** suchen. Befehle öffnen Einstellungen, Berichte, Website-Prüfungen, Wartungsmodus, Dashboard-Informationen, Systeminformationen und Speicher sowie bei aktiven SE-Avataren die Standardbild-Einstellungen. Sie navigieren nur; sie aktivieren keine Funktion und speichern keine Änderungen.

## Gespeicherte Einstellungen ohne Wirkung finden

In der Regelübersicht den Zähler **gespeichert, aber nicht angewendet** anklicken, sofern er größer als null ist. Die betroffenen Regeln erscheinen mit Begründung und Einstellungssprung. Eine gespeicherte Einstellung kann absichtlich durch einen anderen Anbieter oder eine übergeordnete Regel pausieren.

Die manuellen Prüfungen öffentlicher Antworten stehen unter [Website-Prüfungen](Website-Pruefungen). Sie sind von den Konfigurationsinformationen im Website-Zustand zu unterscheiden.


## Systeminformationen und Speicher

Unter **Werkzeuge → Systeminformationen und Speicher** stehen WordPress/PHP, Arbeitsspeicher und Laufzeit, Uploads und Formulare, der Speicherplatz der Upload-Dateien sowie technische Details in getrennten Bereichen. Sprunglinks führen direkt zum gewünschten Abschnitt. Technische Schlüssel bleiben unter den verständlichen Beschriftungen sichtbar. Die Upload-Diagnose ist optional: Bei deaktivierter Option führt ein Link zur Mediathek-Einstellung; beim Aufruf wird keine Messung gestartet. Nach Aktivierung wird das gespeicherte Ergebnis mit Messzeitpunkt angezeigt. Die interne Seitenadresse bleibt erhalten.


Suhosin bleibt unter den weiteren technischen Details auch ohne erkannte Erweiterung aufklappbar. Nicht verfügbare Werte werden entsprechend bezeichnet. Der Link zum nativen Website-Zustand steht oben und nochmals am Seitenende.

![Systeminformationen und Speicher mit Sprungnavigation und getrennten Themenbereichen](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/release111-system-de.png)
