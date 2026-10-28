<?php
/** Site-scoped, explicitly operated maintenance response. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Keeps operational state out of portable profiles and uses a native page as content. */
final class Maintenance {
 const OPTION = 'site_essentials_maintenance';
 /** Register response, indicators and separately protected administration actions. @return void */
 public function boot(): void {
  add_action( 'init', array( $this, 'pause_for_provider' ), 100 );
  add_action( 'template_redirect', array( $this, 'response' ), -100 );
  add_action( 'admin_bar_menu', array( $this, 'toolbar' ), 110 );
  add_action( 'admin_enqueue_scripts', array( $this, 'toolbar_style' ) );
  add_action( 'wp_enqueue_scripts', array( $this, 'toolbar_style' ) );
  if ( is_admin() ) {
   add_action( 'admin_post_se_maintenance', array( $this, 'save' ) );
   add_action( 'admin_footer', array( $this, 'form' ) );
   add_action( 'admin_notices', array( $this, 'notice' ) );
  }
 }
 /** Read only this site's operational data with defensive types.
  * @return array{enabled:bool,page:int,paused:bool,started:int}
  */
 public static function state(): array {
  $raw = get_option( self::OPTION, array() ); $raw = is_array( $raw ) ? $raw : array();
  return array( 'enabled' => true === ( $raw['enabled'] ?? false ), 'page' => ( is_scalar( $raw['page'] ?? null ) ? absint( $raw['page'] ) : 0 ), 'paused' => true === ( $raw['paused'] ?? false ), 'started' => ( isset( $raw['started'] ) && is_int( $raw['started'] ) && $raw['started'] > 0 && $raw['started'] <= time() ? $raw['started'] : 0 ) );
 }
 /** Detect only loaded providers with an explicitly configured maintenance mode.
  * No foreign settings are written and no premium implementation is reused.
  * @return array<string,string> Provider labels and native settings links.
  */
 public static function providers(): array {
  $found = array();
  if ( 'bricks' === strtolower( (string) get_template() ) && is_callable( array( '\Bricks\Maintenance', 'get_mode' ) ) && in_array( \Bricks\Maintenance::get_mode(), array( 'maintenance', 'comingSoon' ), true ) ) {
   $found['Bricks'] = admin_url( 'admin.php?page=bricks-settings&tab=maintenance' );
  }
  if ( did_action( 'elementor/loaded' ) && is_callable( array( '\Elementor\Maintenance_Mode', 'get' ) ) && in_array( \Elementor\Maintenance_Mode::get( 'mode' ), array( 'maintenance', 'coming_soon' ), true ) && is_scalar( \Elementor\Maintenance_Mode::get( 'template_id' ) ) && absint( \Elementor\Maintenance_Mode::get( 'template_id' ) ) > 0 ) {
   $found['Elementor'] = admin_url( 'admin.php?page=elementor-tools#tab-maintenance_mode' );
  }
  if ( defined( 'BREAKDANCE_MODE' ) && 'breakdance' === BREAKDANCE_MODE && function_exists( '\Breakdance\MaintenanceMode\isMaintenanceModeEnabled' ) && \Breakdance\MaintenanceMode\isMaintenanceModeEnabled() ) {
   $found['Breakdance'] = function_exists( '\Breakdance\MaintenanceMode\generateMaintenanceSettingsPageURL' ) ? \Breakdance\MaintenanceMode\generateMaintenanceSettingsPageURL() : admin_url( 'admin.php?page=breakdance_settings&tab=maintenance-mode' );
  }
  return $found;
 }
 /** Persist an observed provider conflict once; never silently resume afterwards.
  * @return void
  */
 public function pause_for_provider(): void {
  $state = self::state();
  if ( $state['enabled'] && ! $state['paused'] && self::providers() ) { $state['paused'] = true; update_option( self::OPTION, $state, false ); }
 }
 /** Resolve whether this site's own maintenance response is enabled.
  * @return bool
  */
 public static function active(): bool { $state = self::state(); return $state['enabled'] && ! $state['paused'] && ! self::providers(); }
 /** Accept a usable published, non-password-protected page only.
  * @param int $id Selected page ID.
  * @return \WP_Post|null Safe page or null for fallback.
  */
 public static function page( int $id ): ?\WP_Post {
  if ( $id <= 0 ) { return null; }
  $page = get_post( $id );
  return $page instanceof \WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status && '' === $page->post_password ? $page : null;
 }
 /** Build an administrator-only preview link; it never activates maintenance.
  * @return string Signed frontend preview URL.
  */
 public static function preview_url(): string { return wp_nonce_url( add_query_arg( 'se_maintenance_preview', '1', home_url( '/' ) ), 'se_maintenance_preview_' . get_current_blog_id() ); }
 /** Respond to ordinary public page requests, leaving APIs and background work intact.
  * @return void Terminates only a maintenance or authorized preview response.
  */
 public function response(): void {
  $preview = isset( $_GET['se_maintenance_preview'] ) && is_scalar( $_GET['se_maintenance_preview'] ) && '1' === (string) $_GET['se_maintenance_preview'] && current_user_can( 'manage_options' ) && isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'se_maintenance_preview_' . get_current_blog_id() );
  if ( ! $preview && ( ! self::active() || current_user_can( 'manage_options' ) ) ) { return; }
  if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || is_trackback() || ( defined( 'WP_CLI' ) && WP_CLI ) ) { return; }
  // Authenticated content editors retain ordinary previews, not unrestricted anonymous bypass URLs.
  if ( ! $preview && is_preview() && current_user_can( 'edit_post', get_queried_object_id() ) ) { return; }
  if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
  status_header( $preview ? 200 : 503 ); nocache_headers();
  header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true );
  header( 'X-Robots-Tag: noindex, nofollow', true );
  header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ), true );
  if ( ! $preview ) { header( 'Retry-After: 600', true ); }
  $page = self::page( self::state()['page'] );
  $content = '';
  if ( $page ) {
   // Content filters and conditional assets must see the selected page, not the intercepted URL.
   $GLOBALS['wp_query'] = new \WP_Query( array( 'page_id' => $page->ID, 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1 ) );
   $GLOBALS['post'] = $page; setup_postdata( $page );
   $content = apply_filters( 'the_content', $page->post_content );
  }
  wp_enqueue_style( 'wp-block-library' );
  wp_enqueue_style( 'se-maintenance', plugins_url( 'assets/maintenance.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION );
  // Native head/footer hooks support authored blocks and shortcodes, not theme template chrome.
  echo '<!doctype html><html '; language_attributes(); echo '><head><meta charset="' . esc_attr( get_option( 'blog_charset' ) ) . '"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . esc_html( $page ? get_the_title( $page ) : __( 'Maintenance', 'site-essentials' ) ) . '</title>';
  wp_head(); echo '</head><body class="se-maintenance"><main id="main" class="se-maintenance-content">';
  if ( $page ) { echo $content; } // Native authored page output, same trust model as the_content.
  else { echo '<h1>' . esc_html__( 'Brief maintenance', 'site-essentials' ) . '</h1><p>' . esc_html__( 'This website is temporarily unavailable. Please try again shortly.', 'site-essentials' ) . '</p>'; }
  echo '</main>'; wp_footer(); echo '</body></html>'; wp_reset_postdata(); exit;
 }
 /** Describe local cache evidence without claiming complete server detection.
  * @return bool Whether a page-cache drop-in is configured.
  */
 public static function cache_detected(): bool { return defined( 'WP_CACHE' ) && WP_CACHE && is_file( WP_CONTENT_DIR . '/advanced-cache.php' ); }
 /** Render controls associated with a separate form to avoid nested settings forms.
  * @return void
  */
 public static function panel(): void {
  $state = self::state(); $providers = self::providers();
  echo '<div class="se-card"><h3>' . esc_html__( 'Maintenance for this website', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Visitors receive a maintenance page with HTTP 503. Administrators retain access. Login, APIs, webhooks and background tasks remain available; this is not an access or privacy barrier.', 'site-essentials' ) . '</p>';
  echo '<details><summary>' . esc_html__( 'Maintenance, not Coming Soon', 'site-essentials' ) . '</summary><p>' . esc_html__( 'Site Essentials is designed for brief maintenance on an existing website. Its maintenance response uses HTTP 503 (temporarily unavailable) with Retry-After. This is not a Coming Soon mode for a launch page that should remain publicly available over a longer period. A public launch page normally uses HTTP 200; its indexing and access settings need separate decisions. Site Essentials does not offer a Coming Soon mode or a status-code switch. For a launch page, use a suitable theme, builder or dedicated plugin. A launch-page workflow is outside the scope of this focused maintenance tool.', 'site-essentials' ) . '</p></details>';
  foreach ( $providers as $name => $url ) { echo '<p><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>: ' . esc_html__( 'Another provider manages maintenance. Site Essentials pauses its response; your settings are retained.', 'site-essentials' ) . '</p>'; }
  if ( $state['paused'] ) { echo '<p role="status">' . esc_html__( 'Maintenance remains paused. Review the other provider and explicitly enable Site Essentials maintenance again when appropriate.', 'site-essentials' ) . '</p>'; }
  /* translators: %s: activation date and time in the website time zone. */
  if ( self::active() ) { echo '<p class="se-maintenance-since">' . esc_html( $state['started'] ? sprintf( __( 'Active since %s (website time zone).', 'site-essentials' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $state['started'] ) ) : __( 'Active; activation time was not recorded by the previous version.', 'site-essentials' ) ) . '</p>'; }
  echo '<div class="se-maintenance-fields"><label for="se-maintenance-page">' . '<strong>' . esc_html__( 'Maintenance page:', 'site-essentials' ) . '</strong></label>';
  // Add the explicit form association to the native select without changing its options.
  $select = wp_dropdown_pages( array( 'name' => 'maintenance_page', 'id' => 'se-maintenance-page', 'selected' => $state['page'], 'option_none_value' => '0', 'show_option_none' => __( 'Choose a published page', 'site-essentials' ), 'post_status' => 'publish', 'echo' => 0 ) );
  if ( ! $select ) { $select = '<select name="maintenance_page" id="se-maintenance-page"><option value="0">' . esc_html__( 'Choose a published page', 'site-essentials' ) . '</option></select>'; }
  echo str_replace( '<select ', '<select form="se-maintenance-form" ', $select );
  echo '<label><span><input form="se-maintenance-form" type="checkbox" name="maintenance_enabled" value="1" ' . checked( $state['enabled'] && ! $state['paused'], true, false ) . '> ' . esc_html__( 'Enable maintenance', 'site-essentials' ) . '</span></label><p>' . esc_html__( 'Use the editor configured for pages: Classic Editor or Block Editor. The selected page must be published and have no password. Its content and embedded services follow your own page configuration.', 'site-essentials' ) . '</p><div class="se-maintenance-actions"><button form="se-maintenance-form" class="button button-primary" name="operation" value="save">' . esc_html__( 'Save maintenance mode', 'site-essentials' ) . '</button><button form="se-maintenance-form" class="button" name="operation" value="create">' . esc_html__( 'Create maintenance page', 'site-essentials' ) . '</button><a class="button" target="_blank" rel="noopener" href="' . esc_url( self::preview_url() ) . '">' . esc_html__( 'Preview saved maintenance page', 'site-essentials' ) . '</a>';
  if ( $state['page'] && current_user_can( 'edit_post', $state['page'] ) ) { echo '<a class="button" href="' . esc_url( get_edit_post_link( $state['page'], 'raw' ) ) . '">' . esc_html__( 'Edit maintenance page', 'site-essentials' ) . '</a>'; }
  echo '</div></div><p>' . esc_html__( 'Maintenance activation and the selected page stay on this website and are excluded from presets, imports, exports and configuration recovery. Save them separately using the maintenance button.', 'site-essentials' ) . '</p><p>' . esc_html__( 'After enabling or disabling maintenance, check the website in a private browser window. Hosting or CDN page caches may require manual clearing or bypassing even without a cache plugin.', 'site-essentials' ) . '</p>';
  if ( self::cache_detected() ) { echo '<p class="notice notice-warning inline">' . esc_html__( 'A page-cache component is configured. Clear or bypass its page cache while maintenance is active. Site Essentials does not change foreign cache settings.', 'site-essentials' ) . '</p>'; }
  echo '</div>';
 }
 /** Provide the external action form only on the local settings screen.
  * @return void
  */
 public function form(): void {
  $screen = get_current_screen();
  if ( ! $screen || 'settings_page_site-essentials' !== $screen->id || ! current_user_can( 'manage_options' ) || is_network_admin() ) { return; }
  echo '<form id="se-maintenance-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="se_maintenance"><input type="hidden" name="revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( self::state() ) ) ) . '">'; wp_nonce_field( Admin::nonce_action( 'se_maintenance' ) ); echo '</form>';
 }
 /** Save explicit local operational choices after nonce, capability and revision checks.
  * @return void Redirects on success; fails without changing state on invalid input.
  */
 public function save(): void {
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) || is_network_admin() ) { wp_die( esc_html__( 'You cannot change maintenance mode.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( ! isset( $_REQUEST['_wpnonce'] ) || ! is_string( $_REQUEST['_wpnonce'] ) ) { wp_die( esc_html__( 'You cannot change maintenance mode.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( Admin::nonce_action( 'se_maintenance' ) );
  $revision = $_POST['revision'] ?? null;
  if ( ! is_string( $revision ) || ! hash_equals( hash( 'sha256', wp_json_encode( self::state() ) ), $revision ) ) { wp_die( esc_html__( 'Maintenance settings changed. Reload before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $operation = $_POST['operation'] ?? '';
  if ( 'create' === $operation ) {
   if ( ! current_user_can( 'edit_pages' ) ) { wp_die( esc_html__( 'You cannot create pages.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
   $title = __( 'Maintenance', 'site-essentials' ); $body = __( 'This website is temporarily unavailable. Please try again shortly.', 'site-essentials' );
   $markup = use_block_editor_for_post_type( 'page' ) ? '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html( $title ) . '</h1><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html( $body ) . '</p><!-- /wp:paragraph -->' : '<h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $body ) . '</p>';
   $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title, 'post_content' => $markup ), true );
   if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); }
   wp_safe_redirect( get_edit_post_link( $id, 'raw' ) ); exit;
  }
  if ( 'save' !== $operation || ! isset( $_POST['maintenance_page'] ) || ! is_string( $_POST['maintenance_page'] ) || ! preg_match( '/^\d+$/D', $_POST['maintenance_page'] ) || ( isset( $_POST['maintenance_enabled'] ) && '1' !== $_POST['maintenance_enabled'] ) ) { wp_die( esc_html__( 'Invalid maintenance settings.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  $id = (int) $_POST['maintenance_page']; $enabled = isset( $_POST['maintenance_enabled'] );
  if ( $enabled && ( self::providers() || ! self::page( $id ) ) ) { wp_die( esc_html__( 'Choose a published page without a password and disable other maintenance providers before enabling this mode.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  if ( $id && ( $enabled || $id !== self::state()['page'] ) && ( ! self::page( $id ) || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( esc_html__( 'This maintenance page is not available to your account.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $old = self::state();
  $started = $enabled ? ( $old['enabled'] && ! $old['paused'] ? $old['started'] : time() ) : 0;
  $new = array( 'enabled' => $enabled, 'page' => $id, 'paused' => false, 'started' => $started ); update_option( self::OPTION, $new, false );
  if ( self::state() !== $new ) { wp_die( esc_html__( 'Maintenance settings could not be saved.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  wp_safe_redirect( admin_url( 'options-general.php?page=site-essentials&se_maintenance_saved=1#se-maintenance' ) ); exit;
 }
 /** Indicate activation without offering unprotected state-changing toolbar links.
  * @param \WP_Admin_Bar $bar Current toolbar object.
  * @return void
  */
 public function toolbar( \WP_Admin_Bar $bar ): void {
  if ( current_user_can( 'manage_options' ) && ! is_network_admin() && self::active() ) { $bar->add_node( array( 'id' => 'se-maintenance', 'title' => __( 'Maintenance active', 'site-essentials' ), 'href' => admin_url( 'options-general.php?page=site-essentials#se-maintenance' ) ) ); }
 }
 /** Highlight only this site's active maintenance indicator, including the frontend toolbar.
  * @return void
  */
 public function toolbar_style(): void {
  if ( is_admin_bar_showing() && current_user_can( 'manage_options' ) && ! is_network_admin() && self::active() ) {
   wp_enqueue_style( 'se-maintenance-toolbar', plugins_url( 'assets/maintenance-toolbar.css', SITE_ESSENTIALS_FILE ), array( 'admin-bar' ), SITE_ESSENTIALS_VERSION );
  }
 }
 /** Show a single maintenance-save confirmation outside the plugin title block.
  * @return void
  */
 public function notice(): void {
  if ( current_user_can( 'manage_options' ) && ! is_network_admin() && ( $_GET['page'] ?? '' ) === 'site-essentials' && ( $_GET['se_maintenance_saved'] ?? '' ) === '1' ) { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Maintenance settings saved.', 'site-essentials' ) . '</p></div>'; }
 }
}
