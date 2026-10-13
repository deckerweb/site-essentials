<?php
/** Focused, site-local appearance access policies. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Personal exceptions are stored separately from all portable settings and presets. */
final class Access {
 /** Local-only option; never included in diagnostics, profiles or exports. */
 public const OPTION = 'site_essentials_access_users';
 /** Bounded read cache, validated against current site and raw option on each read. */
 private static array $cache = array();
 /** Explicit native classic-menu AJAX handlers. */
 private const MENU_AJAX = array( 'add-menu-item', 'menu-get-metabox', 'menu-locations-save', 'menu-quick-search' );
 /** Explicit native widget AJAX handlers. */
 private const WIDGET_AJAX = array( 'widgets-order', 'save-widget', 'update-widget', 'delete-inactive-widgets' );
 /** Register policy gates; never write role capabilities. @return void */
 public function boot(): void {
  add_filter( 'map_meta_cap', array( $this, 'caps' ), PHP_INT_MAX, 4 );
  add_action( 'load-nav-menus.php', array( $this, 'menu_access' ) );
  add_action( 'admin_init', array( $this, 'menu_objects' ), 1 );
  add_action( 'admin_menu', array( $this, 'delegated_menu' ), PHP_INT_MAX );
  add_action( 'admin_init', array( $this, 'ajax_guard' ), 0 );
  add_filter( 'rest_request_before_callbacks', array( $this, 'rest_guard' ), 10, 3 );
  add_filter( 'customize_dynamic_setting_args', array( $this, 'dynamic_setting' ), PHP_INT_MAX, 2 );
  add_action( 'customize_register', array( $this, 'customizer_settings' ), PHP_INT_MAX );
 }
 /** Evaluate a restriction; null means keep native permissions.
  * @param string $area widgets, customizer or menus.
  * @param int $user_id Optional principal, current user by default.
  * @return bool|null
  */
 public static function decision( string $area, int $user_id = 0 ): ?bool {
  if ( ! in_array( $area, array( 'widgets', 'customizer', 'menus' ), true ) ) { return false; }
  $mode = (int) Settings::get( 'access_' . $area . '_mode' );
  if ( 'menus' !== $area && Settings::get( $area . '_admin' ) ) { $mode = 1; }
  if ( 0 === $mode ) { return null; }
  if ( 1 === $mode ) { return false; }
  $id = $user_id ?: get_current_user_id(); $user = get_userdata( $id );
  if ( ! $user || ! $user->exists() || ( is_multisite() && ! is_user_member_of_blog( $id ) && ! is_super_admin( $id ) ) ) { return false; }
  $personal = self::exceptions()[ $area ] ?? array();
  if ( in_array( $id, $personal['deny'] ?? array(), true ) ) { return false; }
  if ( in_array( $id, $personal['allow'] ?? array(), true ) ) { return true; }
  if ( is_multisite() && is_super_admin( $id ) ) { return true; }
  return (bool) array_intersect( $user->roles, Settings::get( 'access_' . $area . '_roles' ) );
 }
 /** Read bounded valid local IDs only; no user data is exposed through the runtime.
  * @return array
  */
 public static function exceptions(): array {
  $raw = get_option( self::OPTION, array() ); $site = get_current_blog_id();
  if ( self::$cache && self::$cache['site'] === $site && self::$cache['raw'] === $raw ) { return self::$cache['value']; }
  $result = array();
  foreach ( array( 'widgets', 'customizer', 'menus' ) as $area ) {
   foreach ( array( 'allow', 'deny' ) as $kind ) {
    $values = is_array( $raw ) && is_array( $raw[ $area ][ $kind ] ?? null ) ? array_slice( $raw[ $area ][ $kind ], 0, 100 ) : array();
    $result[ $area ][ $kind ] = array_values( array_unique( array_map( 'intval', array_filter( $values, static fn( $id ): bool => ( is_int( $id ) && $id > 0 ) || ( is_string( $id ) && ctype_digit( $id ) && (int) $id > 0 ) ) ) ) );
   }
  }
  self::$cache = array( 'site' => $site, 'raw' => $raw, 'value' => $result );
  return $result;
 }
 /** Hash local exception state for stale-form protection. @return string */
 public static function revision(): string { return hash( 'sha256', wp_json_encode( get_option( self::OPTION, false ) ) ); }
 /** Validate site members before any exception write.
  * @param array $input Allow/deny lists for one area.
  * @return array|\WP_Error
  */
 public static function validate_users( array $input ) {
  if ( array_diff( array_keys( $input ), array( 'allow', 'deny' ) ) ) { return new \WP_Error( 'invalid', __( 'Use valid personal access exceptions.', 'site-essentials' ) ); }
  $clean = array( 'allow' => array(), 'deny' => array() );
  foreach ( $clean as $kind => $unused ) {
   $values = $input[ $kind ] ?? array();
   if ( ! is_array( $values ) || count( $values ) > 101 ) { return new \WP_Error( 'invalid', __( 'Use valid personal access exceptions.', 'site-essentials' ) ); }
   $values = array_values( array_filter( $values, static fn( $id ): bool => '' !== $id ) );
   if ( count( $values ) > 100 ) { return new \WP_Error( 'invalid', __( 'Use valid personal access exceptions.', 'site-essentials' ) ); }
   foreach ( $values as $id ) {
    if ( ! ( is_int( $id ) || ( is_string( $id ) && ctype_digit( $id ) && strlen( $id ) < 19 ) ) || (int) $id < 1 || ! get_userdata( (int) $id ) || ( is_multisite() && ! is_user_member_of_blog( (int) $id ) ) ) { return new \WP_Error( 'invalid', __( 'Choose existing users of this site.', 'site-essentials' ) ); }
    $clean[ $kind ][] = (int) $id;
   }
   $clean[ $kind ] = array_values( array_unique( $clean[ $kind ] ) );
  }
  if ( array_intersect( $clean['allow'], $clean['deny'] ) ) { return new \WP_Error( 'invalid', __( 'A user cannot be both allowed and blocked in the same area.', 'site-essentials' ) ); }
  return $clean;
 }
 /** Resolve server-controlled native scripts, never a submitted page/referrer parameter.
  * @return string
  */
 private function script(): string {
  $path = $_SERVER['SCRIPT_FILENAME'] ?? '';
  if ( ! is_string( $path ) ) { return ''; }
  foreach ( array( 'nav-menus.php', 'admin-ajax.php', 'widgets.php', 'customize.php' ) as $name ) {
   if ( realpath( $path ) === realpath( ABSPATH . 'wp-admin/' . $name ) ) { return $name; }
  }
  return '';
 }
 /** Find the relevant native AJAX family; unknown/plugin actions never receive grants.
  * @return string
  */
 private function ajax_area(): string {
  if ( 'admin-ajax.php' !== $this->script() || ! wp_doing_ajax() ) { return ''; }
  $action = $_REQUEST['action'] ?? '';
  if ( ! is_string( $action ) ) { return ''; }
  if ( in_array( $action, self::MENU_AJAX, true ) ) { return 'menus'; }
  if ( in_array( $action, self::WIDGET_AJAX, true ) ) { return 'widgets'; }
  return str_starts_with( $action, 'customize_' ) ? 'customizer' : '';
 }
 /** Narrow request-scoped capability mapping; existing stronger denials are retained.
  * @param array $caps Native required primitives.
  * @param string $cap Requested capability.
  * @param int $user_id Principal.
  * @param array $args Object arguments.
  * @return array
  */
 public function caps( array $caps, string $cap, int $user_id, array $args ): array {
  if ( is_network_admin() ) { return $caps; }
  foreach ( array( 'widgets', 'menus' ) as $area ) {
   if ( 'site_essentials_' . $area === $cap ) { return false === self::decision( $area, $user_id ) ? array( 'do_not_allow' ) : array( 'read' ); }
  }
  if ( 'customize' === $cap && false === self::decision( 'customizer', $user_id ) ) { return array( 'do_not_allow' ); }
  if ( 'edit_theme_options' !== $cap ) { return $caps; }
  $script = $this->script(); $area = 'nav-menus.php' === $script ? 'menus' : ( 'widgets.php' === $script ? 'widgets' : $this->ajax_area() );
  if ( ! $area ) { return $caps; }
  $decision = self::decision( $area, $user_id );
  if ( false === $decision ) { return array( 'do_not_allow' ); }
  // Only explicitly selected classic-menu users get this temporary, native-handler bridge.
  if ( 'menus' === $area && true === $decision && ! in_array( 'do_not_allow', $caps, true ) && get_current_user_id() === $user_id ) { return array( 'read' ); }
  return $caps;
 }
 /** Deny the native classic-menu screen for excluded users. @return void */
 public function menu_access(): void { if ( ! is_network_admin() && false === self::decision( 'menus' ) ) { wp_die( esc_html__( 'Menu editing is not available to your account on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); } }
 /** Add a native-menu shortcut for explicitly delegated editors without exposing other Design pages. @return void */
 public function delegated_menu(): void {
  if ( is_network_admin() || true !== self::decision( 'menus' ) || ! current_theme_supports( 'menus' ) ) { return; }
  $user = wp_get_current_user();
  if ( ! empty( $user->allcaps['edit_theme_options'] ) || ( is_multisite() && is_super_admin() ) ) { return; }
  add_menu_page( __( 'Menus', 'site-essentials' ), __( 'Menus', 'site-essentials' ), 'site_essentials_menus', 'nav-menus.php', '', 'dashicons-menu', 60 );
 }
 /** Guard native AJAX families before their original nonce/capability checks. @return void */
 public function ajax_guard(): void {
  $area = $this->ajax_area();
  if ( $area && false === self::decision( $area ) ) { wp_die( esc_html__( 'This editing area is not available to your account.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
 }
 /** Restrict native editing routes while preserving public navigation reads and native object checks.
  * @param mixed $response Earlier response.
  * @param array $handler Matched REST handler.
  * @param \WP_REST_Request $request Current request.
  * @return mixed
  */
 public function rest_guard( $response, array $handler, \WP_REST_Request $request ) {
  if ( is_wp_error( $response ) ) { return $response; }
  $route = $request->get_route(); $area = '';
  if ( preg_match( '#^/wp/v2/(widgets|sidebars|widget-types)(/|$)#', $route ) ) { $area = 'widgets'; }
  if ( preg_match( '#^/wp/v2/(menus|menu-items|menu-locations|navigation)(/|$)#', $route ) && ( ! in_array( $request->get_method(), array( 'GET', 'HEAD', 'OPTIONS' ), true ) || 'edit' === $request->get_param( 'context' ) ) ) { $area = 'menus'; }
  return $area && false === self::decision( $area ) ? new \WP_Error( 'rest_forbidden', __( 'This editing area is not available to your account.', 'site-essentials' ), array( 'status' => 403 ) ) : $response;
 }
 /** Map native Customizer setting IDs to their policy family.
  * @param string $id Setting identifier.
  * @return string
  */
 private function setting_area( string $id ): string {
  if ( preg_match( '/^(nav_menu\[|nav_menu_item\[|nav_menu_locations\[|nav_menus_created_posts$)/', $id ) ) { return 'menus'; }
  return preg_match( '/^(widget_|sidebars_widgets\[)/', $id ) ? 'widgets' : '';
 }
 /** Protect dynamically submitted settings, including writes without visible controls.
  * @param array|false $args Native setting arguments.
  * @param string $id Setting ID.
  * @return array|false
  */
 public function dynamic_setting( $args, string $id ) {
  $area = $this->setting_area( $id );
  if ( is_array( $args ) && $area && false === self::decision( $area ) ) { $args['capability'] = 'do_not_allow'; }
  return $args;
 }
 /** Remove denied sections and protect already registered native settings. @param \WP_Customize_Manager $manager Manager. @return void */
 public function customizer_settings( \WP_Customize_Manager $manager ): void {
  foreach ( $manager->settings() as $id => $setting ) { $area = $this->setting_area( $id ); if ( $area && false === self::decision( $area ) ) { $setting->capability = 'do_not_allow'; } }
  if ( false === self::decision( 'menus' ) ) { $manager->remove_panel( 'nav_menus' ); }
  if ( false === self::decision( 'widgets' ) ) { $manager->remove_panel( 'widgets' ); }
 }
 /** Check menu lookup/input objects before entering native handlers.
  * @return void
  */
 public function menu_objects(): void {
  if ( is_network_admin() || ( 'nav-menus.php' !== $this->script() && 'menus' !== $this->ajax_area() ) || true !== self::decision( 'menus' ) ) { return; }
  if ( 'nav-menus.php' === $this->script() && isset( $_POST['nav-menu-data'] ) ) {
   if ( ! is_string( $_POST['nav-menu-data'] ) ) { wp_die( esc_html__( 'You cannot use this content in a navigation menu.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
   require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
   _wp_expand_nav_menu_post_data();
  }
  $types = $_POST['menu-item-type'] ?? array(); $objects = $_POST['menu-item-object-id'] ?? array(); $db_ids = $_POST['menu-item-db-id'] ?? array();
  if ( ! is_array( $types ) || ! is_array( $objects ) || ! is_array( $db_ids ) ) { wp_die( esc_html__( 'You cannot use this content in a navigation menu.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  if ( is_array( $types ) && is_array( $objects ) ) { foreach ( $types as $key => $type ) { if ( 'post_type' === $type ) { $this->readable_object( $objects[ $key ] ?? '', $db_ids[ $key ] ?? 0 ); } } }
  $items = $_POST['menu-item'] ?? ( $_REQUEST['menu-item'] ?? array() );
  if ( ! is_array( $items ) ) { $items = array(); } // Native delete/move actions validate nav-menu-item IDs themselves.
  foreach ( $items as $item ) {
   if ( ! is_array( $item ) || 'post_type' !== ( $item['menu-item-type'] ?? '' ) ) { continue; }
   $id = $item['menu-item-object-id'] ?? '';
   $this->readable_object( $id, $item['menu-item-db-id'] ?? 0 );
  }
  if ( 'get-post-item' === ( $_REQUEST['type'] ?? '' ) && is_string( $_REQUEST['object_type'] ?? null ) && post_type_exists( $_REQUEST['object_type'] ) ) { $this->readable_object( $_REQUEST['ID'] ?? '' ); }
 }
 /** Require a real readable content object; menu permission cannot reveal protected content.
  * @param mixed $id Submitted post ID.
  * @param mixed $menu_item Existing native menu item ID.
  * @return void
  */
 private function readable_object( $id, $menu_item = 0 ): void {
  if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) ) { wp_die( esc_html__( 'You cannot use this content in a navigation menu.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $existing = is_scalar( $menu_item ) && ctype_digit( (string) $menu_item ) && is_nav_menu_item( (int) $menu_item ) && (string) get_post_meta( (int) $menu_item, '_menu_item_object_id', true ) === (string) $id && 'post_type' === get_post_meta( (int) $menu_item, '_menu_item_type', true );
  if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || ! ( $post = get_post( (int) $id ) ) || ! ( $type = get_post_type_object( $post->post_type ) ) || ( ! $type->show_in_nav_menus && ! $existing ) || ! current_user_can( 'read_post', $post->ID ) ) { wp_die( esc_html__( 'You cannot use this content in a navigation menu.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
 }

}
