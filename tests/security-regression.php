<?php
/** Destructive integration checks: disposable local WordPress only, never customer data. */
if ( PHP_SAPI !== 'cli' || getenv( 'SE_DISPOSABLE_TESTS' ) !== 'yes' || empty( $argv[1] ) ) { exit( "Set SE_DISPOSABLE_TESTS=yes and pass the path of a disposable WordPress installation.\n" ); }
define( 'WP_ADMIN', true ); $_SERVER['HTTP_HOST'] = getenv( 'SE_TEST_HOST' ) ?: '127.0.0.1';
require rtrim( $argv[1], '/' ) . '/wp-load.php'; require_once ABSPATH . 'wp-admin/includes/admin.php';
if ( wp_get_environment_type() !== 'local' || ! class_exists( '\Deckerweb\SiteEssentials\WriteLock' ) ) { exit( "Requires local test environment and active Site Essentials candidate.\n" ); }
use Deckerweb\SiteEssentials\{Avatars,DefaultAvatar,WriteLock,Settings};
$n = 0; $ids = array(); $users = array(); $old = get_option( Settings::OPTION ); $old_previous = get_option( Settings::PREVIOUS, false ); $admin = get_current_user_id();
function se_security_assert( $value, string $label ): void { global $n; if ( ! $value ) { throw new RuntimeException( $label ); } ++$n; echo 'PASS ' . $label . "\n"; }
try {
 wp_set_current_user( 1 );
 se_security_assert( WriteLock::acquire(), 'exclusive writer acquired' );
 se_security_assert( WriteLock::acquire(), 'same request is reentrant' );
 $a = new Avatars();
 $user = wp_insert_user( array( 'user_login' => 'se-test-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber' ) );
 if ( is_wp_error( $user ) ) { throw new RuntimeException( 'Test user setup failed' ); } $users[] = $user;
 $other = wp_insert_user( array( 'user_login' => 'se-test-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber' ) );
 if ( is_wp_error( $other ) ) { throw new RuntimeException( 'Second test user setup failed' ); } $users[] = $other;
 wp_set_current_user( $user );
 se_security_assert( ! current_user_can( 'upload_files' ) && $a->can_edit( $user ), 'subscriber may manage own avatar without media rights' );
 se_security_assert( ! $a->can_edit( $other ), 'subscriber cannot edit another profile' );
 se_security_assert( true === Avatars::reserve_upload(), 'first self-service upload reserved' );
 se_security_assert( true === Avatars::reserve_upload() && true === Avatars::reserve_upload(), 'second and third upload reserved' );
 $blocked = Avatars::reserve_upload(); se_security_assert( is_wp_error( $blocked ) && 'avatar_rate' === $blocked->get_error_code(), 'fourth upload blocked' );
 $rate = '_site_essentials_avatar_uploads_' . get_current_blog_id(); update_user_meta( $user, $rate, array( time() - HOUR_IN_SECONDS - 1 ) );
 se_security_assert( true === Avatars::reserve_upload(), 'old rate slots expire' );
 $sizes = Avatars::upload_sizes( array( 'huge' => array( 'width' => 4096, 'height' => 4096 ) ) );
 se_security_assert( count( $sizes ) === 2 && max( array_column( $sizes, 'width' ) ) <= 192, 'self-service derivatives bounded' );
 for ( $i = 0; $i < 20; ++$i ) { $id = wp_insert_post( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_title' => 'SE disposable quota fixture' ) ); $ids[] = $id; update_post_meta( $id, '_site_essentials_avatar_uploader', $user ); }
 wp_update_post( array( 'ID' => $ids[0], 'post_status' => 'trash' ) );
 $blocked = Avatars::reserve_upload(); se_security_assert( is_wp_error( $blocked ) && 'avatar_quota' === $blocked->get_error_code(), 'retained-image quota includes trash' );
 wp_set_current_user( 1 ); se_security_assert( true === Avatars::reserve_upload(), 'native media permission remains available' );
 $native = array( 'large' => array( 'width' => 1024, 'height' => 1024 ) ); se_security_assert( Avatars::upload_sizes( $native ) === $native, 'native uploader derivatives unchanged' );
 $bits = wp_upload_bits( 'se-security-' . wp_generate_uuid4() . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/a9sAAAAASUVORK5CYII=' ) );
 if ( ! empty( $bits['error'] ) ) { throw new RuntimeException( 'Image fixture setup failed' ); }
 $image = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_status' => 'inherit', 'post_title' => 'SE disposable avatar' ), $bits['file'] ); $ids[] = $image;
 wp_update_attachment_metadata( $image, array( 'width' => 1, 'height' => 1, 'file' => _wp_relative_upload_path( $bits['file'] ) ) );
 se_security_assert( '' !== $a->image( $image ) && '' !== DefaultAvatar::image( $image ), 'public image accepted by both validators' );
 foreach ( array( 'trash', 'private', 'draft', 'future' ) as $status ) { $GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_status' => $status ), array( 'ID' => $image ) ); clean_post_cache( $image ); se_security_assert( '' === $a->image( $image ) && '' === DefaultAvatar::image( $image ), 'both validators reject ' . $status ); }
 wp_update_post( array( 'ID' => $image, 'post_status' => 'inherit' ) );
 $private = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'private', 'post_title' => 'SE disposable private parent' ) ); $ids[] = $private;
 wp_update_post( array( 'ID' => $image, 'post_parent' => $private ) ); se_security_assert( '' === $a->image( $image ), 'private parent is never exposed' );
 wp_set_current_user( 0 ); se_security_assert( ! $a->can_edit( $user ), 'anonymous assignment denied' );
 wp_set_current_user( 1 );
 $current = Settings::local(); $next = $current; $next['user_ids'] = ! $current['user_ids'];
 $reject = static fn( $value, $old_value ) => $old_value;
 add_filter( 'pre_update_option_' . Settings::OPTION, $reject, 10, 2 );
 se_security_assert( ! Settings::save( $next ) && Settings::local() === $current, 'failed configuration write retains current settings' );
 remove_filter( 'pre_update_option_' . Settings::OPTION, $reject, 10 );
 se_security_assert( Settings::save( $next ), 'normal configuration write succeeds after failure' );
 add_filter( 'pre_update_option_' . Settings::PREVIOUS, $reject, 10, 2 );
 se_security_assert( ! Settings::restore() && Settings::local() === $next, 'failed snapshot rotation rolls back only current locked write' );
 remove_filter( 'pre_update_option_' . Settings::PREVIOUS, $reject, 10 );
 echo 'TOTAL ' . $n . "\n";
} finally {
 wp_set_current_user( 1 );
 foreach ( $ids as $id ) { get_post_type( $id ) === 'attachment' ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true ); }
 foreach ( $users as $id ) { if ( is_multisite() ) { require_once ABSPATH . 'wp-admin/includes/ms.php'; wpmu_delete_user( $id ); } else { wp_delete_user( $id ); } }
 false === $old ? delete_option( Settings::OPTION ) : update_option( Settings::OPTION, $old, false );
 false === $old_previous ? delete_option( Settings::PREVIOUS ) : update_option( Settings::PREVIOUS, $old_previous, false );
 WriteLock::release(); wp_set_current_user( $admin );
}
