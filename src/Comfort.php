<?php
/** Small, optional improvements to native administration. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Adds read-only user columns and presentation helpers without changing native preferences. */
final class Comfort {
 /** Register only administration hooks. @return void */
 public function boot(): void {
  if ( ! is_admin() ) { return; }
  add_filter( 'manage_users_columns', array( $this, 'columns' ) );
  add_filter( 'manage_users_custom_column', array( $this, 'column' ), 10, 3 );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'customize_controls_enqueue_scripts', array( $this, 'customizer' ) );
 }
 /** Restrict optional user columns to the site administration context.
  * @return bool Whether the current user may inspect this site's users.
  */
 private function allowed(): bool { return ! is_network_admin() && current_user_can( 'manage_options' ) && current_user_can( 'list_users' ); }
 /** Add enabled columns without overwriting third-party columns.
  * @param array<string,string> $columns Native and third-party headings.
  * @return array<string,string> Headings with optional local columns.
  */
 public function columns( array $columns ): array {
  if ( ! $this->allowed() ) { return $columns; }
  if ( Settings::get( 'user_ids' ) ) { $columns['se_user_id'] = __( 'User ID', 'site-essentials' ); }
  if ( Settings::get( 'avatar_column' ) && Settings::get( 'local_avatars' ) && ! Avatars::external_provider() ) { $columns['se_avatar'] = __( 'Local avatar', 'site-essentials' ); }
  return $columns;
 }
 /** Render an administrator-only ID or the existing local avatar pipeline.
  * @param string $value Existing column output.
  * @param string $column Column identifier.
  * @param int $user_id Current row user.
  * @return string Escaped ID, locally rendered avatar or unchanged output.
  */
 public function column( string $value, string $column, int $user_id ): string {
  if ( ! $this->allowed() ) { return $value; }
  if ( 'se_user_id' === $column && Settings::get( 'user_ids' ) ) { return esc_html( (string) $user_id ); }
  if ( 'se_avatar' === $column && Settings::get( 'avatar_column' ) && Settings::get( 'local_avatars' ) && ! Avatars::external_provider() ) { return get_avatar( $user_id, 40 ); }
  return $value;
 }
 /** Enqueue explanatory text only on General settings.
  * @param string $hook Current screen hook suffix.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( 'options-general.php' !== $hook || ! current_user_can( 'manage_options' ) || ! Settings::get( 'format_help' ) ) { return; }
  wp_enqueue_script( 'se-format-help', plugins_url( 'assets/format-help.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION, true );
  wp_localize_script( 'se-format-help', 'seFormatHelp', array(
   'time' => array( 'g:i a' => __( '12 hours: 9:05 am / 9:05 pm', 'site-essentials' ), 'G:i' => __( '24 hours without a leading zero: 9:05 / 21:05', 'site-essentials' ), 'H:i' => __( '24 hours with a leading zero: 09:05 / 21:05', 'site-essentials' ), 'g:i A' => __( '12 hours: 9:05 AM / 9:05 PM', 'site-essentials' ) ),
   'date' => __( 'Day without a leading zero (j): 4. Day with a leading zero (d): 04. Month as a number without a leading zero (n): 2; with a leading zero (m): 02. Full year (Y): 2026.', 'site-essentials' ),
  ) );
 }
 /** Enhance the existing CSS section only when editing remains authorized.
  * @return void
  */
 public function customizer(): void {
  if ( ! Settings::get( 'css_width' ) || ! current_user_can( 'edit_css' ) || false === Access::decision( 'customizer' ) ) { return; }
  wp_enqueue_style( 'se-css-width', plugins_url( 'assets/css-width.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION );
  wp_enqueue_script( 'se-css-width', plugins_url( 'assets/css-width.js', SITE_ESSENTIALS_FILE ), array( 'customize-controls' ), SITE_ESSENTIALS_VERSION, true );
  wp_localize_script( 'se-css-width', 'seCssWidth', array( 'expand' => __( 'Expand editor', 'site-essentials' ), 'normal' => __( 'Normal view', 'site-essentials' ), 'width' => __( 'Editor width', 'site-essentials' ) ) );
 }
}
