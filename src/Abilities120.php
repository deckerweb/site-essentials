<?php
/** Read-only native abilities for configuration explanations. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Exposes bounded configuration context, never mutation or personal data. */
final class Abilities120 {
 /** Connect native registration only; no adapter is installed. @return void */
 public function boot(): void {
  add_action( 'wp_abilities_api_categories_init', array( $this, 'category' ) );
  add_action( 'wp_abilities_api_init', array( $this, 'register' ) );
 }
 /** Register a dedicated category on compatible WordPress versions. @return void */
 public function category(): void {
  if ( function_exists( 'wp_register_ability_category' ) ) { wp_register_ability_category( 'site-essentials', array( 'label' => 'Site Essentials', 'description' => __( 'Read-only configuration explanations and previews.', 'site-essentials' ) ) ); }
 }
 /** Shared authenticated capability gate for every entry point.
  * @param string $name Registered ability identifier.
  * @return true|\WP_Error Permission result, including HTTP 403 on denial.
  */
 public static function authorize( string $name ) {
  $caps = array( 'site-essentials/explain-settings' => 'manage_options', 'site-essentials/compare-configurations' => 'manage_options', 'site-essentials/preview-preset' => 'manage_options' );
  /** Filter an additional required capability; never relax the administrator baseline.
   * @since 1.2.0
   * @param string $cap Required capability.
   * @param string $name Ability name.
   */
  $cap = apply_filters( 'site_essentials_ability_capability', $caps[ $name ] ?? 'do_not_allow', $name );
  if ( get_current_user_id() && current_user_can( 'manage_options' ) && is_string( $cap ) && current_user_can( $cap ) && ! is_network_admin() ) { return true; }
  return new \WP_Error( 'rest_forbidden', __( 'Administrator access to this website is required.', 'site-essentials' ), array( 'status' => 403 ) );
 }
 /** Register three explicitly read-only abilities with bounded input schemas. @return void */
 public function register(): void {
  if ( ! function_exists( 'wp_register_ability' ) ) { return; }
  $fields = array_keys( self::safe( Schema::defaults() ) );
  $definitions = array(
   'explain-settings' => array( __( 'Explain Site Essentials settings', 'site-essentials' ), __( 'Explain effective and paused settings, without personal assignments or free text.', 'site-essentials' ), array( 'type' => 'object', 'properties' => array( 'keys' => array( 'type' => 'array', 'maxItems' => 200, 'uniqueItems' => true, 'items' => array( 'type' => 'string', 'enum' => $fields ) ) ), 'additionalProperties' => false ) ),
   'compare-configurations' => array( __( 'Compare Site Essentials configurations', 'site-essentials' ), __( 'Compare a validated partial configuration against saved site values, without applying it.', 'site-essentials' ), array( 'type' => 'object', 'properties' => array( 'settings' => array( 'type' => 'object', 'maxProperties' => 200, 'properties' => $this->properties(), 'additionalProperties' => false ) ), 'required' => array( 'settings' ), 'additionalProperties' => false ) ),
   'preview-preset' => array( __( 'Preview Site Essentials preset changes', 'site-essentials' ), __( 'Preview a preset by its identifier; does not save or apply any settings.', 'site-essentials' ), array( 'type' => 'object', 'properties' => array( 'preset' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 100 ) ), 'required' => array( 'preset' ), 'additionalProperties' => false ) ),
  );
  foreach ( $definitions as $id => $d ) { $name = 'site-essentials/' . $id;
   wp_register_ability( $name, array( 'label' => $d[0], 'description' => $d[1], 'category' => 'site-essentials', 'input_schema' => $d[2], 'output_schema' => array( 'type' => 'object', 'properties' => array( 'site_id' => array( 'type' => 'integer' ), 'readonly' => array( 'type' => 'boolean' ), 'rules' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ) ), 'required' => array( 'site_id', 'readonly', 'rules' ), 'additionalProperties' => false ), 'permission_callback' => static fn( $input ) => self::authorize( $name ), 'execute_callback' => fn( $input ) => $this->execute( $name, $input ), 'meta' => array( 'show_in_rest' => true, 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ) ) ) );
  }
 }
 /** Allowlist scalar technical settings; exclude all free text, per-role access and maps.
  * @param array $values Validated configuration.
  * @return array Safe subset.
  */
 public static function safe( array $values ): array {
  $out = array(); foreach ( Schema::fields() as $key => $field ) { if ( ! in_array( $field['type'], array( 'bool', 'int', 'select', 'list', 'map' ), true ) || str_starts_with( $key, 'access_' ) ) { continue; } if ( array_key_exists( $key, $values ) ) { $out[ $key ] = $values[ $key ]; } }
  return $out;
 }
 /** Build bounded input definitions from the same safe allowlist. @return array */
 private function properties(): array {
  $out = array(); foreach ( self::safe( Schema::defaults() ) as $key => $value ) { $field = Schema::fields()[ $key ]; if ( 'list' === $field['type'] ) { $out[ $key ] = array( 'type' => 'array', 'maxItems' => 100, 'uniqueItems' => true, 'items' => array( 'type' => 'string', 'maxLength' => 100 ) ); continue; } if ( 'map' === $field['type'] ) { $out[ $key ] = array( 'type' => 'object', 'maxProperties' => 100, 'additionalProperties' => array( 'type' => 'integer', 'minimum' => -1, 'maximum' => 1000 ) ); continue; } $out[ $key ] = array( 'type' => is_bool( $value ) ? 'boolean' : ( is_int( $value ) ? 'integer' : 'string' ) ); if ( is_int( $value ) ) { $out[ $key ]['minimum'] = -1; $out[ $key ]['maximum'] = 100000; } if ( is_string( $value ) ) { $out[ $key ]['maxLength'] = 100; } }
  return $out;
 }
 /** Explain or compare safe values without writing options or invoking operations.
  * @param string $name Ability identifier.
  * @param mixed $input Native schema-validated input, defensively rechecked.
  * @return array|\WP_Error Site-scoped, read-only result.
  */
 public function execute( string $name, $input ) {
  $gate = self::authorize( $name ); if ( is_wp_error( $gate ) ) { return $gate; }
  if ( ! is_array( $input ) ) { return new \WP_Error( 'invalid_input', __( 'Invalid settings.', 'site-essentials' ), array( 'status' => 400 ) ); }
  $saved = Settings::local(); $candidate = $saved;
  if ( 'site-essentials/preview-preset' === $name ) {
   $id = $input['preset'] ?? null; $catalog = Presets::catalog();
   if ( ! is_string( $id ) || ! isset( $catalog[ $id ] ) ) { return new \WP_Error( 'preset_not_found', __( 'Preset not found.', 'site-essentials' ), array( 'status' => 404 ) ); }
   $candidate = array_replace( Schema::defaults(), $catalog[ $id ]['settings'] );
  } elseif ( 'site-essentials/compare-configurations' === $name ) {
   $settings = $input['settings'] ?? null;
   if ( ! is_array( $settings ) || array_diff_key( $settings, self::safe( Schema::defaults() ) ) ) { return new \WP_Error( 'invalid_input', __( 'Invalid settings.', 'site-essentials' ), array( 'status' => 400 ) ); }
   $clean = Settings::validate( $settings ); if ( is_wp_error( $clean ) ) { return $clean; } $candidate = array_replace( $saved, $clean );
  }
  $effective = $candidate; if ( Settings::language_locked() ) { $effective['login_language'] = true; }
  $rules = array(); $safe = self::safe( $candidate ); $keys = $input['keys'] ?? array_keys( $safe );
  if ( ! is_array( $keys ) || count( $keys ) > 200 || array_filter( $keys, static fn( $key ) => ! is_string( $key ) || ! array_key_exists( $key, $safe ) ) ) { return new \WP_Error( 'invalid_input', __( 'Invalid settings.', 'site-essentials' ), array( 'status' => 400 ) ); }
  foreach ( $keys as $key ) {
   if ( 'site-essentials/explain-settings' !== $name && $saved[ $key ] === $candidate[ $key ] ) { continue; }
   $field = Schema::fields()[ $key ]; $rules[] = array( 'key' => $key, 'label' => $field['label'], 'explanation' => $field['help'] ?? '', 'saved' => $saved[ $key ], 'proposed' => $candidate[ $key ], 'effective' => $effective[ $key ], 'pause_reason' => RuleStatus::reason( $key, $effective ), 'settings_url' => admin_url( 'options-general.php?page=site-essentials#se-' . $field['group'] ) );
  }
  return array( 'site_id' => get_current_blog_id(), 'readonly' => true, 'rules' => $rules );
 }
}
