<?php
/** Portable site-local preset collections. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Validates a whole selected batch before one preset-store write, never applying settings. */
final class Collections {
 const MAX_BYTES = 262144;
 /** Case-fold names consistently with preset management.
  * @param string $name Plain name.
  * @return string
  */
 private static function fold( string $name ): string { return function_exists( 'mb_strtolower' ) ? mb_strtolower( $name ) : strtolower( $name ); }
 /** Validate bounded human metadata independently of name collisions.
  * @param mixed $name Name input.
  * @param mixed $note Note input.
  * @return array|\WP_Error
  */
 private static function metadata( $name, $note ) {
  if ( ! is_string( $name ) || strlen( $name ) > 320 || preg_match_all( '/./us', $name ) > 80 || ! is_string( $note ) || strlen( $note ) > 2000 || preg_match_all( '/./us', $note ) > 500 ) { return self::invalid(); }
  $label = trim( sanitize_text_field( $name ) );
  if ( '' === $label ) { return self::invalid(); }
  return array( 'label' => $label, 'description' => sanitize_textarea_field( $note ) );
 }
 /** Return one actionable format error. @return \WP_Error */
 private static function invalid(): \WP_Error { return new \WP_Error( 'collection_invalid', __( 'Use a valid Site Essentials preset collection: up to 20 profiles and 256 KiB.', 'site-essentials' ) ); }
 /** Reject malformed stores instead of silently discarding their entries during import.
  * @return array|\WP_Error
  */
 private static function store() {
  $raw = get_option( Presets::OPTION, array() ); $items = Presets::custom();
  if ( ! is_array( $raw ) || count( $raw ) > Presets::LIMIT || count( $raw ) !== count( $items ) ) { return new \WP_Error( 'collection_store', __( 'The local preset store contains invalid entries. Resolve them before importing a collection.', 'site-essentials' ) ); }
  return $items;
 }
 /** Read a strictly typed, bounded collection without changing options.
  * @param string $json Portable JSON.
  * @return array|\WP_Error
  */
 public static function decode( string $json ) {
  if ( strlen( $json ) > self::MAX_BYTES ) { return self::invalid(); }
  $data = json_decode( $json );
  if ( ! $data instanceof \stdClass || array_diff( array_keys( get_object_vars( $data ) ), array( 'plugin', 'type', 'schema', 'version', 'presets' ) ) || 'site-essentials' !== ( $data->plugin ?? null ) || 'preset-collection' !== ( $data->type ?? null ) || 1 !== ( $data->schema ?? null ) || ! is_string( $data->version ?? null ) || strlen( $data->version ) > 80 || ! is_array( $data->presets ?? null ) || ! count( $data->presets ) || count( $data->presets ) > Presets::LIMIT ) { return self::invalid(); }
  $items = array();
  foreach ( $data->presets as $profile ) {
   if ( ! $profile instanceof \stdClass || array_diff( array_keys( get_object_vars( $profile ) ), array( 'label', 'description', 'settings' ) ) || ! ( $profile->settings ?? null ) instanceof \stdClass ) { return self::invalid(); }
   $metadata = self::metadata( $profile->label ?? null, $profile->description ?? '' );
   if ( is_wp_error( $metadata ) ) { return $metadata; }
   $encoded = wp_json_encode( $profile->settings );
   $raw_settings = false === $encoded ? null : json_decode( $encoded, true );
   if ( ! is_array( $raw_settings ) ) { return self::invalid(); }
   $settings = Settings::validate( $raw_settings );
   if ( is_wp_error( $settings ) ) { return $settings; }
   $items[] = $metadata + array( 'settings' => array_replace( Schema::defaults(), $settings ) );
  }
  return $items;
 }
 /** Export explicitly selected custom profiles; no network data or imported remote IDs.
  * @param array $ids Selected local custom IDs.
  * @return array|\WP_Error
  */
 public static function export( array $ids ) {
  $items = self::store(); if ( is_wp_error( $items ) ) { return $items; }
  if ( ! count( $ids ) || count( $ids ) > Presets::LIMIT || count( array_unique( $ids, SORT_REGULAR ) ) !== count( $ids ) ) { return self::invalid(); }
  foreach ( $ids as $id ) { if ( ! is_string( $id ) || ! isset( $items[ $id ] ) ) { return self::invalid(); } }
  $profiles = array();
  foreach ( $items as $id => $item ) {
   if ( ! in_array( $id, $ids, true ) ) { continue; }
   $metadata = self::metadata( $item['label'], $item['description'] ); if ( is_wp_error( $metadata ) ) { return $metadata; }
   $profiles[] = $metadata + array( 'settings' => Settings::portable( $item['settings'] ) );
  }
  $data = array( 'plugin' => 'site-essentials', 'type' => 'preset-collection', 'schema' => 1, 'version' => SITE_ESSENTIALS_VERSION, 'presets' => $profiles );
  return strlen( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) > self::MAX_BYTES ? self::invalid() : $data;
 }
 /** Suggest distinct copy names, retaining bounded Unicode labels.
  * @param string $name Source name.
  * @param array $used Case-folded occupied names.
  * @return string
  */
 private static function suggestion( string $name, array $used ): string {
  if ( ! isset( $used[ self::fold( $name ) ] ) ) { return $name; }
  $chars = preg_split( '//u', $name, -1, PREG_SPLIT_NO_EMPTY );
  for ( $number = 2; $number < 1000; $number++ ) {
   $suffix = ' (' . $number . ')'; $candidate = rtrim( implode( '', array_slice( $chars, 0, 80 - strlen( $suffix ) ) ) ) . $suffix;
   if ( ! isset( $used[ self::fold( $candidate ) ] ) ) { return $candidate; }
  }
  return $name;
 }
 /** Build a no-write review with current capacity, protected revision and distinct suggested names.
  * @param string $json Imported collection.
  * @return array|\WP_Error
  */
 public static function review( string $json ) {
  $profiles = self::decode( $json ); if ( is_wp_error( $profiles ) ) { return $profiles; }
  $items = self::store(); if ( is_wp_error( $items ) ) { return $items; }
  $used = array(); $names = array();
  foreach ( Presets::catalog() as $item ) { $used[ self::fold( $item['label'] ) ] = true; $names[] = $item['label']; }
  $rows = array();
  foreach ( $profiles as $index => $item ) {
   $label = self::suggestion( $item['label'], $used ); $used[ self::fold( $label ) ] = true;
   $rows[] = array( 'index' => $index, 'source' => $item['label'], 'label' => $label, 'renamed' => $label !== $item['label'], 'description' => $item['description'], 'settings' => Settings::portable( $item['settings'] ) );
  }
  return array( 'profiles' => $rows, 'names' => $names, 'capacity' => Presets::LIMIT - count( $items ), 'revision' => hash( 'sha256', wp_json_encode( $items ) ) );
 }
 /** Validate all selections before one confirmed write; existing profiles and active settings stay intact.
  * @param string $json Source collection.
  * @param array $selected Selected numeric row indices.
  * @param array $names Proposed copy names keyed by row index.
  * @param string $revision Reviewed store hash.
  * @return int|\WP_Error Imported count.
  */
 public static function import( string $json, array $selected, array $names, string $revision ) {
  $profiles = self::decode( $json ); if ( is_wp_error( $profiles ) ) { return $profiles; }
  $items = self::store(); if ( is_wp_error( $items ) ) { return $items; }
  if ( ! hash_equals( hash( 'sha256', wp_json_encode( $items ) ), $revision ) ) { return new \WP_Error( 'collection_stale', __( 'Presets changed after the collection review. Review the collection again before importing.', 'site-essentials' ) ); }
  if ( ! count( $selected ) || count( $selected ) > Presets::LIMIT || count( $names ) > Presets::LIMIT ) { return self::invalid(); }
  if ( count( $items ) + count( $selected ) > Presets::LIMIT ) { return new \WP_Error( 'collection_limit', __( 'The selected profiles exceed the 20-preset limit. Select fewer profiles or remove a local preset first.', 'site-essentials' ) ); }
  $used = array(); foreach ( Presets::catalog() as $item ) { $used[ self::fold( $item['label'] ) ] = true; }
  $seen = array(); $pending = array();
  foreach ( $selected as $index ) {
   if ( ! ( is_int( $index ) || ( is_string( $index ) && preg_match( '/^(0|[1-9][0-9]?)$/D', $index ) ) ) || ! isset( $profiles[ (int) $index ] ) || isset( $seen[ (int) $index ] ) ) { return self::invalid(); }
   $index = (int) $index; $seen[ $index ] = true;
   $metadata = self::metadata( $names[ $index ] ?? null, $profiles[ $index ]['description'] ); if ( is_wp_error( $metadata ) ) { return $metadata; }
   $fold = self::fold( $metadata['label'] );
   if ( isset( $used[ $fold ] ) ) { return new \WP_Error( 'collection_name', __( 'Each selected profile needs a unique name that is not already used by a local or built-in preset.', 'site-essentials' ) ); }
   $used[ $fold ] = true; $pending[] = $metadata + array( 'settings' => $profiles[ $index ]['settings'] );
  }
  foreach ( $pending as $item ) { $items[ 'custom_' . wp_generate_uuid4() ] = $item + array( 'created' => gmdate( 'Y-m-d\TH:i:s\Z' ) ); }
  update_option( Presets::OPTION, $items, false );
  return get_option( Presets::OPTION ) === $items ? count( $pending ) : new \WP_Error( 'collection_write', __( 'The collection could not be saved. Reload to check the local preset store.', 'site-essentials' ) );
 }
}
