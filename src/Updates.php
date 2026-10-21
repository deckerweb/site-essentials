<?php
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Register the shared updater and validate packages belonging to this host. */
final class Updates {
 /** Register after host translations initialize; incompatible shared copies pause updates.
  * @return void
  */
 public static function register(): void {
  $class = '\\Deckerweb\\GitHubReleaseUpdater\\V2\\Updater';
  if ( ! defined( $class . '::SUPPORTS_HOST_TRANSLATIONS' ) || ! constant( $class . '::SUPPORTS_HOST_TRANSLATIONS' ) ) {
   add_action( 'admin_notices', array( self::class, 'notice' ) );
   add_action( 'network_admin_notices', array( self::class, 'notice' ) );
   return;
  }
  $options = array( 'translate' => require dirname( SITE_ESSENTIALS_FILE ) . '/includes/updater-translations.php' );
  if ( defined( $class . '::SUPPORTS_CATALOG_PROVIDER' ) && constant( $class . '::SUPPORTS_CATALOG_PROVIDER' ) && function_exists( 'deckerweb_library_updater_options_v1' ) ) {
   $options = array_merge( $options, deckerweb_library_updater_options_v1( SITE_ESSENTIALS_FILE, 'https://github.com/deckerweb/site-essentials' ) );
  }
  $locale = str_starts_with( determine_locale(), 'de' ) ? 'de' : 'en';
  $updater = new $class(
   SITE_ESSENTIALS_FILE, 'https://github.com/deckerweb/site-essentials', 'Site Essentials',
   __( 'Configure WordPress core behavior with reusable presets.', 'site-essentials' ),
   array(
    'icons' => array( 'svg' => plugins_url( 'assets/images/icon.svg', SITE_ESSENTIALS_FILE ), '1x' => plugins_url( 'assets/images/icon-128.png', SITE_ESSENTIALS_FILE ), '2x' => plugins_url( 'assets/images/icon-256.png', SITE_ESSENTIALS_FILE ) ),
    'banners' => array( 'low' => plugins_url( 'assets/images/banner-' . $locale . '-772x250.png', SITE_ESSENTIALS_FILE ), 'high' => plugins_url( 'assets/images/banner-' . $locale . '-1544x500.png', SITE_ESSENTIALS_FILE ) ),
   ),
   $options
  );
  $updater->register();
  add_filter( 'upgrader_source_selection', array( self::class, 'source' ), 30, 4 );
 }
 /** Show a permission-protected shared-component compatibility notice.
  * @return void
  */
 public static function notice(): void {
  if ( current_user_can( 'update_plugins' ) ) {
   echo '<div class="notice notice-warning"><p>' . esc_html__( 'Site Essentials updates are paused because another plugin loaded an incompatible deckerweb Updater. Update that plugin before checking again.', 'site-essentials' ) . '</p></div>';
  }
 }
 /** Validate only native single/bulk/automatic updates for this exact plugin.
  * @param mixed $source Extracted source directory or an earlier error.
  * @param string $remote_source Native extraction root; not changed by this validator.
  * @param object $upgrader Native upgrader; bulk plugin context may omit type/action.
  * @param array $context Native per-package update context.
  * @return mixed Original source on success/unrelated work, or WP_Error before replacement.
  */
 public static function source( $source, string $remote_source, $upgrader, array $context ) {
  $basename = plugin_basename( SITE_ESSENTIALS_FILE );
  if ( is_wp_error( $source ) || ( $context['plugin'] ?? '' ) !== $basename
   || ( isset( $context['type'] ) && 'plugin' !== $context['type'] )
   || ( isset( $context['action'] ) && 'update' !== $context['action'] )
   || ( ! isset( $context['type'], $context['action'] ) && ! ( $upgrader instanceof \Plugin_Upgrader && true === $upgrader->bulk ) ) ) {
   return $source;
  }
  $invalid = new \WP_Error( 'site_essentials_update_invalid', __( 'The Site Essentials update package could not be verified. The installed plugin has not been replaced.', 'site-essentials' ) );
  if ( ! is_string( $source ) ) { return $invalid; }
  $directory = realpath( $source );
  $file = realpath( rtrim( $source, '/\\' ) . '/site-essentials.php' );
  if ( false === $directory || false === $file || dirname( $file ) !== $directory || ! is_readable( $file ) ) { return $invalid; }
  $header = get_file_data( $file, array( 'name' => 'Plugin Name', 'version' => 'Version', 'uri' => 'Update URI', 'wp' => 'Requires at least', 'php' => 'Requires PHP', 'domain' => 'Text Domain' ), 'plugin' );
  $offers = get_site_transient( 'update_plugins' );
  $offer = is_object( $offers ) && isset( $offers->response[ $basename ] ) ? $offers->response[ $basename ] : null;
  $version = is_object( $offer ) ? ( $offer->new_version ?? null ) : null;
  if ( 'Site Essentials' !== $header['name'] || 'site-essentials' !== $header['domain']
   || 'https://github.com/deckerweb/site-essentials' !== $header['uri']
   || ! is_string( $version ) || $version !== $header['version']
   || ! preg_match( '/^\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?$/D', $version )
   || ! version_compare( $version, SITE_ESSENTIALS_VERSION, '>' ) ) { return $invalid; }
  foreach ( array( 'wp', 'php' ) as $requirement ) {
   if ( ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/D', $header[ $requirement ] ) ) { return $invalid; }
  }
  global $wp_version;
  if ( ! version_compare( $wp_version, $header['wp'], '>=' ) || ! version_compare( PHP_VERSION, $header['php'], '>=' ) ) {
   return new \WP_Error( 'site_essentials_update_requirements', __( 'The Site Essentials update requires a newer WordPress or PHP version. The installed plugin has not been replaced.', 'site-essentials' ) );
  }
  return $source;
 }
}
