<?php
/** Native Site Health administration and cached dashboard overview. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Keeps native checks authoritative and omissions transparent. */
final class Health120 {
 const OPTION = 'site_essentials_health';
 const RESULTS = 'site_essentials_health_results';
 private static array $tests = array();
 private static array $baseline = array();
 /** Register lazy integrations; no checks are run here. @return void */
 public function boot(): void {
  add_filter( 'site_status_tests', array( $this, 'tests' ), PHP_INT_MAX );
  add_filter( 'rest_request_after_callbacks', array( $this, 'rest_result' ), 99, 3 );
  add_filter( 'site_status_test_result', array( $this, 'result' ), 99 );
  add_filter( 'debug_information', array( $this, 'baseline' ), -PHP_INT_MAX );
  add_filter( 'debug_information', array( $this, 'information' ), PHP_INT_MAX );
  add_filter( 'pre_set_transient_health-check-site-status-result', array( $this, 'capture_summary' ), PHP_INT_MAX );
  add_action( 'wp_dashboard_setup', array( $this, 'dashboard' ), 100 );
  add_action( 'admin_post_se_health', array( $this, 'save' ) );
  add_action( 'admin_notices', array( $this, 'notice' ) );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'admin_footer', array( $this, 'form' ) );
  add_action( 'core_upgrade_preamble', array( $this, 'translations' ) );
 }
 /** Read bounded site-local exclusions and optional reasons. @return array */
 public static function state(): array {
  $s = get_option( self::OPTION, array() ); $s = is_array( $s ) ? $s : array();
  $out = array( 'disabled' => array(), 'private' => array(), 'reason' => '' );
  foreach ( array( 'disabled', 'private' ) as $kind ) { if ( is_array( $s[ $kind ] ?? null ) ) { $out[ $kind ] = array_values( array_filter( array_slice( $s[ $kind ], 0, 300 ), static fn( $v ) => is_string( $v ) && strlen( $v ) <= 200 ) ); } }
  if ( is_string( $s['reason'] ?? null ) ) { $out['reason'] = wp_html_excerpt( sanitize_textarea_field( $s['reason'] ), 500, '' ); }
  return $out;
 }
 /** Attribute only executable callbacks by their real source file.
  * @param mixed $callback Registered callable, not executed.
  * @return string Reliable source label or empty.
  */
 public static function origin( $callback ): string {
  try {
   if ( is_array( $callback ) && count( $callback ) === 2 ) { $r = new \ReflectionMethod( $callback[0], $callback[1] ); }
   elseif ( $callback instanceof \Closure || ( is_string( $callback ) && function_exists( $callback ) ) ) { $r = new \ReflectionFunction( $callback ); }
   else { return ''; }
   $file = $r->getFileName(); if ( ! $file ) { return ''; } $file = wp_normalize_path( realpath( $file ) ?: $file );
   foreach ( array( WP_PLUGIN_DIR => __( 'Plugin', 'site-essentials' ), WPMU_PLUGIN_DIR => __( 'Must-use plugin', 'site-essentials' ), get_theme_root() => __( 'Theme', 'site-essentials' ) ) as $root => $label ) {
    $root = trailingslashit( wp_normalize_path( realpath( $root ) ?: $root ) );
    if ( str_starts_with( $file, $root ) ) { $relative = substr( $file, strlen( $root ) ); return $label . ': ' . sanitize_text_field( explode( '/', $relative )[0] ); }
   }
   foreach ( array( ABSPATH . WPINC, ABSPATH . 'wp-admin' ) as $root ) { if ( str_starts_with( $file, trailingslashit( wp_normalize_path( $root ) ) ) ) { return 'WordPress Core'; } }
  } catch ( \ReflectionException $error ) { return ''; }
  return '';
 }
 /** Cache the registry for management and remove only explicit selected checks.
  * @param array $tests Native direct/async registry.
  * @return array Registry with selected checks omitted.
  */
 public function tests( array $tests ): array {
  self::$tests = $tests; $disabled = self::state()['disabled'];
  foreach ( array( 'direct', 'async' ) as $kind ) { foreach ( $tests[ $kind ] ?? array() as $id => $test ) { if ( in_array( $kind . ':' . $id, $disabled, true ) ) { unset( $tests[ $kind ][ $id ] ); } } }
  return $tests;
 }
 /** Decorate proven result origins without replacing category badges.
  * @param mixed $result Native test result.
  * @return mixed Unmodified result except optional explanatory text.
  */
 public function result( $result ) {
  if ( ! is_array( $result ) ) { return $result; }
  if ( Settings::get( 'health_widget' ) && 'scheduled_events' === ( $result['test'] ?? '' ) && in_array( $result['status'] ?? '', array( 'good', 'recommended', 'critical' ), true ) ) { update_option( self::RESULTS . '_tasks', array( 'time' => time(), 'status' => $result['status'] ), false ); }
  if ( ! Settings::get( 'health_origins' ) ) { return $result; }
  $id = $result['test'] ?? ''; $origins = array();
  foreach ( self::$tests as $kind => $items ) { foreach ( $items as $key => $test ) { if ( $key !== $id && ( $test['test'] ?? null ) !== $id ) { continue; } $callback = $test['test'] ?? null; if ( is_string( $callback ) && method_exists( '\WP_Site_Health', 'get_test_' . $callback ) ) { $callback = array( '\WP_Site_Health', 'get_test_' . $callback ); } $origin = self::origin( $callback ); if ( $origin ) { $origins[ $origin ] = true; } } }
  if ( count( $origins ) === 1 ) { $result['description'] = '<p class="se-health-origin" data-se-source="' . esc_attr( array_key_first( $origins ) ) . '"><small>' . esc_html__( 'Source:', 'site-essentials' ) . ' ' . esc_html( array_key_first( $origins ) ) . '</small></p>' . ( $result['description'] ?? '' ); }
  return $result;
 }
 /** Label native asynchronous REST results from their actual registered handler.
  * @param mixed $response Native response or error.
  * @param array $handler Matched endpoint definition.
  * @param \WP_REST_Request $request Current request.
  * @return mixed Original response with optional source text.
  */
 public function rest_result( $response, array $handler, $request ) {
  if ( ! Settings::get( 'health_origins' ) || ! current_user_can( 'view_site_health_checks' ) || is_wp_error( $response ) || ! str_starts_with( $request->get_route(), '/wp-site-health/' ) ) { return $response; }
  $data = $response instanceof \WP_REST_Response ? $response->get_data() : $response;
  $source = self::origin( $handler['callback'] ?? null );
  if ( $source && is_array( $data ) && isset( $data['test'], $data['description'], $data['status'] ) ) { $data['description'] = '<p class="se-health-origin" data-se-source="' . esc_attr( $source ) . '"><small>' . esc_html__( 'Source:', 'site-essentials' ) . ' ' . esc_html( $source ) . '</small></p>' . $data['description']; if ( $response instanceof \WP_REST_Response ) { $response->set_data( $data ); } else { $response = $data; } }
  return $response;
 }
 /** Remember native information before plugin filters; never label later-added fields as core.
  * @param array $info Initial native fields.
  * @return array Original information.
  */
 public function baseline( array $info ): array {
  self::$baseline = $info;
  foreach ( $GLOBALS['wp_filter']['debug_information']->callbacks ?? array() as $priority => $callbacks ) { if ( $priority > -PHP_INT_MAX ) { break; } foreach ( $callbacks as $callback ) { $fn = $callback['function']; if ( is_array( $fn ) && $fn[0] instanceof self && 'baseline' === $fn[1] ) { return $info; } self::$baseline = array(); return $info; } }
  return $info;
 }
 /** Match a new report section only to a unique installed name and active plugin slug.
  * This is a conservative metadata match, not execution tracing. No external request is made.
  * @return array<string,string> Section IDs with sufficiently corroborated plugin metadata.
  */
 private static function plugin_sections(): array {
  if ( ! function_exists( 'get_plugins' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
  $plugins = get_plugins(); $names = array(); $slugs = array(); $matches = array();
  $active = get_option( 'active_plugins', array() ); $active = is_array( $active ) ? $active : array();
  if ( is_multisite() ) { $network = get_site_option( 'active_sitewide_plugins', array() ); $active = array_merge( $active, is_array( $network ) ? array_keys( $network ) : array() ); }
  foreach ( $plugins as $file => $plugin ) {
   $name = trim( html_entity_decode( wp_strip_all_tags( $plugin['Name'] ?? '' ), ENT_QUOTES, 'UTF-8' ) );
   $slug = '.' === dirname( $file ) ? basename( $file, '.php' ) : dirname( $file );
   $names[ $name ] = ( $names[ $name ] ?? 0 ) + 1; $slugs[ $slug ] = ( $slugs[ $slug ] ?? 0 ) + 1;
  }
  foreach ( $active as $file ) {
   if ( ! is_string( $file ) || ! isset( $plugins[ $file ] ) ) { continue; }
   $name = trim( html_entity_decode( wp_strip_all_tags( $plugins[ $file ]['Name'] ?? '' ), ENT_QUOTES, 'UTF-8' ) );
   $slug = '.' === dirname( $file ) ? basename( $file, '.php' ) : dirname( $file );
   if ( '' !== $name && 1 === $names[ $name ] && 1 === $slugs[ $slug ] ) { $matches[ $slug ] = $name; }
  }
  return $matches;
 }
 /** Exclude selections only from clipboard export; all fields remain visible.
  * @param array $info Final information registry.
  * @return array Decorated information with private flags.
  */
 public function information( array $info ): array {
  $selected = self::state()['private']; $omitted = 0; $sources = array();
  $plugins = Settings::get( 'health_origins' ) ? self::plugin_sections() : array();
  foreach ( $info as $section => &$data ) {
   if ( ! is_array( $data ) ) { continue; }
   $original = self::$baseline[ $section ] ?? null;
   $native = is_array( $original ) && ( $original['label'] ?? null ) === ( $data['label'] ?? null ) && ( $original['fields'] ?? array() ) === ( $data['fields'] ?? array() );
   if ( Settings::get( 'health_origins' ) ) {
    if ( $native ) { $sources[ $section ] = 'WordPress Core'; }
    elseif ( 'site-essentials' === $section ) { $sources[ $section ] = __( 'Plugin', 'site-essentials' ); }
    elseif ( null === $original && isset( $plugins[ $section ] ) && $plugins[ $section ] === trim( html_entity_decode( wp_strip_all_tags( $data['label'] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) ) { $sources[ $section ] = __( 'Plugin', 'site-essentials' ); }
   }
   if ( in_array( $section, $selected, true ) ) { $data['private'] = true; ++$omitted; }
   foreach ( $data['fields'] ?? array() as $key => $field ) {
    if ( in_array( $section . ':' . $key, $selected, true ) ) { $data['fields'][ $key ]['private'] = true; ++$omitted; }
    if ( ! $native && Settings::get( 'health_origins' ) && isset( $original['fields'][ $key ] ) && $original['fields'][ $key ] === $field ) { $data['fields'][ $key ]['label'] .= ' · WordPress Core'; }
   }
  } unset( $data );
  $fields = array( 'disabled' => array( 'label' => __( 'Disabled checks', 'site-essentials' ), 'value' => count( self::state()['disabled'] ) ), 'omitted' => array( 'label' => __( 'Selected report omissions', 'site-essentials' ), 'value' => $omitted ), 'notice' => array( 'label' => __( 'Report scope', 'site-essentials' ), 'value' => __( 'Some information may be excluded from the copied report. This is not a complete anonymization.', 'site-essentials' ) ) );
  if ( ! isset( $info['site-essentials'] ) ) { $info['site-essentials'] = array( 'label' => 'Site Essentials', 'fields' => array() ); }
  foreach ( $fields as $key => $field ) {
   // Retain omissions selected before these fields joined the existing section.
   if ( in_array( 'site-essentials-health', $selected, true ) || in_array( 'site-essentials-health:' . $key, $selected, true ) || in_array( 'site-essentials', $selected, true ) || in_array( 'site-essentials:health_' . $key, $selected, true ) ) { $field['private'] = true; }
   $info['site-essentials']['fields'][ 'health_' . $key ] = $field;
  }
  if ( Settings::get( 'health_origins' ) && wp_script_is( 'se-health-origins', 'enqueued' ) ) { $sources['site-essentials'] = __( 'Plugin', 'site-essentials' ); wp_add_inline_script( 'se-health-origins', 'window.seHealthSources = ' . wp_json_encode( $sources ) . ';', 'before' ); }
  return $info;
 }
 /** Capture every completed native submission, even when its counts are unchanged.
  * WordPress skips the post-save transient action when update_option changes nothing.
  * @param mixed $value Native summary JSON, returned unchanged.
  * @return mixed Original transient value.
  */
 public function capture_summary( $value ) { $this->summary( $value ); return $value; }

 /** Timestamp only the native summary written after checks finish.
  * @param mixed $value Native JSON result counts.
  * @return void
  */
 public function summary( $value ): void {
  if ( ! Settings::get( 'health_widget' ) ) { return; }
  $counts = is_string( $value ) ? json_decode( $value, true ) : null;
  if ( ! is_array( $counts ) ) { return; }
  $clean = array(); foreach ( array( 'critical', 'recommended', 'good' ) as $key ) { if ( ! isset( $counts[ $key ] ) || ! is_numeric( $counts[ $key ] ) || $counts[ $key ] < 0 ) { return; } $clean[ $key ] = (int) $counts[ $key ]; }
  update_option( self::RESULTS, array( 'time' => time(), 'counts' => $clean, 'scope' => hash( 'sha256', wp_json_encode( self::state()['disabled'] ) ) ), false );
 }
 /** Render administrator management controls, associated with a separate form. @return void */
 public static function panel(): void {
  if ( ! current_user_can( 'view_site_health_checks' ) ) { echo '<p>' . esc_html__( 'Your account cannot view Site Health information.', 'site-essentials' ) . '</p>'; return; }
  if ( ! class_exists( '\WP_Site_Health' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php'; }
  \WP_Site_Health::get_tests(); $s = self::state();
  echo '<div class="se-card"><h3>' . esc_html__( 'Choose which checks run', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Disable check: This check will no longer run or contribute to the assessment. This does not fix the underlying cause.', 'site-essentials' ) . '</p><p>' . esc_html__( 'No checks are disabled by default. Uncheck a selection below and save to reactivate it.', 'site-essentials' ) . '</p><details><summary>' . esc_html__( 'Disabled checks', 'site-essentials' ) . ' (' . count( $s['disabled'] ) . ')</summary>';
  $known = array();
  foreach ( self::$tests as $kind => $items ) { foreach ( $items as $id => $test ) { $value = $kind . ':' . $id; $known[] = $value; echo '<p><label><input form="se-health-form" name="disabled[]" type="checkbox" value="' . esc_attr( $value ) . '" ' . checked( in_array( $value, $s['disabled'], true ), true, false ) . '> ' . esc_html( wp_strip_all_tags( $test['label'] ?? $id ) ) . ' <code>' . esc_html( $value ) . '</code></label></p>'; } }
  foreach ( array_diff( $s['disabled'], $known ) as $id ) { echo '<p><label><input form="se-health-form" name="disabled[]" type="checkbox" checked value="' . esc_attr( $id ) . '"> ' . esc_html( $id ) . ' — ' . esc_html__( 'Currently unavailable', 'site-essentials' ) . '</label></p>'; }
  echo '</details><p><label for="se-health-reason">' . esc_html__( 'Optional local reason (not exported)', 'site-essentials' ) . '</label></p><textarea form="se-health-form" id="se-health-reason" name="reason" maxlength="500" rows="2" class="large-text">' . esc_textarea( $s['reason'] ) . '</textarea><h4>' . esc_html__( 'Copied diagnostic report', 'site-essentials' ) . '</h4><p>' . esc_html__( 'Selected sections or fields stay visible in Site Health but are omitted when copying the report. Review every report before sharing it; other plugins may add sensitive data.', 'site-essentials' ) . '</p><details><summary>' . esc_html__( 'Choose report omissions', 'site-essentials' ) . ' (' . count( $s['private'] ) . ')</summary>';
  if ( ! class_exists( '\WP_Debug_Data' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php'; }
  $load = isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'se_health_fields_' . get_current_blog_id() );
  $info = $load ? \WP_Debug_Data::debug_data() : array(); $known = array();
  if ( ! $load ) { echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'options-general.php?page=site-essentials#se-health' ), 'se_health_fields_' . get_current_blog_id() ) ) . '">' . esc_html__( 'Load available report fields', 'site-essentials' ) . '</a></p><p class="description">' . esc_html__( 'Loads the native information labels on request. Existing omissions remain selected; opening other settings does not collect this information.', 'site-essentials' ) . '</p>'; }
  foreach ( $info as $section => $data ) { if ( 'site-essentials-health' === $section ) { continue; } $known[] = $section; echo '<details><summary><label><input form="se-health-form" type="checkbox" name="private[]" value="' . esc_attr( $section ) . '" ' . checked( in_array( $section, $s['private'], true ), true, false ) . '> ' . esc_html( $data['label'] ) . '</label></summary>';
   foreach ( $data['fields'] ?? array() as $key => $field ) { $id = $section . ':' . $key; $known[] = $id; echo '<p><label><input form="se-health-form" type="checkbox" name="private[]" value="' . esc_attr( $id ) . '" ' . checked( in_array( $id, $s['private'], true ), true, false ) . '> ' . esc_html( $field['label'] ) . '</label></p>'; } echo '</details>'; }
  foreach ( array_diff( $s['private'], $known ) as $id ) { echo '<p><label><input form="se-health-form" type="checkbox" checked name="private[]" value="' . esc_attr( $id ) . '"> ' . esc_html( $id ) . '</label></p>'; }
  echo '</details><p><button form="se-health-form" class="button button-primary">' . esc_html__( 'Save Site Health choices', 'site-essentials' ) . '</button></p><p><a href="' . esc_url( admin_url( 'site-health.php' ) ) . '">' . esc_html__( 'Open Site Health status', 'site-essentials' ) . '</a> · <a href="' . esc_url( admin_url( 'site-health.php?tab=debug' ) ) . '">' . esc_html__( 'Open Site Health information', 'site-essentials' ) . '</a></p></div>';
 }
 /** Independent nonce/revision form on this site's settings screen. @return void */
 public function form(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() || ( get_current_screen()->id ?? '' ) !== 'settings_page_site-essentials' ) { return; }
  echo '<form id="se-health-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="se_health"><input type="hidden" name="revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( self::state() ) ) ) . '">'; wp_nonce_field( 'se_health_' . get_current_blog_id() ); echo '</form>';
 }
 /** Save explicit site-local management choices with stale-form protection. @return void */
 public function save(): void {
  if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'view_site_health_checks' ) || is_network_admin() ) { wp_die( esc_html__( 'Permission denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( ! is_string( $_REQUEST['_wpnonce'] ?? null ) ) { wp_die( esc_html__( 'Permission denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( 'se_health_' . get_current_blog_id() );
  WriteLock::require_lock();
  if ( ! is_string( $_POST['revision'] ?? null ) || ! hash_equals( hash( 'sha256', wp_json_encode( self::state() ) ), sanitize_text_field( wp_unslash( $_POST['revision'] ) ) ) ) { wp_die( esc_html__( 'Settings changed. Reload before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $s = array(); foreach ( array( 'disabled', 'private' ) as $kind ) { $v = isset( $_POST[ $kind ] ) && is_array( $_POST[ $kind ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $kind ] ) ) : ( isset( $_POST[ $kind ] ) ? null : array() ); if ( ! is_array( $v ) || count( $v ) > 300 || array_filter( $v, static fn( $id ) => ! is_string( $id ) || strlen( $id ) > 200 || ! preg_match( '/^[a-zA-Z0-9_.:\/-]+$/D', $id ) ) ) { wp_die( esc_html__( 'Invalid settings.', 'site-essentials' ), '', array( 'response' => 400 ) ); } $s[ $kind ] = array_values( array_unique( $v ) ); }
  if ( ! is_string( $_POST['reason'] ?? '' ) || strlen( sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ) ) > 3000 ) { wp_die( esc_html__( 'Invalid settings.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  $s['reason'] = wp_html_excerpt( sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ), 500, '' ); update_option( self::OPTION, $s, false );
  if ( self::state() !== $s ) { wp_die( esc_html__( 'Settings could not be saved.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  set_transient( 'se_health_saved_' . get_current_user_id(), true, MINUTE_IN_SECONDS ); wp_safe_redirect( admin_url( 'options-general.php?page=site-essentials#se-health' ) ); exit;
 }
 /** Retain only status codes from an already performed SE page check, throttled to one hour.
  * @param array $status Existing WebsiteChecks result; no checks are started here.
  * @return void
  */
 public static function remember_pages( array $status ): void {
  if ( ! Settings::get( 'health_widget' ) ) { return; }
  $allowed = array( 'missing', 'ambiguous', 'unpublished', 'empty', 'review', 'present', 'confirmed' );
  $next = array(); foreach ( array( 'privacy', 'imprint' ) as $kind ) { $code = $status[ $kind ]['status'] ?? ''; if ( ! in_array( $code, $allowed, true ) ) { return; } $next[ $kind ] = $code; }
  $old = get_option( self::RESULTS . '_pages', array() );
  if ( ! is_array( $old ) || ( $old['privacy'] ?? '' ) !== $next['privacy'] || ( $old['imprint'] ?? '' ) !== $next['imprint'] || time() - (int) ( $old['time'] ?? 0 ) >= HOUR_IN_SECONDS ) { $next['time'] = time(); update_option( self::RESULTS . '_pages', $next, false ); }
 }
 /** Replace the native widget only for authorized administrators opting in. @return void */
 public function dashboard(): void {
  if ( ! Settings::get( 'health_widget' ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'view_site_health_checks' ) || is_network_admin() ) { return; }
  remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
  wp_add_dashboard_widget( 'se_site_health', __( 'Site Health at a glance', 'site-essentials' ), array( $this, 'widget' ), array( $this, 'configure' ) );
 }
 /** Show cached state, never run checks or update requests here. @return void */
 public function widget(): void {
  $saved = get_option( self::RESULTS, array() ); $valid = is_array( $saved ) && isset( $saved['time'], $saved['counts'], $saved['scope'] ) && is_array( $saved['counts'] ) && $saved['scope'] === hash( 'sha256', wp_json_encode( self::state()['disabled'] ) );
  echo '<ul>';
  if ( $valid ) { echo '<li><strong>' . esc_html__( 'Critical issues:', 'site-essentials' ) . ' ' . (int) ( $saved['counts']['critical'] ?? 0 ) . '</strong></li><li>' . esc_html__( 'Recommendations:', 'site-essentials' ) . ' ' . (int) ( $saved['counts']['recommended'] ?? 0 ) . '</li><li>' . esc_html__( 'Last native result:', 'site-essentials' ) . ' ' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $saved['time'] ) ) . ( time() - $saved['time'] > DAY_IN_SECONDS ? ' — ' . esc_html__( 'Outdated; open Site Health to refresh.', 'site-essentials' ) : '' ) . '</li>'; }
  else { echo '<li>' . esc_html__( 'No current complete Site Health summary is available. Open Site Health and let its checks finish.', 'site-essentials' ) . '</li>'; }
  foreach ( array( 'core' => 'update_core', 'plugins' => 'update_plugins', 'themes' => 'update_themes' ) as $kind => $cap ) { if ( ! current_user_can( $cap ) ) { continue; } $data = get_site_transient( 'update_' . $kind ); $count = 'core' === $kind ? ( is_object( $data ) && ! empty( $data->updates ) ? count( array_filter( $data->updates, static fn( $u ) => 'upgrade' === ( $u->response ?? '' ) ) ) : 0 ) : ( is_object( $data ) && is_array( $data->response ?? null ) ? count( $data->response ) : 0 ); echo '<li>' . esc_html( array( 'core' => __( 'WordPress updates', 'site-essentials' ), 'plugins' => __( 'Plugin updates', 'site-essentials' ), 'themes' => __( 'Theme updates', 'site-essentials' ) )[ $kind ] ) . ': ' . ( false === $data ? esc_html__( 'Not yet checked', 'site-essentials' ) : esc_html( (string) $count ) ) . '</li>'; }
  if ( current_user_can( 'update_languages' ) ) { echo '<li>' . esc_html__( 'Translation updates:', 'site-essentials' ) . ' ' . ( false !== get_site_transient( 'update_core' ) && false !== get_site_transient( 'update_plugins' ) && false !== get_site_transient( 'update_themes' ) ? count( wp_get_translation_updates() ) : esc_html__( 'Not yet checked', 'site-essentials' ) ) . '</li>'; }
  echo '<li>' . esc_html__( 'Maintenance:', 'site-essentials' ) . ' ' . esc_html( Maintenance::providers() ? implode( ', ', array_keys( Maintenance::providers() ) ) : ( Maintenance::active() ? __( 'Active', 'site-essentials' ) : __( 'Off', 'site-essentials' ) ) ) . '</li><li>' . esc_html__( 'Search engine preference:', 'site-essentials' ) . ' ' . esc_html( get_option( 'blog_public' ) ? __( 'Indexing allowed (not a confirmed indexing result)', 'site-essentials' ) : __( 'Indexing discouraged', 'site-essentials' ) ) . '</li><li><a href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-health' ) ) . '">' . esc_html__( 'Disabled checks', 'site-essentials' ) . ': ' . count( self::state()['disabled'] ) . '</a></li></ul>';
  $extras = self::extras();
  if ( in_array( 'versions', $extras, true ) ) { echo '<p>WordPress ' . esc_html( get_bloginfo( 'version' ) ) . ' · PHP ' . esc_html( PHP_VERSION ) . '</p>'; }
  if ( in_array( 'storage', $extras, true ) && Settings::get( 'storage_diagnosis' ) ) { $storage = get_option( Storage110::OPTION, array() ); echo '<p>' . esc_html__( 'Last upload measurement:', 'site-essentials' ) . ' ' . esc_html( 'complete' === ( $storage['status'] ?? '' ) && is_numeric( $storage['bytes'] ?? null ) ? size_format( $storage['bytes'] ) . ' · ' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $storage['time'] ) : __( 'Not available', 'site-essentials' ) ) . '</p>'; }
  if ( in_array( 'pages', $extras, true ) ) {
   $pages = get_option( self::RESULTS . '_pages', array() );
   if ( isset( $pages['time'], $pages['privacy'], $pages['imprint'] ) ) { $checks = new WebsiteChecks(); echo '<ul>'; foreach ( array( 'privacy', 'imprint' ) as $kind ) { echo '<li>' . esc_html( $checks->message( $kind, array( $kind => array( 'status' => $pages[ $kind ] ) ) ) ) . '</li>'; } echo '</ul><p class="description">' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $pages['time'] ) ) . '</p>'; } else { echo '<p>' . esc_html__( 'Not yet checked', 'site-essentials' ) . '</p>'; }
   echo '<p><a href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-checks' ) ) . '">' . esc_html__( 'Review privacy and imprint pages (no legal assessment)', 'site-essentials' ) . '</a></p>'; }
  if ( in_array( 'cron', $extras, true ) ) { $tasks = get_option( self::RESULTS . '_tasks', array() ); echo '<p>' . esc_html__( 'Scheduled tasks:', 'site-essentials' ) . ' ' . esc_html( isset( $tasks['status'], $tasks['time'] ) ? ( 'good' === $tasks['status'] ? __( 'No issue in the last native check', 'site-essentials' ) : __( 'Attention needed in the last native check', 'site-essentials' ) ) . ' · ' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $tasks['time'] ) : __( 'Not yet checked', 'site-essentials' ) ) . '</p>'; }
  echo '<p><a href="' . esc_url( admin_url( 'site-health.php' ) ) . '">' . esc_html__( 'View status and recommendations', 'site-essentials' ) . '</a> · <a href="' . esc_url( admin_url( 'site-health.php?tab=debug' ) ) . '">' . esc_html__( 'View technical information', 'site-essentials' ) . '</a></p><p class="description">' . esc_html__( 'Cached information, not live monitoring. Update availability follows the latest native WordPress check.', 'site-essentials' ) . '</p>';
 }
 /** Read personal extra rows, never export them.
  * @return array<string> Selected identifiers.
  */
 private static function extras(): array { $v = get_user_option( 'se_health_extras_' . get_current_blog_id() ); return is_array( $v ) ? array_values( array_intersect( $v, array( 'versions', 'storage', 'pages', 'cron' ) ) ) : array(); }
 /** Native widget configuration form, protected by the dashboard's nonce.
  * @return void
  */
 public function configure(): void {
  if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'view_site_health_checks' ) ) { return; }
  if ( 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) && isset( $_POST['se_health_extras_nonce'] ) ) { check_admin_referer( 'se_health_extras', 'se_health_extras_nonce' ); $v = isset( $_POST['se_health_extras'] ) && is_array( $_POST['se_health_extras'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['se_health_extras'] ) ) : array(); if ( is_array( $v ) && ! array_filter( $v, static fn( $id ) => ! is_string( $id ) ) ) { update_user_option( get_current_user_id(), 'se_health_extras_' . get_current_blog_id(), array_values( array_intersect( $v, array( 'versions', 'storage', 'pages', 'cron' ) ) ), true ); } }
  wp_nonce_field( 'se_health_extras', 'se_health_extras_nonce' ); foreach ( array( 'versions' => __( 'WordPress and PHP versions', 'site-essentials' ), 'storage' => __( 'Existing upload measurement', 'site-essentials' ), 'pages' => __( 'Existing privacy and imprint results', 'site-essentials' ), 'cron' => __( 'Last scheduled-tasks result', 'site-essentials' ) ) as $key => $label ) { echo '<p><label><input type="checkbox" name="se_health_extras[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, self::extras(), true ), true, false ) . '> ' . esc_html( $label ) . '</label></p>'; }
 }
 /** Load layout helpers only on the native Updates screen.
  * @param string $hook Native screen hook.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( 'site-health.php' === $hook && Settings::get( 'health_origins' ) && current_user_can( 'view_site_health_checks' ) ) {
   wp_enqueue_script( 'se-health-origins', plugins_url( 'assets/health-origins.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION . '-health3', true );
   wp_enqueue_style( 'se-health-origins', plugins_url( 'assets/health-origins.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION . '-health3' );
  }
  if ( 'update-core.php' === $hook && Settings::get( 'translation_preview' ) && current_user_can( 'update_languages' ) ) { wp_enqueue_script( 'se-translations-preview', plugins_url( 'assets/translations-preview.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION, true ); } }
 /** Show one confirmation outside the plugin heading. @return void */
 public function notice(): void { $key = 'se_health_saved_' . get_current_user_id(); if ( current_user_can( 'manage_options' ) && ! is_network_admin() && ( get_current_screen()->id ?? '' ) === 'settings_page_site-essentials' && get_transient( $key ) ) { delete_transient( $key ); echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Site Health choices saved.', 'site-essentials' ) . '</p></div>'; } }

 /** List native pending translation metadata without changing update behavior. @return void */
 public function translations(): void {
  if ( ! Settings::get( 'translation_preview' ) || ! current_user_can( 'update_languages' ) ) { return; }
  $updates = wp_get_translation_updates(); if ( ! $updates ) { return; }
  $plugins = get_plugins(); $themes = wp_get_themes(); $groups = array();
  foreach ( $updates as $u ) { if ( ! is_object( $u ) || ! is_string( $u->type ?? null ) || ! is_string( $u->slug ?? null ) || ! is_string( $u->language ?? null ) ) { continue; } $groups[ $u->type ][] = $u; }
  echo '<details class="se-translation-preview"><summary>' . esc_html__( 'Pending translation updates', 'site-essentials' ) . ' (' . count( $updates ) . ')</summary>';
  foreach ( $groups as $type => $items ) {
   $types = array( 'core' => 'WordPress', 'plugin' => __( 'Plugins', 'site-essentials' ), 'theme' => __( 'Themes', 'site-essentials' ) );
   echo '<h3>' . esc_html( $types[ $type ] ?? $type ) . '</h3><ul>';
   foreach ( $items as $u ) {
    $name = 'core' === $u->type ? 'WordPress' : $u->slug;
    if ( 'plugin' === $u->type ) { foreach ( $plugins as $path => $p ) { if ( dirname( $path ) === $u->slug || basename( $path, '.php' ) === $u->slug ) { $name = $p['Name']; break; } } } elseif ( 'theme' === $u->type && isset( $themes[ $u->slug ] ) ) { $name = $themes[ $u->slug ]->get( 'Name' ); }
    $languages = get_site_transient( 'available_translations' ); $language = is_array( $languages ) && is_string( $languages[ $u->language ]['native_name'] ?? null ) ? $languages[ $u->language ]['native_name'] . ' (' . $u->language . ')' : $u->language;
    echo '<li>' . esc_html( $name . ' · ' . $language ) . '</li>';
   }
   echo '</ul>';
  }
  echo '<p>' . esc_html__( 'WordPress updates these translations together. This preview does not select individual packages or change automatic updates.', 'site-essentials' ) . '</p></details>';
 }
}
