<?php
/** Focused WordPress command-palette and Site Health integration. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Adds navigation and support information without changing configuration or grading site security. */
final class Integrations {
 /** Register lazy hooks; no data is collected during boot. @return void */
 public function boot(): void {
  add_action( 'admin_enqueue_scripts', array( $this, 'commands' ), 30 );
  add_filter( 'debug_information', array( $this, 'health' ) );
 }
 /** Add focused navigation shortcuts where WordPress already loads its palette.
  * @param string $hook Current admin screen hook.
  * @return void
  */
 public function commands( string $hook ): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ! wp_script_is( 'wp-commands', 'enqueued' ) ) { return; }
  wp_enqueue_script( 'site-essentials-commands', plugins_url( 'assets/commands.js', SITE_ESSENTIALS_FILE ), array( 'wp-commands', 'wp-data', 'wp-dom-ready' ), SITE_ESSENTIALS_VERSION, true );
  $commands = array(
   array( 'name' => 'site-essentials/reports', 'label' => __( 'Site Essentials: Open reports and handover', 'site-essentials' ), 'url' => admin_url( 'options-general.php?page=site-essentials#se-reports' ) ),
   array( 'name' => 'site-essentials/settings', 'label' => __( 'Site Essentials: Open settings', 'site-essentials' ), 'url' => admin_url( 'options-general.php?page=site-essentials' ) ),
   array( 'name' => 'site-essentials/checks', 'label' => __( 'Site Essentials: Open website checks', 'site-essentials' ), 'url' => admin_url( 'options-general.php?page=site-essentials#se-checks' ) ),
   array( 'name' => 'site-essentials/maintenance', 'label' => __( 'Site Essentials: Open maintenance mode', 'site-essentials' ), 'url' => admin_url( 'options-general.php?page=site-essentials#se-maintenance' ) ),
  );
  wp_localize_script( 'site-essentials-commands', 'siteEssentialsCommands', array( 'label' => $commands[0]['label'], 'url' => $commands[0]['url'], 'commands' => $commands ) );
 }
 /** Add a permission-protected, data-minimal Site Health Info section on demand.
  * @param array $info Existing WordPress and plugin sections.
  * @return array
  */
 public function health( array $info ): array {
  if ( ! current_user_can( 'manage_options' ) ) { return $info; }
  $raw = get_option( Settings::OPTION, false );
  $valid = is_array( $raw ) && ! is_wp_error( Settings::validate( $raw, false ) );
  $status = false === $raw ? 'not-initialized' : ( $valid ? 'valid-known-settings' : 'invalid-neutral-fallback' );
  $scope = is_multisite() ? ( Settings::network_active() ? 'network-active; current-site configuration' : 'site-active; current-site configuration' ) : 'single-site';
  $local = Settings::local(); $effective = Settings::effective(); $local_count = 0; $keys = array(); $labels = array(); $inactive = array(); $inactive_keys = array();
  foreach ( Schema::fields() as $key => $field ) {
   if ( $local[ $key ] !== $field['default'] ) { $local_count++; }
   if ( $effective[ $key ] !== $field['default'] ) {
    $reason = self::inactive_reason( $key, $effective );
    if ( $reason ) { $inactive[] = $field['label'] . ': ' . $reason; $inactive_keys[] = $key; continue; }
    $keys[] = $key; $labels[] = $field['label'];
   }
  }
  $snapshot_raw = get_option( Settings::PREVIOUS, false );
  $snapshot = false === $snapshot_raw ? 'absent' : ( null === Settings::previous() ? 'invalid' : 'available' );
  $policy = Settings::network_active() ? ( Settings::language_locked() ? 'forced-hidden' : 'per-site' ) : 'not-applied';
  $maintenance = Maintenance::state(); $providers = Maintenance::providers();
  $maintenance_status = ! $maintenance['enabled'] ? __( 'Off', 'site-essentials' ) : ( $maintenance['paused'] ? __( 'Paused after a provider conflict', 'site-essentials' ) : ( $providers ? __( 'Deferred to another provider', 'site-essentials' ) : __( 'Active', 'site-essentials' ) ) );
  $info['site-essentials'] = array(
   'label' => 'Site Essentials', 'description' => esc_html__( 'Configuration information only. No runtime tests or security rating. Preset names, notes, footer text and customer data are omitted.', 'site-essentials' ),
   'fields' => array(
    'avatar_provider' => array( 'label' => __( 'Local avatar provider', 'site-essentials' ), 'value' => Avatars::external_provider() ? __( 'Another avatar provider', 'site-essentials' ) : ( Settings::get( 'local_avatars' ) ? 'Site Essentials' : 'WordPress' ) ),
    'maintenance_status' => array( 'label' => __( 'Maintenance mode', 'site-essentials' ), 'value' => $maintenance_status ),
    'maintenance_started' => array( 'label' => __( 'Active since', 'site-essentials' ), 'value' => Maintenance::active() ? ( $maintenance['started'] ? wp_date( 'Y-m-d H:i:s T', $maintenance['started'] ) : __( 'Not recorded', 'site-essentials' ) ) : __( 'Not applicable', 'site-essentials' ) ),
    'maintenance_page' => array( 'label' => __( 'Maintenance page', 'site-essentials' ), 'value' => ! $maintenance['page'] ? __( 'Not assigned', 'site-essentials' ) : ( Maintenance::page( $maintenance['page'] ) ? __( 'Published without a password', 'site-essentials' ) : __( 'Unavailable or not publicly published without a password', 'site-essentials' ) ) ),
    'maintenance_provider' => array( 'label' => __( 'Other maintenance provider', 'site-essentials' ), 'value' => $providers ? implode( ', ', array_keys( $providers ) ) : __( 'None detected', 'site-essentials' ) ),
    'public_checks' => array( 'label' => __( 'Public website checks', 'site-essentials' ), 'value' => __( 'Manual only; results are not stored permanently', 'site-essentials' ) ),
    'version' => array( 'label' => __( 'Plugin version', 'site-essentials' ), 'value' => SITE_ESSENTIALS_VERSION ),
    'scope' => array( 'label' => __( 'Activation and scope', 'site-essentials' ), 'value' => is_multisite() ? ( Settings::network_active() ? __( 'Network active; this site has local settings', 'site-essentials' ) : __( 'Site active on multisite', 'site-essentials' ) ) : __( 'Single site', 'site-essentials' ), 'debug' => $scope ),
    'configuration' => array( 'label' => __( 'Stored configuration', 'site-essentials' ), 'value' => false === $raw ? __( 'Not initialized', 'site-essentials' ) : ( $valid ? __( 'Known settings are valid', 'site-essentials' ) : __( 'Invalid values; neutral fallback is used', 'site-essentials' ) ), 'debug' => $status ),
    'local_changes' => array( 'label' => __( 'Local deviations from defaults', 'site-essentials' ), 'value' => $local_count ),
    'effective_rules' => array( 'label' => __( 'Effective configured rules', 'site-essentials' ), 'value' => $labels ? implode( '; ', $labels ) : __( 'WordPress defaults', 'site-essentials' ), 'debug' => $keys ? implode( ', ', $keys ) : 'none' ),
    'inapplicable' => array( 'label' => __( 'Configured but not applicable', 'site-essentials' ), 'value' => $inactive ? implode( '; ', $inactive ) : __( 'None', 'site-essentials' ), 'debug' => $inactive_keys ? implode( ', ', $inactive_keys ) : 'none' ),
    'network_login' => array( 'label' => __( 'Network login policy', 'site-essentials' ), 'value' => ! Settings::network_active() ? __( 'Not applied', 'site-essentials' ) : ( Settings::language_locked() ? __( 'Hide on every site', 'site-essentials' ) : __( 'Let each site decide', 'site-essentials' ) ), 'debug' => $policy ),
    'custom_presets' => array( 'label' => __( 'Number of custom presets', 'site-essentials' ), 'value' => count( Presets::custom() ) ),
    'snapshot' => array( 'label' => __( 'Previous configuration', 'site-essentials' ), 'value' => 'absent' === $snapshot ? __( 'Not available', 'site-essentials' ) : ( 'available' === $snapshot ? __( 'Available', 'site-essentials' ) : __( 'Invalid snapshot', 'site-essentials' ) ), 'debug' => $snapshot ),
    'verification' => array( 'label' => __( 'Verification', 'site-essentials' ), 'value' => __( 'Configured; not runtime-tested', 'site-essentials' ), 'debug' => 'configuration-only' ),
   ),
  );
  return $info;
 }
 /** Explain known overrides without running frontend tests or changing saved values.
  * @param string $key Schema field key.
  * @param array $values Effective current-site values.
  * @return string Explanation, or empty when no known override applies.
  */
 private static function inactive_reason( string $key, array $values ): string {
  if ( in_array( $key, array( 'local_avatars', 'avatar_column' ), true ) && Avatars::external_provider() ) { return __( 'Local avatars are provided by another provider', 'site-essentials' ); }
  if ( 'avatar_column' === $key && ! $values['local_avatars'] ) { return __( 'The local avatar module is disabled', 'site-essentials' ); }
  if ( 'file_editors' === $key && is_multisite() ) { return __( 'The site-local file-editor setting does not apply on Multisite', 'site-essentials' ); }
  if ( $values['comments_disabled'] && in_array( $key, array( 'comment_types', 'hide_comments', 'comment_reply', 'self_pingbacks' ), true ) ) { return __( 'Overridden by complete comment shutdown', 'site-essentials' ); }
  if ( 'hide_comments' === $key && ! $values['comment_types'] ) { return __( 'No content types selected for comment restrictions', 'site-essentials' ); }
  return '';
 }

}
