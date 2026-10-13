<?php
/** Optional post destinations with explicit, non-destructive Page Links To import. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Keeps content destinations separate from portable site configuration. */
final class AlternativeTargets {
 const META = '_site_essentials_target';
 private static bool $original = false;
 /** Register lightweight runtime and capability-gated administration hooks. @return void */
 public function boot(): void {
  foreach ( array( 'post_link', 'page_link', 'post_type_link' ) as $hook ) { add_filter( $hook, array( $this, 'link' ), 30, 2 ); }
  add_filter( 'preview_post_link', array( $this, 'preview_link' ), 30, 2 );
  add_action( 'template_redirect', array( $this, 'redirect' ), 1 );
  add_filter( 'wp_nav_menu_objects', array( $this, 'menu_links' ) );
  add_filter( 'page_menu_link_attributes', array( $this, 'page_attributes' ), 20, 2 );
  add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'sitemap' ), 20, 2 );
  if ( is_admin() ) {
   add_action( 'add_meta_boxes', array( $this, 'boxes' ), 20, 2 );
   add_action( 'save_post', array( $this, 'save_box' ), 20, 2 );
   add_filter( 'display_post_states', array( $this, 'states' ), 20, 2 );
   add_action( 'admin_menu', array( $this, 'menu' ) );
   add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
   add_action( 'admin_post_site_essentials_targets_import', array( $this, 'import' ) );
  }
 }
 /** Detect the actual loaded provider, including MU/network activation. @return bool */
 public static function provider(): bool { return class_exists( '\CWS_PageLinksTo', false ); }
 /** Resolve allowed public content types; internal WordPress types are excluded. @return array<string> */
 public static function types(): array {
  $out = array();
  foreach ( (array) Settings::get( 'target_types' ) as $type ) {
   $object = get_post_type_object( $type );
   if ( $object && $object->public && $object->show_ui && ! in_array( $type, array( 'attachment', 'nav_menu_item', 'revision' ), true ) ) { $out[] = $type; }
  }
  return $out;
 }
 /** Determine whether one type is enabled without a competing provider. @param string $type Content type. @return bool */
 public static function enabled( string $type ): bool { return (bool) Settings::get( 'targets' ) && ! self::provider() && in_array( $type, self::types(), true ); }
 /** Accept absolute HTTP(S) or root-relative URLs, rejecting ambiguous/control characters. @param mixed $url Submitted destination. @return string|\WP_Error */
 public static function url( $url ) {
  if ( ! is_string( $url ) || strlen( $url ) > 2048 ) { return self::url_error(); }
  $url = trim( $url );
  if ( '' === $url || preg_match( '/[\x00-\x20\x7F\\\\]/', $url ) || preg_match( '/%(?:0[0-9a-f]|1[0-9a-f]|7f|5c)/i', $url ) ) { return self::url_error(); }
  if ( str_starts_with( $url, '/' ) ) { return str_starts_with( $url, '//' ) ? self::url_error() : $url; }
  $parts = wp_parse_url( $url );
  if ( ! is_array( $parts ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || esc_url_raw( $url, array( 'http', 'https' ) ) !== $url ) { return self::url_error(); }
  return $url;
 }
 /** Construct translated validation feedback only for rejected values. @return \WP_Error */
 private static function url_error(): \WP_Error { return new \WP_Error( 'target_url', __( 'Use a complete HTTP(S) URL or an internal path starting with one slash.', 'site-essentials' ) ); }
 /** Expand root-relative paths against the site's origin, including subdirectory sites. @param string $url Valid destination. @return string */
 public static function absolute( string $url ): string {
  if ( ! str_starts_with( $url, '/' ) ) { return $url; }
  $p = wp_parse_url( home_url( '/' ) );
  return $p['scheme'] . '://' . $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' ) . $url;
 }
 /** Read one validated content record without changing data. @param int $id Content ID. @return array<string,mixed> */
 public static function record( int $id ): array {
  $raw = get_post_meta( $id, self::META, true );
  if ( ! is_array( $raw ) ) { return array(); }
  $url = self::url( $raw['url'] ?? null ); if ( is_wp_error( $url ) ) { return array(); }
  return array( 'url' => $url, 'new_tab' => ! empty( $raw['new_tab'] ), 'nofollow' => ! empty( $raw['nofollow'] ) );
 }
 /** Obtain the original permalink without our replacement filters. @param int $id Content ID. @return string */
 public function original( int $id ): string {
  $old = self::$original; self::$original = true;
  try { return (string) get_permalink( $id ); } finally { self::$original = $old; }
 }
 /** Check local chains with a bounded walk; never request the remote destination. @param int $id Source ID. @param string $url Proposed destination. @return bool */
 public function safe_chain( int $id, string $url ): bool {
  $seen = array( $id ); $home = wp_parse_url( home_url( '/' ) ); $raw = null; $types = null;
  for ( $i = 0; $i < 12; $i++ ) {
   $absolute = self::absolute( $url ); $dest = wp_parse_url( $absolute );
   if ( preg_replace( '/^www\./i', '', strtolower( $home['host'] ) ) !== preg_replace( '/^www\./i', '', strtolower( $dest['host'] ?? '' ) ) || ( $home['port'] ?? ( strtolower( $home['scheme'] ) === 'https' ? 443 : 80 ) ) !== ( $dest['port'] ?? ( strtolower( $dest['scheme'] ?? '' ) === 'https' ? 443 : 80 ) ) ) { return true; }
   // Resolve equivalent local origins (www and explicit default ports) using Core's own origin.
   $lookup = $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . ( $dest['path'] ?? '/' ) . ( isset( $dest['query'] ) ? '?' . $dest['query'] : '' );
   $raw ??= $this->original( $id );
   if ( strtok( $lookup, '#' ) === strtok( $raw, '#' ) ) { return false; }
   $old = self::$original; self::$original = true;
   try { $next = url_to_postid( $lookup ); } finally { self::$original = $old; }
   if ( ! $next ) { return true; }
   if ( in_array( $next, $seen, true ) ) { return false; }
   $seen[] = $next;
   $types ??= self::types();
   if ( ! in_array( get_post_type( $next ), $types, true ) ) { return true; }
   $record = self::record( $next ); if ( ! $record ) { return true; } $url = $record['url'];
  }
  return false;
 }
 /** Replace public, published permalinks while leaving admin/editor/preview contexts intact. @param string $link Native URL. @param \WP_Post|int $post Content. @return string */
 public function link( string $link, $post ): string {
  if ( self::$original || is_admin() || is_preview() || is_customize_preview() ) { return $link; }
  if ( defined( 'REST_REQUEST' ) && REST_REQUEST && 'edit' === ( $_GET['context'] ?? '' ) ) { return $link; }
  $post = get_post( $post );
  if ( ! $post || 'publish' !== $post->post_status || ! self::enabled( $post->post_type ) ) { return $link; }
  $record = self::record( $post->ID ); return $record ? self::absolute( $record['url'] ) : $link;
 }
 /** Keep generated preview addresses local even outside admin requests. @param string $url Generated preview. @param \WP_Post $post Content. @return string */
 public function preview_link( string $url, \WP_Post $post ): string {
  if ( self::$original || ! self::enabled( $post->post_type ) || ! self::record( $post->ID ) ) { return $url; }
  $query = wp_parse_url( $url, PHP_URL_QUERY ); $args = array(); if ( is_string( $query ) ) { wp_parse_str( $query, $args ); }
  $old = self::$original; self::$original = true;
  try { $native = get_preview_post_link( $post ); } finally { self::$original = $old; }
  return add_query_arg( array_intersect_key( $args, array_flip( array( 'preview', 'preview_id', 'preview_nonce' ) ) ), $native );
 }
 /** Redirect only ordinary public GET/HEAD singular requests; use temporary redirects for reversibility. @return void */
 public function redirect(): void {
  if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_singular() || is_preview() || is_customize_preview() || is_feed() || is_embed() || is_trackback() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) { return; }
  $post = get_queried_object();
  if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status || ! self::enabled( $post->post_type ) ) { return; }
  $record = self::record( $post->ID );
  if ( $record && $this->safe_chain( $post->ID, $record['url'] ) ) {
   nocache_headers();
   // External URLs are explicitly selected content destinations, not request-controlled redirect parameters.
   if ( wp_redirect( self::absolute( $record['url'] ), 302, 'Site Essentials' ) ) { exit; }
  }
 }
 /** Add opt-in attributes to native page/navigation links. @param array $attrs Existing attributes. @param array $record Valid destination. @return array */
 private function attributes( array $attrs, array $record ): array {
  $rel = preg_split( '/\s+/', trim( (string) ( $attrs['rel'] ?? '' ) ) ) ?: array();
  if ( $record['new_tab'] ) { $attrs['target'] = '_blank'; }
  if ( '_blank' === ( $attrs['target'] ?? '' ) ) { $rel[] = 'noopener'; }
  if ( $record['nofollow'] ) { $rel[] = 'nofollow'; }
  if ( $rel ) { $attrs['rel'] = implode( ' ', array_unique( array_filter( $rel ) ) ); }
  return $attrs;
 }
 /** Update native menu items referencing affected posts; custom links stay intact. @param array $items Menu objects. @return array */
 public function menu_links( array $items ): array {
  if ( is_admin() || is_customize_preview() ) { return $items; }
  foreach ( $items as $item ) {
   if ( 'post_type' !== $item->type || ! self::enabled( $item->object ) || 'publish' !== get_post_status( $item->object_id ) ) { continue; }
   $record = self::record( (int) $item->object_id ); if ( ! $record ) { continue; }
   $attrs = $this->attributes( array( 'target' => $item->target, 'rel' => $item->xfn ), $record );
   $item->url = self::absolute( $record['url'] ); $item->target = $attrs['target'] ?? ''; $item->xfn = $attrs['rel'] ?? '';
  }
  return $items;
 }
 /** Add attributes in native wp_list_pages output. @param array $attrs Link attributes. @param \WP_Post $page Page. @return array */
 public function page_attributes( array $attrs, \WP_Post $page ): array {
  $record = self::enabled( $page->post_type ) && 'publish' === $page->post_status ? self::record( $page->ID ) : array();
  return $record && ! is_admin() && ! is_customize_preview() ? $this->attributes( $attrs, $record ) : $attrs;
 }
 /** Exclude destination-only content from the native posts sitemap. @param array $args Query arguments. @param string $type Content type. @return array */
 public function sitemap( array $args, string $type ): array {
  if ( self::enabled( $type ) ) { $args['meta_query'] = array( 'relation' => 'AND', $args['meta_query'] ?? array(), array( 'key' => self::META, 'compare' => 'NOT EXISTS' ) ); }
  return $args;
 }
 /** Register the same native meta box in classic and block editors. @param string $type Screen content type. @param \WP_Post $post Content object. @return void */
 public function boxes( string $type, \WP_Post $post ): void {
  if ( self::enabled( $type ) && current_user_can( 'edit_post', $post->ID ) ) { add_meta_box( 'se-alternative-target', __( 'Alternative post destinations', 'site-essentials' ), array( $this, 'box' ), $type, 'normal', 'default', array( '__block_editor_compatible_meta_box' => true ) ); }
 }
 /** Render a nonce-bound content form without enabling additional editor capabilities. @param \WP_Post $post Content. @return void */
 public function box( \WP_Post $post ): void {
  $r = self::record( $post->ID );
  wp_nonce_field( 'se_target_' . get_current_blog_id() . '_' . $post->ID, 'se_target_nonce' );
  echo '<input type="hidden" name="se_target_revision" value="' . esc_attr( self::revision( $post->ID ) ) . '">';
  echo '<p><label><input type="radio" name="se_target_mode" value="normal" ' . checked( ! $r, true, false ) . '> ' . esc_html__( 'Normal WordPress address', 'site-essentials' ) . '</label></p><p><label><input type="radio" name="se_target_mode" value="custom" ' . checked( (bool) $r, true, false ) . '> ' . esc_html__( 'Alternative destination', 'site-essentials' ) . '</label></p><div id="se-target-fields"><p><label for="se-target-url">' . esc_html__( 'Destination URL', 'site-essentials' ) . '</label><br><input class="widefat" type="text" maxlength="2048" id="se-target-url" name="se_target_url" value="' . esc_attr( $r['url'] ?? '' ) . '" aria-describedby="se-target-help" placeholder="https://example.com/"></p>';
  foreach ( array( 'new_tab' => __( 'Open native menu and page-list links in a new tab', 'site-essentials' ), 'nofollow' => __( 'Add nofollow to native menu and page-list links', 'site-essentials' ) ) as $key => $label ) { echo '<p><label><input type="checkbox" name="se_target_' . esc_attr( $key ) . '" value="1" ' . checked( ! empty( $r[ $key ] ), true, false ) . '> ' . esc_html( $label ) . '</label></p>'; }
  echo '<p class="description" id="se-target-help">' . esc_html__( 'Use a complete HTTP(S) URL or an internal path starting with one slash.', 'site-essentials' ) . '</p><p class="description">' . esc_html__( 'The original public address redirects with HTTP 302. Preview and editing stay local. Builders and custom link renderers may need their own link attributes.', 'site-essentials' ) . '</p></div>';
 }
 /** Validate native meta-box writes, excluding autosaves/revisions and unauthorized requests. @param int $id Content ID. @param \WP_Post $post Content. @return void */
 public function save_box( int $id, \WP_Post $post ): void {
  $nonce = $_POST['se_target_nonce'] ?? null; $mode = $_POST['se_target_mode'] ?? null;
  if ( ! self::enabled( $post->post_type ) || wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'se_target_' . get_current_blog_id() . '_' . $id ) || ! in_array( $mode, array( 'normal', 'custom' ), true ) ) { return; }
  $revision = $_POST['se_target_revision'] ?? null;
  if ( ! is_string( $revision ) || ! hash_equals( self::revision( $id ), $revision ) ) { wp_die( esc_html__( 'The destination changed since this editor was opened. Reload before saving it.', 'site-essentials' ), '', array( 'response' => 409, 'back_link' => true ) ); }
  if ( 'normal' === $mode ) {
   delete_post_meta( $id, self::META );
   if ( metadata_exists( 'post', $id, self::META ) ) { wp_die( esc_html__( 'The destination could not be saved. Please reload and check it.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
   $_POST['se_target_revision'] = self::revision( $id ); return;
  }
  $url = self::url( wp_unslash( $_POST['se_target_url'] ?? null ) );
  if ( is_wp_error( $url ) || ! $this->safe_chain( $id, $url ) ) { wp_die( esc_html__( 'The destination is invalid or creates a local redirect loop. The previous destination remains unchanged.', 'site-essentials' ), '', array( 'response' => 400, 'back_link' => true ) ); }
  $record = array( 'url' => $url, 'new_tab' => '1' === ( $_POST['se_target_new_tab'] ?? '' ), 'nofollow' => '1' === ( $_POST['se_target_nofollow'] ?? '' ) );
  update_post_meta( $id, self::META, $record );
  if ( get_post_meta( $id, self::META, true ) !== $record ) { wp_die( esc_html__( 'The destination could not be saved. Please reload and check it.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  $_POST['se_target_revision'] = self::revision( $id );
 }
 /** Mark affected posts in the native list without fetching private foreign-plugin data. @param array $states Existing labels. @param \WP_Post $post Content. @return array */
 public function states( array $states, \WP_Post $post ): array { if ( self::enabled( $post->post_type ) && self::record( $post->ID ) ) { $states['se_target'] = __( 'Alternative destination', 'site-essentials' ); } return $states; }
 /** Hash only the current content destination, scoped to its site and ID. @param int $id Content ID. @return string */
 public static function revision( int $id ): string { return hash( 'sha256', wp_json_encode( array( get_current_blog_id(), $id, get_post_meta( $id, self::META, true ) ) ) ); }
 /** Enqueue progressive controls only on supported content screens or our migration page. @param string $hook Screen hook. @return void */
 public function assets( string $hook ): void {
  $screen = get_current_screen();
  $editor = in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && $screen && self::enabled( (string) $screen->post_type );
  if ( 'tools_page_site-essentials-targets' !== $hook && ! $editor ) { return; }
  wp_enqueue_script( 'site-essentials-targets', plugins_url( 'assets/targets.js', SITE_ESSENTIALS_FILE ), $editor && $screen->is_block_editor() ? array( 'wp-api-fetch' ) : array(), SITE_ESSENTIALS_VERSION, true );
  wp_enqueue_style( 'site-essentials-targets', plugins_url( 'assets/targets.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION );
 }
 /** Register administrator-only migration under Tools. @return void */
 public function menu(): void { add_management_page( __( 'Alternative post destinations', 'site-essentials' ), __( 'Alternative post destinations', 'site-essentials' ), 'manage_options', 'site-essentials-targets', array( $this, 'migration' ) ); }
 /** Read the current source record (including pre-v3 metadata) without modifying it. @param int $id Content ID. @return array */
 public static function source( int $id ): array {
  $url = get_post_meta( $id, '_links_to', true ); $target = get_post_meta( $id, '_links_to_target', true );
  if ( '' === $url && ! metadata_exists( 'post', $id, '_links_to' ) ) { $url = get_post_meta( $id, 'links_to', true ); $target = get_post_meta( $id, 'links_to_target', true ); }
  return array( 'url' => $url, 'new_tab' => '_blank' === $target, 'nofollow' => false );
 }
 /** Bind each selected migration to its exact current source and destination. @param int $id Content ID. @return string */
 public static function fingerprint( int $id ): string { return hash( 'sha256', wp_json_encode( array( get_current_blog_id(), $id, self::source( $id ), get_post_meta( $id, self::META, true ), get_post_type( $id ), get_post_status( $id ) ) ) ); }
 /** Render a bounded, paginated migration preview with no preselected records. @return void */
 public function migration(): void {
  if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { return; }
  echo '<div class="wrap se-targets-page"><header class="se-target-header"><img src="' . esc_url( plugins_url( 'assets/images/icon.svg', SITE_ESSENTIALS_FILE ) ) . '" width="40" height="40" alt=""><div><h1 id="se-target-import-title">' . esc_html__( 'Alternative post destinations', 'site-essentials' ) . '</h1><p>Site Essentials ' . esc_html( SITE_ESSENTIALS_VERSION ) . '</p></div></header><hr class="wp-header-end"><p>' . esc_html__( 'Copy selected Page Links To destinations. Existing Site Essentials destinations are never overwritten; original Page Links To data remain intact. Content destinations are not included in configuration or preset exports.', 'site-essentials' ) . '</p>';
  if ( self::provider() ) { echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Page Links To is active. Site Essentials link processing and editing are paused. Copies can be prepared here; deactivate Page Links To yourself when switching providers.', 'site-essentials' ) . '</p></div>'; }
  $done = isset( $_GET['copied'] ) && is_scalar( $_GET['copied'] ) ? absint( $_GET['copied'] ) : 0;
  $skipped = isset( $_GET['skipped'] ) && is_scalar( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
  if ( isset( $_GET['copied'] ) || isset( $_GET['skipped'] ) ) { echo '<div class="notice notice-info inline"><p>' . esc_html( sprintf( __( 'Copied: %1$d. Skipped or changed since preview: %2$d. Review the current list before continuing.', 'site-essentials' ), $done, $skipped ) ) . '</p></div>'; }
  $types = self::types();
  if ( ! $types ) { echo '<p>' . esc_html__( 'Select content types in Site Essentials and save before reviewing destinations.', 'site-essentials' ) . '</p></div>'; return; }
  $page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, min( 10000, absint( $_GET['paged'] ) ) ) : 1;
  $q = new \WP_Query( array( 'post_type' => $types, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 25, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'meta_query' => array( 'relation' => 'OR', array( 'key' => '_links_to', 'compare' => 'EXISTS' ), array( 'key' => 'links_to', 'compare' => 'EXISTS' ) ) ) );
  echo '<form id="se-target-import" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; wp_nonce_field( 'se_targets_import_' . get_current_blog_id() );
  echo '<input type="hidden" name="action" value="site_essentials_targets_import"><p><label><input type="checkbox" id="se-target-select-page"> ' . esc_html__( 'Select all available destinations on this page', 'site-essentials' ) . '</label></p><div class="se-target-table" tabindex="0" role="region" aria-labelledby="se-target-import-title"><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Select', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Content', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Destination URL', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Status', 'site-essentials' ) . '</th></tr></thead><tbody>';
  $visible = 0;
  foreach ( $q->posts as $post ) {
   if ( ! current_user_can( 'edit_post', $post->ID ) ) { continue; }
   $visible++; $label = trim( $post->post_title ) !== '' ? $post->post_title : sprintf( __( 'Untitled content #%d', 'site-essentials' ), $post->ID );
   $r = self::source( $post->ID ); $valid = ! is_wp_error( self::url( $r['url'] ) ) && $this->safe_chain( $post->ID, $r['url'] ); $exists = metadata_exists( 'post', $post->ID, self::META );
   echo '<tr><td>'; if ( $valid && ! $exists ) { echo '<input type="checkbox" aria-label="' . esc_attr( sprintf( __( 'Select %s', 'site-essentials' ), $label ) ) . '" name="selected[' . esc_attr( $post->ID ) . ']" value="' . esc_attr( self::fingerprint( $post->ID ) ) . '">'; }
   echo '</td><td>' . esc_html( $label ) . '</td><td><code>' . esc_html( is_string( $r['url'] ) ? $r['url'] : '' ) . '</code>' . ( $r['new_tab'] ? '<br>' . esc_html__( 'New tab', 'site-essentials' ) : '' ) . '</td><td>' . esc_html( $exists ? __( 'Already has a destination', 'site-essentials' ) : ( $valid ? __( 'Ready to copy', 'site-essentials' ) : __( 'Invalid destination or local loop', 'site-essentials' ) ) ) . '</td></tr>';
  }
  if ( ! $visible ) { echo '<tr><td colspan="4">' . esc_html__( 'No Page Links To destinations are available for the selected content types on this page.', 'site-essentials' ) . '</td></tr>'; }
  echo '</tbody></table></div>'; submit_button( __( 'Copy selected destinations', 'site-essentials' ) ); echo '<input type="hidden" name="se_targets_complete" value="1"></form>';
  echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', admin_url( 'tools.php?page=site-essentials-targets' ) ), 'total' => $q->max_num_pages, 'current' => $page ) ) );
  echo '<p><a href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-targets' ) ) . '">' . esc_html__( 'Open Site Essentials settings', 'site-essentials' ) . '</a></p></div>';
 }
 /** Copy one unchanged selected source; retain all original metadata and skip occupied destinations. @param int $id Content ID. @param string $fingerprint Preview fingerprint. @return bool */
 public function copy( int $id, string $fingerprint ): bool {
  if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) || ! in_array( get_post_status( $id ), array( 'publish', 'draft', 'pending', 'private', 'future' ), true ) || ! in_array( get_post_type( $id ), self::types(), true ) || metadata_exists( 'post', $id, self::META ) || ! hash_equals( self::fingerprint( $id ), $fingerprint ) ) { return false; }
  $r = self::source( $id );
  if ( is_wp_error( self::url( $r['url'] ) ) || ! $this->safe_chain( $id, $r['url'] ) ) { return false; }
  $r['url'] = self::url( $r['url'] );
  // Unique metadata writes avoid overwriting an existing record; read-back catches storage failures.
  return (bool) add_post_meta( $id, self::META, $r, true ) && get_post_meta( $id, self::META, true ) === $r;
 }
 /** Execute only bounded, nonce-bound, site-specific selected copies. @return void */
 public function import(): void {
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $nonce = $_POST['_wpnonce'] ?? null;
  if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'se_targets_import_' . get_current_blog_id() ) ) { wp_die( esc_html__( 'The request could not be verified. Reload the page and try again.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $selected = $_POST['selected'] ?? array();
  if ( ! is_array( $selected ) || count( $selected ) > 25 ) { wp_die( esc_html__( 'Invalid selection.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  if ( ! $selected ) { wp_die( esc_html__( 'Select at least one destination to copy.', 'site-essentials' ), '', array( 'response' => 400, 'back_link' => true ) ); }
  if ( '1' !== ( $_POST['se_targets_complete'] ?? '' ) ) { wp_die( esc_html__( 'The selection form was incomplete. Reload and try again; no destinations were copied.', 'site-essentials' ), '', array( 'response' => 400, 'back_link' => true ) ); }
  $done = 0; $skipped = 0;
  foreach ( $selected as $id => $hash ) { if ( ctype_digit( (string) $id ) && is_string( $hash ) && preg_match( '/^[a-f0-9]{64}$/D', $hash ) && $this->copy( (int) $id, $hash ) ) { $done++; } else { $skipped++; } }
  wp_safe_redirect( add_query_arg( array( 'page' => 'site-essentials-targets', 'copied' => $done, 'skipped' => $skipped ), admin_url( 'tools.php' ) ) ); exit;
 }
}
