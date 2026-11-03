# Alternative Beitragsziele nur Administratoren anzeigen

[English](Alternative-destination-snippet) · [Anleitung](Anleitung) · [Fragen zu Beitragszielen](FAQ-de#wie-funktionieren-alternative-beitragsziele)

Dieses optionale Snippet vereinfacht den Inhaltseditor für Redakteure und andere Benutzer ohne Administratorrolle. Es entfernt nur die Eingabebox **Alternative Beitragsziele** bei den gewählten Inhaltstypen. Vorhandene Ziele und Weiterleitungen bleiben wirksam; beim normalen Speichern ohne diese Box bleiben die gespeicherten Ziele erhalten. Dies ist eine Anzeigeanpassung, keine Berechtigungssperre.

## Im Snippet-Manager einrichten

1. Ein neues **PHP-Snippet** anlegen, beispielsweise „SE: Beitragsziele nur für Admins“.
2. Automatisch im **Adminbereich** ausführen lassen, nicht erst innerhalb eines späteren Hooks. Das Snippet registriert den passenden Hook selbst.
3. Den folgenden Code einfügen. Viele PHP-Snippet-Manager benötigen die öffnende Zeile `<?php` nicht oder ergänzen sie selbst. Deshalb fehlt sie im Beispiel. Falls dein Werkzeug sie verlangt, füge `<?php` als erste Zeile hinzu; beachte dessen Anleitung.
4. Die Liste `$post_types` anpassen, speichern und das Snippet aktivieren.

```php
add_action( 'add_meta_boxes', function ( $post_type, $post ) {

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $user = wp_get_current_user();

    if (
        in_array( 'administrator', (array) $user->roles, true )
        || ( is_multisite() && is_super_admin() )
    ) {
        return;
    }

    $post_types = array(
        'post',
        'page',
        // 'product',
        // 'projekt',
    );

    if ( ! in_array( $post_type, $post_types, true ) ) {
        return;
    }

    remove_meta_box(
        'se-alternative-target',
        $post_type,
        'normal'
    );

}, 100, 2 );
```

## Inhaltstypen auswählen

`post` steht für Beiträge, `page` für Seiten. Beide sind im Beispiel ausgewählt. Für WooCommerce-Produkte die Kommentarzeichen `//` vor `'product'` entfernen. `'projekt'` ist lediglich ein Beispiel: durch den tatsächlichen technischen Namen des eigenen Inhaltstyps ersetzen. Nicht aufgeführte Inhaltstypen bleiben unverändert.

Administratoren behalten die Box; in Multisite außerdem Super-Administratoren. Die Abfrage prüft ausdrücklich die Rolle `administrator`, nicht eine beliebige Verwaltungsberechtigung. Die Anpassung betrifft die native SE-Metabox im Classic Editor und im Block-Editor. Die Einstellungsseite und andere Darstellungen werden damit nicht ausgeblendet.

## Kurz prüfen und zurücknehmen

Als Redakteur einen betroffenen Inhalt öffnen: Die Box sollte fehlen. Normal speichern und eine bereits vorhandene Weiterleitung anschließend abgemeldet prüfen. Als Administrator muss die Box weiterhin erscheinen. Zum Rückgängigmachen genügt das Deaktivieren des Snippets; es werden keine Daten gelöscht.

Das Snippet wurde mit Site Essentials 1.1.1 auf einer Kundensite erfolgreich eingesetzt und vom Anwender bestätigt.
