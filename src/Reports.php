<?php
/** Data-minimal configuration reporting. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Reads configured rules without inspecting content, accounts or external services. */
final class Reports {
 /** Build active rule rows with explicit scope and applicability. @return array<int,array<string,mixed>> */
 public static function rows(): array {
  $rows = array(); $effective = Settings::effective();
  foreach ( Schema::fields() as $key => $field ) {
   if ( in_array( $key, array( 'local_avatars', 'avatar_column' ), true ) && Avatars::external_provider() ) { continue; }
   $network = 'login_language' === $key && Settings::language_locked();
   if ( $effective[ $key ] === $field['default'] && ! $network ) { continue; }
   $rows[] = array( 'key' => $key, 'label' => $field['label'], 'group' => Schema::groups()[ $field['group'] ], 'value' => in_array( $key, array( 'footer_text', 'post_plural', 'post_singular' ), true ) ? __( 'Custom text omitted', 'site-essentials' ) : $effective[ $key ], 'scope' => $network ? __( 'Network policy', 'site-essentials' ) : __( 'This site', 'site-essentials' ), 'applicable' => ! ( 'file_editors' === $key && is_multisite() ) );
  }
  return $rows;
 }
 /** Generate a report only when explicitly requested by an authorized admin. @return array<string,mixed> */
 public static function diagnostic(): array {
  $rules = array();
  foreach ( self::rows() as $row ) { $rules[] = array( 'setting' => $row['key'], 'value' => $row['value'], 'scope' => $row['scope'], 'applicable' => $row['applicable'], 'verification' => 'configuration-only' ); }
  return array( 'avatar_provider' => Avatars::external_provider() ? 'external-provider' : ( Settings::get( 'local_avatars' ) ? 'site-essentials' : 'wordpress' ), 'plugin' => 'site-essentials', 'version' => SITE_ESSENTIALS_VERSION, 'wordpress' => get_bloginfo( 'version' ), 'php' => PHP_VERSION, 'scope' => is_multisite() ? 'multisite-current-site' : 'single-site', 'network_active' => Settings::network_active(), 'verification' => 'No runtime or compatibility tests performed by this report.', 'rules' => $rules );
 }
 /** Render a compact, escaped read-only handover table for existing administrators. @return void */
 public static function handover(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Customer handover', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Saved active rules and their responsibility. This is a read-only configuration view, not a security or compatibility certificate.', 'site-essentials' ) . '</p><div class="se-status-scroll"><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Setting', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Configured value', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Responsibility', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Verification', 'site-essentials' ) . '</th></tr></thead><tbody>';
  $rows = self::rows();
  foreach ( $rows as $row ) {
   $value = is_bool( $row['value'] ) ? ( $row['value'] ? __( 'Enabled', 'site-essentials' ) : __( 'Disabled', 'site-essentials' ) ) : ( is_array( $row['value'] ) ? wp_json_encode( $row['value'] ) : (string) $row['value'] );
   echo '<tr><th scope="row">' . esc_html( $row['label'] ) . '</th><td>' . esc_html( $value ) . '</td><td>' . esc_html( $row['scope'] ) . '</td><td>' . esc_html( $row['applicable'] ? __( 'Configured; not runtime-tested', 'site-essentials' ) : __( 'Not applicable on multisite', 'site-essentials' ) ) . '</td></tr>';
  }
  if ( ! $rows ) { echo '<tr><td colspan="4">' . esc_html__( 'No active rules beyond WordPress defaults.', 'site-essentials' ) . '</td></tr>'; }
  echo '</tbody></table></div><p><button type="button" class="button" id="se-handover-print">' . esc_html__( 'Print handover', 'site-essentials' ) . '</button></p></div>';
 }
}
