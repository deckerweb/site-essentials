<?php
/** Optional fixed, reversible classic-menu interface policies. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Changes available controls, never stored menu values or frontend link attributes. */
final class MenuInterface {
 /** Native defaults for users who have never selected advanced properties. */
 private const DEFAULT_HIDDEN = array( 'link-target', 'css-classes', 'xfn', 'description', 'title-attribute' );
 /** Prevent recursion while preserving metadata through the native API. */
 private bool $writing = false;
 /** Register narrow, lazy hooks. @return void */
 public function boot(): void {
  add_filter( 'get_user_option_managenav-menuscolumnshidden', array( $this, 'hidden' ), 100, 3 );
  add_filter( 'update_user_metadata', array( $this, 'preserve_preferences' ), 100, 5 );
  add_filter( 'register_post_type_args', array( $this, 'post_type' ), 100, 2 );
  add_filter( 'register_taxonomy_args', array( $this, 'taxonomy' ), 100, 3 );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
 }
 /** Map only selected policies to their native visibility values. @return array<string,bool> */
 public function rules(): array {
  $selected = Settings::get( 'menu_fields' ); $rules = array();
  foreach ( array( 'link-target', 'css-classes', 'xfn' ) as $key ) { if ( in_array( $key, $selected, true ) ) { $rules[ $key ] = true; } }
  foreach ( array( 'title-attribute', 'description' ) as $key ) { if ( in_array( $key, $selected, true ) ) { $rules[ $key ] = false; } }
  return $rules;
 }
 /** Apply effective field visibility without updating stored user preferences.
  * @param mixed $hidden Stored preference or false when unset.
  * @param string $option Native option name.
  * @param \WP_User $user Preference owner.
  * @return mixed
  */
 public function hidden( $hidden, string $option, \WP_User $user ) {
  $rules = $this->rules();
  if ( ! $rules || is_network_admin() ) { return $hidden; }
  $value = is_array( $hidden ) ? array_values( array_filter( $hidden, 'is_string' ) ) : self::DEFAULT_HIDDEN;
  foreach ( $rules as $key => $show ) { $value = array_values( array_diff( $value, array( $key ) ) ); if ( ! $show ) { $value[] = $key; } }
  return array_values( array_unique( $value ) );
 }
 /** Preserve underlying forced choices when users toggle other native screen options.
  * @param mixed $check Earlier short-circuit result.
  * @param int $user_id Preference owner.
  * @param string $key Metadata key.
  * @param mixed $value Submitted native hidden-column list.
  * @param mixed $previous Optional previous value.
  * @return mixed
  */
 public function preserve_preferences( $check, int $user_id, string $key, $value, $previous ) {
  if ( null !== $check || $this->writing || 'managenav-menuscolumnshidden' !== $key || is_network_admin() || ! wp_doing_ajax() || 'hidden-columns' !== ( $_REQUEST['action'] ?? '' ) || 'nav-menus' !== ( $_POST['page'] ?? '' ) || get_current_user_id() !== $user_id || ! is_array( $value ) ) { return $check; }
  $rules = $this->rules(); if ( ! $rules || count( array_filter( $value, 'is_string' ) ) !== count( $value ) ) { return $check; }
  $old = get_user_meta( $user_id, $key, true ); $base = is_array( $old ) ? array_values( array_filter( $old, 'is_string' ) ) : self::DEFAULT_HIDDEN;
  $clean = $value;
  foreach ( $rules as $field => $show ) { $clean = array_values( array_diff( $clean, array( $field ) ) ); if ( in_array( $field, $base, true ) ) { $clean[] = $field; } }
  $clean = array_values( array_unique( $clean ) );
  if ( $clean === $value ) { return $check; }
  $this->writing = true;
  try { $result = update_user_meta( $user_id, $key, $clean ); return $result || get_user_meta( $user_id, $key, true ) === $clean; }
  finally { $this->writing = false; }
 }
 /** Resolve verified Bricks identifiers without depending on its classes.
  * @return array<string,string>
  */
 private function bricks_names(): array { return array(
  'post' => defined( 'BRICKS_DB_TEMPLATE_SLUG' ) && is_string( BRICKS_DB_TEMPLATE_SLUG ) ? BRICKS_DB_TEMPLATE_SLUG : 'bricks_template',
  'tag' => defined( 'BRICKS_DB_TEMPLATE_TAX_TAG' ) && is_string( BRICKS_DB_TEMPLATE_TAX_TAG ) ? BRICKS_DB_TEMPLATE_TAX_TAG : 'template_tag',
  'bundle' => defined( 'BRICKS_DB_TEMPLATE_TAX_BUNDLE' ) && is_string( BRICKS_DB_TEMPLATE_TAX_BUNDLE ) ? BRICKS_DB_TEMPLATE_TAX_BUNDLE : 'template_bundle',
 ); }
 /** Remove only the Bricks template post type from native navigation offers.
  * @param array $args Native registration arguments.
  * @param string $name Post type name.
  * @return array
  */
 public function post_type( array $args, string $name ): array {
  if ( Settings::get( 'menu_bricks' ) && $name === $this->bricks_names()['post'] ) { $args['show_in_nav_menus'] = false; }
  return $args;
 }
 /** Remove only verified template taxonomies associated with the Bricks post type.
  * @param array $args Taxonomy arguments.
  * @param string $name Taxonomy name.
  * @param array $types Associated post types.
  * @return array
  */
 public function taxonomy( array $args, string $name, array $types ): array {
  $names = $this->bricks_names();
  if ( Settings::get( 'menu_bricks' ) && in_array( $names['post'], $types, true ) && in_array( $name, array( $names['tag'], $names['bundle'] ), true ) ) { $args['show_in_nav_menus'] = false; }
  return $args;
 }
 /** Lock selected screen-option controls, retaining all native menu inputs.
  * @param string $hook Native screen hook.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( 'nav-menus.php' !== ( $GLOBALS['pagenow'] ?? '' ) || is_network_admin() || ! $this->rules() ) { return; }
  wp_enqueue_script( 'site-essentials-menu-interface', plugins_url( 'assets/menu-interface.js', SITE_ESSENTIALS_FILE ), array( 'nav-menu' ), SITE_ESSENTIALS_VERSION, true );
  wp_localize_script( 'site-essentials-menu-interface', 'siteEssentialsMenuInterface', array( 'rules' => $this->rules(), 'fixed' => __( 'Fixed website setting', 'site-essentials' ), 'note' => __( 'Selected menu properties are fixed by Site Essentials. Your saved personal preferences and existing menu values are retained.', 'site-essentials' ) ) );
 }
}
