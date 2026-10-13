<?php
/** Optional complete public WordPress comment shutdown for the current site. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Suppresses core comment features without deleting data or interfering with private editor notes. */
final class CommentsOff {
 /** Register neutral, site-local controls. @return void */
 public function boot(): void {
  add_filter( 'manage_posts_columns', array( $this, 'columns' ), PHP_INT_MAX );
  add_filter( 'manage_pages_columns', array( $this, 'columns' ), PHP_INT_MAX );
  add_filter( 'manage_media_columns', array( $this, 'columns' ), PHP_INT_MAX );
  add_action( 'init', array( $this, 'supports' ), 100 );
  add_action( 'registered_post_type', array( $this, 'registered' ), 100, 2 );
  add_filter( 'comments_open', array( $this, 'closed' ), PHP_INT_MAX );
  add_filter( 'pings_open', array( $this, 'closed' ), PHP_INT_MAX );
  add_filter( 'comments_array', array( $this, 'empty_comments' ), PHP_INT_MAX );
  add_filter( 'comments_number', array( $this, 'feed_link' ), PHP_INT_MAX );
  add_filter( 'get_comments_number', array( $this, 'number' ), PHP_INT_MAX );
  add_filter( 'comments_template', array( $this, 'template' ), PHP_INT_MAX );
  add_filter( 'pre_render_block', array( $this, 'block' ), PHP_INT_MAX, 2 );
  add_filter( 'allowed_block_types_all', array( $this, 'allowed_blocks' ), 100 );
  add_filter( 'feed_links_show_comments_feed', array( $this, 'closed' ), PHP_INT_MAX );
  add_filter( 'feed_links_extra_show_post_comments_feed', array( $this, 'closed' ), PHP_INT_MAX );
  add_filter( 'post_comments_feed_link', array( $this, 'feed_link' ), PHP_INT_MAX );
  add_filter( 'post_comments_feed_link_html', array( $this, 'feed_link' ), PHP_INT_MAX );
  add_action( 'template_redirect', array( $this, 'feed_request' ), 0 );
  add_filter( 'wp_headers', array( $this, 'headers' ), PHP_INT_MAX );
  add_action( 'pre_ping', array( $this, 'pings' ), PHP_INT_MAX );
  add_filter( 'pre_option_default_pingback_flag', array( $this, 'pingback_default' ), PHP_INT_MAX );
  add_filter( 'pre_comment_approved', array( $this, 'approve' ), PHP_INT_MAX, 2 );
  add_filter( 'rest_pre_dispatch', array( $this, 'rest' ), 100, 3 );
  add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc' ), 100 );
  add_action( 'widgets_init', array( $this, 'widgets' ), 100 );
  add_filter( 'show_recent_comments_widget_style', array( $this, 'closed' ), PHP_INT_MAX );
  add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), PHP_INT_MAX );
  add_action( 'wp_print_scripts', array( $this, 'assets' ), PHP_INT_MAX );
  add_action( 'wp_print_footer_scripts', array( $this, 'assets' ), 0 );
  if ( is_admin() ) {
   add_action( 'admin_menu', array( $this, 'menus' ), PHP_INT_MAX );
   add_action( 'admin_head', array( $this, 'menus' ), PHP_INT_MAX );
   foreach ( array( 'edit-comments.php', 'comment.php', 'options-discussion.php' ) as $screen ) { add_action( 'load-' . $screen, array( $this, 'access' ) ); }
   add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ), 100, 2 );
   add_action( 'wp_dashboard_setup', array( $this, 'dashboard' ), 100 );
   add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ), PHP_INT_MAX );
  }
  add_action( 'admin_bar_menu', array( $this, 'toolbar' ), PHP_INT_MAX );
 }
 /** Whether the complete option overrides narrower stored rules. @return bool */
 private function enabled(): bool { return (bool) Settings::get( 'comments_disabled' ); }
 /** Remove comment/trackback support from current registered types for this request only. @return void */
 public function supports(): void { if ( $this->enabled() ) { foreach ( get_post_types() as $type ) { remove_post_type_support( $type, 'comments' ); remove_post_type_support( $type, 'trackbacks' ); } } }
 /** Cover types registered later without altering other supports.
  * @param string $type Registered type.
  * @param \WP_Post_Type $object Definition.
  * @return void
  */
 public function registered( string $type, $object ): void { if ( $this->enabled() ) { remove_post_type_support( $type, 'comments' ); remove_post_type_support( $type, 'trackbacks' ); } }
 /** Preserve stronger incoming restrictions when inactive.
  * @param mixed $value Existing bool.
  * @return mixed
  */
 public function closed( $value ) { return $this->enabled() ? false : $value; }
 /** Hide ordinary template comments, leaving the database intact.
  * @param array $comments Existing comments.
  * @return array
  */
 public function empty_comments( array $comments ): array { return $this->enabled() ? array() : $comments; }
 /** Hide core comment counts.
  * @param mixed $count Count.
  * @return mixed
  */
 public function number( $count ) { return $this->enabled() ? 0 : $count; }
 /** Replace theme comment templates with a local empty template.
  * @param string $path Existing template.
  * @return string
  */
 public function template( string $path ): string { return $this->enabled() ? dirname( SITE_ESSENTIALS_FILE ) . '/templates/comments-empty.php' : $path; }
 /** Identify core public comment blocks without touching private notes or third-party blocks.
  * @param string $name Registered block name.
  * @return bool
  */
 private function comment_block( string $name ): bool { return 'core/latest-comments' === $name || 'core/comments' === $name || str_starts_with( $name, 'core/comment-' ) || str_starts_with( $name, 'core/comments-' ) || str_starts_with( $name, 'core/post-comments' ); }
 /** Short-circuit rendering before dedicated block assets enqueue.
  * @param mixed $pre Existing rendered override.
  * @param array $block Parsed block.
  * @return mixed
  */
 public function block( $pre, array $block ) { return $this->enabled() && $this->comment_block( $block['blockName'] ?? '' ) ? '' : $pre; }
 /** Hide comment blocks in inserters while keeping incoming allowlists restrictive.
  * @param mixed $allowed Existing allowlist.
  * @return mixed
  */
 public function allowed_blocks( $allowed ) {
  if ( ! $this->enabled() || false === $allowed ) { return $allowed; }
  $names = true === $allowed ? array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() ) : $allowed;
  return is_array( $names ) ? array_values( array_filter( $names, fn( $name ) => ! $this->comment_block( $name ) ) ) : $allowed;
 }
 /** Remove only comment feed links.
  * @param string $link Incoming URL or HTML.
  * @return string
  */
 public function feed_link( string $link ): string { return $this->enabled() ? '' : $link; }
 /** Return 404 for comment feeds, keeping ordinary post feeds. @return void */
 public function feed_request(): void { if ( $this->enabled() && is_comment_feed() ) { wp_die( esc_html__( 'Comments are disabled on this site.', 'site-essentials' ), '', array( 'response' => 404 ) ); } }
 /** Remove only the pingback response header.
  * @param array $headers Response headers.
  * @return array
  */
 public function headers( array $headers ): array { if ( $this->enabled() ) { foreach ( $headers as $key => $value ) { if ( 'x-pingback' === strtolower( $key ) ) { unset( $headers[ $key ] ); } } } return $headers; }
 /** Suppress all outgoing core pingbacks.
  * @param array $links Targets modified by reference.
  * @return void
  */
 public function pings( array &$links ): void { if ( $this->enabled() ) { $links = array(); } }
 /** Override the option only at runtime.
  * @param mixed $value Existing short-circuit result.
  * @return mixed
  */
 public function pingback_default( $value ) { return $this->enabled() ? 0 : $value; }
 /** Reject normal new comments/pings at the core validation boundary, preserving private/system types.
  * @param mixed $approved Existing approval or error.
  * @param array $data Comment data.
  * @return mixed
  */
 public function approve( $approved, array $data ) {
  return $this->enabled() && ! is_wp_error( $approved ) && in_array( $data['comment_type'] ?? '', array( '', 'comment', 'pingback', 'trackback' ), true ) ? new \WP_Error( 'site_essentials_comments_disabled', __( 'Comments are disabled on this site.', 'site-essentials' ), array( 'status' => 403 ) ) : $approved;
 }
 /** Block public core comment routes; notes keep native per-post authorization and cannot spoof normal items.
  * @param mixed $result Previous dispatch override.
  * @param \WP_REST_Server $server Server.
  * @param \WP_REST_Request $request Request.
  * @return mixed
  */
 public function rest( $result, $server, $request ) {
  $route = $request->get_route();
  if ( ! $this->enabled() || null !== $result || ! preg_match( '#^/wp/v2/comments(?:/|$)#', $route ) ) { return $result; }
  if ( 'OPTIONS' === $request->get_method() ) { return $result; }
  if ( preg_match( '#^/wp/v2/comments/(\d+)(?:/|$)#', $route, $match ) ) {
   $comment = get_comment( (int) $match[1] ); if ( $comment && 'note' === $comment->comment_type ) { return $result; }
  } elseif ( '/wp/v2/comments' === rtrim( $route, '/' ) && 'note' === $request->get_param( 'type' ) ) { return $result; }
  return new \WP_Error( 'site_essentials_comments_disabled', __( 'Comments are disabled on this site.', 'site-essentials' ), array( 'status' => 403 ) );
 }
 /** Remove comment and pingback XML-RPC methods while leaving unrelated methods intact.
  * @param array $methods Existing method map.
  * @return array
  */
 public function xmlrpc( array $methods ): array {
  if ( $this->enabled() ) { foreach ( array( 'wp.getComment', 'wp.getComments', 'wp.newComment', 'wp.editComment', 'wp.deleteComment', 'wp.getCommentStatusList', 'wp.getCommentCount', 'pingback.ping', 'pingback.extensions.getPingbacks', 'mt.getTrackbackPings' ) as $method ) { unset( $methods[ $method ] ); } } return $methods;
 }
 /** Remove the recent-comments widget and its dedicated inline style. @return void */
 public function widgets(): void { if ( $this->enabled() ) { unregister_widget( 'WP_Widget_Recent_Comments' ); } }
 /** Remove dedicated comment assets only; shared WordPress bundles remain usable. @return void */
 public function assets(): void {
  if ( ! $this->enabled() ) { return; }
  wp_dequeue_script( 'comment-reply' ); wp_deregister_script( 'comment-reply' );
  foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block ) {
   if ( ! $this->comment_block( $name ) ) { continue; }
   foreach ( array_merge( $block->style_handles, $block->view_style_handles, $block->editor_style_handles ) as $handle ) { if ( str_starts_with( $handle, 'wp-block-comment' ) || str_starts_with( $handle, 'wp-block-post-comments' ) || 'wp-block-latest-comments' === $handle ) { wp_dequeue_style( $handle ); } }
  }
 }
 /** Remove comment columns while retaining unrelated list-table columns.
  * @param array $columns Columns.
  * @return array
  */
 public function columns( array $columns ): array { if ( $this->enabled() ) { unset( $columns['comments'] ); } return $columns; }
 /** Hide core menus only on local admin screens. @return void */
 public function menus(): void { if ( ! is_network_admin() && $this->enabled() ) { remove_menu_page( 'edit-comments.php' ); remove_submenu_page( 'options-general.php', 'options-discussion.php' ); } }
 /** Block direct entry to removed local management screens. @return void */
 public function access(): void { if ( $this->enabled() ) { wp_die( esc_html__( 'Comments are disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); } }
 /** Remove comment metaboxes without changing stored statuses.
  * @param string $type Current content type.
  * @param \WP_Post $post Current post.
  * @return void
  */
 public function meta_boxes( string $type, $post ): void { if ( $this->enabled() ) { foreach ( array( 'commentsdiv', 'commentstatusdiv', 'trackbacksdiv' ) as $id ) { remove_meta_box( $id, $type, 'normal' ); } } }
 /** Hide the old comment widget; modern activity remains for non-comment activity. @return void */
 public function dashboard(): void {
  if ( ! $this->enabled() ) { return; }
  remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
  global $wp_meta_boxes;
  if ( 'wp_dashboard_site_activity' === ( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']['callback'] ?? null ) ) { $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']['callback'] = array( $this, 'activity' ); }
 }
 /** Render the core post activity without querying or outputting the recent-comment section. @return void */
 public function activity(): void {
  echo '<div id="activity-widget">';
  $future = wp_dashboard_recent_posts( array( 'max' => 5, 'status' => 'future', 'order' => 'ASC', 'title' => esc_html__( 'Publishing Soon' ), 'id' => 'future-posts' ) );
  $recent = wp_dashboard_recent_posts( array( 'max' => 5, 'status' => 'publish', 'order' => 'DESC', 'title' => esc_html__( 'Recently Published' ), 'id' => 'published-posts' ) );
  if ( ! $future && ! $recent ) { echo '<div class="no-activity"><p>' . esc_html__( 'No activity yet!' ) . '</p></div>'; }
  echo '</div>';
 }
 /** Remove current-site toolbar links without hiding other sites' independently available comments.
  * @param \WP_Admin_Bar $bar Toolbar.
  * @return void
  */
 public function toolbar( $bar ): void { if ( $this->enabled() ) { $bar->remove_node( 'comments' ); $bar->remove_node( 'blog-' . get_current_blog_id() . '-c' ); } }
 /** Target remaining core dashboard/profile appearances via one local stylesheet.
  * @param string $hook Admin screen.
  * @return void
  */
 public function admin_assets( string $hook ): void {
  if ( ! $this->enabled() ) { return; }
  wp_dequeue_script( 'admin-comments' ); $this->assets();
  if ( in_array( $hook, array( 'index.php', 'profile.php', 'user-edit.php' ), true ) ) { wp_enqueue_style( 'site-essentials-comments-off', plugins_url( 'assets/comments-off.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION ); }
 }
}
