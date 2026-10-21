# Falcon und Unbloater ersetzen

[English](Takeover) · [Startseite](Home-de)

**Einstellungen → Site Essentials → Import und Export → Einstellungen von Falcon und Unbloater prüfen** öffnen. Die Hilfe liest installierte unterstützte Quellen einmalig, auch gespeicherte Einstellungen inaktiver installierter Plugins. Sie führt deren Code nicht aus und richtet keine dauerhafte Synchronisierung ein.

1. Eine normale Sicherung bereithalten und bisher benötigte Quellfunktionen festhalten.
2. Erkannte Quellen und vorgeschlagene Entsprechungen prüfen.
3. Je Einstellung ausdrücklich entscheiden. Vorausgewählt ist **Aktuellen Wert beibehalten**; unterschiedliche Quellen oder bisherige Entscheidungen werden hervorgehoben.
4. Die vollständig geprüfte Auswahl bestätigen.
5. Site Essentials testen und die bisherigen Plugins anschließend selbst deaktivieren und entfernen.

![Konflikte ausdrücklich entscheiden](https://raw.githubusercontent.com/deckerweb/site-essentials/main/assets-github/screenshots/takeover-conflict-de.png)

Den aktuellen Wert behalten oder bewusst eine Quelle wählen. Bisherige Plugins und deren Einstellungen bleiben unangetastet.

Geprüfte Quellversionen: **Falcon 2.12.0** und **Unbloater 1.7.0**. Andere Versionen werden erkannt, aber nicht ungeprüft importiert. Die Hilfe übernimmt ausgewählte passende Verhaltensweisen, nicht jede Funktion beider Plugins. Relevante Unterschiede werden erklärt, etwa jQuery Migrate für angemeldete Benutzer oder die XML-RPC-Sperre.

Die vorherige Site-Essentials-Konfiguration bleibt für die Wiederherstellung verfügbar, bis ein späteres Speichern den Stand ersetzt. Quelleinstellungen und Quellplugins werden nicht gelöscht. In Multisite betrifft die Aktion die aktuelle Website; sie verteilt keine Einstellungen im Netzwerk. Für das Lesen netzwerkweiter Quelleinstellungen ist die passende Netzwerkberechtigung nötig.
