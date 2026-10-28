<?php
/** Local default-avatar selection, independent of portable presets. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;
/** Resolves safe local replacement images and keeps the shipped fallback available. */
final class DefaultAvatar {
 const OPTION = 'site_essentials_default_avatar';
 /** Register own form handling and the media usage hint. @return void */
 public function boot(): void { add_action( 'admin_post_site_essentials_default_avatar', array( $this, 'save' ) ); add_filter( 'attachment_fields_to_edit', array( $this, 'usage' ), 10, 2 ); }
 /** Accept a raster attachment that is public, locally served and physically available.
  * @param int $id Current-site attachment ID.
  * @param int $size Requested display size.
  * @return string Empty if it cannot safely provide a local image.
  */
 public static function image( int $id, int $size = 96 ): string {
  $post = $id > 0 ? get_post( $id ) : null;
  if ( ! $post || ! in_array( $post->post_status, array( 'inherit', 'publish' ), true ) ) { return ''; } $file = get_attached_file( $id );
  if ( ! is_string( $file ) || wp_is_stream( $file ) || ! is_file( $file ) ) { return ''; }
  return ( new Avatars() )->image( $id, PHP_INT_MAX );
 }
 /** Resolve developer override, saved image and shipped fallback in that order.
  * @return array{id:int,url:string,filtered:bool} The effective current-site default.
  */
 public static function current(): array {
  /**
   * Filters the current site's local default-avatar attachment ID.
   *
   * Return a positive integer for a locally stored, public raster attachment.
   * Return 0 for no override. Invalid/unavailable values fall back to the saved
   * image, then the shipped SE placeholder. Personal user images remain first.
   *
   * @since 1.1.0
   * @param int $attachment_id Default 0, meaning no developer override.
   */
  $override = apply_filters( 'site_essentials_local_avatar_default_id', 0 );
  $url = is_int( $override ) && $override > 0 ? self::image( $override ) : '';
  if ( $url ) { return array( 'id' => $override, 'url' => $url, 'filtered' => true ); }
  $raw = get_option( self::OPTION, 0 ); $id = is_int( $raw ) || ( is_string( $raw ) && ctype_digit( $raw ) ) ? max( 0, (int) $raw ) : 0; $url = self::image( $id );
  return array( 'id' => $url ? $id : 0, 'url' => $url ?: ( new Avatars() )->shipped_default(), 'filtered' => false );
 }
 /** Render a preview and detached-form controls below the master option. @return void */
 public static function panel(): void {
  if ( Avatars::external_provider() ) { return; } $current = self::current();
  echo '<div class="se-card se-local-panel" id="se-default-avatar-panel"' . ( ! Settings::get( 'local_avatars' ) ? ' hidden' : '' ) . '><h3>' . esc_html__( 'Default image for local avatars', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Used when a user has no personal image. Save this site-local image separately; its media ID is never included in configuration or preset exports.', 'site-essentials' ) . '</p><img id="se-default-avatar-preview" class="se-avatar-default-preview" src="' . ( str_starts_with( $current['url'], 'data:image/svg+xml;base64,' ) ? esc_attr( $current['url'] ) : esc_url( $current['url'] ) ) . '" width="96" height="96" alt="' . esc_attr__( 'Current default avatar', 'site-essentials' ) . '">';
  if ( $current['filtered'] ) { echo '<p role="status">' . esc_html__( 'A developer filter supplies this default image. Image selection is paused; the saved choice is retained.', 'site-essentials' ) . '</p>'; }
  echo '<input type="hidden" name="default_attachment" id="se-default-avatar-id" value="' . esc_attr( $current['id'] ) . '" form="se-default-avatar-form"><input type="hidden" name="default_mode" id="se-default-avatar-mode" value="select" form="se-default-avatar-form"><fieldset' . ( $current['filtered'] || ! current_user_can( 'upload_files' ) ? ' disabled' : '' ) . '><legend class="screen-reader-text">' . esc_html__( 'Select the default image', 'site-essentials' ) . '</legend><div class="se-control-row"><button type="button" class="button" id="se-default-avatar-choose">' . esc_html__( 'Choose a local image', 'site-essentials' ) . '</button><button type="button" class="button" id="se-default-avatar-reset">' . esc_html__( 'Use the SE default image', 'site-essentials' ) . '</button></div><p><label for="se-default-avatar-file">' . esc_html__( 'Upload an avatar image', 'site-essentials' ) . '</label><input type="file" id="se-default-avatar-file" name="se_default_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" form="se-default-avatar-form"></p><p>' . esc_html__( 'JPEG, PNG, GIF, WebP or AVIF; up to 4 MiB and 4096 × 4096 pixels. Deleting the selected media file restores the shipped SE default; the media file is not locked or copied.', 'site-essentials' ) . '</p><div class="se-control-row"><button class="button" type="submit" form="se-default-avatar-form">' . esc_html__( 'Save default avatar', 'site-essentials' ) . '</button> <button type="button" class="button" data-se-local-discard="se-default-avatar-form">' . esc_html__( 'Discard image selection', 'site-essentials' ) . '</button></div></fieldset><p id="se-default-avatar-status" role="status"></p></div>';
 }
 /** Show an informational usage note, never a deletion lock.
  * @param array $fields Existing native media fields.
  * @param \WP_Post $post Attachment being edited.
  * @return array Native fields with a current-site default usage hint when relevant.
  */
 public function usage( array $fields, \WP_Post $post ): array {
  if ( current_user_can( 'manage_options' ) && ! is_network_admin() && ( $post->ID === (int) get_option( self::OPTION, 0 ) || $post->ID === self::current()['id'] ) ) { $fields['se_default_avatar'] = array( 'label' => __( 'Site Essentials', 'site-essentials' ), 'input' => 'html', 'html' => '<p>' . esc_html__( 'Used as this site’s default avatar. Deleting it restores the shipped SE default image.', 'site-essentials' ) . '</p>' ); }
  return $fields;
 }
 /** Save an authorized local selection/upload without touching personal assignments. @return never */
 public function save(): never {
  SitePanels::authorize( 'site_essentials_default_avatar', self::OPTION );
  check_admin_referer( 'site_essentials_default_avatar_' . get_current_blog_id() );
  if ( Avatars::external_provider() || self::current()['filtered'] || ! current_user_can( 'upload_files' ) ) { wp_die( esc_html__( 'Default image selection is currently unavailable.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $mode = SitePanels::input( 'default_mode' ); $id = 0;
  if ( 'upload' === $mode ) {
   $file = isset( $_FILES['se_default_file'] ) && is_array( $_FILES['se_default_file'] ) ? filter_var_array( map_deep( $_FILES['se_default_file'], 'sanitize_text_field' ), array( 'name' => FILTER_UNSAFE_RAW, 'tmp_name' => FILTER_UNSAFE_RAW, 'error' => FILTER_VALIDATE_INT, 'size' => FILTER_VALIDATE_INT ) ) : null; $mimes = Avatars::upload_mimes();
   if ( ! is_array( $file ) || UPLOAD_ERR_OK !== ( $file['error'] ?? -1 ) || ! is_int( $file['size'] ?? null ) || $file['size'] < 1 || $file['size'] > min( 4194304, wp_max_upload_size() ) || ! is_string( $file['tmp_name'] ?? null ) || ! is_string( $file['name'] ?? null ) || ! is_uploaded_file( $file['tmp_name'] ) ) { self::invalid(); }
   $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes ); $dimensions = wp_getimagesize( $file['tmp_name'] );
   if ( ! $check['ext'] || ! in_array( $check['type'], array_values( $mimes ), true ) || ! $dimensions || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 4096 || $dimensions[1] > 4096 ) { self::invalid(); }
   require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
   $upload = media_handle_upload( 'se_default_file', 0, array(), array( 'test_form' => false, 'mimes' => $mimes ) ); if ( is_wp_error( $upload ) ) { self::invalid(); } $id = (int) $upload;
  } elseif ( 'select' === $mode ) {
   $raw = SitePanels::input( 'default_attachment' ); if ( ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $raw ) ) { self::invalid(); } $id = (int) $raw;
   if ( $id && ! current_user_can( 'read_post', $id ) ) { self::invalid(); }
  } elseif ( 'remove' !== $mode ) { self::invalid(); }
  if ( $id && ! self::image( $id ) ) { self::invalid(); }
  SitePanels::authorize( 'site_essentials_default_avatar', self::OPTION );
  SitePanels::store( self::OPTION, $id ); SitePanels::redirect( 'default_avatar_saved', 'avatars' );
 }
 /** Reject unsuitable input without broadening native upload policy. @return never */
 private static function invalid(): never { wp_die( esc_html__( 'Choose a locally served JPEG, PNG, GIF, WebP or AVIF image up to 4 MiB and 4096 × 4096 pixels.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
}
