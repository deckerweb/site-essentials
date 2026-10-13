<?php
/** Site-local named configuration snapshots. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Stores reusable presets without applying settings or transferring network policies. */
final class Presets {
 const OPTION = 'site_essentials_presets';
 const LIMIT = 20;
 /** Read valid presets from the current site's bounded store.
  * @return array<string,array<string,mixed>>
  */
 public static function custom(): array {
  $stored = get_option( self::OPTION, array() ); $items = array();
  if ( ! is_array( $stored ) ) { return $items; }
  foreach ( array_slice( $stored, 0, self::LIMIT, true ) as $id => $item ) {
   if ( ! is_string( $id ) || ! preg_match( '/^custom_[a-f0-9-]{36}$/D', $id ) || ! is_array( $item ) || ! is_string( $item['label'] ?? null ) || ! is_array( $item['settings'] ?? null ) ) { continue; }
   $clean = Settings::validate( $item['settings'] );
   if ( is_wp_error( $clean ) ) { continue; }
   $items[ $id ] = array( 'label' => sanitize_text_field( $item['label'] ), 'description' => sanitize_textarea_field( is_string( $item['description'] ?? null ) && strlen( $item['description'] ) <= 2000 ? $item['description'] : '' ), 'created' => is_string( $item['created'] ?? null ) ? $item['created'] : '', 'settings' => array_replace( Schema::defaults(), $clean ) );
  }
  return $items;
 }
 /** Combine built-in and site-local presets without overwriting built-ins.
  * @return array<string,array<string,mixed>>
  */
 public static function catalog(): array { return Schema::presets() + self::custom(); }
 /** Preserve empty map types in catalog JSON.
  * @return array<string,array<string,mixed>>
  */
 public static function portable(): array {
  $items = self::catalog();
  foreach ( $items as &$item ) { $item['settings'] = Settings::portable( array_replace( Schema::defaults(), $item['settings'] ) ); }
  unset( $item ); return $items;
 }
 /** Save the currently persisted local configuration under a new name.
  * @param string $name Plain-text preset name.
  * @param string $description Explicit configuration note; never gathered from the site.
  * @return string|\WP_Error Created ID or an actionable error.
  */
 public static function create( string $name, string $description = '' ) {
  $items = self::custom();
  if ( count( $items ) >= self::LIMIT ) { return new \WP_Error( 'preset_limit', __( 'This site already has 20 custom presets. Remove one before adding another.', 'site-essentials' ) ); }
  $metadata = self::metadata( $name, $description ); if ( is_wp_error( $metadata ) ) { return $metadata; }
  $label = $metadata['label']; $description = $metadata['description'];
  $id = 'custom_' . wp_generate_uuid4();
  $items[ $id ] = array( 'label' => $label, 'description' => sanitize_textarea_field( $description ), 'created' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'settings' => Settings::local() );
  update_option( self::OPTION, $items, false );
  return get_option( self::OPTION ) === $items ? $id : new \WP_Error( 'preset_write', __( 'The preset could not be saved.', 'site-essentials' ) );
 }
 /** Validate neutral profile names and explicitly entered notes.
  * @param string $name Preset name.
  * @param string $description Configuration note.
  * @param string $exclude ID being renamed, if any.
  * @return array<string,string>|\WP_Error
  */
 private static function metadata( string $name, string $description, string $exclude = '' ) {
  $label = trim( sanitize_text_field( $name ) );
  if ( '' === $label || strlen( $label ) > 320 || preg_match_all( '/./us', $label ) > 80 ) { return new \WP_Error( 'preset_name', __( 'Enter a preset name of up to 80 characters.', 'site-essentials' ) ); }
  if ( strlen( $description ) > 2000 || preg_match_all( '/./us', $description ) > 500 ) { return new \WP_Error( 'preset_note', __( 'Use a configuration note of up to 500 characters.', 'site-essentials' ) ); }
  $fold = static fn( string $v ): string => function_exists( 'mb_strtolower' ) ? mb_strtolower( $v ) : strtolower( $v );
  foreach ( self::catalog() as $id => $item ) { if ( $id !== $exclude && $fold( $item['label'] ) === $fold( $label ) ) { return new \WP_Error( 'preset_duplicate', __( 'A preset with this name already exists. Choose another name.', 'site-essentials' ) ); } }
  return array( 'label' => $label, 'description' => sanitize_textarea_field( $description ) );
 }
 /** Change only a custom preset's name and note, keeping its settings and identity.
  * @param string $id Custom preset ID.
  * @param string $name New name.
  * @param string $description New configuration note.
  * @return true|\WP_Error
  */
 public static function update_metadata( string $id, string $name, string $description ) {
  $items = self::custom();
  if ( ! isset( $items[ $id ] ) ) { return new \WP_Error( 'preset_missing', __( 'This custom preset is no longer available.', 'site-essentials' ) ); }
  $metadata = self::metadata( $name, $description, $id ); if ( is_wp_error( $metadata ) ) { return $metadata; }
  $items[ $id ] = array_replace( $items[ $id ], $metadata ); update_option( self::OPTION, $items, false );
  return get_option( self::OPTION ) === $items ? true : new \WP_Error( 'preset_write', __( 'The preset could not be saved.', 'site-essentials' ) );
 }
 /** Remove a known custom preset without changing site settings.
  * @param string $id Custom preset ID.
  * @return true|\WP_Error
  */
 public static function remove( string $id ) {
  $items = self::custom();
  if ( ! isset( $items[ $id ] ) ) { return new \WP_Error( 'preset_missing', __( 'This custom preset is no longer available.', 'site-essentials' ) ); }
  unset( $items[ $id ] ); update_option( self::OPTION, $items, false );
  return get_option( self::OPTION ) === $items ? true : new \WP_Error( 'preset_write', __( 'The preset could not be removed.', 'site-essentials' ) );
 }
 /** Export a single preset in the existing portable configuration format.
  * @param string $id Built-in or custom preset ID.
  * @return array<string,mixed>|\WP_Error
  */
 public static function export( string $id ) {
  $items = self::catalog();
  if ( ! isset( $items[ $id ] ) ) { return new \WP_Error( 'preset_missing', __( 'This preset is no longer available.', 'site-essentials' ) ); }
  return array( 'plugin' => 'site-essentials', 'schema' => 1, 'version' => SITE_ESSENTIALS_VERSION, 'label' => $items[ $id ]['label'], 'profile' => array( 'id' => $id, 'origin' => 'site-essentials', 'description' => $items[ $id ]['description'] ?? '', 'created' => $items[ $id ]['created'] ?? '' ), 'settings' => Settings::portable( array_replace( Schema::defaults(), $items[ $id ]['settings'] ) ) );
 }
}
