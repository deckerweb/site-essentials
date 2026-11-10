<?php
/** Optional, site-local maintenance access. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Grants visitor bypass without authenticating a user or modifying capabilities. */
final class MaintenanceAccess {
 const OPTION = 'site_essentials_maintenance_access';
 /** Connect guarded requests and independent administration. @return void */
 public function boot(): void {
  add_action( 'init', array( $this, 'exchange' ), 101 );
  add_filter( 'rest_post_dispatch', array( $this, 'rest_headers' ), 10, 1 );
  add_filter( 'rest_pre_dispatch', array( $this, 'rest' ), 99, 3 );
  add_action( 'admin_post_se_maintenance_access', array( $this, 'save' ) );
  add_action( 'admin_footer', array( $this, 'form' ) );
 }
 /** Normalize bounded operational choices; never part of portable settings. @return array */
 public static function state(): array {
  $s = get_option( self::OPTION, array() ); $s = is_array( $s ) ? $s : array();
  return array( 'roles' => array_values( array_intersect( array_keys( wp_roles()->roles ), is_array( $s['roles'] ?? null ) ? array_filter( $s['roles'], 'is_string' ) : array() ) ), 'rest' => true === ( $s['rest'] ?? false ), 'hash' => is_string( $s['hash'] ?? null ) && preg_match( '/^[a-f0-9]{64}$/D', $s['hash'] ) ? $s['hash'] : '', 'expires' => is_int( $s['expires'] ?? null ) ? $s['expires'] : 0 );
 }
 /** Cookie name bound to this website, including subdirectory networks. @return string */
 public static function cookie(): string { return 'se_maintenance_' . get_current_blog_id(); }
 /** Check selected staff roles or a signed, expiring visitor grant. @return bool */
 public static function allowed(): bool {
  if ( current_user_can( 'manage_options' ) ) { return true; }
  $s = self::state();
  if ( is_user_logged_in() && array_intersect( wp_get_current_user()->roles, $s['roles'] ) ) { return true; }
  $cookie = ( isset( $_COOKIE[ self::cookie() ] ) && is_string( $_COOKIE[ self::cookie() ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::cookie() ] ) ) : '' );
  if ( ! is_string( $cookie ) || strlen( $cookie ) > 160 || ! $s['hash'] || $s['expires'] <= time() ) { return false; }
  $parts = explode( '.', $cookie );
  return count( $parts ) === 2 && ctype_digit( $parts[0] ) && (int) $parts[0] <= $s['expires'] && (int) $parts[0] > time() && hash_equals( self::signature( $parts[0], $s['hash'] ), $parts[1] );
 }
 /** Sign a grant with WordPress salts and site identity.
  * @param string $expiry Expiry timestamp.
  * @param string $hash Current token digest, used as revocable generation.
  * @return string HMAC, not the private link token.
  */
 private static function signature( string $expiry, string $hash ): string { return hash_hmac( 'sha256', get_current_blog_id() . ':' . $expiry . ':' . $hash, wp_salt( 'auth' ) ); }
 /** Exchange a high-entropy link once per browser visit and remove it from the URL.
  * @return void Redirects only valid requests for the current website.
  */
 public function exchange(): void {
  $token = isset( $_GET['se_maintenance_key'] ) && is_string( $_GET['se_maintenance_key'] ) ? sanitize_text_field( wp_unslash( $_GET['se_maintenance_key'] ) ) : null;
  if ( null === $token && self::allowed() && Maintenance::active() && isset( $_COOKIE[ self::cookie() ] ) ) { Maintenance::prevent_cache(); nocache_headers(); }
  if ( null === $token || is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return; }
  Maintenance::prevent_cache();
  nocache_headers(); header( 'Referrer-Policy: no-referrer' );
  $s = self::state();
  if ( ! self::maintenance_available() || ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) || ! $s['hash'] || $s['expires'] <= time() || ! hash_equals( $s['hash'], hash( 'sha256', $token ) ) ) { wp_die( esc_html__( 'This private maintenance link is invalid or has expired.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $expiry = (string) min( $s['expires'], time() + DAY_IN_SECONDS );
  setcookie( self::cookie(), $expiry . '.' . self::signature( $expiry, $s['hash'] ), array( 'expires' => (int) $expiry, 'path' => wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
  wp_safe_redirect( home_url( '/' ) ); exit;
 }
 /** A private bypass belongs only to this provider's active maintenance. @return bool */
 private static function maintenance_available(): bool { return Maintenance::active(); }
 /** Optional REST restriction, preserving previous responses and authenticated integrations.
  * @param mixed $result Earlier dispatch result, never replaced.
  * @param \WP_REST_Server $server Native server.
  * @param \WP_REST_Request $request Native request.
  * @return mixed Original result or HTTP 503 error.
  */
 public function rest( $result, $server, $request ) {
  if ( null !== $result || ! Maintenance::active() || ! self::state()['rest'] || is_user_logged_in() || self::allowed() ) { return $result; }
  return new \WP_Error( 'se_maintenance', __( 'The website is temporarily undergoing maintenance.', 'site-essentials' ), array( 'status' => 503 ) );
 }
 /** Attach maintenance headers only to this module's REST refusal.
  * @param \WP_REST_Response $response Converted native response.
  * @return \WP_REST_Response Original response with retry/cache headers when applicable.
  */
 public function rest_headers( $response ) {
  if ( $response instanceof \WP_REST_Response && 503 === $response->get_status() && 'se_maintenance' === ( $response->get_data()['code'] ?? '' ) ) { $response->header( 'Retry-After', '600' ); $response->header( 'Cache-Control', 'no-store' ); }
  return $response;
 }
 /** Print separate controls without nesting forms. @return void */
 public static function panel(): void {
  $s = self::state();
  echo '<div class="se-card"><h3>' . esc_html__( 'Access during maintenance', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Administrators always retain access. Selected logged-in roles may also view the website. This does not grant editing permissions.', 'site-essentials' ) . '</p><fieldset><legend>' . esc_html__( 'Additional staff roles', 'site-essentials' ) . '</legend>';
  foreach ( wp_roles()->roles as $role => $data ) { if ( 'administrator' === $role ) { continue; } echo '<p><label><input form="se-maintenance-access" type="checkbox" name="roles[]" value="' . esc_attr( $role ) . '" ' . checked( in_array( $role, $s['roles'], true ), true, false ) . '> ' . esc_html( translate_user_role( $data['name'] ) ) . '</label></p>'; }
  echo '</fieldset><p><label><input form="se-maintenance-access" type="checkbox" name="rest" value="1" ' . checked( $s['rest'], true, false ) . '> ' . esc_html__( 'Restrict anonymous REST requests during maintenance', 'site-essentials' ) . '</label></p><p class="description">' . esc_html__( 'Off by default. Can interrupt forms, shops, webhooks and headless clients. Authenticated requests and valid private-link visitors remain allowed; native permissions still apply. This is not a complete privacy barrier. AJAX and XML-RPC remain unchanged.', 'site-essentials' ) . '</p><button form="se-maintenance-access" class="button" name="operation" value="save">' . esc_html__( 'Save maintenance access', 'site-essentials' ) . '</button><h4>' . esc_html__( 'Private visitor link', 'site-essentials' ) . '</h4><p>' . esc_html__( 'A private link grants temporary viewing access, not a login. Share only with trusted reviewers. Creating a new link or revoking it also invalidates existing browser grants. The initial URL may be recorded by servers or browser history. Page caches must bypass visitors with the maintenance cookie.', 'site-essentials' ) . '</p>';
  if ( $s['hash'] && $s['expires'] > time() ) { echo '<p>' . esc_html__( 'Current link expires:', 'site-essentials' ) . ' ' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $s['expires'] ) ) . '</p>'; }
  $key = 'se_maintenance_reveal_' . get_current_user_id(); $reveal = get_transient( $key );
  if ( is_string( $reveal ) && preg_match( '/^[a-f0-9]{64}$/D', $reveal ) && hash_equals( $s['hash'], hash( 'sha256', $reveal ) ) ) { echo '<p><label>' . esc_html__( 'Copy this link now; it is shown briefly only to your account.', 'site-essentials' ) . '<input id="se-private-link" class="large-text" readonly value="' . esc_attr( add_query_arg( 'se_maintenance_key', $reveal, home_url( '/' ) ) ) . '"></label><button type="button" class="button" data-se-copy-private>' . esc_html__( 'Copy link', 'site-essentials' ) . '</button><span id="se-private-copy-status" role="status"></span></p>'; }
  echo '<p><label>' . esc_html__( 'Link lifetime', 'site-essentials' ) . ' <select name="hours" form="se-maintenance-access"><option value="1">1 h</option><option value="24" selected>24 h</option><option value="72">72 h</option></select></label></p><p><button form="se-maintenance-access" class="button" name="operation" value="rotate">' . esc_html__( 'Create / replace private link', 'site-essentials' ) . '</button> <button form="se-maintenance-access" class="button" name="operation" value="revoke">' . esc_html__( 'Revoke private link', 'site-essentials' ) . '</button></p><p class="description">' . esc_html__( 'Access choices and private links remain local to this website and are not exported.', 'site-essentials' ) . '</p></div>';
 }
 /** Render an independent revision-protected action form. @return void */
 public function form(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() || ( get_current_screen()->id ?? '' ) !== 'settings_page_site-essentials' ) { return; }
  echo '<form id="se-maintenance-access" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="se_maintenance_access"><input type="hidden" name="revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( self::state() ) ) ) . '">'; wp_nonce_field( 'se_maintenance_access_' . get_current_blog_id() ); echo '</form>';
 }
 /** Validate explicit access changes, token rotation and revocation. @return void */
 public function save(): void {
  if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || ! current_user_can( 'manage_options' ) || is_network_admin() ) { wp_die( esc_html__( 'Permission denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( ! is_string( $_REQUEST['_wpnonce'] ?? null ) ) { wp_die( esc_html__( 'Permission denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( 'se_maintenance_access_' . get_current_blog_id() ); WriteLock::require_lock(); $s = self::state();
  if ( ! is_string( $_POST['revision'] ?? null ) || ! hash_equals( hash( 'sha256', wp_json_encode( $s ) ), sanitize_text_field( wp_unslash( $_POST['revision'] ) ) ) ) { wp_die( esc_html__( 'Maintenance settings changed. Reload before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $op = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : ''; $roles = isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['roles'] ) ) : ( isset( $_POST['roles'] ) ? null : array() );
  if ( ! is_array( $roles ) || count( $roles ) > 100 || array_filter( $roles, static fn( $r ) => ! is_string( $r ) || ! isset( wp_roles()->roles[ $r ] ) ) || ( isset( $_POST['rest'] ) && '1' !== $_POST['rest'] ) ) { wp_die( esc_html__( 'Invalid maintenance settings.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  if ( 'save' === $op ) { $s['roles'] = array_values( array_unique( $roles ) ); $s['rest'] = isset( $_POST['rest'] ); }
  elseif ( 'rotate' === $op && in_array( $_POST['hours'] ?? '', array( '1', '24', '72' ), true ) ) { $token = bin2hex( random_bytes( 32 ) ); $s['hash'] = hash( 'sha256', $token ); $s['expires'] = time() + HOUR_IN_SECONDS * (int) $_POST['hours']; set_transient( 'se_maintenance_reveal_' . get_current_user_id(), $token, 5 * MINUTE_IN_SECONDS ); }
  elseif ( 'revoke' === $op ) { $s['hash'] = ''; $s['expires'] = 0; delete_transient( 'se_maintenance_reveal_' . get_current_user_id() ); }
  else { wp_die( esc_html__( 'Invalid maintenance settings.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  update_option( self::OPTION, $s, false ); if ( self::state() !== $s ) { wp_die( esc_html__( 'Settings could not be saved.', 'site-essentials' ), '', array( 'response' => 500 ) ); } wp_safe_redirect( admin_url( 'options-general.php?page=site-essentials&se_maintenance_saved=1#se-maintenance' ) ); exit;
 }
}
