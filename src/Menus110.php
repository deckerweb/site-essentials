<?php
/** Focused optional conveniences for classic WordPress menus. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Preserves menu data while allowing explicit copying and login-based visibility. */
final class Menus110 {
 /** Whether the current setup offers classic menu management, independent of the current user's access policy.
  * @return bool Theme support or an explicitly restored native editor.
  */
 public static function available(): bool {
  $available = current_theme_supports( 'menus' ) || current_theme_supports( 'widgets' ) || (bool) get_registered_nav_menus();
  foreach ( $GLOBALS['submenu']['themes.php'] ?? array() as $item ) { if ( 'nav-menus.php' === ( $item[2] ?? '' ) ) { $available = true; break; } }
  return (bool) apply_filters( 'site_essentials_classic_menus_available', $available );
 }
 /** This website's menu-item visibility metadata. */
 const META = '_site_essentials_menu_visibility';
 /** Register native menu hooks and a protected copy action. @return void */
 public function boot(): void {
  add_filter( 'wp_nav_menu_objects', array( $this, 'visible' ), 20 );
  add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'field' ), 10, 2 );
  add_action( 'wp_update_nav_menu_item', array( $this, 'save' ), 10, 2 );
  add_action( 'admin_post_site_essentials_menu_duplicate', array( $this, 'duplicate_action' ) );
 }
 /** Decide whether the current principal may edit classic menus here. @return bool */
 public static function allowed(): bool { return ! is_network_admin() && current_user_can( 'edit_theme_options' ) && false !== Access::decision( 'menus' ); }
 /** Remove hidden items and descendants without changing stored links or order.
  * @param array $items Prepared native menu objects.
  * @return array Visible objects, preserving keys.
  */
 public function visible( array $items ): array {
  if ( ! Settings::get( 'menu_visibility' ) || is_admin() ) { return $items; }
  $hidden = array(); $logged = is_user_logged_in();
  foreach ( $items as $item ) {
   $rule = get_post_meta( $item->ID, self::META, true );
   if ( ( 'logged_in' === $rule && ! $logged ) || ( 'logged_out' === $rule && $logged ) ) { $hidden[ $item->ID ] = true; }
  }
  do {
   $changed = false;
   foreach ( $items as $item ) { if ( ! isset( $hidden[ $item->ID ] ) && isset( $hidden[ (int) $item->menu_item_parent ] ) ) { $hidden[ $item->ID ] = true; $changed = true; } }
  } while ( $changed );
  return array_filter( $items, static fn( $item ) => ! isset( $hidden[ $item->ID ] ) );
 }
 /** Render a per-item visibility selection in the native menu form.
  * @param int $id Menu item ID.
  * @param object $item Native menu object, not mutated.
  * @return void
  */
 public function field( int $id, $item ): void {
  if ( ! Settings::get( 'menu_visibility' ) || ! self::allowed() ) { return; }
  echo '<p class="description description-wide"><label for="se-menu-visibility-' . esc_attr( (string) $id ) . '">' . esc_html__( 'Site Essentials: visibility', 'site-essentials' ) . '<br><select id="se-menu-visibility-' . esc_attr( (string) $id ) . '" name="se_menu_visibility[' . esc_attr( (string) $id ) . ']">';
  foreach ( array( 'always' => __( 'Always visible', 'site-essentials' ), 'logged_in' => __( 'Logged-in visitors', 'site-essentials' ), 'logged_out' => __( 'Logged-out visitors', 'site-essentials' ) ) as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '"' . selected( get_post_meta( $id, self::META, true ) ?: 'always', $value, false ) . '>' . esc_html( $label ) . '</option>'; }
  echo '</select></label><br>' . esc_html__( 'Visibility is not page access protection. Hidden parents also hide their submenu. Page caches must distinguish login status.', 'site-essentials' ) . '</p>';
 }
 /** Save only explicitly submitted fields through an authorized native menu POST.
  * @param int $menu Menu term ID.
  * @param int $id Menu item ID.
  * @return void
  */
 public function save( int $menu, int $id ): void {
  if ( ! Settings::get( 'menu_visibility' ) || ! self::allowed() || 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) { return; }
  $nonce = isset( $_POST['update-nav-menu-nonce'] ) && is_string( $_POST['update-nav-menu-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['update-nav-menu-nonce'] ) ) : '';
  $values = isset( $_POST['se_menu_visibility'] ) && is_array( $_POST['se_menu_visibility'] ) ? map_deep( wp_unslash( $_POST['se_menu_visibility'] ), 'sanitize_text_field' ) : array();
  if ( ! wp_verify_nonce( $nonce, 'update-nav_menu' ) || ! isset( $values[ $id ] ) || ! is_string( $values[ $id ] ) || 'nav_menu_item' !== get_post_type( $id ) || ! is_object_in_term( $id, 'nav_menu', $menu ) ) { return; }
  if ( in_array( $values[ $id ], array( 'always', 'logged_in', 'logged_out' ), true ) ) { update_post_meta( $id, self::META, $values[ $id ] ); }
 }
 /** Copy a bounded menu into a new menu without changing assignments.
  * @param int $source Source classic menu ID.
  * @param string $name Explicit name for the new menu.
  * @return int|\WP_Error New menu ID or an error; failed copies are rolled back.
  */
 public static function duplicate( int $source, string $name ) {
  if ( ! Settings::get( 'menu_duplicate' ) || ! self::allowed() || ! current_user_can( 'manage_options' ) ) { return new \WP_Error( 'denied', __( 'Permission denied.', 'site-essentials' ) ); }
  $object = wp_get_nav_menu_object( $source ); $name = sanitize_text_field( $name );
  if ( ! $object || '' === trim( $name ) || strlen( $name ) > 200 ) { return new \WP_Error( 'invalid', __( 'Select a classic menu and a distinct name of up to 200 bytes.', 'site-essentials' ) ); }
  $items = wp_get_nav_menu_items( $source, array( 'post_status' => 'any', 'numberposts' => 501 ) );
  if ( ! is_array( $items ) || count( $items ) > 500 ) { return new \WP_Error( 'limit', __( 'This menu cannot be copied within the safety limit of 500 items.', 'site-essentials' ) ); }
  $new = wp_create_nav_menu( $name ); if ( is_wp_error( $new ) ) { return $new; }
  $map = array();
  foreach ( $items as $item ) {
   $args = array( 'menu-item-title' => $item->title, 'menu-item-url' => $item->url, 'menu-item-description' => $item->description, 'menu-item-attr-title' => $item->attr_title, 'menu-item-target' => $item->target, 'menu-item-classes' => implode( ' ', $item->classes ), 'menu-item-xfn' => $item->xfn, 'menu-item-object-id' => $item->object_id, 'menu-item-object' => $item->object, 'menu-item-type' => $item->type, 'menu-item-position' => $item->menu_order, 'menu-item-status' => $item->post_status );
   $copy = wp_update_nav_menu_item( $new, 0, $args );
   if ( is_wp_error( $copy ) || ! $copy ) { wp_delete_nav_menu( $new ); return new \WP_Error( 'copy', __( 'The menu could not be copied. The incomplete copy was removed.', 'site-essentials' ) ); }
   $map[ $item->ID ] = $copy;
   $visibility = get_post_meta( $item->ID, self::META, true );
   if ( in_array( $visibility, array( 'always', 'logged_in', 'logged_out' ), true ) ) { update_post_meta( $copy, self::META, $visibility ); }
  }
  foreach ( $items as $item ) {
   $parent = (int) $item->menu_item_parent;
   if ( $parent && ! isset( $map[ $parent ] ) ) { wp_delete_nav_menu( $new ); return new \WP_Error( 'parent', __( 'A menu parent is missing. No copy was retained.', 'site-essentials' ) ); }
   if ( $parent && ! update_post_meta( $map[ $item->ID ], '_menu_item_menu_item_parent', (string) $map[ $parent ] ) ) { wp_delete_nav_menu( $new ); return new \WP_Error( 'parent', __( 'A menu parent is missing. No copy was retained.', 'site-essentials' ) ); }
  }
  return $new;
 }
 /** Authenticate a copy request and return to the native editor for review. @return void */
 public function duplicate_action(): void {
  if ( ! self::allowed() || ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( 'site_essentials_menu_duplicate' );
  $source = isset( $_POST['source_menu'] ) && is_scalar( $_POST['source_menu'] ) ? absint( $_POST['source_menu'] ) : 0;
  $name = isset( $_POST['copy_name'] ) && is_string( $_POST['copy_name'] ) ? sanitize_text_field( wp_unslash( $_POST['copy_name'] ) ) : '';
  $result = self::duplicate( $source, $name );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'back_link' => true ) ); }
  wp_safe_redirect( admin_url( 'nav-menus.php?menu=' . $result ) ); exit;
 }
 /** Render a separate protected menu-copy form outside the settings form. @return void */
 public static function panel(): void {
  if ( ! Settings::get( 'menu_duplicate' ) || ! current_user_can( 'manage_options' ) || ! self::allowed() ) { return; }
  echo '<div class="se-menu-copy-tool"><h4>' . esc_html__( 'Duplicate a classic menu', 'site-essentials' ) . '</h4><p>' . esc_html__( 'Creates a separate menu without replacing any menu location. Core link properties and Site Essentials visibility are copied; other plugin metadata is not copied. Review the result before assigning a location.', 'site-essentials' ) . '</p>';
  echo '<p><label for="se-source-menu">' . esc_html__( 'Source menu', 'site-essentials' ) . '</label> <select id="se-source-menu" name="source_menu" form="se-menu-copy-form">';
  foreach ( wp_get_nav_menus() as $menu ) { echo '<option value="' . esc_attr( (string) $menu->term_id ) . '">' . esc_html( $menu->name ) . '</option>'; }
  echo '</select></p><p><label for="se-copy-name">' . esc_html__( 'Name of the copy', 'site-essentials' ) . '</label> <input id="se-copy-name" name="copy_name" form="se-menu-copy-form" type="text" required maxlength="80"></p><button class="button" type="submit" form="se-menu-copy-form">' . esc_html__( 'Duplicate menu', 'site-essentials' ) . '</button></div>';
 }
}
