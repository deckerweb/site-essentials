# Standard-Avatar per Code vorgeben

[English](Default-avatar-snippets) · [Lokale Avatare und Verwaltungshilfen](Verwaltungshilfen#ein-lokaler-standard-avatar) · [Startseite](Home-de)

Mit dem Filter `site_essentials_local_avatar_default_id` lässt sich das lokale Standardbild zentral per Code vorgeben. Es erscheint bei Benutzern ohne eigenes Profilbild. Voraussetzung: **Lokale Avatare in Site Essentials sind aktiviert und werden von Site Essentials bereitgestellt.** Der Filter aktiviert das Modul nicht selbst.

Für die normale Bildauswahl ist kein Code nötig: Unter **Site Essentials → Lokale Avatare** kann das Standardbild auch hochgeladen oder aus der Mediathek gewählt werden.

## Vorbereitung: Bild und ID

1. Ein lokales JPEG-, PNG-, GIF-, WebP- oder AVIF-Bild in die Mediathek der betreffenden Website hochladen.
2. Die Bild-ID ermitteln: Das Bild über die Listenansicht zum Bearbeiten öffnen. In der Adresse `post.php?post=123&action=edit` ist `123` die ID. Alternativ die optionale ID-Spalte von Site Essentials verwenden.
3. **Eine** der folgenden Varianten im eigenen Site-Functionality-Plugin oder einem PHP-Snippet-Plugin einfügen und aktivieren. Bei Snippet-Plugins den PHP-Code üblicherweise ohne öffnendes `<?php` einfügen; im eigenen Plugin gehört er unter dessen vorhandene PHP-Eröffnung.

Kein Eintrag in der `wp-config.php` nötig. Den Code nicht in Site Essentials selbst ändern, damit ein Update ihn nicht überschreibt.

## Variante 1: Single-Site

`123` durch die ID des gewünschten Bildes ersetzen:

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $attachment_id ): int {
    return 123;
} );
```

Danach bei **Lokale Avatare → Standardbild** die Vorschau prüfen. Eine gültige Code-Vorgabe pausiert die Bildauswahl in der Oberfläche; die bisher gespeicherte Auswahl bleibt erhalten.

## Variante 2: Nur eine Website in einer Multisite

Das Snippet muss auf der gewünschten Website geladen werden, beispielsweise über ein netzwerkweit aktiviertes Site-Functionality-Plugin. `2` durch deren Website-ID und `123` durch eine Bild-ID **aus ihrer eigenen Mediathek** ersetzen:

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $attachment_id ): int {
    if ( get_current_blog_id() !== 2 ) {
        return $attachment_id;
    }

    return 123;
} );
```

Die Website-ID steht in der Netzwerkverwaltung unter **Websites**, etwa in der Bearbeitungsadresse `site-info.php?id=2`. Andere Websites behalten ihre bisherige Auswahl.

## Variante 3: Mehrere Websites gezielt zuordnen

Ein netzwerkweit geladenes Snippet kann für jede Website eine eigene lokale Bild-ID vorgeben:

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $attachment_id ): int {
    $images_by_site = array(
        2 => 123,
        5 => 456,
    );

    return $images_by_site[ get_current_blog_id() ] ?? $attachment_id;
} );
```

Links stehen die Website-IDs, rechts die Bild-IDs aus der jeweiligen Mediathek. Die Beispielwerte ersetzen. Nicht aufgeführte Websites behalten ihre bisherige Auswahl. Eine Bild-ID ist nicht automatisch auf anderen Websites gültig; dies greift nicht auf die Mediathek der Hauptwebsite zu.

## Vorrang, Rückfall und Abschalten

- Persönliche Profilbilder haben weiterhin Vorrang vor dem Standardbild.
- Für das Standardbild gilt: **gültige Filtervorgabe → gespeicherte lokale Auswahl → mitgeliefertes SE-Bild**.
- Akzeptiert wird nur eine positive ganzzahlige ID eines verfügbaren, öffentlichen, lokal bereitgestellten Rasterbildes. Ein ungültiges, gelöschtes oder extern ausgeliefertes Bild wird nicht als Vorgabe verwendet; dann greift die nächste verfügbare Auswahl.
- Der Filter schützt die Mediendatei nicht vor dem Löschen und legt keine zusätzliche Kopie an.
- Das Snippet deaktivieren oder `0` zurückgeben, um die Code-Vorgabe aufzuheben. Die gespeicherte Bildauswahl kann dann wieder über die Oberfläche geändert werden, sofern keine andere gültige Filtervorgabe besteht.
- IDs gelten je Website und sind nicht zwischen Installationen übertragbar. Die Snippets werden separat verwaltet und sind nicht Bestandteil von SE-Preset-Exporten.

## Kurz prüfen

Mit einem Benutzer ohne eigenes Profilbild die Avatar-Anzeige kontrollieren. Anschließend bei einem Benutzer mit eigenem Bild prüfen, dass dieses erhalten bleibt. In Multisite zusätzlich eine nicht zugeordnete Website kontrollieren. WordPress-Aufrufe, die ausdrücklich das Standardbild anfordern, verwenden das wirksame Standardbild auch bei vorhandenem Profilbild.

Der Filter ist seit Site Essentials **1.1.0** verfügbar und erwartet eine Attachment-ID als Integer, keine Bild-URL.
