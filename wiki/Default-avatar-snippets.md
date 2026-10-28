# Set the default avatar with code

[Deutsch](Standard-Avatar-Snippets) · [Local avatars and administration conveniences](Administration-conveniences#a-local-default-avatar) · [Home](Home)

The `site_essentials_local_avatar_default_id` filter lets you set a local default image centrally in code. It appears for users without a personal profile image. **Local avatars must be enabled in Site Essentials and provided by Site Essentials.** The filter does not enable the module itself.

No code is needed for ordinary image selection: under **Site Essentials → Local avatars**, upload a default image or choose one from the Media Library.

## Prepare the image and ID

1. Upload a local JPEG, PNG, GIF, WebP, or AVIF image to the relevant website’s Media Library.
2. Find its image ID: open the image for editing from the media list view. In an address such as `post.php?post=123&action=edit`, `123` is the ID. Alternatively, use Site Essentials’ optional ID column.
3. Add and activate **one** of the following examples in your site functionality plugin or a PHP snippet plugin. Snippet plugins usually expect PHP without the opening `<?php`; in your own plugin, place it below the existing PHP opening tag.

No `wp-config.php` entry is needed. Do not edit Site Essentials itself: an update would overwrite that change.

## Option 1: Single site

Replace `123` with the desired image ID:

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $attachment_id ): int {
    return 123;
} );
```

Check the preview under **Local avatars → Default image**. A valid code override pauses image selection in the interface; the saved selection is retained.

## Option 2: One website in a multisite network

The snippet must load on the intended website, for example through a network-activated site functionality plugin. Replace `2` with its site ID and `123` with an image ID **from that website’s own Media Library**:

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $attachment_id ): int {
    if ( get_current_blog_id() !== 2 ) {
        return $attachment_id;
    }

    return 123;
} );
```

Find the site ID under **Network Admin → Sites**, for example in the editing address `site-info.php?id=2`. Other websites keep their existing selection.

## Option 3: Map several websites individually

A network-loaded snippet can supply a different local image ID for each website:

```php
add_filter( 'site_essentials_local_avatar_default_id', function ( int $attachment_id ): int {
    $images_by_site = array(
        2 => 123,
        5 => 456,
    );

    return $images_by_site[ get_current_blog_id() ] ?? $attachment_id;
} );
```

Site IDs are on the left; image IDs from each site’s own Media Library are on the right. Replace the example values. Unlisted sites keep their existing selection. An image ID is not automatically valid on other sites; this does not access the main site’s Media Library.

## Priority, fallback, and removal

- Personal profile images continue to take priority over the default image.
- The default image follows this order: **valid filter override → saved local selection → shipped SE image**.
- Only a positive integer ID for an available, public, locally served raster image is accepted. An invalid, deleted, or externally served image is not used as an override; the next available choice is used instead.
- The filter does not protect the media file from deletion or create another copy.
- Disable the snippet or return `0` to remove its override. The saved image can then be changed in the interface again, provided no other valid filter override exists.
- IDs belong to each website and are not portable between installations. Snippets are managed separately and are not included in SE preset exports.

## Quick check

Check the avatar display for a user without a personal image. Then confirm that another user’s personal image still takes priority. In multisite, also check an unlisted website. WordPress calls that explicitly request the default image use the effective default even when a personal image exists.

The filter is available since Site Essentials **1.1.0** and expects an integer attachment ID, not an image URL.
