<?php
/** Optional native post management, public output and labels. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Keeps stored posts intact and isolates changes from pages and other content types. */
final class Blog {
 private bool $blocked = false;
 private $native_labels;
 /** Register native hooks without rewriting content, capabilities or permalinks. @return void */
 public function boot(): void {
  $this->native_labels = clone get_post_type_object( 'post' )->labels;
  add_action( 'init', array( $this, 'sync_labels' ), 20 );
  add_action( 'switch_blog', array( $this, 'sync_labels' ) );
  add_filter( 'post_type_labels_post', array( $this, 'labels' ), 100 );
  add_filter( 'map_meta_cap', array( $this, 'caps' ), 110, 4 );
  add_action( 'pre_get_posts', array( $this, 'query' ), 100 );
  add_action( 'template_redirect', array( $this, 'request' ), 1 );
  add_filter( 'redirect_canonical', array( $this, 'canonical' ), 110 );
  add_filter( 'rest_pre_dispatch', array( $this, 'rest' ), 110, 3 );
  add_filter( 'rest_post_search_query', array( $this, 'rest_search' ), 100, 2 );
  add_filter( 'wp_sitemaps_post_types', array( $this, 'sitemap' ), 100 );
  add_filter( 'oembed_response_data', array( $this, 'oembed' ), 100, 2 );
  add_filter( 'comments_clauses', array( $this, 'comments' ), 100 );
  add_action( 'admin_bar_menu', array( $this, 'toolbar' ), 10001 );
  if ( is_admin() ) {
   add_action( 'admin_menu', array( $this, 'menu' ), 110 );
   foreach ( array( 'edit.php', 'post.php', 'post-new.php', 'edit-tags.php', 'term.php' ) as $file ) { add_action( 'load-' . $file, array( $this, 'admin_access' ) ); }
   add_action( 'wp_dashboard_setup', array( $this, 'dashboard' ), 110 );
   add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
   add_filter( 'post_updated_messages', array( $this, 'messages' ) );
   add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_messages' ), 100, 2 );
   add_filter( 'ngettext', array( $this, 'count_label' ), 100, 5 );
  }
 }
 /** Refresh native post labels for the actual current site without changing other registries. @return void */
 public function sync_labels(): void {
  // Site creation switches context before the new site's option tables exist.
  if ( doing_action( 'wp_initialize_site' ) ) { return; }
  $type = get_post_type_object( 'post' ); if ( ! $type || ! $this->native_labels ) { return; } $copy = clone $type; $copy->labels = clone $this->native_labels; $type->labels = get_post_type_labels( $copy ); }
 /** Return both explicit plain-text names or retain the native labels. @return array */
 private function names(): array {
  $plural = trim( (string) Settings::get( 'post_plural' ) ); $single = trim( (string) Settings::get( 'post_singular' ) );
  return Settings::get( 'post_rename' ) && '' !== $plural && '' !== $single ? array( $plural, $single ) : array();
 }
 /** Rename native labels used by menus, toolbars, REST and both editors.
  * @param object $labels Native labels.
  * @return object
  */
 public function labels( $labels ) {
  $names = $this->names(); if ( ! $names ) { return $labels; } $labels = clone $labels; list( $plural, $single ) = $names;
  foreach ( array( 'name', 'menu_name' ) as $key ) { $labels->$key = $plural; }
  foreach ( array( 'singular_name', 'name_admin_bar' ) as $key ) { $labels->$key = $single; }
  /* translators: %s: the configured singular or plural post label. */
  $single_templates = array( 'add_new' => __( 'Add %s', 'site-essentials' ), 'add_new_item' => __( 'Add %s', 'site-essentials' ), 'edit_item' => __( 'Edit %s', 'site-essentials' ), 'new_item' => __( 'New: %s', 'site-essentials' ), 'view_item' => __( 'View %s', 'site-essentials' ), 'attributes' => __( '%s attributes', 'site-essentials' ), 'insert_into_item' => __( 'Insert into %s', 'site-essentials' ), 'uploaded_to_this_item' => __( 'Uploaded to %s', 'site-essentials' ), 'item_published' => __( '%s published.', 'site-essentials' ), 'item_published_privately' => __( '%s published privately.', 'site-essentials' ), 'item_reverted_to_draft' => __( '%s reverted to draft.', 'site-essentials' ), 'item_trashed' => __( '%s moved to Trash.', 'site-essentials' ), 'item_scheduled' => __( '%s scheduled.', 'site-essentials' ), 'item_updated' => __( '%s updated.', 'site-essentials' ), 'item_link' => __( '%s link', 'site-essentials' ), 'item_link_description' => __( 'A link to %s.', 'site-essentials' ), 'template_name' => __( 'Single item: %s', 'site-essentials' ) );
  /* translators: %s: the configured singular or plural post label. */
  $plural_templates = array( 'view_items' => __( 'View %s', 'site-essentials' ), 'search_items' => __( 'Search %s', 'site-essentials' ), 'not_found' => __( 'No %s found.', 'site-essentials' ), 'not_found_in_trash' => __( 'No %s found in Trash.', 'site-essentials' ), 'all_items' => __( 'All %s', 'site-essentials' ), 'archives' => __( '%s archives', 'site-essentials' ), 'filter_items_list' => __( 'Filter %s list', 'site-essentials' ), 'filter_by_date' => __( 'Filter %s by date', 'site-essentials' ), 'items_list_navigation' => __( '%s list navigation', 'site-essentials' ), 'items_list' => __( '%s list', 'site-essentials' ) );
  foreach ( $single_templates as $key => $template ) { $labels->$key = sprintf( $template, $single ); }
  foreach ( $plural_templates as $key => $template ) { $labels->$key = sprintf( $template, $plural ); }
  return $labels;
 }
 /** Deny only per-object native post edits/deletions; shared CPT primitive caps stay intact.
  * @param array $caps Existing permissions.
  * @param string $cap Requested meta capability.
  * @param int $user User identifier.
  * @param array $args Capability object arguments.
  * @return array
  */
 public function caps( array $caps, string $cap, int $user, array $args ): array {
  if ( Settings::get( 'blog_admin' ) && in_array( $cap, array( 'edit_post', 'delete_post' ), true ) && ! empty( $args[0] ) && 'post' === get_post_type( (int) $args[0] ) ) { if ( ! in_array( 'do_not_allow', $caps, true ) ) { $caps[] = 'do_not_allow'; } }
  return $caps;
 }
 /** Determine whether a taxonomy is used solely by native posts.
  * @param string $name Taxonomy identifier.
  * @return bool
  */
 private function post_only_taxonomy( string $name ): bool { $tax = get_taxonomy( $name ); return $tax && in_array( 'post', $tax->object_type, true ) && ! array_diff( $tax->object_type, array( 'post' ) ); }
 /** Remove the native post menu; shared taxonomy screens remain usable. @return void */
 public function menu(): void { if ( Settings::get( 'blog_admin' ) ) { remove_menu_page( 'edit.php' ); } }
 /** Remove only native New Post; preserve pages and third-party toolbar nodes.
  * @param \WP_Admin_Bar $bar Toolbar instance.
  * @return void
  */
 public function toolbar( $bar ): void { if ( Settings::get( 'blog_admin' ) ) { $bar->remove_node( 'new-post' ); if ( is_singular( 'post' ) ) { $bar->remove_node( 'edit' ); } } }
 /** Block direct native management URLs for posts and unshared post taxonomies. @return void */
 public function admin_access(): void {
  if ( ! Settings::get( 'blog_admin' ) ) { return; }
  $file = $GLOBALS['pagenow'] ?? ''; $type = isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post'; $deny = false;
  if ( in_array( $file, array( 'edit.php', 'post-new.php' ), true ) ) { $deny = 'post' === $type; }
  if ( 'post.php' === $file ) { $id = absint( $_REQUEST['post'] ?? $_POST['post_ID'] ?? 0 ); $deny = $id > 0 && 'post' === get_post_type( $id ); }
  if ( in_array( $file, array( 'edit-tags.php', 'term.php' ), true ) ) { $taxonomy = $_REQUEST['taxonomy'] ?? ''; $deny = is_string( $taxonomy ) && in_array( $taxonomy, array( 'category', 'post_tag' ), true ) && $this->post_only_taxonomy( $taxonomy ); }
  if ( $deny ) { wp_die( esc_html__( 'Native post management is disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
 }
 /** Preserve comments in the activity widget while removing post publishing offerings. @return void */
 public function dashboard(): void {
  if ( ! Settings::get( 'blog_admin' ) ) { return; }
  remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
  global $wp_meta_boxes;
  $callback = $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']['callback'] ?? null;
  if ( 'wp_dashboard_site_activity' === $callback || ( is_array( $callback ) && $callback[0] instanceof CommentsOff ) ) { $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']['callback'] = array( $this, 'activity' ); }
 }
 /** Render only native dashboard comment activity if comments remain enabled. @return void */
 public function activity(): void { if ( ! Settings::get( 'comments_disabled' ) && function_exists( 'wp_dashboard_recent_comments' ) ) { wp_dashboard_recent_comments(); } }
 /** Hide native summary post counts and links only on the dashboard.
  * @param string $hook Current admin page.
  * @return void
  */
 public function assets( string $hook ): void { if ( 'index.php' === $hook && Settings::get( 'blog_admin' ) ) { wp_enqueue_style( 'site-essentials-blog', plugins_url( 'assets/blog.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION ); } }
 /** Determine a public output request; authenticated REST management is separate. @return bool */
 private function public_context(): bool { return ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ); }
 /** Exclude native posts from WordPress frontend queries without changing stored records.
  * @param \WP_Query $query Query instance.
  * @return void
  */
 public function query( $query ): void {
  if ( ! Settings::get( 'blog_public' ) || ! $this->public_context() ) { return; }
  if ( $query->is_preview() && current_user_can( 'edit_post', (int) ( $query->get( 'preview_id' ) ?: $query->get( 'p' ) ) ) ) { return; }
  $types = $query->get( 'post_type' );
  if ( ! $types ) {
   if ( $query->is_page() || $query->is_attachment() ) { return; }
   if ( $query->is_search() ) { $types = get_post_types( array( 'exclude_from_search' => false ) ); }
   elseif ( $query->is_category() || $query->is_tag() ) { $tax = get_taxonomy( $query->is_category() ? 'category' : 'post_tag' ); $types = $tax ? $tax->object_type : array( 'post' ); }
   else { $types = array( 'post' ); }
  }
  if ( 'any' === $types ) { $types = get_post_types( array( 'exclude_from_search' => false ) ); }
  $types = array_values( array_diff( (array) $types, array( 'post' ) ) );
  $query->set( 'post_type', $types ?: array( 'se_no_public_posts' ) );
 }
 /** End blog-only archives/home requests with an actual 404 while preserving static homepages. @return void */
 public function request(): void {
  if ( ! Settings::get( 'blog_public' ) || is_admin() ) { return; }
  global $wp_query;
  $only_posts = in_array( 'se_no_public_posts', (array) $wp_query->get( 'post_type' ), true );
  if ( ! $only_posts || is_search() ) { return; }
  $this->blocked = true; $wp_query->set_404(); $wp_query->posts = array(); $wp_query->post_count = 0; $wp_query->found_posts = 0; status_header( 404 ); nocache_headers();
 }
 /** Prevent canonical redirects from reopening disabled blog routes.
  * @param string|false $url Proposed URL.
  * @return string|false
  */
 public function canonical( $url ) { return $this->blocked ? false : $url; }
 /** Protect native REST management separately from public post reads.
  * @param mixed $result Earlier dispatch result.
  * @param \WP_REST_Server $server REST server.
  * @param \WP_REST_Request $request Request.
  * @return mixed
  */
 public function rest( $result, $server, $request ) {
  if ( null !== $result || 'OPTIONS' === $request->get_method() || ! preg_match( '#^/wp/v2/posts(?:/|$)#', $request->get_route() ) ) { return $result; }
  $write = ! in_array( $request->get_method(), array( 'GET', 'HEAD' ), true ); $editing = 'edit' === $request->get_param( 'context' );
  if ( Settings::get( 'blog_admin' ) && ( $write || $editing ) ) { return new \WP_Error( 'site_essentials_posts_disabled', __( 'Native post management is disabled on this site.', 'site-essentials' ), array( 'status' => 403 ) ); }
  if ( Settings::get( 'blog_public' ) && ! ( current_user_can( 'edit_posts' ) && ( $write || $editing ) ) ) { return new \WP_Error( 'site_essentials_posts_unavailable', __( 'Public blog output is disabled on this site.', 'site-essentials' ), array( 'status' => 404 ) ); }
  return $result;
 }
 /** Exclude posts from public native REST searches, retaining pages and editor lookup.
  * @param array $args Search query arguments.
  * @param \WP_REST_Request $request REST request.
  * @return array
  */
 public function rest_search( array $args, $request ): array {
  if ( Settings::get( 'blog_public' ) && ! current_user_can( 'edit_posts' ) ) { $types = $args['post_type'] ?? get_post_types( array( 'exclude_from_search' => false ) ); $args['post_type'] = array_values( array_diff( (array) $types, array( 'post' ) ) ) ?: array( 'se_no_public_posts' ); }
  return $args;
 }
 /** Keep only native post entries out of the core posts sitemap.
  * @param array $types Sitemap content types.
  * @return array
  */
 public function sitemap( array $types ): array { if ( Settings::get( 'blog_public' ) ) { unset( $types['post'] ); } return $types; }
 /** Suppress own oEmbed responses for hidden native posts.
  * @param array|false $data Native oEmbed response.
  * @param \WP_Post $post Embedded object.
  * @return array|false
  */
 public function oembed( $data, $post ) { return Settings::get( 'blog_public' ) && 'post' === $post->post_type ? false : $data; }
 /** Exclude comments attached to hidden posts from ordinary public comment queries.
  * @param array $clauses SQL query clauses.
  * @return array
  */
 public function comments( array $clauses ): array {
  if ( Settings::get( 'blog_public' ) && $this->public_context() ) { global $wpdb; $clauses['where'] .= $wpdb->prepare( ' AND comment_post_ID NOT IN (SELECT ID FROM %i WHERE post_type = %s)', $wpdb->posts, 'post' ); }
  return $clauses;
 }
 /** Use the selected singular name in classic-editor result messages.
  * @param array $messages Messages keyed by post type.
  * @return array
  */
 public function messages( array $messages ): array {
  $names = $this->names(); if ( ! $names || ! isset( $messages['post'] ) ) { return $messages; }
  /* translators: %s: the configured singular or plural post label. */
  $single = esc_html( $names[1] ); $templates = array( 1 => __( '%s updated.', 'site-essentials' ), 4 => __( '%s updated.', 'site-essentials' ), 5 => __( '%s restored to a revision.', 'site-essentials' ), 6 => __( '%s published.', 'site-essentials' ), 7 => __( '%s saved.', 'site-essentials' ), 8 => __( '%s submitted.', 'site-essentials' ), 9 => __( '%s scheduled.', 'site-essentials' ), 10 => __( '%s draft updated.', 'site-essentials' ) );
  foreach ( $templates as $key => $text ) { $messages['post'][ $key ] = sprintf( $text, $single ); } return $messages;
 }
 /** Rename native bulk result messages without changing other content types.
  * @param array $messages Messages by post type.
  * @param array $counts Bulk action counts.
  * @return array
  */
 public function bulk_messages( array $messages, array $counts ): array {
  $names = $this->names(); if ( ! $names || ! isset( $messages['post'] ) ) { return $messages; }
  /* translators: %1$s: configured plural post label; %2$d: affected post count; %3$s: translated operation result. */
  foreach ( array( 'updated', 'locked', 'deleted', 'trashed', 'untrashed' ) as $key ) { $messages['post'][ $key ] = sprintf( __( '%1$s: %2$d (%3$s).', 'site-essentials' ), esc_html( $names[0] ), (int) ( $counts[ $key ] ?? 0 ), esc_html( array( 'updated' => __( 'updated', 'site-essentials' ), 'locked' => __( 'locked', 'site-essentials' ), 'deleted' => __( 'deleted', 'site-essentials' ), 'trashed' => __( 'moved to Trash', 'site-essentials' ), 'untrashed' => __( 'restored', 'site-essentials' ) )[ $key ] ) ); } return $messages;
 }
 /** Rename the core dashboard post count without translating unrelated plugin text.
  * @param string $translation Existing translation.
  * @param string $single Native singular source.
  * @param string $plural Native plural source.
  * @param int $number Count.
  * @param string $domain Text domain.
  * @return string
  */
 public function count_label( string $translation, string $single, string $plural, int $number, string $domain ): string {
  $names = $this->names(); return $names && 'default' === $domain && '%s Post' === $single && '%s Posts' === $plural ? '%s ' . $names[ 1 === $number ? 1 : 0 ] : $translation;
 }
}
