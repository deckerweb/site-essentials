<?php
/** Optional native media, user and administration conveniences. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;

/** Keeps small conveniences site-local and independent of builder implementations. */
final class Essentials110 {
 /** Register narrow native hooks; new functions remain off by default. @return void */
 public function boot(): void {
  add_filter( 'upload_mimes', array( $this, 'mimes' ) );
  add_filter( 'wp_handle_upload_prefilter', array( $this, 'upload' ) );
  add_filter( 'wp_handle_sideload_prefilter', array( $this, 'upload' ) );
  add_action( 'wp_login', array( $this, 'login' ), 10, 2 );
  add_filter( 'manage_users_sortable_columns', array( $this, 'user_sortable' ) );
  add_filter( 'manage_upload_sortable_columns', array( $this, 'id_sortable' ) );
  add_filter( 'manage_users_columns', array( $this, 'users_columns' ) );
  add_filter( 'manage_users_custom_column', array( $this, 'user_column' ), 10, 3 );
  add_action( 'init', array( $this, 'content_hooks' ), 30 );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'admin_bar_menu', array( $this, 'badge' ), 220 );
  add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
  add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
 }
 /** Remove only video MIME entries while the option is enabled.
  * @param array $mimes Allowed native MIME map.
  * @return array Native map with video uploads excluded.
  */
 public function mimes( array $mimes ): array {
  if ( Settings::get( 'video_uploads' ) ) {
   foreach ( $mimes as $key => $mime ) { if ( str_starts_with( $mime, 'video/' ) ) { unset( $mimes[ $key ] ); } }
  }
  return $mimes;
 }
 /** Reject native upload/sideload video inputs without modifying existing files.
  * @param array $file Native temporary-file description.
  * @return array File description with a localized upload error when necessary.
  */
 public function upload( array $file ): array {
  if ( ! Settings::get( 'video_uploads' ) || ! empty( $file['error'] ) ) { return $file; }
  $type = wp_check_filetype( (string) ( $file['name'] ?? '' ), wp_get_mime_types() );
  $mime = is_string( $file['type'] ?? null ) ? $file['type'] : '';
  $video = str_starts_with( (string) $type['type'], 'video/' ) || str_starts_with( $mime, 'video/' );
  if ( ! $video && function_exists( 'finfo_open' ) && is_string( $file['tmp_name'] ?? null ) && is_file( $file['tmp_name'] ) ) {
   $info = finfo_open( FILEINFO_MIME_TYPE );
   if ( $info ) { $detected = finfo_file( $info, $file['tmp_name'] ); finfo_close( $info ); $video = is_string( $detected ) && str_starts_with( $detected, 'video/' ); }
  }
  if ( $video ) { $file['error'] = __( 'Video uploads are disabled on this website. Existing videos remain available; use an approved video hosting service or contact an administrator.', 'site-essentials' ); }
  return $file;
 }
 /** Resolve the current website's user timestamp key. @return string */
 public static function login_key(): string { return '_site_essentials_last_login_' . get_current_blog_id(); }
 /** Record only successful interactive logins for members of this website.
  * @param string $name Authenticated login name, never stored here.
  * @param \WP_User $user Authenticated user.
  * @return void
  */
 public function login( string $name, \WP_User $user ): void {
  if ( Settings::get( 'last_login' ) && ( ! is_multisite() || is_user_member_of_blog( $user->ID ) ) ) { update_user_meta( $user->ID, self::login_key(), time() ); }
 }
 /** Add an optional administrator-only timestamp heading.
  * @param array $columns Native headings.
  * @return array Augmented headings.
  */
 public function users_columns( array $columns ): array {
  if ( ! is_network_admin() && current_user_can( 'manage_options' ) && current_user_can( 'list_users' ) && Settings::get( 'last_login' ) ) { $columns['se_last_login'] = __( 'Last login', 'site-essentials' ); }
  return $columns;
 }
 /** Offer native numeric user-ID ordering only when its column is available.
  * @param array $columns Existing sortable headings.
  * @return array Preserves native default ordering.
  */
 public function user_sortable( array $columns ): array { if ( ! is_network_admin() && current_user_can( 'manage_options' ) && current_user_can( 'list_users' ) && Settings::get( 'user_ids' ) ) { $columns['se_user_id'] = array( 'ID', false ); } return $columns; }
 /** Offer native numeric post/media-ID ordering without custom SQL.
  * @param array $columns Existing sortable headings.
  * @return array Augmented headings when enabled for administrators.
  */
 public function id_sortable( array $columns ): array { if ( ! is_network_admin() && current_user_can( 'manage_options' ) && Settings::get( 'content_ids' ) ) { $columns['se_content_id'] = array( 'ID', false ); } return $columns; }
 /** Render recorded timestamps and enhance the existing user ID with copying.
  * @param string $value Existing column output.
  * @param string $column Native column key.
  * @param int $id Row user ID.
  * @return string Escaped column output.
  */
 public function user_column( string $value, string $column, int $id ): string {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ! current_user_can( 'list_users' ) ) { return $value; }
  if ( 'se_user_id' === $column && Settings::get( 'user_ids' ) ) { return self::id_html( $id ); }
  if ( 'se_last_login' !== $column || ! Settings::get( 'last_login' ) ) { return $value; }
  $stamp = (int) get_user_meta( $id, self::login_key(), true );
  if ( $stamp < 1 ) { return esc_html__( 'Not recorded yet', 'site-essentials' ); }
  /* translators: %s: elapsed time since the recorded successful login. */
  return esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stamp ) ) . '<br><small>' . esc_html( sprintf( __( '%s ago', 'site-essentials' ), human_time_diff( $stamp ) ) ) . '</small>';
 }
 /** Render selectable plain text and an accessible copy button.
  * @param int $id Native object identifier.
  * @return string Escaped HTML without an inline handler.
  */
 public static function id_html( int $id ): string {
  return '<span class="se-id-value">' . esc_html( (string) $id ) . '</span> <button type="button" class="button-link se-copy-id" data-se-copy="' . esc_attr( (string) $id ) . '" aria-label="' . esc_attr__( 'Copy ID', 'site-essentials' ) . '">' . esc_html__( 'Copy', 'site-essentials' ) . '</button>';
 }
 /** Register columns only for native visible content lists. @return void */
 public function content_hooks(): void {
  if ( ! is_admin() || is_network_admin() || ! Settings::get( 'content_ids' ) || ! current_user_can( 'manage_options' ) ) { return; }
  foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $type ) {
   if ( 'attachment' === $type->name || ! $type->public ) { continue; }
   add_filter( 'manage_edit-' . $type->name . '_sortable_columns', array( $this, 'id_sortable' ) );
   add_filter( 'manage_' . $type->name . '_posts_columns', array( $this, 'content_columns' ) );
   add_action( 'manage_' . $type->name . '_posts_custom_column', array( $this, 'content_column' ), 10, 2 );
  }
  add_filter( 'manage_upload_columns', array( $this, 'content_columns' ) );
  add_action( 'manage_media_custom_column', array( $this, 'content_column' ), 10, 2 );
 }
 /** Add the content ID heading without overwriting existing columns.
  * @param array $columns Native headings.
  * @return array Augmented headings.
  */
 public function content_columns( array $columns ): array { $columns['se_content_id'] = __( 'ID', 'site-essentials' ); return $columns; }
 /** Render IDs only for editable objects.
  * @param string $column Native column key.
  * @param int $id Content ID.
  * @return void
  */
 public function content_column( string $column, int $id ): void {
  if ( 'se_content_id' === $column && current_user_can( 'manage_options' ) && current_user_can( 'edit_post', $id ) ) { echo wp_kses( self::id_html( $id ), array( 'span' => array( 'class' => true ), 'button' => array( 'class' => true, 'type' => true, 'data-se-copy' => true, 'aria-label' => true ) ) ); }
 }
 /** Identify an active BAS implementation through its public toolbar callback.
  * @return bool Whether BAS owns environment presentation, including intentional suppression.
  */
 public static function bas_provider(): bool { return class_exists( 'BAS_Plugin', false ) && is_callable( array( 'BAS_Plugin', 'environment_badge' ) ); }
 /** Add a plain administrator environment label after BAS has registered its node.
  * @param \WP_Admin_Bar $bar WordPress toolbar object.
  * @return void
  */
 public function badge( \WP_Admin_Bar $bar ): void {
  if ( ! current_user_can( 'manage_options' ) || ! Settings::get( 'environment_badge' ) || self::bas_provider() || $bar->get_node( 'bas-environment' ) ) { return; }
  $labels = array( 'production' => __( 'Production', 'site-essentials' ), 'staging' => __( 'Staging', 'site-essentials' ), 'development' => __( 'Development', 'site-essentials' ), 'local' => __( 'Local', 'site-essentials' ) );
  /* translators: %s: WordPress environment name. */
  $bar->add_node( array( 'id' => 'se-environment', 'parent' => 'top-secondary', 'title' => esc_html( sprintf( __( 'Environment: %s', 'site-essentials' ), $labels[ wp_get_environment_type() ] ) ), 'href' => admin_url( 'options-general.php?page=site-essentials#se-admin' ) ) );
 }
 /** Load copying and selected visual profile simplifications only on relevant screens.
  * @param string $hook Current admin screen suffix.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( ! is_network_admin() && current_user_can( 'manage_options' ) && in_array( $hook, array( 'users.php', 'edit.php', 'upload.php' ), true ) && ( Settings::get( 'user_ids' ) || Settings::get( 'content_ids' ) ) ) { wp_enqueue_style( 'se-list-comfort', plugins_url( 'assets/list-comfort.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_ASSET_VERSION ); }
  if ( current_user_can( 'manage_options' ) && ( Settings::get( 'user_ids' ) || Settings::get( 'content_ids' ) || Settings::get( 'media_trash_help' ) || Settings::get( 'menu_duplicate' ) ) && in_array( $hook, array( 'users.php', 'edit.php', 'upload.php', 'settings_page_site-essentials' ), true ) ) {
   wp_enqueue_script( 'se-conveniences', plugins_url( 'assets/conveniences.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION, true );
   wp_localize_script( 'se-conveniences', 'seConveniences', array( 'draft' => __( 'Save or discard the settings draft before duplicating a menu.', 'site-essentials' ), 'copied' => __( 'Copied', 'site-essentials' ), 'failed' => __( 'Copy unavailable. Select and copy the text manually.', 'site-essentials' ) ) );
  }
  if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) || is_network_admin() ) { return; }
  $selectors = array( 'website' => '.user-url-wrap', 'biography' => '.user-description-wrap', 'colors' => '.user-admin-color-wrap', 'rich_editing' => '.user-rich-editing-wrap', 'syntax' => '.user-syntax-highlighting-wrap', 'shortcuts' => '.user-comment-shortcuts-wrap', 'toolbar' => '.show-admin-bar.user-admin-bar-front-wrap,.user-admin-bar-front-wrap' );
  $hide = array_intersect_key( $selectors, array_flip( Settings::get( 'profile_fields' ) ) );
  if ( $hide ) { wp_enqueue_style( 'se-profile-comfort', plugins_url( 'assets/profile-comfort.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION ); wp_add_inline_style( 'se-profile-comfort', implode( ',', $hide ) . '{display:none}' ); }
 }
 /** Render an optional read-only media configuration card. @param bool $nested Inside the setting card. @return void */
 public static function trash_panel( bool $nested = false ): void {
  if ( ! Settings::get( 'media_trash_help' ) || ! current_user_can( 'manage_options' ) ) { return; }
  $days = (int) EMPTY_TRASH_DAYS; $enabled = MEDIA_TRASH && $days > 0;
  echo '<div class="' . ( $nested ? 'se-subfield' : 'se-card' ) . '"><h3>' . esc_html__( 'Native media trash', 'site-essentials' ) . '</h3><p>' . esc_html( $enabled ? __( 'Media trash is available.', 'site-essentials' ) : __( 'Media trash is not available with the current configuration.', 'site-essentials' ) ) . '</p><p><code>MEDIA_TRASH: ' . esc_html( MEDIA_TRASH ? 'true' : 'false' ) . '</code><br><code>EMPTY_TRASH_DAYS: ' . esc_html( (string) $days ) . '</code></p><details><summary>' . esc_html__( 'Manual setup and limitations', 'site-essentials' ) . '</summary><p>' . esc_html__( 'An administrator with server access can set MEDIA_TRASH to true in wp-config.php before WordPress loads. Edit an existing definition instead of adding a duplicate. Keep the existing positive EMPTY_TRASH_DAYS value; zero disables trash. These settings normally affect the entire installation or Multisite network.', 'site-essentials' ) . '</p><p><code data-se-copy-text>define( \'MEDIA_TRASH\', true );</code> <button type="button" class="button se-copy-code">' . esc_html__( 'Copy', 'site-essentials' ) . '</button></p><p>' . esc_html__( 'Files remain stored and may remain accessible by direct URL. This is not a backup or access restriction. Scheduled cleanup follows the configured retention; explicit permanent deletion can bypass trash. Site Essentials changes no files and does not remove existing trash.', 'site-essentials' ) . '</p></details></div>';
 }
 /** Render provider ownership independently of whether a BAS badge is visible. @return void */
 public static function environment_panel(): void {
  if ( ! current_user_can( 'manage_options' ) || ! Settings::get( 'environment_badge' ) ) { return; }
  echo '<p class="se-provider-note">' . esc_html( self::bas_provider() ? __( 'Brand Admin Schemes manages the environment indicator. Your choice is retained.', 'site-essentials' ) : __( 'Site Essentials displays the WordPress environment. Brand Admin Schemes takes priority when active; this choice is retained.', 'site-essentials' ) ) . '</p>';
 }
 /** Register one site-local personal-data exporter.
  * @param array $items Native exporter registrations.
  * @return array Registrations with this website timestamp exporter.
  */
 public function exporters( array $items ): array { $items['site-essentials-login'] = array( 'exporter_friendly_name' => __( 'Site Essentials last login', 'site-essentials' ), 'callback' => array( $this, 'export_login' ) ); return $items; }
 /** Register one site-local personal-data eraser.
  * @param array $items Native eraser registrations.
  * @return array Registrations with this website timestamp eraser.
  */
 public function erasers( array $items ): array { $items['site-essentials-login'] = array( 'eraser_friendly_name' => __( 'Site Essentials last login', 'site-essentials' ), 'callback' => array( $this, 'erase_login' ) ); return $items; }
 /** Export only the requested user's timestamp on this website.
  * @param string $email Verified request email supplied by WordPress.
  * @param int $page Native pagination value.
  * @return array Native privacy exporter result.
  */
 public function export_login( string $email, int $page = 1 ): array {
  $user = get_user_by( 'email', $email ); $stamp = $user ? (int) get_user_meta( $user->ID, self::login_key(), true ) : 0;
  $data = $stamp > 0 && 1 === $page ? array( array( 'group_id' => 'site-essentials', 'group_label' => 'Site Essentials', 'item_id' => 'last-login-' . get_current_blog_id(), 'data' => array( array( 'name' => __( 'Last login', 'site-essentials' ), 'value' => gmdate( 'c', $stamp ) ) ) ) ) : array();
  return array( 'data' => $data, 'done' => true );
 }
 /** Erase only this website timestamp for a verified request email.
  * @param string $email Verified request email supplied by WordPress.
  * @param int $page Native pagination value.
  * @return array Native privacy eraser result.
  */
 public function erase_login( string $email, int $page = 1 ): array {
  $user = get_user_by( 'email', $email ); $removed = $user && delete_user_meta( $user->ID, self::login_key() );
  return array( 'items_removed' => (bool) $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
 }
}
