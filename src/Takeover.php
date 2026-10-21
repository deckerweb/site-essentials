<?php
/** Explicit, read-only source adapters and a reviewed local settings takeover. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Imports known source values on demand; never loads, changes or removes source plugins. */
final class Takeover {
 /** Register an administrator-only review screen and its protected commit action. @return void */
 public function boot(): void {
  add_action( 'admin_menu', array( $this, 'menu' ) );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'admin_post_site_essentials_takeover', array( $this, 'submit' ) );
 }
 /** Register the linked review page without another permanent navigation entry. @return void */
 public function menu(): void {
  $hook = add_submenu_page( null, __( 'Review Falcon and Unbloater settings', 'site-essentials' ), 'Site Essentials', 'manage_options', 'site-essentials-takeover', array( $this, 'page' ) );
  if ( $hook ) { add_action( 'load-' . $hook, array( $this, 'title' ) ); }
 }
 /** Supply the document title for the hidden, directly linked WordPress screen. @return void */
 public function title(): void { $GLOBALS['title'] = __( 'Review Falcon and Unbloater settings', 'site-essentials' ); }
 /** Load existing local styling only on the review page.
  * @param string $hook Current admin screen suffix.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( 'admin_page_site-essentials-takeover' === $hook ) {
   wp_enqueue_style( 'site-essentials-admin', plugins_url( 'assets/admin.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION );
   wp_enqueue_script( 'site-essentials-admin', plugins_url( 'assets/admin.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION, true );
   wp_localize_script( 'site-essentials-admin', 'siteEssentials', array( 'takeover' => true ) );
  }
 }
 /** Read installed source identities and their actual option scope on this site.
  * @return array<string,array<string,mixed>> Internal source data; raw values are never rendered.
  */
 private static function sources(): array {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { return array(); }
  require_once ABSPATH . 'wp-admin/includes/plugin.php';
  $plugins = get_plugins(); $result = array();
  foreach ( array( 'falcon' => array( 'falcon/falcon.php', 'Falcon', '2.12.0', 'falcon' ), 'unbloater' => array( 'unbloater/unbloater.php', 'Unbloater', '1.7.0', 'unbloater_settings' ) ) as $id => $spec ) {
   list( $file, $name, $tested, $option ) = $spec;
   if ( ! isset( $plugins[ $file ] ) ) { continue; }
   $network = 'unbloater' === $id && is_plugin_active_for_network( $file );
   $allowed = ! $network || current_user_can( 'manage_network_options' );
   $raw = $allowed ? ( $network ? get_site_option( $option, null ) : get_option( $option, null ) ) : null;
   $version = (string) ( $plugins[ $file ]['Version'] ?? '' );
   $valid = null === $raw || is_array( $raw ) || ( 'unbloater' === $id && '' === $raw );
   if ( 'falcon' === $id && is_array( $raw ) && isset( $raw['features'] ) ) {
    $valid = is_array( $raw['features'] ) && count( $raw['features'] ) <= 200;
    if ( $valid ) { foreach ( $raw['features'] as $value ) { if ( ! is_string( $value ) || strlen( $value ) > 100 ) { $valid = false; break; } } }
   }
   if ( 'unbloater' === $id && is_array( $raw ) ) {
    $valid = count( $raw ) <= 200;
    if ( $valid ) { foreach ( $raw as $key => $value ) { if ( ! is_string( $key ) || strlen( $key ) > 100 || ! in_array( $value, array( '1', '0', 1, 0, true, false, '' ), true ) ) { $valid = false; break; } } }
   }
   $result[ $id ] = array( 'name' => $name, 'version' => $version, 'active' => is_plugin_active( $file ), 'network' => $network, 'raw' => $raw, 'defaulted' => null === $raw && 'falcon' === $id,
    'error' => ! $allowed ? __( 'Network settings require a network administrator. No network values are read.', 'site-essentials' ) : ( $version !== $tested ? __( 'This source version has not been verified. No settings are offered for takeover.', 'site-essentials' ) : ( ! $valid ? __( 'The source settings have an unexpected format. No settings are offered for takeover.', 'site-essentials' ) : '' ) ) );
  }
  return $result;
 }
 /** Interpret source feature state without calling any source code.
  * @param string $id Known source identifier.
  * @param array $source Internal source record.
  * @param string $key Known source feature identifier.
  * @return bool Source behavior, including Falcon's unsaved defaults.
  */
 private static function enabled( string $id, array $source, string $key ): bool {
  if ( 'unbloater' === $id ) { return ( $source['raw'][ $key ] ?? null ) === '1'; }
  if ( null !== $source['raw'] ) { return in_array( $key, $source['raw']['features'] ?? array(), true ); }
  return ! in_array( $key, array( 'no_gutenberg', 'no_cron', 'no_external_requests', 'no_comments', 'no_thumbnails', 'search_posts_only', 'block_ai_bots', 'limit_logins', 'maintenance_mode', 'force_login', 'smtp', 'cache', 'no_ai_features' ), true );
 }
 /** Declare exact source identifiers and explicit differences in target behavior.
  * @param string $id Known source identifier.
  * @return array<string,array> Target key => source key(s), value mode and optional notice.
  */
 private static function mappings( string $id ): array {
  $jquery = __( 'Site Essentials retains jQuery Migrate for signed-in sessions and administrators.', 'site-essentials' );
  $xml = __( 'Site Essentials blocks XML-RPC requests with HTTP 403; test external publishing before removing the source plugin.', 'site-essentials' );
  $notes = __( 'Review the Site Essentials effect description; the source implementation is not copied.', 'site-essentials' );
  if ( 'falcon' === $id ) { return array(
   'feed_links' => array( array( 'no_feed_links' ), 'bool', '' ), 'rsd' => array( array( 'no_rsd_link' ), 'bool', '' ), 'wlw' => array( array( 'no_wlwmanifest_link' ), 'bool', '' ),
   'shortlink' => array( array( 'no_shortlink' ), 'bool', '' ), 'rest_discovery' => array( array( 'no_rest_link' ), 'bool', '' ),
   'admin_email' => array( array( 'no_admin_email_confirm' ), 'bool', '' ), 'self_pingbacks' => array( array( 'no_self_pings' ), 'bool', '' ), 'emoji' => array( array( 'no_emojis' ), 'bool', '' ),
   'jquery_migrate' => array( array( 'no_jquery_migrate' ), 'bool', $jquery ), 'xmlrpc' => array( array( 'no_xmlrpc' ), 'bool', $xml ),
   'core_ai' => array( array( 'no_ai_features' ), 'bool', $notes ), 'comments_disabled' => array( array( 'no_comments' ), 'bool', $notes ),
   'revisions' => array( array( 'no_revisions' ), 'revisions_zero', __( 'Existing per-content-type revision limits stay unchanged and can override this global limit.', 'site-essentials' ) ),
   'adminbar' => array( array( 'no_wp_logo' ), 'logo', '' ),
   'classic_editor_types' => array( array( 'no_gutenberg' ), 'editors', __( 'Only editing interfaces are transferred. Frontend block styles remain available to preserve existing content.', 'site-essentials' ) ),
   'classic_widgets' => array( array( 'no_gutenberg' ), 'bool', __( 'Only editing interfaces are transferred. Frontend block styles remain available to preserve existing content.', 'site-essentials' ) ),
   'footer' => array( array( 'no_footer_text' ), 'bool', __( 'Only the native admin footer text is hidden; version information remains available.', 'site-essentials' ) ),
   'dashboard' => array( array( 'no_dashboard_widgets' ), 'dashboard', __( 'Only the six supported core dashboard widgets are transferred. Third-party and obsolete widgets are not removed.', 'site-essentials' ) ),
  ); }
  return array(
   'login_language' => array( array( 'disable_login_language_dropdown' ), 'bool', '' ), 'admin_email' => array( array( 'disable_admin_email_confirmation' ), 'bool', '' ),
   'login_logo_url' => array( array( 'normalize_login_logo_url' ), 'bool', '' ), 'login_logo_text' => array( array( 'normalize_login_logo_title' ), 'bool', '' ),
   'fullscreen' => array( array( 'block_editor_autoexit_fullscreen_mode' ), 'bool', '' ), 'welcome' => array( array( 'block_editor_autoclose_welcome_guide' ), 'bool', $notes ),
   'block_directory' => array( array( 'block_editor_deactivate_block_directory' ), 'bool', '' ), 'core_patterns' => array( array( 'block_editor_deactivate_core_block_patterns' ), 'bool', '' ),
   'template_editor' => array( array( 'block_editor_deactivate_template_editor' ), 'bool', $notes ),
   'generator' => array( array( 'remove_generator_tag', 'remove_feed_generator_tag' ), 'both', __( 'HTML and feed generator settings must agree because Site Essentials controls both together.', 'site-essentials' ) ),
   'rsd' => array( array( 'remove_rsd_link' ), 'bool', '' ), 'wlw' => array( array( 'remove_wlw_manifest_link' ), 'bool', '' ), 'shortlink' => array( array( 'remove_shortlink' ), 'bool', '' ),
   'feed_links' => array( array( 'remove_feed_links', 'disable_feeds' ), 'any', '' ), 'feeds' => array( array( 'disable_feeds' ), 'bool', __( 'Site Essentials returns HTTP 404 for feeds instead of redirecting to the homepage.', 'site-essentials' ) ),
   'emoji' => array( array( 'disable_emojis' ), 'bool', '' ), 'jquery_migrate' => array( array( 'remove_jquery_migrate' ), 'bool', $jquery ),
   'comment_reply' => array( array( 'optimize_comment_js_loading' ), 'bool', $notes ), 'comment_autolinks' => array( array( 'disable_comment_hyperlinks' ), 'bool', '' ),
   'favicon' => array( array( 'normalize_favicon' ), 'bool', $notes ), 'xmlrpc' => array( array( 'disable_xmlrpc' ), 'bool', $xml ),
   'file_editors' => array( array( 'disallow_file_edit' ), 'bool', __( 'Single-site only. Network file-editor permissions cannot be changed by a local takeover.', 'site-essentials' ) ),
   'footer' => array( array( 'remove_admin_footer' ), 'bool', '' ), 'adminbar' => array( array( 'remove_admin_bar_wordpress_item' ), 'logo', '' ),
   'revisions' => array( array( 'limit_post_revisions' ), 'revisions_five', __( 'The global limit becomes five; existing per-content-type revision limits remain stored.', 'site-essentials' ) ),
   'heartbeat_front' => array( array( 'reduce_heartbeat_interval' ), 'interval', __( 'Heartbeat remains enabled. Existing per-content-type intervals stay unchanged.', 'site-essentials' ) ),
   'heartbeat_admin' => array( array( 'reduce_heartbeat_interval' ), 'interval', __( 'Heartbeat remains enabled. Existing per-content-type intervals stay unchanged.', 'site-essentials' ) ),
   'heartbeat_editor' => array( array( 'reduce_heartbeat_interval' ), 'interval', __( 'Heartbeat remains enabled. Existing per-content-type intervals stay unchanged.', 'site-essentials' ) ),
  );
 }
 /** Build a bounded preview with server-owned proposals and a state fingerprint.
  * @return array Sources, proposals, untransferred active identifiers and scoped revision.
  */
 public static function review(): array {
  $sources = self::sources(); $local = Settings::local(); $rows = array(); $skipped = array();
  foreach ( $sources as $id => $source ) {
   if ( $source['error'] ) { continue; }
   $handled = array();
   foreach ( self::mappings( $id ) as $target => $spec ) {
    list( $keys, $mode, $note ) = $spec;
    $states = array_map( static fn( string $key ): bool => self::enabled( $id, $source, $key ), $keys );
    $on = in_array( true, $states, true );
    if ( ( 'both' === $mode && count( array_unique( $states ) ) > 1 ) || ( 'file_editors' === $target && is_multisite() ) || ( 'login_language' === $target && Settings::language_locked() ) || ( 'footer' === $target && '' !== $local['footer_text'] ) ) { continue; }
    $value = $on;
    if ( in_array( $mode, array( 'interval', 'revisions_zero', 'revisions_five' ), true ) ) { $value = $on ? ( 'interval' === $mode ? 60 : ( 'revisions_five' === $mode ? 5 : 0 ) ) : ( 'interval' === $mode ? 0 : -1 ); }
    if ( 'editors' === $mode ) {
     $items = array_values( get_post_types( array( 'show_ui' => true ) ) );
     $items = array_values( array_filter( $items, static fn( string $type ): bool => post_type_supports( $type, 'editor' ) && ! in_array( $type, array( 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' ), true ) ) );
     $value = $on ? array_values( array_unique( array_merge( $local[ $target ], $items ) ) ) : array_values( array_diff( $local[ $target ], $items ) );
    }
    if ( in_array( $mode, array( 'logo', 'dashboard' ), true ) ) {
     $items = 'logo' === $mode ? array( 'wp-logo' ) : Schema::fields()['dashboard']['choices'];
     $value = $on ? array_values( array_unique( array_merge( $local[ $target ], $items ) ) ) : array_values( array_diff( $local[ $target ], $items ) );
    }
    $rows[ $target ]['proposals'][ $id ] = array( 'value' => $value, 'note' => $note, 'keys' => $keys );
    $handled = array_merge( $handled, $keys );
   }
   $keys = 'falcon' === $id ? ( $source['raw']['features'] ?? array() ) : array_keys( is_array( $source['raw'] ) ? $source['raw'] : array() );
   if ( 'falcon' === $id && $source['defaulted'] ) {
    $keys = array( 'no_feed_links', 'no_rsd_link', 'no_wlwmanifest_link', 'no_adjacent_posts_links', 'no_wp_generator', 'no_shortlink', 'no_rest_link', 'login_site_icon', 'no_update_nags', 'no_footer_text', 'no_dashboard_widgets', 'no_wp_logo', 'no_application_passwords', 'no_heartbeat', 'no_embeds', 'no_revisions', 'no_self_pings', 'no_privacy', 'no_auto_updates', 'no_comment_url', 'no_texturize', 'no_rest_api', 'no_xmlrpc', 'no_login_errors', 'restrict_upload', 'comment_spam_protection', 'no_query_string', 'no_jquery_migrate', 'schema_less_urls', 'no_recent_comments_widget_style', 'cleanup_menu', 'no_emojis', 'no_image_threshold', 'no_exif_rotate', 'no_admin_email_confirm', 'no_update_emails', 'no_new_user_emails', 'no_password_reset_emails', 'change_default_email' );
   }
   foreach ( array_diff( $keys, $handled ) as $key ) { if ( is_string( $key ) && self::enabled( $id, $source, $key ) ) { $skipped[ $id ][] = $key; } }
   // Broader source options still require explicit attention even when a safe subset is offered.
   if ( 'falcon' === $id && self::enabled( $id, $source, 'no_dashboard_widgets' ) ) { $skipped[ $id ][] = 'no_dashboard_widgets'; }
   if ( 'falcon' === $id && self::enabled( $id, $source, 'no_gutenberg' ) ) { $skipped[ $id ][] = 'no_gutenberg'; }
   if ( 'falcon' === $id && self::enabled( $id, $source, 'no_footer_text' ) ) { $skipped[ $id ][] = 'no_footer_text'; }
   foreach ( array( 'lazy_load_css', 'smtp', 'default_email' ) as $extra ) { if ( 'falcon' === $id && ! empty( $source['raw'][ $extra ] ) ) { $skipped[ $id ][] = $extra; } }
  }
  foreach ( $rows as $key => &$row ) {
   $row['current'] = $local[ $key ];
   $values = array_column( $row['proposals'], 'value' );
   $row['source_conflict'] = count( array_unique( array_map( 'wp_json_encode', $values ) ) ) > 1;
   $row['existing_conflict'] = count( array_filter( $values, static fn( $value ): bool => $value !== $local[ $key ] ) ) > 0;
  }
  unset( $row );
  $state = array( get_current_blog_id(), get_current_network_id(), get_current_user_id(), $sources, $local, get_option( Settings::PREVIOUS, null ), Settings::language_locked(), is_multisite(), get_post_types( array( 'show_ui' => true ) ) );
  foreach ( $sources as &$source ) { unset( $source['raw'] ); }
  unset( $source );
  return array( 'sources' => $sources, 'rows' => $rows, 'skipped' => $skipped, 'revision' => wp_hash( wp_json_encode( $state ), 'nonce' ) );
 }
 /** Apply only reviewed source choices; values are always recomputed server-side.
  * @param array $choices Target key => source identifier; empty values mean keep current.
  * @param string $revision Fingerprint of the exact reviewed source and local state.
  * @return int|\WP_Error Number of changed settings, or an error; no source data is written.
  */
 public static function apply( array $choices, string $revision ) {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { return new \WP_Error( 'permission', __( 'You do not have permission to change these settings.', 'site-essentials' ), array( 'status' => 403 ) ); }
  $review = self::review();
  if ( ! hash_equals( $review['revision'], $revision ) ) { return new \WP_Error( 'stale', __( 'Source settings, plugin status or Site Essentials settings changed. Review them again before taking over.', 'site-essentials' ), array( 'status' => 409 ) ); }
  if ( count( $choices ) > count( Schema::fields() ) ) { return new \WP_Error( 'choices', __( 'Use only the choices offered in the takeover preview.', 'site-essentials' ) ); }
  $values = Settings::local(); $changed = 0;
  foreach ( $choices as $key => $id ) {
   if ( ! is_string( $id ) || ! isset( $review['rows'][ $key ] ) || ( '' !== $id && ! isset( $review['rows'][ $key ]['proposals'][ $id ] ) ) ) { return new \WP_Error( 'choices', __( 'Use only the choices offered in the takeover preview.', 'site-essentials' ) ); }
   if ( '' === $id ) { continue; }
   $value = $review['rows'][ $key ]['proposals'][ $id ]['value'];
   if ( $values[ $key ] !== $value ) { $values[ $key ] = $value; ++$changed; }
  }
  $validated = Settings::validate( $values );
  if ( is_wp_error( $validated ) ) { return $validated; }
  if ( ! $changed ) { return 0; }
  $old_settings = get_option( Settings::OPTION, null ); $old_previous = get_option( Settings::PREVIOUS, null );
  if ( ! Settings::save( $validated ) ) {
   foreach ( array( Settings::OPTION => $old_settings, Settings::PREVIOUS => $old_previous ) as $option => $old ) { null === $old ? delete_option( $option ) : update_option( $option, $old, false ); }
   return new \WP_Error( 'save', __( 'The takeover could not be saved. Reload to verify the retained configuration.', 'site-essentials' ), array( 'status' => 500 ) );
  }
  return $changed;
 }
 /** Commit a complete, site-bound POST with an explicit acknowledgement. @return void */
 public function submit(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { wp_die( esc_html__( 'You do not have permission to change these settings.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Use the settings form to perform this action.', 'site-essentials' ), '', array( 'response' => 405 ) ); }
  $nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : null;
  if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), Admin::nonce_action( 'site_essentials_takeover' ) ) ) { wp_die( esc_html__( 'The request could not be verified. Reload the page and try again.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( '1' !== ( $_POST['takeover_complete'] ?? null ) || '1' !== ( $_POST['acknowledge'] ?? null ) || ! is_array( $_POST['choices'] ?? null ) || ! is_string( $_POST['revision'] ?? null ) ) { wp_die( esc_html__( 'Select settings and confirm the reviewed takeover using the complete form.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  $result = self::apply( wp_unslash( $_POST['choices'] ), wp_unslash( $_POST['revision'] ) );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => $result->get_error_data()['status'] ?? 400 ) ); }
  wp_safe_redirect( add_query_arg( array( 'page' => 'site-essentials-takeover', 'se_takeover' => $result ? 'saved' : 'unchanged' ), admin_url( 'admin.php' ) ) ); exit;
 }
 /** Format known target values without exposing any source credentials.
  * @param mixed $value Validated target value.
  * @return string Readable boolean, number or identifier list.
  */
 private static function display( $value ): string {
  if ( is_bool( $value ) ) { return $value ? __( 'Enabled', 'site-essentials' ) : __( 'Disabled', 'site-essentials' ); }
  return is_array( $value ) ? ( $value ? implode( ', ', $value ) : __( 'None', 'site-essentials' ) ) : (string) $value;
 }
 /** Render the explicit preview and keep every choice initially unchanged. @return void */
 public function page(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { return; }
  $review = self::review();
  echo '<div class="wrap se-root se-takeover"><header class="se-header"><img class="se-brand-icon" src="' . esc_url( plugins_url( 'assets/images/icon-128.png', SITE_ESSENTIALS_FILE ) ) . '" width="56" height="56" alt=""><div><h1>Site Essentials</h1><p>' . esc_html__( 'Review Falcon and Unbloater settings', 'site-essentials' ) . '</p></div></header><div class="wp-header-end"></div>';
  if ( 'saved' === ( $_GET['se_takeover'] ?? null ) ) { echo '<div class="notice inline notice-success"><p>' . esc_html__( 'Selected settings taken over. The previous Site Essentials configuration is available for restoration.', 'site-essentials' ) . '</p></div>'; }
  if ( 'unchanged' === ( $_GET['se_takeover'] ?? null ) ) { echo '<div class="notice inline notice-info"><p>' . esc_html__( 'No settings changed. The previous configuration is unchanged.', 'site-essentials' ) . '</p></div>'; }
  echo '<p><a href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-transfer' ) ) . '">' . esc_html__( 'Back to Site Essentials', 'site-essentials' ) . '</a></p><div class="se-card"><h2>' . esc_html__( 'One-time settings takeover', 'site-essentials' ) . '</h2><p>' . esc_html__( 'Only selected local settings change. Source plugins, source settings, network policies, personal exceptions and content stay unchanged. Remove the old plugins yourself after checking the result.', 'site-essentials' ) . '</p><p>' . esc_html__( 'Every choice starts with Keep current. Choose a source explicitly for each setting, including conflicts. This is a translation to Site Essentials behavior, not a full copy of another plugin.', 'site-essentials' ) . '</p><p>' . esc_html__( 'Verified source versions: Falcon 2.12.0 and Unbloater 1.7.0. Other versions are detected but not imported.', 'site-essentials' ) . '</p>';
  foreach ( $review['sources'] as $source ) {
   echo '<h3>' . esc_html( $source['name'] . ' ' . $source['version'] ) . '</h3><p>' . esc_html( $source['active'] ? __( 'Active: overlapping functions may still be controlled by the source plugin until you deactivate it.', 'site-essentials' ) : __( 'Installed but inactive: stored settings can be reviewed.', 'site-essentials' ) ) . '</p><p>' . esc_html( $source['network'] ? __( 'Network source; takeover applies only to this website.', 'site-essentials' ) : __( 'Source settings belong to this website.', 'site-essentials' ) ) . '</p>';
   if ( $source['defaulted'] && ! $source['error'] ) { echo '<p>' . esc_html__( 'Falcon has no saved configuration; its verified built-in defaults are shown.', 'site-essentials' ) . '</p>'; }
   if ( $source['error'] ) { echo '<p role="status">' . esc_html( $source['error'] ) . '</p>'; }
  }
  if ( ! $review['sources'] ) { echo '<p>' . esc_html__( 'Neither Falcon nor Unbloater is installed in its standard plugin directory.', 'site-essentials' ) . '</p>'; }
  echo '</div>';
  if ( $review['rows'] ) {
   echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="site_essentials_takeover"><input type="hidden" name="revision" value="' . esc_attr( $review['revision'] ) . '">';
   wp_nonce_field( Admin::nonce_action( 'site_essentials_takeover' ) );
   foreach ( $review['rows'] as $key => $row ) {
    $field = Schema::fields()[ $key ];
    echo '<div class="se-card"><h3 id="se-takeover-title-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</h3><p>' . esc_html( $field['help'] ) . '</p><p><strong>' . esc_html__( 'Current Site Essentials value', 'site-essentials' ) . ':</strong> ' . esc_html( self::display( $row['current'] ) ) . '</p>';
    if ( $row['source_conflict'] || $row['existing_conflict'] ) { echo '<p class="se-takeover-conflict"><strong>' . esc_html__( 'Conflict: sources disagree or an existing Site Essentials choice would be replaced.', 'site-essentials' ) . '</strong></p>'; }
    foreach ( $row['proposals'] as $id => $proposal ) {
     echo '<p><strong>' . esc_html( $review['sources'][ $id ]['name'] ) . ':</strong> ' . esc_html( self::display( $proposal['value'] ) ) . ' <small>(' . esc_html( implode( ', ', $proposal['keys'] ) ) . ')</small></p>';
     if ( $proposal['note'] ) { echo '<p class="description">' . esc_html( $proposal['note'] ) . '</p>'; }
    }
    echo '<label id="se-takeover-label-' . esc_attr( $key ) . '" for="se-takeover-' . esc_attr( $key ) . '">' . esc_html__( 'Value to take over', 'site-essentials' ) . '</label><select id="se-takeover-' . esc_attr( $key ) . '" name="choices[' . esc_attr( $key ) . ']" aria-labelledby="se-takeover-title-' . esc_attr( $key ) . ' se-takeover-label-' . esc_attr( $key ) . '" class="se-takeover-choice"><option value="">' . esc_html__( 'Keep current', 'site-essentials' ) . '</option>';
    foreach ( $row['proposals'] as $id => $proposal ) { echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $review['sources'][ $id ]['name'] . ': ' . self::display( $proposal['value'] ) ) . '</option>'; }
    echo '</select></div>';
   }
   self::skipped( $review );
   echo '<div class="se-card"><h3>' . esc_html__( 'Confirm selected changes', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Saving replaces the single previous-configuration snapshot. Use Review previous configuration and Restore previous configuration on the settings page to undo this takeover; a later settings save replaces that snapshot again. Export first if you need a lasting copy.', 'site-essentials' ) . '</p><p><label><input type="checkbox" name="acknowledge" value="1" required> ' . esc_html__( 'I reviewed the selected values, conflicts and functions that are not transferred.', 'site-essentials' ) . '</label></p><button type="submit" class="button button-primary">' . esc_html__( 'Take over selected settings', 'site-essentials' ) . '</button></div><input type="hidden" name="takeover_complete" value="1"></form>';
  } else { self::skipped( $review ); }
  echo '<p><a class="button" href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-overview' ) ) . '">' . esc_html__( 'Review settings and previous configuration', 'site-essentials' ) . '</a></p>';
  ( new Admin() )->footer(); echo '</div>';
 }
 /** Explain common untransferred features using local translated text.
  * @param string $key Source feature identifier; unknown identifiers use a neutral label.
  * @return string Human-readable scope or missing feature.
  */
 private static function skipped_label( string $key ): string {
  $labels = array(
   'no_heartbeat' => __( 'Complete Heartbeat shutdown: no equivalent; configure intervals separately if needed.', 'site-essentials' ),
   'no_gutenberg' => __( 'Removal of frontend block styles: not transferred; editor choices are offered above.', 'site-essentials' ),
   'no_dashboard_widgets' => __( 'Third-party dashboard widget removal: not transferred; supported core widgets are offered above.', 'site-essentials' ),
   'no_footer_text' => __( 'Removal of footer version information: not transferred.', 'site-essentials' ),
   'no_wp_generator' => __( 'HTML-only generator removal: no separate equivalent for HTML and feeds.', 'site-essentials' ),
   'remove_generator_tag' => __( 'HTML and feed generator choices differ; no combined takeover is offered.', 'site-essentials' ),
   'remove_feed_generator_tag' => __( 'HTML and feed generator choices differ; no combined takeover is offered.', 'site-essentials' ),
   'no_embeds' => __( 'Source embed restrictions differ from Site Essentials; configure embeddings separately.', 'site-essentials' ),
   'no_update_emails' => __( 'Suppression of all update emails: not transferred; Site Essentials retains core failure alerts.', 'site-essentials' ),
   'no_password_reset_emails' => __( 'Password notification suppression: source behavior is broader than Site Essentials.', 'site-essentials' ),
   'no_update_nags' => __( 'Hiding update notices for everyone: not transferred; Site Essentials preserves notices for authorized users.', 'site-essentials' ),
   'remove_update_available_notice' => __( 'Hiding update notices for everyone: not transferred; Site Essentials preserves notices for authorized users.', 'site-essentials' ),
   'no_jquery_migrate' => __( 'Source-only jQuery Migrate behavior has no safe takeover in this context.', 'site-essentials' ),
   'disable_login_language_dropdown' => __( 'The login language selector is controlled by network policy.', 'site-essentials' ),
   'disallow_file_edit' => __( 'Network file-editor restrictions cannot be imported per site.', 'site-essentials' ),
   'remove_admin_footer' => __( 'Existing custom footer text takes priority; no footer takeover is offered.', 'site-essentials' ),
   'cache' => __( 'Page caching is outside Site Essentials.', 'site-essentials' ),
   'smtp' => __( 'SMTP configuration is outside Site Essentials.', 'site-essentials' ),
   'default_email' => __( 'Custom sender configuration is outside Site Essentials.', 'site-essentials' ),
   'change_default_email' => __( 'Custom sender configuration is outside Site Essentials.', 'site-essentials' ),
   'lazy_load_css' => __( 'Asynchronous CSS loading is outside Site Essentials.', 'site-essentials' ),
   'limit_logins' => __( 'Login attempt limits need a separate security solution.', 'site-essentials' ),
   'no_rest_api' => __( 'Public REST API restriction has no equivalent in Site Essentials.', 'site-essentials' ),
   'no_auto_updates' => __( 'Disabling automatic updates is not transferred.', 'site-essentials' ),
   'no_cron' => __( 'Disabling WordPress Cron is not transferred.', 'site-essentials' ),
   'no_external_requests' => __( 'Blocking all external requests is not transferred.', 'site-essentials' ),
   'force_login' => __( 'Requiring login for all visitors has no equivalent in Site Essentials.', 'site-essentials' ),
   'maintenance_mode' => __( 'Maintenance mode has no equivalent in Site Essentials.', 'site-essentials' ),
  );
  return $labels[ $key ] ?? __( 'Source function without a safe matching takeover.', 'site-essentials' );
 }
 /** List only active untransferred identifiers; never render raw option values.
  * @param array $review Server-generated preview.
  * @return void
  */
 private static function skipped( array $review ): void {
  if ( ! $review['skipped'] ) { return; }
  echo '<div class="se-card"><h2>' . esc_html__( 'Active functions not transferred', 'site-essentials' ) . '</h2><p>' . esc_html__( 'These source functions have no matching, safe takeover or are blocked by the current scope. Check them before removing the old plugins. Credentials and other source values are never displayed or exported.', 'site-essentials' ) . '</p>';
  foreach ( $review['skipped'] as $id => $keys ) { echo '<h3>' . esc_html( $review['sources'][ $id ]['name'] ) . '</h3><ul>'; foreach ( array_unique( $keys ) as $key ) { echo '<li>' . esc_html( self::skipped_label( $key ) ) . ' <small><code>' . esc_html( $key ) . '</code></small></li>'; } echo '</ul>'; }
  echo '</div>';
 }
}
