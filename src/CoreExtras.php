<?php
/** Focused optional core cleanup without third-party adapters. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Controls public routes, classic widgets and small core presentation details. */
final class CoreExtras {
 private bool $blocked = false;
 /** Register callbacks; neutral settings preserve native behavior. @return void */
 public function boot(): void {
  add_action( 'wp_print_styles', array( $this, 'dashicons' ), 100 );
  add_filter( 'fallback_intermediate_image_sizes', array( $this, 'pdf' ), 100 );
  add_action( 'init', array( $this, 'text_filters' ), 110 );
  // Current core adds the account item at 9991 and its public search at 9999.
  add_action( 'admin_bar_menu', array( $this, 'greeting' ), 10000 );
  add_action( 'template_redirect', array( $this, 'public_request' ), 0 );
  add_filter( 'redirect_canonical', array( $this, 'canonical' ), 100 );
  add_filter( 'get_search_form', array( $this, 'search_form' ), 100 );
  add_filter( 'pre_render_block', array( $this, 'search_block' ), 100, 2 );
  add_filter( 'rest_pre_dispatch', array( $this, 'search_rest' ), 100, 3 );
  add_filter( 'author_link', array( $this, 'author_link' ), 100 );
  add_filter( 'the_author_posts_link', array( $this, 'author_markup' ), 100, 2 );
  add_filter( 'wp_sitemaps_add_provider', array( $this, 'author_sitemap' ), 100, 2 );
  add_action( 'widgets_init', array( $this, 'widgets' ), 110 );
  if ( is_admin() ) { add_action( 'admin_init', array( $this, 'update_notice' ) ); }
 }
 /** Remove the direct public enqueue only; dependencies may still load it. @return void */
 public function dashicons(): void {
  if ( ! Settings::get( 'dashicons' ) || is_admin() || is_user_logged_in() || is_customize_preview() || 'wp-login.php' === ( $GLOBALS['pagenow'] ?? '' ) ) { return; }
  wp_dequeue_style( 'dashicons' );
 }
 /** Suppress new PDF fallback thumbnails, never existing files.
  * @param array $sizes Incoming fallback image sizes.
  * @return array
  */
 public function pdf( array $sizes ): array { return Settings::get( 'pdf_previews' ) ? array() : $sizes; }
 /** Remove only the requested native output filters, never stored content. @return void */
 public function text_filters(): void {
  if ( Settings::get( 'capital_p' ) ) {
   remove_filter( 'the_title', 'capital_P_dangit', 11 ); remove_filter( 'the_content', 'capital_P_dangit', 11 ); remove_filter( 'comment_text', 'capital_P_dangit', 31 );
  }
  if ( Settings::get( 'comment_autolinks' ) ) { remove_filter( 'comment_text', 'make_clickable', 9 ); }
 }
 /** Remove the native translated greeting only, preserving avatar and custom nodes.
  * @param \WP_Admin_Bar $bar Toolbar instance.
  * @return void
  */
 public function greeting( $bar ): void {
  if ( ! is_admin() && Settings::get( 'public_search' ) ) { $bar->remove_node( 'search' ); }
  if ( ! Settings::get( 'toolbar_greeting' ) || ! is_user_logged_in() ) { return; }
  $node = $bar->get_node( 'my-account' ); if ( ! $node ) { return; }
  $name = wp_get_current_user()->display_name;
  $span = '<span class="display-name">' . $name . '</span>';
  /* translators: %s: the current user’s display name. */
  $native = sprintf( __( 'Howdy, %s', 'default' ), $span );
  if ( ! str_contains( $node->title, $native ) ) { return; }
  $node->title = str_replace( $native, '<span class="display-name">' . esc_html( $name ) . '</span>', $node->title );
  if ( isset( $node->meta['menu_title'] ) && $node->meta['menu_title'] === wp_strip_all_tags( $native ) ) { $node->meta['menu_title'] = wp_strip_all_tags( $name ); }
  $bar->add_node( (array) $node );
 }
 /** Return genuine 404 templates for disabled public search and author feeds/archives. @return void */
 public function public_request(): void {
  if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { return; }
  if ( ! ( Settings::get( 'public_search' ) && is_search() ) && ! ( Settings::get( 'author_archives' ) && is_author() ) ) { return; }
  global $wp_query;
  $this->blocked = true; $wp_query->set_404(); $wp_query->posts = array(); $wp_query->post_count = 0; $wp_query->found_posts = 0; $wp_query->max_num_pages = 0;
  status_header( 404 ); nocache_headers();
 }
 /** Prevent canonical redirects reopening deliberately disabled routes.
  * @param string|false $url Proposed redirect.
  * @return string|false
  */
 public function canonical( $url ) { return $this->blocked ? false : $url; }
 /** Hide only native public search forms.
  * @param string $html Form markup.
  * @return string
  */
 public function search_form( string $html ): string { return ! is_admin() && Settings::get( 'public_search' ) ? '' : $html; }
 /** Skip native public search blocks before their markup/assets are rendered.
  * @param string|null $html Existing short circuit.
  * @param array $block Parsed block.
  * @return string|null
  */
 public function search_block( $html, array $block ) {
  return ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) && Settings::get( 'public_search' ) && 'core/search' === ( $block['blockName'] ?? '' ) ? '' : $html;
 }
 /** Restrict native public REST search while preserving authenticated editor lookup.
  * @param mixed $result Existing dispatch result.
  * @param \WP_REST_Server $server REST server.
  * @param \WP_REST_Request $request Request.
  * @return mixed
  */
 public function search_rest( $result, $server, $request ) {
  if ( null !== $result || ! Settings::get( 'public_search' ) || '/wp/v2/search' !== rtrim( $request->get_route(), '/' ) || 'OPTIONS' === $request->get_method() || current_user_can( 'edit_posts' ) ) { return $result; }
  return new \WP_Error( 'site_essentials_search_disabled', __( 'Public WordPress search is disabled.', 'site-essentials' ), array( 'status' => 404 ) );
 }
 /** Remove native author archive URLs, retaining author identity.
  * @param string $url Archive URL.
  * @return string
  */
 public function author_link( string $url ): string { return ! is_admin() && Settings::get( 'author_archives' ) ? '' : $url; }
 /** Keep the native author name as text instead of an empty archive link.
  * @param string $html Native archive link.
  * @param string $author Display name.
  * @return string
  */
 public function author_markup( string $html, string $author ): string { return ! is_admin() && Settings::get( 'author_archives' ) ? esc_html( $author ) : $html; }
 /** Remove only the native users sitemap when author archives are disabled.
  * @param object|false $provider Sitemap provider.
  * @param string $name Provider identifier.
  * @return object|false
  */
 public function author_sitemap( $provider, string $name ) { return Settings::get( 'author_archives' ) && 'users' === $name ? false : $provider; }
 /** Known classic core widget identifiers and class names. @return array<string,string> */
 public static function widget_classes(): array { return array( 'archives' => 'WP_Widget_Archives', 'calendar' => 'WP_Widget_Calendar', 'meta' => 'WP_Widget_Meta', 'search' => 'WP_Widget_Search', 'recent-posts' => 'WP_Widget_Recent_Posts', 'recent-comments' => 'WP_Widget_Recent_Comments', 'categories' => 'WP_Widget_Categories', 'tag-cloud' => 'WP_Widget_Tag_Cloud', 'rss' => 'WP_Widget_RSS' ); }
 /** Unregister selected native widgets without deleting saved instances or sidebars. @return void */
 public function widgets(): void {
  $classes = self::widget_classes();
  foreach ( Settings::get( 'core_widgets' ) as $name ) { if ( isset( $classes[ $name ] ) ) { unregister_widget( $classes[ $name ] ); } }
 }
 /** Hide only the core update notice from users who cannot perform that update. @return void */
 public function update_notice(): void {
  if ( Settings::get( 'update_notice' ) && ! current_user_can( 'update_core' ) ) { remove_action( 'admin_notices', 'update_nag', 3 ); }
 }
}
