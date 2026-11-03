# Show alternative post destinations only to administrators

[Deutsch](Beitragsziel-Snippet) · [User guide](User-guide) · [Destination questions](FAQ#how-do-alternative-post-destinations-work)

This optional snippet simplifies the content editor for editors and other users without the administrator role. It removes only the **Alternative post destinations** input box for selected post types. Existing destinations and redirects continue to work; saving content normally without the box retains its saved destination. This is a display adjustment, not an authorization restriction.

## Set up in Advanced Scripts

1. Create a new **PHP snippet**, for example “SE: Destinations for admins only”.
2. Configure it to run automatically in the **admin area**, rather than inside a later hook. The snippet registers its own hook.
3. Paste the following code **without `<?php`**.
4. Adjust `$post_types`, save and activate the snippet.

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

## Choose post types

`post` means posts and `page` means pages; both are selected in the example. For WooCommerce products, remove `//` before `'product'`. `'projekt'` is only an example: replace it with the actual technical name of your custom post type. Types not listed are unaffected.

Administrators retain the box, as do Super Admins on multisite. The check explicitly uses the `administrator` role rather than a general management capability. It targets the native SE meta box in both the Classic Editor and the block editor. It does not hide the settings page or other displays.

## Check and undo

Open affected content as an editor: the box should be absent. Save normally, then check an existing redirect while logged out. The box should remain visible to an administrator. To undo the adjustment, deactivate the snippet; it deletes no data.

The snippet was successfully used on a client site with Site Essentials 1.1.1 and confirmed by the user.
