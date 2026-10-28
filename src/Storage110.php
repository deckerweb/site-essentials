<?php
/** Bounded scheduled local-upload storage diagnosis. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Never traverses the filesystem while displaying a report. */
final class Storage110 {
 /** Current website's cached, non-autoloaded result. */
 const OPTION = 'site_essentials_storage';
 /** Current website's WordPress scheduled event. */
 const EVENT = 'site_essentials_storage_daily';
 /** Coordinate independent cron/manual workers without scanning on every page. @return void */
 public function boot(): void {
  add_action( 'admin_init', array( $this, 'schedule' ) );
  add_action( self::EVENT, array( $this, 'measure' ) );
  add_action( 'admin_post_site_essentials_storage_refresh', array( $this, 'refresh' ) );
 }
 /** Schedule at most one daily event; disabled settings remove this site's event. @return void */
 public function schedule(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { return; }
  if ( ! Settings::get( 'storage_diagnosis' ) ) { wp_clear_scheduled_hook( self::EVENT ); return; }
  if ( ! wp_next_scheduled( self::EVENT ) ) { wp_schedule_event( time() + 60, 'daily', self::EVENT ); }
 }
 /** Measure only local regular files, bounded by time, entries and directory count. @return void */
 public function measure(): void {
  if ( ! Settings::get( 'storage_diagnosis' ) ) { wp_clear_scheduled_hook( self::EVENT ); return; }
  $lock_key = self::OPTION . '_lock'; $old = (int) get_option( $lock_key, 0 );
  if ( $old && $old < time() - 60 ) { delete_option( $lock_key ); }
  if ( ! add_option( $lock_key, time(), '', false ) ) { return; }
  $result = array( 'time' => time(), 'status' => 'unavailable', 'bytes' => null, 'files' => 0 );
  try {
   $uploads = wp_get_upload_dir(); $root = realpath( $uploads['basedir'] );
   if ( ! $root || ! is_dir( $root ) || ! empty( $uploads['error'] ) ) { return; }
   $stack = array( $root ); $seen = array(); $entries = 0; $bytes = 0; $started = microtime( true ); $status = 'complete';
   while ( $stack ) {
    $directory = array_pop( $stack );
    if ( isset( $seen[ $directory ] ) ) { continue; }
    $seen[ $directory ] = true;
    foreach ( new \DirectoryIterator( $directory ) as $entry ) {
     if ( ++$entries > 10000 || count( $seen ) + count( $stack ) > 512 || microtime( true ) - $started > 2 ) { $status = 'limit'; break 2; }
     if ( $entry->isDot() || $entry->isLink() ) { continue; }
     $path = $entry->getPathname();
     if ( $entry->isDir() ) {
      // A network main site's upload root can contain the other sites' directories.
      if ( is_multisite() && is_main_site() && $path === $root . DIRECTORY_SEPARATOR . 'sites' ) { continue; }
      $stack[] = $path;
     } elseif ( $entry->isFile() ) { $bytes += $entry->getSize(); ++$result['files']; }
    }
   }
   $result['status'] = $status; $result['bytes'] = 'complete' === $status ? $bytes : null;
  } catch ( \UnexpectedValueException $error ) { $result['status'] = 'unavailable'; }
  catch ( \RuntimeException $error ) { $result['status'] = 'unavailable'; }
  finally { update_option( self::OPTION, $result, false ); delete_option( $lock_key ); }
 }
 /** Queue a separate measurement request with a fifteen-minute minimum interval. @return void */
 public function refresh(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ! Settings::get( 'storage_diagnosis' ) ) { wp_die( esc_html__( 'Permission denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( 'site_essentials_storage_refresh' );
  $result = get_option( self::OPTION, array() );
  if ( time() - (int) ( $result['time'] ?? 0 ) >= 15 * MINUTE_IN_SECONDS ) {
   wp_clear_scheduled_hook( self::EVENT ); wp_schedule_event( time() + 10, 'daily', self::EVENT );
  }
  wp_safe_redirect( admin_url( 'tools.php?page=site-essentials-php' ) ); exit;
 }
 /** Render cached figures without reading directories or performing a measurement. @return void */
 public static function panel(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() || ! Settings::get( 'storage_diagnosis' ) ) { return; }
  $result = get_option( self::OPTION, array() );
  echo '<div class="se-card"><h3>' . esc_html__( 'Local upload storage', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Cached diagnosis, refreshed daily through WordPress scheduled tasks. Only this website local uploads are measured; symlinks and other Multisite upload directories are skipped. This is not the hosting quota, database size or remote media storage.', 'site-essentials' ) . '</p>';
  if ( ! empty( $result['time'] ) ) {
   echo '<p>' . esc_html__( 'Last measurement:', 'site-essentials' ) . ' ' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $result['time'] ) ) . '</p><p>' . esc_html( 'complete' === ( $result['status'] ?? '' ) ? size_format( (int) $result['bytes'] ) : ( 'limit' === ( $result['status'] ?? '' ) ? __( 'Incomplete measurement: a safety limit was reached. No partial total is displayed.', 'site-essentials' ) : __( 'Not available: the upload directory could not be measured.', 'site-essentials' ) ) ) . '</p>';
  } else { echo '<p>' . esc_html__( 'No measurement yet. The first scheduled task will create a result.', 'site-essentials' ) . '</p>'; }
  echo '<p>' . esc_html__( 'At most 10,000 directory entries, 512 directories and two seconds per measurement. Refresh requests are limited to once per fifteen minutes. WordPress cron can run later than scheduled when traffic or hosting scheduling is limited.', 'site-essentials' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
  wp_nonce_field( 'site_essentials_storage_refresh' );
  echo '<input type="hidden" name="action" value="site_essentials_storage_refresh"><button class="button" type="submit">' . esc_html__( 'Request updated measurement', 'site-essentials' ) . '</button></form></div>';
 }
 /** Remove only this site's diagnostic cache and scheduled task. @return void */
 public static function clear(): void { wp_clear_scheduled_hook( self::EVENT ); delete_option( self::OPTION ); delete_option( self::OPTION . '_lock' ); }
 /** Cancel own tasks on deactivation; retain cached data and all settings.
  * @param bool $network Network-wide deactivation flag.
  * @return void
  */
 public static function deactivate( bool $network = false ): void {
  if ( ! $network || ! is_multisite() ) { wp_clear_scheduled_hook( self::EVENT ); return; }
  $offset = 0;
  do {
   $ids = get_sites( array( 'network_id' => get_current_network_id(), 'fields' => 'ids', 'number' => 100, 'offset' => $offset ) );
   foreach ( $ids as $id ) { switch_to_blog( (int) $id ); wp_clear_scheduled_hook( self::EVENT ); restore_current_blog(); }
   $offset += 100;
  } while ( count( $ids ) === 100 );
 }
}
