<?php
/** Small opt-in media and native list conveniences. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;
/** Read existing image metadata; leave content and personal Screen Options intact. */
final class MediaComfort {
 /** Register cheap gated native hooks. @return void */
 public function boot(): void {
  add_filter( 'sanitize_file_name', array( $this, 'filename' ), 20 );
  add_action( 'restrict_manage_posts', array( $this, 'search_control' ) );
  add_action( 'pre_get_posts', array( $this, 'search_query' ) );
  add_filter( 'query_vars', array( $this, 'query_vars' ) );
  add_action( 'add_meta_boxes_attachment', array( $this, 'image_box' ) );
  add_action( 'current_screen', array( $this, 'columns' ) );
  add_filter( 'manage_users_columns', array( $this, 'user_columns' ) );
  add_filter( 'manage_users_custom_column', array( $this, 'user_value' ), 10, 3 );
 }
 /** Register a read-only, allowlisted native search query variable. @param array $vars Native query names. @return array */
 public function query_vars( array $vars ): array { $vars[] = 'se_title_search'; return $vars; }
 /** Normalize new names through WordPress, never rename existing files.
  * @param string $name Sanitized candidate filename.
  * @return string Filename for native uniqueness checks.
  */
 public function filename( string $name ): string { return Settings::get( 'upload_lowercase' ) ? ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name ) ) : $name; }
 /** Native title-only search choice, isolated from frontend and media grids. @return void */
 public function search_control(): void {
  $screen = get_current_screen(); $type = $screen && $screen->post_type ? get_post_type_object( $screen->post_type ) : null;
  if ( ! Settings::get( 'admin_title_search' ) || ! $screen || 'edit' !== $screen->base || ! $type || ! current_user_can( $type->cap->edit_posts ) ) { return; }
  $title = in_array( get_query_var( 'se_title_search' ), array( 1, '1' ), true );
  echo '<label for="se-title-search" class="screen-reader-text">' . esc_html__( 'Search in', 'site-essentials' ) . '</label><select id="se-title-search" name="se_title_search"><option value="0">' . esc_html__( 'Title and content', 'site-essentials' ) . '</option><option value="1" ' . selected( $title, true, false ) . '>' . esc_html__( 'Title only', 'site-essentials' ) . '</option></select>';
 }
 /** Restrict only an explicitly chosen native admin main search.
  * @param \WP_Query $query Current query.
  * @return void
  */
 public function search_query( \WP_Query $query ): void {
  if ( ! is_admin() || is_network_admin() || ! $query->is_main_query() || ! $query->is_search() || ! Settings::get( 'admin_title_search' ) ) { return; }
  $screen = get_current_screen();
  if ( ! $screen || 'edit' !== $screen->base || empty( $screen->post_type ) ) { return; }
  $type = get_post_type_object( $screen->post_type );
  if ( ! $type || ! current_user_can( $type->cap->edit_posts ) ) { return; }
  if ( in_array( $query->get( 'se_title_search' ), array( 1, '1' ), true ) ) { $query->set( 'search_columns', array( 'post_title' ) ); }
 }
 /** Register optional read-only image information.
  * @param \WP_Post $post Native attachment.
  * @return void
  */
 public function image_box( \WP_Post $post ): void {
  if ( Settings::get( 'image_variants' ) && wp_attachment_is_image( $post ) && current_user_can( 'edit_post', $post->ID ) ) { add_meta_box( 'se-image-variants', __( 'Image files and sizes', 'site-essentials' ), array( $this, 'image_panel' ), 'attachment', 'normal', 'low' ); }
 }
 /** Display at most one bounded image's native metadata; never fetch remote URLs.
  * @param \WP_Post $post Attachment being edited.
  * @return void
  */
 public function image_panel( \WP_Post $post ): void {
  if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
  $meta = wp_get_attachment_metadata( $post->ID );
  if ( ! is_array( $meta ) ) { echo '<p>' . esc_html__( 'Image metadata is unavailable.', 'site-essentials' ) . '</p>'; return; }
  $rows = array( 'full' => $meta ); $sizes = is_array( $meta['sizes'] ?? null ) ? $meta['sizes'] : array();
  $rows += array_slice( $sizes, 0, 100, true ); $file = get_attached_file( $post->ID );
  echo '<p>' . esc_html__( 'Read-only image information. No files are regenerated or deleted. Remote file sizes are not requested.', 'site-essentials' ) . '</p><div style="overflow:auto" tabindex="0" role="region" aria-label="' . esc_attr__( 'Image files and sizes', 'site-essentials' ) . '"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Size', 'site-essentials' ) . '</th><th>' . esc_html__( 'Dimensions', 'site-essentials' ) . '</th><th>' . esc_html__( 'File size', 'site-essentials' ) . '</th><th>URL</th></tr></thead><tbody>';
  foreach ( $rows as $size => $data ) {
   if ( ! is_array( $data ) ) { continue; } $src = wp_get_attachment_image_src( $post->ID, $size ); if ( ! $src ) { continue; }
   $bytes = isset( $data['filesize'] ) && is_numeric( $data['filesize'] ) ? (int) $data['filesize'] : -1;
   $local_file = 'full' === $size ? $file : ( $file && isset( $data['file'] ) && is_string( $data['file'] ) ? dirname( $file ) . '/' . wp_basename( $data['file'] ) : '' );
   if ( $bytes < 0 && $local_file && ! wp_is_stream( $local_file ) && is_file( $local_file ) ) { $bytes = (int) filesize( $local_file ); }
   echo '<tr><th scope="row">' . esc_html( $size ) . '</th><td>' . esc_html( absint( $src[1] ) . ' × ' . absint( $src[2] ) ) . '</td><td>' . esc_html( $bytes >= 0 ? size_format( $bytes ) : __( 'Not available', 'site-essentials' ) ) . '</td><td><a href="' . esc_url( $src[0] ) . '">' . esc_html( $src[0] ) . '</a></td></tr>';
  }
  if ( ! empty( $meta['original_image'] ) ) {
   $original = wp_get_original_image_path( $post->ID ); $url = wp_get_original_image_url( $post->ID );
   $local = $original && ! wp_is_stream( $original ) && is_file( $original ); $dimensions = $local ? wp_getimagesize( $original ) : false;
   if ( $url ) { echo '<tr><th scope="row">' . esc_html__( 'Original upload', 'site-essentials' ) . '</th><td>' . esc_html( $dimensions ? $dimensions[0] . ' × ' . $dimensions[1] : __( 'Not available', 'site-essentials' ) ) . '</td><td>' . esc_html( $local ? size_format( filesize( $original ) ) : __( 'Not available', 'site-essentials' ) ) . '</td><td><a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a></td></tr>'; }
  }
  echo '</tbody></table></div>';
  if ( count( $sizes ) > 100 ) { echo '<p>' . esc_html__( 'Only the first 100 generated sizes are listed.', 'site-essentials' ) . '</p>'; }
 }
 /** Register extra columns for editable public content types only.
  * @param \WP_Screen $screen Native screen.
  * @return void
  */
 public function columns( \WP_Screen $screen ): void {
  if ( 'edit' !== $screen->base || $screen->in_admin( 'network' ) || ! $screen->post_type ) { return; }
  $type = get_post_type_object( $screen->post_type );
  if ( ! $type || ! $type->public || ! current_user_can( $type->cap->edit_posts ) ) { return; }
  add_filter( 'manage_edit-' . $type->name . '_sortable_columns', array( $this, 'sortable' ) );
  add_filter( 'manage_' . $type->name . '_posts_columns', array( $this, 'content_columns' ) );
  add_action( 'manage_' . $type->name . '_posts_custom_column', array( $this, 'content_value' ), 10, 2 );
 }
 /** Add only the selected optional columns.
  * @param array $columns Existing columns.
  * @return array Preserves other plugins and screen preferences.
  */
 public function content_columns( array $columns ): array {
  $selected = Settings::get( 'extra_columns' );
  if ( in_array( 'image', $selected, true ) ) { $columns['se_featured_image'] = __( 'Featured image', 'site-essentials' ); }
  if ( in_array( 'modified', $selected, true ) ) { $columns['se_modified'] = __( 'Last modified', 'site-essentials' ); }
  return $columns;
 }
 /** Use the native modified-date query order only when that column is enabled.
  * @param array $columns Existing sortable headings.
  * @return array Adds no default sorting or query filters.
  */
 public function sortable( array $columns ): array { if ( in_array( 'modified', Settings::get( 'extra_columns' ), true ) ) { $columns['se_modified'] = array( 'modified', true ); } return $columns; }
 /** Render without changing dates or post data.
  * @param string $column Column key.
  * @param int $id Content ID.
  * @return void
  */
 public function content_value( string $column, int $id ): void {
  if ( ! current_user_can( 'edit_post', $id ) ) { return; }
  if ( 'se_featured_image' === $column ) { echo wp_kses_post( get_the_post_thumbnail( $id, array( 48, 48 ) ) ); }
  if ( 'se_modified' === $column ) { /* translators: Native WordPress date-column format: 1: date, 2: time. */
   $core = get_translations_for_domain( 'default' );
   echo esc_html( sprintf( $core->translate( '%1$s at %2$s' ), get_post_modified_time( $core->translate( 'Y/m/d' ), false, $id, true ), get_post_modified_time( $core->translate( 'g:i a' ), false, $id, true ) ) ); }
 }
 /** Add registration date for authorized administrators.
  * @param array $columns Current columns.
  * @return array Updated headings.
  */
 public function user_columns( array $columns ): array { if ( ! is_network_admin() && current_user_can( 'manage_options' ) && current_user_can( 'list_users' ) && in_array( 'registered', Settings::get( 'extra_columns' ), true ) ) { $columns['se_registered'] = __( 'Registered', 'site-essentials' ); } return $columns; }
 /** Display WordPress's UTC registration date in the site timezone.
  * @param string $value Existing value.
  * @param string $column Column key.
  * @param int $id User ID.
  * @return string Escaped date or original output.
  */
 public function user_value( string $value, string $column, int $id ): string {
  if ( 'se_registered' !== $column || ! current_user_can( 'manage_options' ) || ! current_user_can( 'list_users' ) || is_network_admin() ) { return $value; }
  $user = get_userdata( $id ); $stamp = $user ? strtotime( $user->user_registered . ' UTC' ) : false;
  return $stamp ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stamp ) ) : esc_html__( 'Not available', 'site-essentials' );
 }
}
