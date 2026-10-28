<?php
/** Separate, site-local content forms. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;
/** Shares bounded form authorization without adding local content to preset exports. */
final class SitePanels {
 /** Register assets only for the host settings page. @return void */
 public function boot(): void { add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) ); }
 /** Hash the stored option for optimistic concurrency.
  * @param string $option Local option name.
  * @return string Revision token, scoped by the form nonce.
  */
 public static function revision( string $option ): string { return hash( 'sha256', wp_json_encode( get_option( $option, false ) ) ); }
 /** Require administrator, site-scoped nonce, POST and an unchanged option.
  * @param string $action Exact form action.
  * @param string $option Option being edited.
  * @return void Dies on denied or stale writes.
  */
 public static function authorize( string $action, string $option ): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You cannot manage these site settings.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) { wp_die( esc_html__( 'Use the settings form to perform this action.', 'site-essentials' ), '', array( 'response' => 405 ) ); }
  check_admin_referer( $action . '_' . get_current_blog_id() );
  $revision = self::input( 'panel_revision' );
  if ( ! hash_equals( self::revision( $option ), $revision ) ) { wp_die( esc_html__( 'These settings changed elsewhere. Reload before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
 }
 /** Read a scalar POST string after the caller has checked authorization.
  * @param string $key Field name.
  * @return string Original scalar value, empty for nonstrings. Reading the PHP request avoids WordPress-added slashes in HTML/JSON; callers validate by field type after authorization.
  */
 public static function input( string $key ): string { $value = filter_input( INPUT_POST, $key, FILTER_UNSAFE_RAW ); return is_string( $value ) ? $value : ''; }
 /** Return one normal inline result on the host page.
  * @param string $result Allowlisted caller result.
  * @param string $section Host section fragment.
  * @return never
  */
 public static function redirect( string $result, string $section ): never { wp_safe_redirect( add_query_arg( array( 'page' => 'site-essentials', 'se_result' => $result ), admin_url( 'options-general.php' ) ) . '#se-' . $section ); exit; }
 /** Persist one small local option and verify it.
  * @param string $option Exact local option name.
  * @param mixed $value Normalized value.
  * @return void Dies if the database did not retain the value.
  */
 public static function store( string $option, $value ): void { update_option( $option, $value, false ); if ( get_option( $option ) !== $value ) { wp_die( esc_html__( 'The settings could not be saved. Reload and try again.', 'site-essentials' ), '', array( 'response' => 500 ) ); } }
 /** Emit detached forms after the main settings form closes; no nested forms. @return void */
 public static function forms(): void {
  foreach ( array( 'widget_save' => DashboardInfo::OPTION, 'widget_export' => DashboardInfo::OPTION, 'widget_import' => DashboardInfo::OPTION, 'default_avatar' => DefaultAvatar::OPTION ) as $kind => $option ) {
   $id = 'se-' . str_replace( '_', '-', $kind ) . '-form'; $action = 'site_essentials_' . $kind;
   echo '<form id="' . esc_attr( $id ) . '" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="' . esc_attr( $action ) . '"><input type="hidden" name="panel_revision" value="' . esc_attr( self::revision( $option ) ) . '">';
   wp_nonce_field( $action . '_' . get_current_blog_id() ); echo '</form>';
  }
 }
 /** Enqueue the native picker and own interaction controller on the local page only.
  * @param string $hook Native screen suffix.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( 'settings_page_site-essentials' !== $hook || is_network_admin() || ! current_user_can( 'manage_options' ) ) { return; }
  if ( current_user_can( 'upload_files' ) ) { wp_enqueue_media(); }
  wp_enqueue_script( 'se-site-panels', plugins_url( 'assets/site-panels.js', SITE_ESSENTIALS_FILE ), array( 'site-essentials-admin' ), SITE_ESSENTIALS_ASSET_VERSION, true );
  wp_localize_script( 'se-site-panels', 'seSitePanels', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( Admin::nonce_action( 'site_essentials_access_search' ) ), 'draft' => __( 'Save or discard other drafts before saving these settings.', 'site-essentials' ), 'unsaved' => __( 'Unsaved widget changes.', 'site-essentials' ), 'choose' => __( 'Choose a local image', 'site-essentials' ), 'use' => __( 'Use this image', 'site-essentials' ), 'searching' => __( 'Searching site users…', 'site-essentials' ), 'empty' => __( 'No matching site users found.', 'site-essentials' ), 'failed' => __( 'User lookup failed. Try again.', 'site-essentials' ), 'short' => __( 'Enter at least two characters.', 'site-essentials' ), 'selected' => __( 'Selected image: save to apply.', 'site-essentials' ), 'reset' => __( 'SE default selected: save to apply.', 'site-essentials' ), 'local' => __( 'Choose a locally served image; external avatar images are not used.', 'site-essentials' ), 'shipped' => ( new Avatars() )->shipped_default(), 'origins' => array( home_url( '/' ), site_url( '/' ) ) ) );
 }
}
