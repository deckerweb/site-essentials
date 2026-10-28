<?php
/** One site-local dashboard information widget. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;
/** Keeps contact content, role visibility and personal grants out of generic presets. */
final class DashboardInfo {
 /** Limit UTF-8 content by bytes without requiring mbstring.
  * @param string $body Raw widget content.
  * @return string
  */
 private static function limit_body( string $body ): string {
  $body = substr( wp_check_invalid_utf8( $body ), 0, 50000 );
  while ( '' !== $body && 1 !== preg_match( '//u', $body ) ) {
   $body = substr( $body, 0, -1 );
  }
  return $body;
 }
 const OPTION = 'site_essentials_dashboard_info';
 /** Register native rendering and administrator-only actions. @return void */
 public function boot(): void {
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
  add_action( 'admin_post_site_essentials_widget_save', array( $this, 'save' ) );
  add_action( 'admin_post_site_essentials_widget_export', array( $this, 'export' ) );
  add_action( 'admin_post_site_essentials_widget_import', array( $this, 'import' ) );
 }
 /** Load small widget-content styles only on an eligible local dashboard.
  * @param string $hook Native screen suffix.
  * @return void
  */
 public function assets( string $hook ): void { if ( 'index.php' === $hook && self::visible() ) { wp_enqueue_style( 'se-dashboard-info', plugins_url( 'assets/dashboard-info.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_ASSET_VERSION ); } }
 /** Allow only simple formatting; no images, embeds, scripts, styles or forms. @return array */
 public static function allowed(): array { return array( 'p' => array(), 'br' => array(), 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'a' => array( 'href' => true, 'title' => true ) ); }
 /** Normalize a small configuration; malformed input fails closed.
  * @param array $raw Incoming local configuration.
  * @return array Normalized data with default-off visibility.
  */
 public static function normalize( array $raw ): array {
  $roles = array(); foreach ( array_slice( is_array( $raw['roles'] ?? null ) ? $raw['roles'] : array(), 0, 100 ) as $role ) { if ( is_string( $role ) && '' !== $role && strlen( $role ) <= 191 && ! preg_match( '/[\x00-\x1F\x7F]/', $role ) ) { $roles[] = $role; } }
  $users = array(); foreach ( array_slice( is_array( $raw['users'] ?? null ) ? $raw['users'] : array(), 0, 100 ) as $id ) { if ( ( is_int( $id ) || ( is_string( $id ) && ctype_digit( $id ) ) ) && (int) $id > 0 ) { $users[] = (int) $id; } }
  return array( 'enabled' => in_array( $raw['enabled'] ?? false, array( true, 1, '1' ), true ), 'title' => is_string( $raw['title'] ?? null ) ? sanitize_text_field( mb_substr( $raw['title'], 0, 120 ) ) : '', 'body' => is_string( $raw['body'] ?? null ) ? wp_kses( self::limit_body( $raw['body'] ), self::allowed(), array( 'http', 'https', 'mailto', 'tel' ) ) : '', 'roles' => array_values( array_unique( $roles ) ), 'users' => array_values( array_unique( $users ) ) );
 }
 /** Read only the current website's configuration. @return array */
 public static function state(): array { $raw = get_option( self::OPTION, array() ); return self::normalize( is_array( $raw ) ? $raw : array() ); }
 /** Apply additive roles/personal grants without changing capabilities.
  * @param int $id User ID; zero means current user.
  * @return bool No audience selected means hidden for everyone.
  */
 public static function visible( int $id = 0 ): bool {
  $id = $id ?: get_current_user_id(); $s = self::state(); $user = get_userdata( $id );
  return ! is_network_admin() && $s['enabled'] && $user && $user->exists() && user_can( $user, 'read' ) && ( ! is_multisite() || is_user_member_of_blog( $id ) ) && ( (bool) array_intersect( $s['roles'], $user->roles ) || in_array( $id, $s['users'], true ) );
 }
 /** Add one native dashboard widget only for its audience. @return void */
 public function register(): void { if ( self::visible() ) { $s = self::state(); wp_add_dashboard_widget( 'site_essentials_information', $s['title'] ?: __( 'Site information', 'site-essentials' ), array( $this, 'render' ) ); } }
 /** Check visibility again immediately before rendering sanitized content. @return void */
 public function render(): void { if ( self::visible() ) { echo '<div class="se-widget-body">' . wp_kses( wpautop( self::state()['body'] ), self::allowed(), array( 'http', 'https', 'mailto', 'tel' ) ) . '</div>'; } }
 /** Associate only this native editor with its own form, including without JavaScript.
  * @param string $markup Native editor HTML template, before WordPress inserts escaped content.
  * @return string Template with a constant form attribute; output remains owned by wp_editor().
  */
 public static function editor_markup( string $markup ): string { return str_contains( $markup, 'id="se-widget-body"' ) ? str_replace( '<textarea ', '<textarea form="se-widget-save-form" ', $markup ) : $markup; }
 /** Render detached-form controls within the administration section. @return void */
 public static function panel(): void {
  $s = self::state(); $form = 'se-widget-save-form';
  echo '<div class="se-card se-local-panel" id="se-dashboard-panel"><h3>' . esc_html__( 'Dashboard information widget', 'site-essentials' ) . '</h3><p>' . esc_html__( 'One optional widget for contact details or short site instructions. Save this content separately. Nothing here is included in settings or preset exports.', 'site-essentials' ) . '</p><label class="se-choice"><input type="checkbox" name="widget_enabled" value="1" form="' . esc_attr( $form ) . '" ' . checked( $s['enabled'], true, false ) . '> ' . esc_html__( 'Show the information widget to the selected audience', 'site-essentials' ) . '</label><p><label for="se-widget-title">' . esc_html__( 'Widget title', 'site-essentials' ) . '</label><input class="widefat" type="text" id="se-widget-title" name="widget_title" maxlength="120" form="' . esc_attr( $form ) . '" value="' . esc_attr( $s['title'] ) . '"></p><label for="se-widget-body">' . esc_html__( 'Widget text', 'site-essentials' ) . '</label>';
  add_filter( 'the_editor', array( self::class, 'editor_markup' ) );
  try { wp_editor( $s['body'], 'se-widget-body', array( 'textarea_name' => 'widget_body', 'textarea_rows' => 7, 'media_buttons' => false, 'teeny' => true, 'tinymce' => array( 'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo', 'toolbar2' => '', 'content_style' => 'body ul,body ol{padding-inline-start:1.5em;margin-inline-start:0;margin-inline-end:0;list-style-position:outside}body ul{list-style-type:disc}body ol{list-style-type:decimal}body li{margin-block:.35em}body li>ul,body li>ol{margin-block:.35em}' ), 'quicktags' => array( 'buttons' => 'strong,em,link,ul,ol,li,close' ) ) ); } finally { remove_filter( 'the_editor', array( self::class, 'editor_markup' ) ); }
  echo '<p class="description">' . esc_html__( 'Simple text, bold, italic, lists and links. Users in the selected audience may hide the widget in Screen Options. An empty audience shows it to nobody.', 'site-essentials' ) . '</p><fieldset><legend>' . esc_html__( 'Allowed roles', 'site-essentials' ) . '</legend>';
  foreach ( wp_roles()->roles as $key => $role ) { echo '<label class="se-choice"><input type="checkbox" name="widget_roles[]" value="' . esc_attr( $key ) . '" form="' . esc_attr( $form ) . '" ' . checked( in_array( $key, $s['roles'], true ), true, false ) . '> ' . esc_html( translate_user_role( $role['name'] ) ) . '</label>'; }
  echo '</fieldset><fieldset><legend>' . esc_html__( 'Additional individual users', 'site-essentials' ) . '</legend><p>' . esc_html__( 'A matching role or a selected user is sufficient. Personal user selections stay on this website and are never exported.', 'site-essentials' ) . '</p><div id="se-widget-users">';
  foreach ( $s['users'] as $id ) { $user = get_userdata( $id ); if ( ! $user || ( is_multisite() && ! is_user_member_of_blog( $id ) ) ) { continue; } echo '<label class="se-choice"><input type="checkbox" name="widget_users[]" value="' . esc_attr( $id ) . '" form="' . esc_attr( $form ) . '" checked> ' . esc_html( $user->display_name . ' (' . $user->user_login . ')' ) . '</label>'; }
  echo '</div>';
  if ( current_user_can( 'list_users' ) ) { echo '<div class="se-control-row"><label for="se-widget-user-search">' . esc_html__( 'Find site users', 'site-essentials' ) . '</label><input type="search" id="se-widget-user-search"><button type="button" id="se-widget-user-find" class="button">' . esc_html__( 'Search', 'site-essentials' ) . '</button></div><div id="se-widget-user-results"></div>'; }
  echo '</fieldset><p class="se-control-row"><button type="submit" class="button" form="' . esc_attr( $form ) . '">' . esc_html__( 'Save dashboard information', 'site-essentials' ) . '</button> <button type="button" class="button" data-se-local-discard="se-widget-save-form">' . esc_html__( 'Discard widget changes', 'site-essentials' ) . '</button></p><p id="se-widget-status" role="status"></p><details><summary>' . esc_html__( 'Transfer the widget text separately', 'site-essentials' ) . '</summary><p>' . esc_html__( 'Only saved title and text are transferred. Activation, roles and individual users are excluded. Check contact details before using them on another site.', 'site-essentials' ) . '</p><label class="se-choice"><input type="checkbox" name="confirm_content" value="1" form="se-widget-export-form" required> ' . esc_html__( 'Explicitly include the saved title and text', 'site-essentials' ) . '</label><button type="submit" class="button" form="se-widget-export-form">' . esc_html__( 'Export widget text', 'site-essentials' ) . '</button><p><label for="se-widget-import">' . esc_html__( 'Widget text JSON', 'site-essentials' ) . '</label><textarea id="se-widget-import" name="widget_json" rows="4" maxlength="65536" form="se-widget-import-form"></textarea></p><label class="se-choice"><input type="checkbox" name="confirm_content" value="1" form="se-widget-import-form" required> ' . esc_html__( 'Replace only the saved widget title and text', 'site-essentials' ) . '</label><button type="submit" class="button" form="se-widget-import-form">' . esc_html__( 'Import widget text', 'site-essentials' ) . '</button></details></div>';
 }
 /** Save bounded local content and audience after nonce/concurrency validation. @return never */
 public function save(): never {
  SitePanels::authorize( 'site_essentials_widget_save', self::OPTION );
  $title = SitePanels::input( 'widget_title' ); $body = SitePanels::input( 'widget_body' ); $roles = filter_input( INPUT_POST, 'widget_roles', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY ) ?? array(); $users = filter_input( INPUT_POST, 'widget_users', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY ) ?? array();
  if ( strlen( $title ) > 1000 || strlen( $body ) > 50000 || ! is_array( $roles ) || count( $roles ) > 100 || ! is_array( $users ) || count( $users ) > 100 ) { self::invalid(); }
  $s = self::normalize( array( 'enabled' => SitePanels::input( 'widget_enabled' ), 'title' => $title, 'body' => $body, 'roles' => $roles, 'users' => $users ) );
  $old = self::state(); if ( ! current_user_can( 'list_users' ) && $s['users'] !== $old['users'] ) { wp_die( esc_html__( 'You cannot change the selected users.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  foreach ( $s['users'] as $id ) { if ( ! get_userdata( $id ) || ( is_multisite() && ! is_user_member_of_blog( $id ) ) ) { self::invalid(); } }
  SitePanels::authorize( 'site_essentials_widget_save', self::OPTION );
  SitePanels::store( self::OPTION, $s ); SitePanels::redirect( 'widget_saved', 'dashboard' );
 }
 /** Export only explicitly confirmed, saved title and text. @return never */
 public function export(): never {
  SitePanels::authorize( 'site_essentials_widget_export', self::OPTION ); if ( '1' !== SitePanels::input( 'confirm_content' ) ) { self::invalid(); }
  nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="site-essentials-widget-text.json"' );
  echo wp_json_encode( self::portable(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); exit;
 }
 /** Build the intentionally separate content-only export. @return array */
 public static function portable(): array { $s = self::state(); return array( 'plugin' => 'site-essentials-widget', 'schema' => 1, 'title' => $s['title'], 'body' => $s['body'] ); }
 /** Validate and sanitize content-only imports without accepting audience keys.
  * @param string $json JSON entered by an administrator.
  * @return array|\WP_Error Sanitized title/body or an error.
  */
 public static function parse( string $json ) {
  $x = strlen( $json ) <= 65536 ? json_decode( $json, true, 8 ) : null;
  if ( ! is_array( $x ) || count( $x ) !== 4 || array_diff( array_keys( $x ), array( 'plugin', 'schema', 'title', 'body' ) ) || 'site-essentials-widget' !== ( $x['plugin'] ?? '' ) || 1 !== ( $x['schema'] ?? null ) || ! is_string( $x['title'] ?? null ) || mb_strlen( $x['title'] ) > 120 || ! is_string( $x['body'] ?? null ) || strlen( $x['body'] ) > 50000 ) { return new \WP_Error( 'widget', __( 'Use a valid widget-text export. Visibility cannot be imported.', 'site-essentials' ) ); }
  $s = self::normalize( $x ); return array( 'title' => $s['title'], 'body' => $s['body'] );
 }
 /** Import confirmed content while retaining all current visibility decisions. @return never */
 public function import(): never {
  SitePanels::authorize( 'site_essentials_widget_import', self::OPTION ); if ( '1' !== SitePanels::input( 'confirm_content' ) ) { self::invalid(); }
  $content = self::parse( SitePanels::input( 'widget_json' ) ); if ( is_wp_error( $content ) ) { self::invalid(); }
  SitePanels::authorize( 'site_essentials_widget_import', self::OPTION );
  SitePanels::store( self::OPTION, array_replace( self::state(), $content ) ); SitePanels::redirect( 'widget_imported', 'dashboard' );
 }
 /** Reject malformed or unconfirmed local operations. @return never */
 private static function invalid(): never { wp_die( esc_html__( 'Check the widget input and explicit transfer confirmation.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
}
