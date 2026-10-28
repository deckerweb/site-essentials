<?php
/** Settings storage and portable configuration. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Owns local values, network policy and a single previous configuration. */
final class Settings {
 /** Last normalized read; raw option and site are checked on every access. */
 private static array $read_cache = array();
 const OPTION = 'site_essentials_settings';
 const PREVIOUS = 'site_essentials_previous';
 const NETWORK = 'site_essentials_network';
 /** Initialize only the activating site; network sites remain independent.
  * @param bool $network_wide Whether activation is network-wide.
  * @return void
  */
 public static function activate( bool $network_wide = false ): void {
  if ( $network_wide ) { return; }
  self::initialize();
 }
 /** Apply an explicitly supplied startup preset once, lazily for each site.
  * @return void
  */
 public static function initialize(): void {
  if ( false !== get_option( self::OPTION, false ) ) { return; }
  $startup = json_decode( (string) file_get_contents( dirname( SITE_ESSENTIALS_FILE ) . '/startup-preset.json' ), true );
  $presets = Schema::presets();
  $name = is_array( $startup ) ? ( $startup['preset'] ?? '' ) : '';
  $values = is_string( $name ) && isset( $presets[ $name ] ) ? $presets[ $name ]['settings'] : array();
  add_option( self::OPTION, array_replace( Schema::defaults(), $values ), '', false );
 }
 /** Read current site values; reuse validation only while site and raw values match.
  * @return array<string,mixed> Validated local settings.
  */
 public static function local(): array {
  $raw = get_option( self::OPTION, array() );
  $site = get_current_blog_id();
  if ( self::$read_cache && self::$read_cache['site'] === $site && self::$read_cache['raw'] === $raw ) { return self::$read_cache['values']; }
  $clean = self::validate( is_array( $raw ) ? $raw : array(), false );
  $values = array_replace( Schema::defaults(), is_wp_error( $clean ) ? array() : $clean );
  self::$read_cache = array( 'site' => $site, 'raw' => $raw, 'values' => $values );
  foreach ( array( 'widgets', 'customizer' ) as $area ) { if ( $values[ $area . '_admin' ] && 0 === $values[ 'access_' . $area . '_mode' ] ) { $values[ 'access_' . $area . '_mode' ] = 1; } }
  self::$read_cache['values'] = $values;
  return $values;
 }
 /** Determine whether this copy is network active.
  * @return bool
  */
 public static function network_active(): bool {
  return is_multisite() && isset( get_site_option( 'active_sitewide_plugins', array() )[ plugin_basename( SITE_ESSENTIALS_FILE ) ] );
 }
 /** Read the effective network policy only for network-active hosts.
  * @return bool Whether login languages are hidden network-wide.
  */
 public static function language_locked(): bool {
  return self::network_active() && 'hide' === get_site_option( self::NETWORK, 'inherit' );
 }
 /** Resolve local settings and the selected network policy.
  * @return array<string,mixed>
  */
 public static function effective(): array {
  $values = self::local();
  if ( self::language_locked() ) { $values['login_language'] = true; }
  return $values;
 }
 /** Read a single effective setting.
  * @param string $key Stable setting identifier.
  * @return mixed
  */
 public static function get( string $key ) { return self::effective()[ $key ] ?? null; }
 /** Validate values without silently accepting unknown identifiers or malformed types.
  * @param array<string,mixed> $input Incoming settings.
  * @param bool $strict Reject unknown settings when true.
  * @return array<string,mixed>|\WP_Error
  */
 public static function validate( array $input, bool $strict = true ) {
  // Expand legacy switches on every read/import, without writing during normalization.
  if ( in_array( $input['feed_links'] ?? false, array( true, 1, '1' ), true ) ) {
   foreach ( array( 'feed_blog', 'feed_comments', 'feed_terms', 'feed_authors', 'feed_search' ) as $key ) { $input[ $key ] = true; }
   $input['feed_types'] = array( '_all' ); $input['feed_taxonomies'] = array( '_all' ); $input['feed_links'] = false;
  }
  if ( in_array( $input['update_emails'] ?? false, array( true, 1, '1' ), true ) ) {
   foreach ( array( 'core', 'plugins', 'themes' ) as $kind ) { $input[ 'update_email_' . $kind ] = true; }
   $input['update_emails'] = false;
  }
  foreach ( array( 'widgets', 'customizer' ) as $area ) {
   if ( in_array( $input[ $area . '_admin' ] ?? false, array( true, 1, '1' ), true ) && ( ! isset( $input[ 'access_' . $area . '_mode' ] ) || in_array( $input[ 'access_' . $area . '_mode' ], array( 0, '0' ), true ) ) ) { $input[ 'access_' . $area . '_mode' ] = 1; }
  }
  $schema = Schema::fields(); $result = array();
  foreach ( $input as $key => $value ) {
   if ( ! isset( $schema[ $key ] ) ) {
    if ( $strict ) { return new \WP_Error( 'unknown', __( 'The configuration contains an unknown setting.', 'site-essentials' ) ); }
    continue;
   }
   $field = $schema[ $key ]; $valid = true;
   switch ( $field['type'] ) {
    case 'bool': $valid = is_bool( $value ) || in_array( $value, array( '0', '1', 0, 1 ), true ); $value = in_array( $value, array( true, '1', 1 ), true ); break;
    case 'int':
     $valid = ( is_int( $value ) || ( is_string( $value ) && preg_match( '/^-?\d+$/D', $value ) ) ) && (int) $value >= $field['min'] && (int) $value <= $field['max'];
     $value = (int) $value;
     if ( str_starts_with( $key, 'heartbeat_' ) && ! in_array( $value, array( 0, 15, 30, 60, 120 ), true ) ) { $valid = false; }
     break;
    case 'text': $valid = is_string( $value ) && strlen( $value ) <= 500; $value = $valid ? sanitize_text_field( $value ) : ''; if ( isset( $field['max_chars'] ) && mb_strlen( $value ) > $field['max_chars'] ) { $valid = false; } break;
    case 'list':
     $valid = is_array( $value ) && count( $value ) <= 100;
     if ( $valid ) {
      foreach ( $value as $item ) { if ( ! is_string( $item ) || ( 'roles' === ( $field['dynamic'] ?? '' ) ? ( '' === $item || strlen( $item ) > 191 || preg_match( '/[\x00-\x1F\x7F]/', $item ) ) : ! preg_match( '/^[a-z0-9_-]{1,80}$/D', $item ) ) ) { $valid = false; break; } }
      if ( $valid && isset( $field['choices'] ) && array_diff( $value, $field['choices'] ) ) { $valid = false; }
      if ( $valid ) { $value = array_values( array_unique( $value ) ); }
     }
     break;
    case 'map':
     $valid = is_array( $value ) && count( $value ) <= 100;
     if ( $valid ) {
      foreach ( $value as $name => $number ) {
       if ( ! is_string( $name ) || ! preg_match( '/^[a-z0-9_-]{1,80}$/D', $name ) || ! ( is_int( $number ) || ( is_string( $number ) && preg_match( '/^-?\d+$/D', $number ) ) ) ) { $valid = false; break; }
       $number = (int) $number;
       if ( 'heartbeat_types' === $key ? ! in_array( $number, array( 0, 15, 30, 60, 120 ), true ) : ( $number < -1 || $number > 100 ) ) { $valid = false; break; }
       $value[ $name ] = $number;
      }
     }
     break;
   }
   if ( ! $valid ) { return new \WP_Error( 'invalid', __( 'A setting contains an invalid value.', 'site-essentials' ) . ' ' . $key ); }
   if ( 'map' === $field['type'] ) { $neutral = 'heartbeat_types' === $key ? 0 : -1; $value = array_filter( $value, static fn( int $number ): bool => $number !== $neutral ); }
   $result[ $key ] = $value;
  }
  foreach ( array( 'widgets', 'customizer' ) as $area ) { if ( isset( $result[ 'access_' . $area . '_mode' ] ) ) { $result[ $area . '_admin' ] = 1 === $result[ 'access_' . $area . '_mode' ]; } }
  return $result;
 }
 /** Save validated settings and retain one rollback snapshot.
  * @param array<string,mixed> $values Complete configuration.
  * @return bool Whether the saved values and rollback snapshot are confirmed.
  */
 public static function save( array $values ): bool {
  $old = self::local();
  if ( $old === $values ) { return true; }
  update_option( self::PREVIOUS, $old, false );
  if ( get_option( self::PREVIOUS ) !== $old ) { return false; }
  update_option( self::OPTION, $values, false );
  return get_option( self::OPTION ) === $values;
 }
 /** Read a validated previous local configuration without changing it. @return array<string,mixed>|null */
 public static function previous(): ?array {
  $raw = get_option( self::PREVIOUS, false );
  if ( ! is_array( $raw ) ) { return null; }
  $clean = self::validate( $raw );
  return is_wp_error( $clean ) ? null : array_replace( Schema::defaults(), $clean );
 }
 /** Restore a snapshot while preserving history if a write fails.
  * @return bool Whether both writes were confirmed; not a database transaction.
  */
 public static function restore(): bool {
  $previous = self::previous(); if ( null === $previous ) { return false; }
  $current = self::local();
  update_option( self::OPTION, $previous, false );
  if ( get_option( self::OPTION ) !== $previous ) { return false; }
  update_option( self::PREVIOUS, $current, false );
  if ( get_option( self::PREVIOUS ) !== $current ) {
   update_option( self::OPTION, $current, false ); return false;
  }
  return true;
 }
 /** Parse a portable import envelope without importing network policy.
  * @param string $json JSON configuration, at most 100 KB.
  * @return array<string,mixed>|\WP_Error
  */
 public static function import( string $json ) {
  if ( strlen( $json ) > 102400 ) { return new \WP_Error( 'size', __( 'The configuration is too large.', 'site-essentials' ) ); }
  $data = json_decode( $json, true, 16 );
  if ( ! is_array( $data ) || ( $data['plugin'] ?? '' ) !== 'site-essentials' || ( $data['schema'] ?? 0 ) !== 1 || ! is_array( $data['settings'] ?? null ) ) {
   return new \WP_Error( 'format', __( 'Use a Site Essentials configuration with schema version 1.', 'site-essentials' ) );
  }
  return self::validate( $data['settings'] );
 }
 /** Preserve map/list distinctions in JSON, including empty maps.
  * @param array<string,mixed> $values Validated values.
  * @return array<string,mixed>
  */
 public static function portable( array $values ): array {
  foreach ( Schema::fields() as $key => $field ) { if ( 'map' === $field['type'] && isset( $values[ $key ] ) ) { $values[ $key ] = (object) $values[ $key ]; } }
  return $values;
 }
 /** Export local values without personal data, network policy or rollback history.
  * @return array<string,mixed>
  */
 public static function export(): array { return array( 'plugin' => 'site-essentials', 'schema' => 1, 'version' => SITE_ESSENTIALS_VERSION, 'settings' => self::portable( self::local() ) ); }
}
