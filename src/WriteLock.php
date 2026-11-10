<?php
/** Request-lifetime serialization for related administrative writes. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;
/** Serializes cooperating SE writers before revision checks; never changes capabilities. */
final class WriteLock {
 private static string $name = '';
 /** Pinned core database and native session; never reused after a lost lock. @var \wpdb|null */
 private static $database = null;
 /** Native mysqli session that owns the advisory lock. @var \mysqli|null */
 private static $connection = null;
 private static int $connection_id = 0;
 private static int $reconnect_retries = 0;
 private static bool $lost = false;
 /** SQLite file descriptor, used only for advisory locking, never database I/O. @var resource|null */
 private static $file = null;
 /** Acquire a connection-bound MySQL lock or a kernel lock beside the SQLite database.
  * The scope covers this installation's network so network/local reset cannot overlap.
  * No timeout lease is stolen from a live writer; process exit releases the lock.
  * @return bool False on contention or unavailable lock support; fail closed.
  */
 public static function acquire(): bool {
  global $wpdb;
  if ( self::$lost ) { return false; }
  if ( '' !== self::$name ) { if ( ! self::owns_mysql_lock() ) { self::stop_lost_lock(); } return true; }
  if ( is_resource( self::$file ) ) { return true; }
  if ( $wpdb instanceof \WP_SQLite_DB ) {
   if ( ! function_exists( 'flock' ) || ! defined( 'FQDB' ) || ! is_string( FQDB ) || is_link( FQDB ) || ! is_file( FQDB ) ) { return false; }
   $path = dirname( FQDB ) . '/.site-essentials-write.lock';
   if ( is_link( $path ) ) { return false; }
   $file = fopen( $path, 'c' );
   if ( false === $file ) { return false; }
   if ( ! flock( $file, LOCK_EX | LOCK_NB ) ) { fclose( $file ); return false; }
   self::$file = $file;
  } else {
   // Core wpdb exposes these members through its documented compatibility accessors.
   // A custom reconnecting driver needs its own verified session contract; fail closed.
   if ( get_class( $wpdb ) !== 'wpdb' || ! $wpdb->dbh instanceof \mysqli || ! is_int( $wpdb->reconnect_retries ) ) { return false; }
   self::$database = $wpdb;
   self::$connection = $wpdb->dbh;
   self::$reconnect_retries = $wpdb->reconnect_retries;
   $wpdb->reconnect_retries = 0;
   $name = substr( 'se_' . hash( 'sha256', DB_NAME . '|' . $wpdb->base_prefix ), 0, 64 );
   try {
    self::$connection_id = mysqli_thread_id( self::$connection );
    $result = mysqli_query( self::$connection, $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) );
    $row = $result instanceof \mysqli_result ? mysqli_fetch_row( $result ) : null;
    if ( $result instanceof \mysqli_result ) { mysqli_free_result( $result ); }
   } catch ( \Throwable $error ) { $row = null; }
   if ( ! is_array( $row ) || '1' !== (string) $row[0] ) {
    $wpdb->reconnect_retries = self::$reconnect_retries;
    self::$database = null; self::$connection = null;
    return false;
   }
   self::$name = $name;
   add_filter( 'query', array( self::class, 'guard_query' ), PHP_INT_MAX );
   if ( ! self::owns_mysql_lock() ) { self::stop_lost_lock(); }
  }
  // A request may have primed options before the preceding writer finished.
  wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
  $keys = $wpdb->get_col( $wpdb->prepare( 'SELECT option_name FROM %i WHERE option_name LIKE %s', $wpdb->options, $wpdb->esc_like( 'site_essentials_' ) . '%' ) );
  foreach ( $keys as $key ) { wp_cache_delete( $key, 'options' ); }
  wp_cache_delete( get_current_user_id(), 'user_meta' );
  if ( is_multisite() ) { wp_cache_delete( get_current_network_id() . ':site_essentials_network', 'site-options' ); }
  register_shutdown_function( array( self::class, 'release' ) );
  return true;
 }
 /** Release only this request's lock. No stored data or lock files are deleted. @return void */
 public static function release(): void {
  global $wpdb;
  if ( is_resource( self::$file ) ) { flock( self::$file, LOCK_UN ); fclose( self::$file ); self::$file = null; }
  if ( '' !== self::$name && ! self::$lost ) {
   if ( ! self::owns_mysql_lock() ) { self::$lost = true; return; }
   try {
    $result = mysqli_query( self::$connection, self::$database->prepare( 'SELECT RELEASE_LOCK(%s)', self::$name ) );
    if ( $result instanceof \mysqli_result ) { mysqli_free_result( $result ); }
   } catch ( \Throwable $error ) { self::$lost = true; return; }
   remove_filter( 'query', array( self::class, 'guard_query' ), PHP_INT_MAX );
   self::$database->reconnect_retries = self::$reconnect_retries;
   self::$database = null; self::$connection = null; self::$name = '';
  }
 }
 /** Verify the original native session still owns this request's lock without wpdb retry.
  * @return bool False after any handle/session/owner change or native query failure.
  */
 private static function owns_mysql_lock(): bool {
  global $wpdb;
  if ( self::$lost || ! self::$database || $wpdb !== self::$database || $wpdb->dbh !== self::$connection ) { return false; }
  try {
   if ( mysqli_thread_id( self::$connection ) !== self::$connection_id ) { return false; }
   $result = mysqli_query( self::$connection, self::$database->prepare( 'SELECT IS_USED_LOCK(%s) = CONNECTION_ID()', self::$name ) );
   $row = $result instanceof \mysqli_result ? mysqli_fetch_row( $result ) : null;
   if ( $result instanceof \mysqli_result ) { mysqli_free_result( $result ); }
   return is_array( $row ) && '1' === (string) $row[0];
  } catch ( \Throwable $error ) { return false; }
 }
 /** Guard every query in the critical section, including writes after revision validation.
  * Core retries remain zero until healthy release, closing the check-to-write reconnect gap.
  * @param string $query WordPress SQL statement, returned unchanged only for the pinned owner.
  * @return string Original query on verified ownership; otherwise stops the request.
  */
 public static function guard_query( string $query ): string {
  if ( ! self::owns_mysql_lock() ) { self::stop_lost_lock(); }
  self::$database->reconnect_retries = 0;
  return $query;
 }
 /** Fail the entire request after session loss; never reacquire under stale revisions.
  * @return void Does not return under normal WordPress error handling.
  */
 private static function stop_lost_lock(): void {
  global $wpdb;
  self::$lost = true;
  if ( $wpdb instanceof \wpdb ) { $wpdb->ready = false; }
  if ( self::$database ) { self::$database->ready = false; }
  wp_die( esc_html__( 'The database write lock was lost. Some changes may already have been saved. Reload and check the current state before trying again.', 'site-essentials' ), '', array( 'response' => 409 ) );
  exit;
 }
 /** Stop an authorized write when another SE request owns the lock. @return void */
 public static function require_lock(): void {
  if ( ! self::acquire() ) { wp_die( esc_html__( 'This change could not be reserved safely. Another Site Essentials request may be saving. Reload and try again.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
 }
}
