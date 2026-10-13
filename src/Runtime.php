<?php
/** WordPress core behavior controls. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Registers focused core hooks; inactive modules leave incoming values unchanged. */
final class Runtime {
 /** Register all runtime callbacks without altering stored WordPress data. @return void */
 public function boot(): void {
  add_filter( 'enable_post_by_email_configuration', array( $this, 'writing_email_ui' ), PHP_INT_MAX );
  add_filter( 'enable_update_services_configuration', array( $this, 'writing_pings_ui' ), PHP_INT_MAX );
  add_filter( 'allowed_options', array( $this, 'writing_allowed' ), PHP_INT_MAX );
  add_filter( 'wp_supports_ai', array( $this, 'ai_support' ), PHP_INT_MAX );
  add_action( 'admin_menu', array( $this, 'connectors_menu' ), PHP_INT_MAX );
  add_action( 'admin_head', array( $this, 'connectors_menu' ), PHP_INT_MAX );
  add_action( 'load-options-connectors.php', array( $this, 'connectors_access' ) );
  add_filter( 'map_meta_cap', array( $this, 'file_editor_caps' ), 100, 4 );
  add_action( 'init', array( Settings::class, 'initialize' ), 0 );
  add_action( 'init', array( $this, 'xmlrpc' ), 1 );
  add_action( 'init', array( $this, 'cleanup' ), 100 );
  add_action( 'after_setup_theme', array( Settings::class, 'initialize' ), 1 );
  add_action( 'init', array( $this, 'patterns' ), 100 );
  add_filter( 'login_display_language_dropdown', fn( $value ) => Settings::get( 'login_language' ) ? false : $value );
  add_filter( 'admin_email_check_interval', fn( $value ) => Settings::get( 'admin_email' ) ? 0 : $value );
  add_filter( 'should_load_remote_block_patterns', fn( $value ) => Settings::get( 'remote_patterns' ) ? false : $value );
  add_filter( 'block_editor_settings_all', array( $this, 'editor_settings' ), 100, 2 );
  add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );
  add_action( 'admin_menu', array( $this, 'template_menu' ), 100 );
  add_action( 'admin_menu', array( $this, 'tools_menu' ), 999 );
  add_action( 'load-site-editor.php', array( $this, 'template_access' ) );
  add_action( 'wp_dashboard_setup', array( $this, 'dashboard' ), 100 );
  add_action( 'admin_bar_menu', array( $this, 'toolbar' ), 999 );
  add_filter( 'admin_footer_text', array( $this, 'footer' ) );
  add_filter( 'the_generator', fn( $value ) => Settings::get( 'generator' ) ? '' : $value );
  add_action( 'template_redirect', array( $this, 'feed_request' ), 1 );
  add_filter( 'comments_open', array( $this, 'comments_open' ), 100, 2 );
  add_filter( 'comments_array', array( $this, 'comments' ), 100, 2 );
  add_filter( 'get_comments_number', array( $this, 'comments_number' ), 100, 2 );
  add_action( 'wp_enqueue_scripts', array( $this, 'comment_script' ), 100 );
  add_action( 'pre_ping', array( $this, 'self_pingbacks' ), 10, 1 );
  add_action( 'wp_default_scripts', array( $this, 'jquery' ), 100 );
  add_filter( 'heartbeat_settings', array( $this, 'heartbeat' ), 100 );
  add_filter( 'wp_revisions_to_keep', array( $this, 'revisions' ), 100, 2 );
  add_filter( 'auto_core_update_send_email', fn( $send, $type ) => Settings::get( 'update_emails' ) && 'success' === $type ? false : $send, 100, 2 );
  add_filter( 'auto_plugin_update_send_email', array( $this, 'update_email' ), 100, 2 );
  add_filter( 'auto_theme_update_send_email', array( $this, 'update_email' ), 100, 2 );
  add_filter( 'wp_password_change_notification_email', array( $this, 'password_email' ), 100 );
 }
 /** Hide native email configuration without re-enabling earlier restrictions.
  * @param bool $enabled Incoming visibility.
  * @return bool
  */
 public function writing_email_ui( bool $enabled ): bool { return $enabled && ! in_array( 'post_by_email', Settings::get( 'writing_sections' ), true ); }
 /** Hide native update-service configuration without changing ping execution.
  * @param bool $enabled Incoming visibility.
  * @return bool
  */
 public function writing_pings_ui( bool $enabled ): bool { return $enabled && ! in_array( 'update_services', Settings::get( 'writing_sections' ), true ); }
 /** Preserve omitted native values when the Writing form saves other options.
  * @param array $allowed Native options allowed per settings group.
  * @return array
  */
 public function writing_allowed( array $allowed ): array {
  if ( ! isset( $allowed['writing'] ) || ! is_array( $allowed['writing'] ) ) { return $allowed; }
  $hidden = Settings::get( 'writing_sections' ); $omit = array();
  if ( in_array( 'post_by_email', $hidden, true ) ) { $omit = array( 'mailserver_url', 'mailserver_port', 'mailserver_login', 'mailserver_pass', 'default_email_category' ); }
  if ( in_array( 'update_services', $hidden, true ) ) { $omit[] = 'ping_sites'; }
  $allowed['writing'] = array_values( array_diff( $allowed['writing'], $omit ) ); return $allowed;
 }
 /** Preserve stronger incoming AI restrictions and opt out for this site.
  * @param bool $supported Incoming environment support.
  * @return bool Effective support.
  */
 public function ai_support( bool $supported ): bool { return Settings::get( 'core_ai' ) ? false : $supported; }
 /** Hide the local Connectors page after ordinary and late menu alterations. @return void */
 public function connectors_menu(): void { if ( ! is_network_admin() && Settings::get( 'core_ai' ) ) { remove_submenu_page( 'options-general.php', 'options-connectors.php' ); } }
 /** Reject direct access without deleting credentials or blocking unrelated HTTP. @return void */
 public function connectors_access(): void { if ( Settings::get( 'core_ai' ) ) { wp_die( esc_html__( 'Core AI and the Connectors settings page are disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); } }
 /** Hide selected native Tools links in the current site's menu only. @return void */
 public function tools_menu(): void {
  if ( is_network_admin() ) { return; }
  $pages = array( 'available_tools' => 'tools.php', 'core_import' => 'import.php', 'core_export' => 'export.php' );
  foreach ( Settings::get( 'tools_menu' ) as $key ) { if ( isset( $pages[ $key ] ) ) { remove_submenu_page( 'tools.php', $pages[ $key ] ); } }
 }
 /** Deny only built-in file editing on single sites, retaining stronger incoming restrictions.
  * @param array $caps Existing primitive capabilities.
  * @param string $cap Requested capability.
  * @param int $user_id Requested user.
  * @param array $args Capability arguments.
  * @return array
  */
 public function file_editor_caps( array $caps, string $cap, int $user_id, array $args ): array {
  if ( is_multisite() || ! in_array( $cap, array( 'edit_plugins', 'edit_themes', 'edit_files' ), true ) || ! Settings::get( 'file_editors' ) ) { return $caps; }
  if ( ! in_array( 'do_not_allow', $caps, true ) ) { $caps[] = 'do_not_allow'; }
  return $caps;
 }
 /** Disable only core pattern sources, preserving user/theme/plugin registrations. @return void */
 public function patterns(): void {
  if ( ! Settings::get( 'core_patterns' ) ) { return; }
  foreach ( \WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
   if ( ( $pattern['source'] ?? '' ) === 'core' ) { unregister_block_pattern( $pattern['name'] ); }
  }
 }
 /** Block XML-RPC early, including unauthenticated pingback and system methods. @return void */
 public function xmlrpc(): void {
  if ( Settings::get( 'xmlrpc' ) && defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
   add_filter( 'wp_die_xmlrpc_handler', static fn() => '_default_wp_die_handler' );
   wp_die( esc_html__( 'XML-RPC is disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) );
  }
 }
 /** Remove explicitly selected core markup and emoji callbacks. @return void */
 public function cleanup(): void {
  $settings = Settings::effective();
  $head = array( 'generator' => 'wp_generator', 'rsd' => 'rsd_link', 'wlw' => 'wlwmanifest_link', 'shortlink' => 'wp_shortlink_wp_head' );
  foreach ( $head as $key => $callback ) { if ( $settings[ $key ] ) { remove_action( 'wp_head', $callback ); } }
  if ( $settings['rest_discovery'] ) { remove_action( 'wp_head', 'rest_output_link_wp_head', 10 ); remove_action( 'template_redirect', 'rest_output_link_header', 11 ); }
  if ( $settings['oembed_discovery'] ) { remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 ); remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 ); }
  if ( $settings['shortlink'] ) { remove_action( 'template_redirect', 'wp_shortlink_header', 11 ); }
  if ( $settings['feed_links'] ) { remove_action( 'wp_head', 'feed_links', 2 ); remove_action( 'wp_head', 'feed_links_extra', 3 ); }
  if ( $settings['emoji'] ) {
   remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
   remove_action( 'wp_print_styles', 'print_emoji_styles' ); remove_action( 'admin_print_styles', 'print_emoji_styles' );
   remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' ); remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
   remove_filter( 'the_content_feed', 'wp_staticize_emoji' ); remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
   add_filter( 'tiny_mce_plugins', array( $this, 'tinymce' ) );
   add_filter( 'emoji_svg_url', '__return_false' );
   add_filter( 'wp_resource_hints', array( $this, 'resource_hints' ), 10, 2 );
  }
 }
 /** Remove the emoji editor plugin only.
  * @param array $plugins TinyMCE plugins.
  * @return array
  */
 public function tinymce( array $plugins ): array { return array_values( array_diff( $plugins, array( 'wpemoji' ) ) ); }
 /** Remove the core emoji resource hint without altering other preload hints.
  * @param array $urls Resource hints.
  * @param string $relation Hint relation.
  * @return array
  */
 public function resource_hints( array $urls, string $relation ): array {
  if ( 'dns-prefetch' !== $relation ) { return $urls; }
  return array_values( array_filter( $urls, static function ( $url ): bool {
   $href = is_array( $url ) ? ( $url['href'] ?? '' ) : $url;
   return ! is_string( $href ) || ! str_contains( $href, 's.w.org' );
  } ) );
 }
 /** Adjust post editor capabilities while retaining block content editing.
  * @param array $settings Editor settings.
  * @param \WP_Block_Editor_Context $context Editor context.
  * @return array
  */
 public function editor_settings( array $settings, $context ): array {
  if ( Settings::get( 'block_directory' ) ) { $settings['enableBlockDirectory'] = false; }
  if ( Settings::get( 'remote_patterns' ) ) { $settings['enableBlockPatternDirectory'] = false; }
  if ( Settings::get( 'template_editor' ) ) { $settings['supportsTemplateMode'] = false; }
  return $settings;
 }
 /** Enqueue preference changes only in the post editor, never builder or Site Editor screens. @return void */
 public function editor_assets(): void {
  $screen = get_current_screen();
  if ( ! $screen || 'post' !== $screen->base || ! $screen->is_block_editor() ) { return; }
  $options = array( 'fullscreen' => Settings::get( 'fullscreen' ), 'welcome' => Settings::get( 'welcome' ) );
  if ( ! $options['fullscreen'] && ! $options['welcome'] ) { return; }
  wp_enqueue_script( 'site-essentials-editor', plugins_url( 'assets/editor.js', SITE_ESSENTIALS_FILE ), array( 'wp-data', 'wp-dom-ready', 'wp-preferences', 'wp-edit-post' ), SITE_ESSENTIALS_VERSION, true );
  wp_add_inline_script( 'site-essentials-editor', 'window.siteEssentialsEditor = ' . wp_json_encode( $options ) . ';', 'before' );
 }
 /** Remove Site Editor navigation when access is deliberately disabled. @return void */
 public function template_menu(): void { if ( Settings::get( 'template_editor' ) ) { remove_submenu_page( 'themes.php', 'site-editor.php' ); } }
 /** Deny direct Site Editor access; existing block templates still render. @return void */
 public function template_access(): void { if ( Settings::get( 'template_editor' ) ) { wp_die( esc_html__( 'Template editing is disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); } }
 /** Remove selected core dashboard widgets from known contexts only. @return void */
 public function dashboard(): void {
  foreach ( Settings::get( 'dashboard' ) as $id ) {
   if ( 'welcome_panel' === $id ) { remove_action( 'welcome_panel', 'wp_welcome_panel' ); continue; }
   if ( ! in_array( $id, Schema::fields()['dashboard']['choices'], true ) ) { continue; }
   foreach ( array( 'normal', 'side' ) as $context ) { remove_meta_box( $id, 'dashboard', $context ); }
  }
 }
 /** Remove selected core toolbar nodes.
  * @param \WP_Admin_Bar $bar Current toolbar.
  * @return void
  */
 public function toolbar( $bar ): void {
  foreach ( Settings::get( 'adminbar' ) as $id ) { if ( in_array( $id, Schema::fields()['adminbar']['choices'], true ) ) { $bar->remove_node( $id ); } }
 }
 /** Customize only the core admin footer text with escaped plain text.
  * @param string $text Existing footer.
  * @return string
  */
 public function footer( string $text ): string {
  $custom = Settings::get( 'footer_text' );
  return '' !== $custom ? esc_html( $custom ) : ( Settings::get( 'footer' ) ? '' : $text );
 }
 /** Return a genuine 404 for disabled feed endpoints, with no deletion. @return void */
 public function feed_request(): void {
  if ( Settings::get( 'feeds' ) && is_feed() ) { wp_die( esc_html__( 'Feeds are disabled on this site.', 'site-essentials' ), '', array( 'response' => 404 ) ); }
 }
 /** Determine whether a post belongs to a selected comment-disabled type.
  * @param int $post_id Post identifier.
  * @return bool
  */
 private function comments_disabled( int $post_id ): bool { return in_array( get_post_type( $post_id ), Settings::get( 'comment_types' ), true ); }
 /** Disable new comments for selected content types.
  * @param bool $open Existing comment permission.
  * @param int $post_id Post identifier.
  * @return bool
  */
 public function comments_open( bool $open, int $post_id ): bool { return $this->comments_disabled( $post_id ) ? false : $open; }
 /** Hide selected existing comments in ordinary frontend comment templates.
  * @param array $comments Comments to display.
  * @param int $post_id Post identifier.
  * @return array
  */
 public function comments( array $comments, int $post_id ): array { return ! is_admin() && Settings::get( 'hide_comments' ) && $this->comments_disabled( $post_id ) ? array() : $comments; }
 /** Match visible comment counts to the optional hiding behavior.
  * @param int|string $count Existing count.
  * @param int $post_id Post identifier.
  * @return int|string
  */
 public function comments_number( $count, int $post_id ) { return ! is_admin() && Settings::get( 'hide_comments' ) && $this->comments_disabled( $post_id ) ? 0 : $count; }
 /** Keep the reply script only where core threaded replies can be used. @return void */
 public function comment_script(): void {
  if ( Settings::get( 'comment_reply' ) && ! ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) ) { wp_dequeue_script( 'comment-reply' ); }
 }
 /** Remove outgoing pingbacks to the same site's URL boundary.
  * @param array $links Pingback URLs, modified by reference.
  * @return void
  */
 public function self_pingbacks( array &$links ): void {
  if ( ! Settings::get( 'self_pingbacks' ) ) { return; }
  $home = wp_parse_url( home_url( '/' ) );
  foreach ( $links as $index => $url ) {
   $target = wp_parse_url( $url ); $base = trailingslashit( $home['path'] ?? '/' ); $path = $target['path'] ?? '/';
   if ( is_array( $target ) && strtolower( $target['host'] ?? '' ) === strtolower( $home['host'] ?? '' ) && ( rtrim( $path, '/' ) === rtrim( $base, '/' ) || str_starts_with( $path, $base ) ) ) { unset( $links[ $index ] ); }
  }
 }
 /** Remove Migrate only for public frontend visitors, retaining builder sessions.
  * @param \WP_Scripts $scripts Script registry.
  * @return void
  */
 public function jquery( $scripts ): void {
  if ( is_admin() || 'wp-login.php' === ( $GLOBALS['pagenow'] ?? '' ) || is_user_logged_in() || ! Settings::get( 'jquery_migrate' ) || empty( $scripts->registered['jquery'] ) ) { return; }
  $scripts->registered['jquery']->deps = array_values( array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) ) );
 }
 /** Set a supported polling interval without disabling Heartbeat.
  * @param array $settings Core heartbeat settings.
  * @return array
  */
 public function heartbeat( array $settings ): array {
  $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
  $editor = $screen && 'post' === $screen->base;
  $key = $editor ? 'heartbeat_editor' : ( is_admin() ? 'heartbeat_admin' : 'heartbeat_front' );
  $interval = Settings::get( $key );
  if ( $editor ) { $specific = Settings::get( 'heartbeat_types' )[ $screen->post_type ] ?? 0; if ( $specific ) { $interval = $specific; } }
  if ( $interval ) { $settings['interval'] = $interval; }
  return $settings;
 }
 /** Limit future revisions without running a database deletion task.
  * @param int $number Existing revision limit.
  * @param \WP_Post $post Current post.
  * @return int
  */
 public function revisions( int $number, $post ): int {
  $specific = Settings::get( 'revision_types' )[ $post->post_type ] ?? -1;
  $limit = $specific >= 0 ? $specific : Settings::get( 'revisions' );
  return $limit >= 0 ? ( $number >= 0 ? min( $number, $limit ) : $limit ) : $number;
 }
 /** Suppress only successful automatic plugin/theme update summaries.
  * @param bool $send Existing decision.
  * @param array $results Update results.
  * @return bool
  */
 public function update_email( bool $send, array $results ): bool {
  if ( ! Settings::get( 'update_emails' ) ) { return $send; }
  foreach ( $results as $result ) { if ( empty( $result->result ) || is_wp_error( $result->result ) ) { return $send; } }
  return false;
 }
 /** Suppress the administrator notification without changing user/reset messages.
  * @param array $email Notification data.
  * @return array
  */
 public function password_email( array $email ): array { if ( Settings::get( 'password_admin_email' ) ) { $email['to'] = ''; } return $email; }
}
