<?php
/** Optional private-service-free avatar rendering and authorized local assignments. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Uses core avatar interfaces and site-scoped user metadata, never Gravatar fallbacks. */
final class Avatars {
 private int $depth = 0;
 /** Nested active/neutral scopes per avatar hook; balanced even if settings change. */
 private array $scopes = array();
 private int $profile_user = 0;
 /** Register neutral avatar hooks and protected profile actions. @return void */
 public function boot(): void {
  foreach ( array( 'pre_get_avatar', 'get_avatar', 'pre_get_avatar_data', 'get_avatar_data' ) as $hook ) { add_filter( $hook, array( $this, 'begin' ), -PHP_INT_MAX ); }
  add_filter( 'pre_get_avatar_data', array( $this, 'data' ), PHP_INT_MAX, 2 );
  add_filter( 'get_avatar_data', array( $this, 'data' ), PHP_INT_MAX, 2 );
  add_filter( 'pre_get_avatar', array( $this, 'pre_html' ), PHP_INT_MAX, 3 );
  add_filter( 'get_avatar', array( $this, 'html' ), PHP_INT_MAX, 6 );
  add_filter( 'pre_http_request', array( $this, 'http' ), PHP_INT_MAX, 3 );
  add_filter( 'wp_prepare_attachment_for_js', array( $this, 'media_data' ), PHP_INT_MAX, 3 );
  add_filter( 'wp_resource_hints', array( $this, 'hints' ), PHP_INT_MAX, 2 );
  if ( is_admin() ) {
   add_action( 'show_user_profile', array( $this, 'profile' ) ); add_action( 'edit_user_profile', array( $this, 'profile' ) );
   add_action( 'admin_footer', array( $this, 'form' ) ); add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
   add_action( 'admin_post_site_essentials_avatar_save', array( $this, 'save' ) ); add_action( 'admin_notices', array( $this, 'notice' ) );
  }
 }
 /** Site-scoped metadata avoids sharing attachments across multisite media libraries. @return string */
 public static function key(): string { return 'site_essentials_avatar_' . get_current_blog_id(); }
 /** Hash the actual local assignment records only on explicit admin data-clear flows. @return string */
 public static function revision(): string {
  global $wpdb;
  $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT user_id, meta_value FROM %i WHERE meta_key = %s ORDER BY user_id, umeta_id', $wpdb->usermeta, self::key() ), ARRAY_A );
  return hash( 'sha256', wp_json_encode( $rows ) );
 }
 /** Explicit local clearing removes assignments only and verifies their removal. @return bool */
 public static function clear(): bool {
  global $wpdb;
  delete_metadata( 'user', 0, self::key(), '', true );
  $remaining = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE meta_key = %s', $wpdb->usermeta, self::key() ) );
  return null !== $remaining && 0 === (int) $remaining;
 }
 /** Check the loaded provider's effective rule in the current website context.
  * Rendering registration and a valid local rule are both required. No rules
  * or assignments are rewritten; no cross-site cache or external lookup is used.
  * @return bool Whether Leitstand currently provides local avatars on this site.
  */
 public static function leitstand(): bool {
  $rules = \Deckerweb\Leitstand\NetworkRules::class;
  $avatars = \Deckerweb\Leitstand\LocalAvatars::class;
  if ( ! is_multisite() || ! class_exists( $rules, false ) || ! class_exists( $avatars, false )
   || ! is_callable( array( $rules, 'effective' ) )
   || false === has_filter( 'pre_get_avatar_data', array( $avatars, 'data' ) ) ) { return false; }
  try {
   $effective = $rules::effective();
   return is_array( $effective ) && 'local' === ( $effective['avatars'] ?? null );
  } catch ( \Throwable $error ) { return false; }
 }
 /** Enable this site's avatar configuration only while Leitstand is not its provider. @return bool */
 private function enabled(): bool { return (bool) Settings::get( 'local_avatars' ) && ! self::leitstand(); }
 /** Track network activity occurring inside the avatar filter pipeline.
  * @param mixed $value Incoming value.
  * @return mixed
  */
 public function begin( $value ) {
  $active = $this->enabled(); $this->scopes[ current_filter() ][] = $active;
  if ( $active ) { $this->depth++; } return $value;
 }
 /** Close only the matching avatar-hook scope, including disabled endings. @return void */
 private function finish(): void {
  $hook = current_filter();
  if ( empty( $this->scopes[ $hook ] ) ) { return; }
  if ( array_pop( $this->scopes[ $hook ] ) ) { $this->depth = max( 0, $this->depth - 1 ); }
  if ( ! $this->scopes[ $hook ] ) { unset( $this->scopes[ $hook ] ); }
 }
 /** Compare exact origins; known Gravatar hosts are never accepted.
  * @param string $url Candidate image URL.
  * @return bool
  */
 private function local( string $url ): bool {
  $parts = wp_parse_url( $url );
  if ( ! is_array( $parts ) || ! in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) || empty( $parts['host'] ) || isset( $parts['user'] ) || $this->gravatar( $parts['host'] ) ) { return false; }
  foreach ( array( home_url( '/' ), site_url( '/' ) ) as $origin ) {
   $own = wp_parse_url( $origin );
   if ( strtolower( $parts['host'] ) === strtolower( $own['host'] ?? '' ) && $parts['scheme'] === ( $own['scheme'] ?? '' ) && ( $parts['port'] ?? ( 'https' === $parts['scheme'] ? 443 : 80 ) ) === ( $own['port'] ?? ( 'https' === ( $own['scheme'] ?? '' ) ? 443 : 80 ) ) ) { return true; }
  }
  return false;
 }
 /** Identify avatar-only service domains, not general WordPress update hosts.
  * @param string $host Host.
  * @return bool
  */
 private function gravatar( string $host ): bool { $host = strtolower( rtrim( $host, '.' ) ); return 'gravatar.com' === $host || str_ends_with( $host, '.gravatar.com' ) || 'gravatar.net' === $host || str_ends_with( $host, '.gravatar.net' ); }
 /** Resolve supported core identities without external lookups.
  * @param mixed $identity User/post/comment/email.
  * @return int
  */
 private function user_id( $identity ): int {
  if ( $identity instanceof \WP_User ) { return $identity->ID; }
  if ( $identity instanceof \WP_Post ) { return (int) $identity->post_author; }
  if ( $identity instanceof \WP_Comment ) { return (int) $identity->user_id; }
  if ( is_int( $identity ) || ( is_string( $identity ) && ctype_digit( $identity ) ) ) { return max( 0, (int) $identity ); }
  if ( is_string( $identity ) && is_email( $identity ) ) { $user = get_user_by( 'email', $identity ); return $user ? $user->ID : 0; }
  return 0;
 }
 /** Validate a locally served ordinary raster image; private parent content is not exposed as an avatar.
  * @param int $id Attachment.
  * @param int $size Display size.
  * @return string Empty when unsuitable.
  */
 public function image( int $id, int $size = 96 ): string {
  $post = get_post( $id );
  if ( ! $post || 'attachment' !== $post->post_type || ! in_array( get_post_mime_type( $post ), array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif' ), true ) ) { return ''; }
  if ( $post->post_parent && ! is_post_publicly_viewable( $post->post_parent ) ) { return ''; }
  $url = wp_get_attachment_image_url( $id, array( $size, $size ) ); return is_string( $url ) && $this->local( $url ) ? $url : '';
 }
 /** Keep the shipped placeholder local, with a trusted inline fallback when asset URLs are rewritten remotely. @return string */
 private function fallback(): string {
  $url = plugins_url( 'assets/images/avatar-default.svg', SITE_ESSENTIALS_FILE );
  return $this->local( $url ) ? $url : 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><rect width="128" height="128" fill="#eef1f3"/><circle cx="64" cy="45" r="22" fill="#86949c"/><path d="M22 113c0-26 18-43 42-43s42 17 42 43" fill="#86949c"/></svg>' );
 }
 /** Return a local URL and found-state.
  * @param mixed $identity Core identity.
  * @param array $args Display arguments.
  * @return array
  */
 private function resolve( $identity, array $args ): array {
  $user = $this->user_id( $identity ); $size = max( 1, min( 2048, (int) ( $args['size'] ?? 96 ) ) );
  $id = $user && empty( $args['force_default'] ) ? (int) get_user_meta( $user, self::key(), true ) : 0;
  $url = $id > 0 ? $this->image( $id, $size ) : '';
  return array( 'url' => $url ?: $this->fallback(), 'found_avatar' => '' !== $url );
 }
 /** Override URLs before Gravatar calculation and after third-party data filters.
  * @param array $args Avatar arguments.
  * @param mixed $identity Identity.
  * @return array
  */
 public function data( array $args, $identity ): array {
  try { return $this->enabled() ? array_replace( $args, $this->resolve( $identity, $args ) ) : $args; } finally { $this->finish(); }
 }
 /** Produce avatar HTML without raw extra attributes or remote srcsets.
  * @param mixed $identity Identity.
  * @param array $args Arguments.
  * @return string|false
  */
 private function render( $identity, array $args ) {
  if ( empty( $args['force_display'] ) && ! get_option( 'show_avatars' ) ) { return false; }
  $data = $this->resolve( $identity, $args ); $size = max( 1, min( 2048, (int) ( $args['size'] ?? 96 ) ) );
  $classes = $args['class'] ?? array(); if ( is_string( $classes ) ) { $classes = preg_split( '/\s+/', $classes ); } if ( ! is_array( $classes ) ) { $classes = array(); }
  $classes = array_map( 'sanitize_html_class', array_filter( array_slice( $classes, 0, 50 ), 'is_string' ) );
  $url = str_starts_with( $data['url'], 'data:image/svg+xml;base64,' ) ? esc_attr( $data['url'] ) : esc_url( $data['url'] );
  return '<img alt="' . esc_attr( is_string( $args['alt'] ?? null ) ? $args['alt'] : '' ) . '" src="' . $url . '" class="' . esc_attr( implode( ' ', array_merge( array( 'avatar', 'avatar-' . $size, 'photo' ), $classes ) ) ) . '" height="' . esc_attr( $size ) . '" width="' . esc_attr( $size ) . '" loading="' . ( 'eager' === ( $args['loading'] ?? '' ) ? 'eager' : 'lazy' ) . '" decoding="async">';
 }
 /** Short-circuit core HTML before service-based fallback.
  * @param mixed $html Existing override.
  * @param mixed $identity Identity.
  * @param array $args Arguments.
  * @return mixed
  */
 public function pre_html( $html, $identity, array $args ) { try { return $this->enabled() ? $this->render( $identity, $args ) : $html; } finally { $this->finish(); } }
 /** Enforce local HTML after avatar plugins have filtered it.
  * @param mixed $html Existing HTML.
  * @param mixed $identity Identity.
  * @param int $size Size.
  * @param string $default Default.
  * @param string $alt Alt text.
  * @param array $args Arguments.
  * @return mixed
  */
 public function html( $html, $identity, $size, $default, $alt, array $args ) { try { return $this->enabled() ? $this->render( $identity, $args ) : $html; } finally { $this->finish(); } }
 /** Block service requests and nonlocal requests during avatar filters only.
  * @param mixed $pre Existing request short circuit.
  * @param array $args HTTP arguments.
  * @param string $url URL.
  * @return mixed
  */
 public function http( $pre, array $args, string $url ) {
  if ( ! $this->enabled() || is_wp_error( $pre ) ) { return $pre; }
  $host = wp_parse_url( $url, PHP_URL_HOST );
  return ( is_string( $host ) && $this->gravatar( $host ) ) || ( ( $this->depth > 0 || $this->picker_request() ) && ! $this->local( $url ) ) ? new \WP_Error( 'site_essentials_avatar_remote', __( 'External avatar requests are disabled.', 'site-essentials' ) ) : $pre;
 }
 /** Identify the specifically marked native avatar-picker request. @return bool */
 private function picker_request(): bool { return 'query-attachments' === ( $_POST['action'] ?? '' ) && is_array( $_POST['query'] ?? null ) && '1' === ( $_POST['query']['se_local_avatar'] ?? '' ); }
 /** Keep native picker thumbnail responses local even when a media/CDN plugin rewrites URLs.
  * @param array $response Media metadata.
  * @param \WP_Post $attachment Image.
  * @param array $meta Stored metadata.
  * @return array
  */
 public function media_data( array $response, $attachment, $meta ): array {
  if ( ! $this->enabled() || ! $this->picker_request() ) { return $response; }
  $response['seAvatarLocal'] = '' !== $this->image( $attachment->ID );
  foreach ( array( 'url', 'icon' ) as $key ) { if ( isset( $response[ $key ] ) && ! $this->local( $response[ $key ] ) ) { $response[ $key ] = $this->fallback(); } }
  if ( isset( $response['sizes'] ) ) { foreach ( $response['sizes'] as &$size ) { if ( isset( $size['url'] ) && ! $this->local( $size['url'] ) ) { $size['url'] = $this->fallback(); } } unset( $size ); }
  return $response;
 }
 /** Remove Gravatar connection hints without altering independent update/service hints.
  * @param array $urls Hints.
  * @param string $relation Hint type.
  * @return array
  */
 public function hints( array $urls, string $relation ): array {
  if ( ! $this->enabled() ) { return $urls; }
  return array_values( array_filter( $urls, function ( $item ) { $url = is_array( $item ) ? ( $item['href'] ?? '' ) : $item; $host = is_string( $url ) ? wp_parse_url( $url, PHP_URL_HOST ) : null; return ! is_string( $host ) || ! $this->gravatar( $host ); } ) );
 }
 /** Gate profile management by the specific user and current site membership.
  * @param int $user_id Target.
  * @return bool
  */
 public function can_edit( int $user_id ): bool { return current_user_can( 'read' ) && current_user_can( 'edit_user', $user_id ) && ( ! is_multisite() || is_user_member_of_blog( $user_id ) || ( get_current_user_id() === $user_id && is_super_admin( $user_id ) ) ); }
 /** Render a profile subsection; detached controls never submit with ordinary user changes.
  * @param \WP_User $user Target user.
  * @return void
  */
 public function profile( $user ): void {
  if ( ! $this->enabled() || is_network_admin() || $this->profile_user || ! $this->can_edit( $user->ID ) ) { return; }
  $this->profile_user = $user->ID; $id = (int) get_user_meta( $user->ID, self::key(), true ); $url = $this->image( $id ) ?: $this->fallback();
  echo '<h2>' . esc_html__( 'Local avatar for this site', 'site-essentials' ) . '</h2><div id="se-avatar-profile"><p><img id="se-avatar-preview" src="' . ( str_starts_with( $url, 'data:image/svg+xml;base64,' ) ? esc_attr( $url ) : esc_url( $url ) ) . '" width="96" height="96" alt=""></p><input type="hidden" id="se-avatar-id" name="avatar_attachment" value="' . esc_attr( $id ) . '" form="se-avatar-form"><input type="hidden" id="se-avatar-mode" name="avatar_mode" value="select" form="se-avatar-form">';
  if ( current_user_can( 'upload_files' ) ) { echo '<p><button type="button" class="button" id="se-avatar-select">' . esc_html__( 'Choose a local image', 'site-essentials' ) . '</button></p>'; }
  echo '<p><label for="se-avatar-file">' . esc_html__( 'Upload an avatar image', 'site-essentials' ) . '</label> <input id="se-avatar-file" type="file" name="se_avatar_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" form="se-avatar-form"></p><p>' . esc_html__( 'JPEG, PNG, GIF, WebP or AVIF; up to 4 MiB and 4096 × 4096 pixels. Images remain normal public WordPress media. This assignment belongs only to this site and is not included in preset exports.', 'site-essentials' ) . '</p><p><button type="button" class="button" id="se-avatar-remove">' . esc_html__( 'Use the local placeholder', 'site-essentials' ) . '</button> <button type="button" class="button" id="se-avatar-discard">' . esc_html__( 'Discard avatar selection', 'site-essentials' ) . '</button> <button type="submit" class="button button-primary" form="se-avatar-form">' . esc_html__( 'Save local avatar', 'site-essentials' ) . '</button></p><p id="se-avatar-message" role="alert" tabindex="-1"></p></div>';
 }
 /** Emit one own form after the native user form has closed. @return void */
 public function form(): void {
  if ( ! $this->profile_user || ! $this->enabled() ) { return; }
  echo '<form id="se-avatar-form" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="site_essentials_avatar_save"><input type="hidden" name="avatar_user" value="' . esc_attr( $this->profile_user ) . '"><input type="hidden" name="avatar_revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( get_user_meta( $this->profile_user, self::key(), true ) ) ) ) . '">';
  wp_nonce_field( 'site_essentials_avatar_' . get_current_blog_id() . '_' . $this->profile_user ); echo '</form>';
 }
 /** Load profile assets only when local avatar controls can be used.
  * @param string $hook Screen.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( ! $this->enabled() || is_network_admin() || ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) { return; }
  if ( current_user_can( 'upload_files' ) ) { wp_enqueue_media(); }
  wp_enqueue_style( 'site-essentials-avatar', plugins_url( 'assets/avatars.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION );
  wp_enqueue_script( 'site-essentials-avatar', plugins_url( 'assets/avatars.js', SITE_ESSENTIALS_FILE ), array( 'user-profile' ), SITE_ESSENTIALS_VERSION, true );
  wp_localize_script( 'site-essentials-avatar', 'siteEssentialsAvatar', array( 'fallback' => $this->fallback(), 'origins' => array( home_url( '/' ), site_url( '/' ) ), 'strings' => array( 'choose' => __( 'Choose a local image', 'site-essentials' ), 'use' => __( 'Use this image', 'site-essentials' ), 'local' => __( 'Choose a locally served image; external avatar images are not used.', 'site-essentials' ), 'profileDraft' => __( 'Save or discard other profile changes before saving the avatar.', 'site-essentials' ), 'avatarDraft' => __( 'Save or discard the avatar selection before updating the profile.', 'site-essentials' ) ) ) );
 }
 /** Intersect safe raster extensions with native user and network upload policy.
  * @return array<string,string> Only file types permitted by both policies.
  */
 public static function upload_mimes(): array {
  $safe = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif' );
  $allowed = get_allowed_mime_types(); $result = array();
  foreach ( $safe as $extensions => $mime ) {
   foreach ( explode( '|', $extensions ) as $extension ) {
    $native = wp_check_filetype( 'avatar.' . $extension, $allowed );
    if ( $mime === $native['type'] && $extension === $native['ext'] ) { $result[ $extension ] = $mime; }
   }
  }
  return $result;
 }
 /** Save an authorized assignment or a tightly limited profile-only upload. @return void */
 public function save(): void {
  $raw = $_POST['avatar_user'] ?? ''; $user_id = is_string( $raw ) && preg_match( '/^[1-9][0-9]{0,9}$/D', $raw ) ? (int) $raw : 0;
  if ( ! $this->enabled() || ! $user_id || ! $this->can_edit( $user_id ) ) { wp_die( esc_html__( 'You cannot change this user’s local avatar.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Use the avatar form to perform this action.', 'site-essentials' ), '', array( 'response' => 405 ) ); }
  if ( ! is_string( $_REQUEST['_wpnonce'] ?? null ) ) { wp_die( esc_html__( 'The request could not be verified. Reload the page and try again.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( 'site_essentials_avatar_' . get_current_blog_id() . '_' . $user_id );
  $revision = $_POST['avatar_revision'] ?? '';
  if ( ! is_string( $revision ) || ! hash_equals( hash( 'sha256', wp_json_encode( get_user_meta( $user_id, self::key(), true ) ) ), wp_unslash( $revision ) ) ) { wp_die( esc_html__( 'This avatar changed elsewhere. Reload before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $mode = $_POST['avatar_mode'] ?? ''; $id = 0; $uploaded = false;
  if ( 'upload' === $mode ) {
   $file = $_FILES['se_avatar_file'] ?? null;
   $mimes = self::upload_mimes();
   if ( ! is_array( $file ) || UPLOAD_ERR_OK !== ( $file['error'] ?? -1 ) || ! is_int( $file['size'] ?? null ) || $file['size'] > min( 4194304, wp_max_upload_size() ) || $file['size'] <= 0 || ! is_string( $file['tmp_name'] ?? null ) || ! is_string( $file['name'] ?? null ) || ! is_uploaded_file( $file['tmp_name'] ) ) { $this->upload_error(); }
   $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes ); $dimensions = wp_getimagesize( $file['tmp_name'] );
   if ( ! $check['ext'] || ! in_array( $check['type'], array_values( $mimes ), true ) || ! $dimensions || $dimensions[0] > 4096 || $dimensions[1] > 4096 || $dimensions[0] < 1 || $dimensions[1] < 1 ) { $this->upload_error(); }
   require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
   $id = media_handle_upload( 'se_avatar_file', 0, array(), array( 'test_form' => false, 'mimes' => $mimes ) );
   if ( is_wp_error( $id ) ) { $this->upload_error(); } $uploaded = true;
  } elseif ( 'select' === $mode ) {
   $raw_id = $_POST['avatar_attachment'] ?? ''; if ( ! is_string( $raw_id ) || ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $raw_id ) ) { $this->upload_error(); } $id = (int) $raw_id;
   if ( $id && $id !== (int) get_user_meta( $user_id, self::key(), true ) && ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'read_post', $id ) ) ) { wp_die( esc_html__( 'You cannot select this image.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  } elseif ( 'remove' !== $mode ) { $this->upload_error(); }
  if ( $id && '' === $this->image( $id ) ) { if ( $uploaded ) { wp_delete_attachment( $id, true ); } $this->upload_error(); }
  if ( $id ) { update_user_meta( $user_id, self::key(), $id ); } else { delete_user_meta( $user_id, self::key() ); }
  if ( $id !== (int) get_user_meta( $user_id, self::key(), true ) ) { if ( $uploaded ) { wp_delete_attachment( $id, true ); } wp_die( esc_html__( 'The local avatar could not be saved.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  $url = get_current_user_id() === $user_id ? admin_url( 'profile.php' ) : add_query_arg( 'user_id', $user_id, admin_url( 'user-edit.php' ) );
  wp_safe_redirect( add_query_arg( 'se_avatar_result', 'saved', $url ) ); exit;
 }
 /** Return one bounded upload validation error. @return never */
 private function upload_error(): never { wp_die( esc_html__( 'Choose a locally served JPEG, PNG, GIF, WebP or AVIF image up to 4 MiB and 4096 × 4096 pixels.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
 /** Show one avatar-specific result on native profile screens. @return void */
 public function notice(): void {
  $screen = get_current_screen();
  if ( $this->enabled() && $screen && in_array( $screen->base, array( 'profile', 'user-edit' ), true ) && 'saved' === ( $_GET['se_avatar_result'] ?? '' ) ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Local avatar saved for this site.', 'site-essentials' ) . '</p></div>'; }
 }
}
