<?php
/** Focused native media-library enhancements. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Extends native screens without replacing files or permitting new upload types. */
final class Media {
 /** Site-local numeric index; -1 means unavailable. */
 public const SIZE_KEY = '_site_essentials_file_size';
 /** Register lazy screen and attachment hooks. @return void */
 public function boot(): void {
  add_filter( 'manage_upload_columns', array( $this, 'columns' ) );
  add_action( 'manage_media_custom_column', array( $this, 'column' ), 10, 2 );
  add_filter( 'manage_upload_sortable_columns', array( $this, 'sortable' ) );
  add_filter( 'posts_clauses', array( $this, 'sort' ), 20, 2 );
  add_filter( 'post_mime_types', array( $this, 'list_types' ) );
  add_filter( 'media_view_settings', array( $this, 'grid_types' ) );
  add_filter( 'ajax_query_attachments_args', array( $this, 'grid_query' ) );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'wp_ajax_site_essentials_media_index', array( $this, 'ajax_index' ) );
  add_action( 'added_post_meta', array( $this, 'changed' ), 10, 4 );
  add_action( 'updated_post_meta', array( $this, 'changed' ), 10, 4 );
 }
 /** Identify the native media screen, excluding Network Admin. @return bool */
 private function screen(): bool { return is_admin() && ! is_network_admin() && function_exists( 'get_current_screen' ) && 'upload' === ( get_current_screen()->id ?? '' ); }
 /** Known document families and MIME types; no upload allowlist changes. @return array */
 public function types(): array { return array(
  'pdf' => array( 'PDF', array( 'application/pdf' ) ),
  'word' => array( 'Word', array( 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-word.document.macroEnabled.12' ) ),
  'excel' => array( 'Excel', array( 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel.sheet.macroEnabled.12' ) ),
  'powerpoint' => array( 'PowerPoint', array( 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation' ) ),
  'zip' => array( 'ZIP', array( 'application/zip', 'application/x-zip-compressed' ) ),
  'opendocument' => array( 'OpenDocument', array( 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation' ) ),
  'csv' => array( 'CSV', array( 'text/csv' ) ),
  'text' => array( __( 'Text files', 'site-essentials' ), array( 'text/plain' ) ),
 ); }
 /** Add native list filters only on the media screen.
  * @param array $types Native MIME groups.
  * @return array
  */
 public function list_types( array $types ): array {
  if ( ! $this->screen() || ! Settings::get( 'media_types' ) ) { return $types; }
  foreach ( $this->types() as $type ) { $types[ implode( ',', $type[1] ) ] = array( $type[0], $type[0], $type[0] ); }
  return $types;
 }
 /** Extend the existing grid type selector.
  * @param array $settings Native media settings.
  * @return array
  */
 public function grid_types( array $settings ): array {
  if ( ! $this->screen() || ! Settings::get( 'media_types' ) ) { return $settings; }
  foreach ( $this->types() as $key => $type ) { $settings['mimeTypes'][ 'se-media-' . $key ] = $type[0]; }
  return $settings;
 }
 /** Translate only known grid tokens to native MIME constraints.
  * @param array $query Native authorized attachment query.
  * @return array
  */
 public function grid_query( array $query ): array {
  $mime = $query['post_mime_type'] ?? '';
  if ( Settings::get( 'media_types' ) && is_string( $mime ) && str_starts_with( $mime, 'se-media-' ) ) {
   $type = $this->types()[ substr( $mime, 9 ) ] ?? null;
   $query['post_mime_type'] = $type ? $type[1] : 'application/x-site-essentials-unknown';
  }
  return $query;
 }
 /** Add a column; sorting also implies its visibility.
  * @param array $columns Native list columns.
  * @return array
  */
 public function columns( array $columns ): array {
  if ( Settings::get( 'media_size' ) || Settings::get( 'media_size_sort' ) ) { $columns['se_file_size'] = __( 'File size', 'site-essentials' ); }
  return $columns;
 }
 /** Read only a real attached file within this site's uploads; never fetch URLs.
  * @param int $id Attachment ID.
  * @return int Bytes or -1 when unavailable.
  */
 public function size( int $id ): int {
  if ( 'attachment' !== get_post_type( $id ) ) { return -1; }
  $file = get_attached_file( $id, true ); $uploads = wp_get_upload_dir();
  if ( ! is_string( $file ) || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) { return -1; }
  $base = realpath( $uploads['basedir'] ); $path = realpath( $file );
  if ( false === $base || false === $path || ! str_starts_with( wp_normalize_path( $path ), trailingslashit( wp_normalize_path( $base ) ) ) || ! is_file( $path ) || ! is_readable( $path ) ) { return -1; }
  $bytes = filesize( $path ); return false === $bytes ? -1 : (int) $bytes;
 }
 /** Render the size without exposing filesystem paths.
  * @param string $column Column key.
  * @param int $id Attachment ID.
  * @return void
  */
 public function column( string $column, int $id ): void {
  if ( 'se_file_size' !== $column || ( ! Settings::get( 'media_size' ) && ! Settings::get( 'media_size_sort' ) ) ) { return; }
  $size = $this->size( $id );
  echo esc_html( $size >= 0 ? size_format( $size, 1 ) : __( 'Unavailable', 'site-essentials' ) );
 }
 /** Register the native list-column sorting key.
  * @param array $columns Native sortable columns.
  * @return array
  */
 public function sortable( array $columns ): array {
  if ( Settings::get( 'media_size_sort' ) ) { $columns['se_file_size'] = 'se_file_size'; }
  return $columns;
 }
 /** Sort only the native main attachment list; preserve unindexed attachments.
  * @param array $clauses SQL clauses.
  * @param \WP_Query $query Current query.
  * @return array
  */
 public function sort( array $clauses, \WP_Query $query ): array {
  if ( ! $this->screen() || ! Settings::get( 'media_size_sort' ) || ! $query->is_main_query() || 'attachment' !== $query->get( 'post_type' ) || 'se_file_size' !== $query->get( 'orderby' ) ) { return $clauses; }
  global $wpdb;
  // A scalar subquery avoids duplicate rows and retains missing metadata, pagination and existing constraints.
  $value = $wpdb->prepare( "(SELECT MAX(CAST(se_size.meta_value AS SIGNED)) FROM {$wpdb->postmeta} se_size WHERE se_size.post_id = {$wpdb->posts}.ID AND se_size.meta_key = %s)", self::SIZE_KEY );
  $order = 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';
  $clauses['orderby'] = "CASE WHEN COALESCE($value, -1) < 0 THEN 1 ELSE 0 END ASC, $value $order, {$wpdb->posts}.ID ASC";
  return $clauses;
 }
 /** Refresh derived metadata after native file/metadata updates.
  * @param int $meta_id Metadata row ID.
  * @param int $id Attachment ID.
  * @param string $key Changed metadata key.
  * @param mixed $value New metadata value.
  * @return void
  */
 public function changed( int $meta_id, int $id, string $key, $value ): void {
  if ( ! in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata' ), true ) || 'attachment' !== get_post_type( $id ) ) { return; }
  if ( Settings::get( 'media_size_sort' ) ) { clearstatcache(); update_post_meta( $id, self::SIZE_KEY, $this->size( $id ) ); }
  else { delete_post_meta( $id, self::SIZE_KEY ); }
 }
 /** Index at most 50 existing attachments per request, in the current site only. @return array */
 public function batch(): array {
  global $wpdb;
  $ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'attachment' AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = %s) ORDER BY p.ID ASC LIMIT 50", self::SIZE_KEY ) );
  if ( $wpdb->last_error ) { return array( 'processed' => 0, 'done' => false, 'failed' => true ); }
  foreach ( $ids as $id ) {
   $size = $this->size( (int) $id ); update_post_meta( (int) $id, self::SIZE_KEY, $size );
   if ( (string) $size !== (string) get_post_meta( (int) $id, self::SIZE_KEY, true ) ) { return array( 'processed' => 0, 'done' => false, 'failed' => true ); }
  }
  return array( 'processed' => count( $ids ), 'done' => count( $ids ) < 50, 'failed' => false );
 }
 /** Protect each indexing request by native capability and a site-bound nonce. @return void */
 public function ajax_index(): void {
  if ( ! current_user_can( 'upload_files' ) || is_network_admin() || ! Settings::get( 'media_size_sort' ) ) { wp_send_json_error( null, 403 ); }
  $nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : null;
  if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'se_media_index_' . get_current_blog_id() ) ) { wp_send_json_error( null, 403 ); }
  $batch = $this->batch();
  if ( $batch['failed'] ) { wp_send_json_error( null, 500 ); }
  wp_send_json_success( $batch );
 }
 /** Run indexing only while an authorized user has the native media screen open.
  * @param string $hook Current admin hook.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( 'upload.php' !== $hook || ! $this->screen() || ! current_user_can( 'upload_files' ) || ! Settings::get( 'media_size_sort' ) ) { return; }
  wp_enqueue_script( 'site-essentials-media', plugins_url( 'assets/media.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION, true );
  wp_localize_script( 'site-essentials-media', 'siteEssentialsMedia', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'se_media_index_' . get_current_blog_id() ), 'working' => __( 'Preparing file-size sorting…', 'site-essentials' ), 'ready' => __( 'File-size sorting is ready. Reload the list to use the updated index.', 'site-essentials' ), 'failed' => __( 'File-size indexing paused. Reopen the media library to retry.', 'site-essentials' ), 'stop' => __( 'Pause indexing', 'site-essentials' ) ) );
 }
 /** Clear derived metadata in the current site without touching files. @return bool */
 public static function clear(): bool {
  global $wpdb;
  $exists = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1", self::SIZE_KEY ) );
  if ( $wpdb->last_error ) { return false; }
  return null === $exists || delete_metadata( 'post', 0, self::SIZE_KEY, '', true );
 }
}
