<?php
/** Local administrator-only website completeness checks. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Checks native visibility and legal-page presence without rendering content or legal assessment. */
final class WebsiteChecks {
 const OPTION = 'site_essentials_checks';
 /** Register visibility acknowledgement invalidation and protected admin actions. @return void */
 public function boot(): void {
  add_action( 'update_option_blog_public', array( $this, 'index_changed' ), 10, 2 );
  if ( is_admin() ) {
   add_action( 'admin_notices', array( $this, 'notice' ) );
   add_action( 'admin_post_site_essentials_check_action', array( $this, 'action' ) );
   add_action( 'admin_footer', array( $this, 'forms' ) );
  }
 }
 /** Read site-local page assignments and acknowledgements, never configuration exports. @return array */
 public static function state(): array {
  $value = get_option( self::OPTION, array() ); if ( ! is_array( $value ) ) { return array(); }
  $state = array();
  foreach ( array( 'epoch', 'index_ack' ) as $key ) { if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) && preg_match( '/^[a-f0-9-]{36}$/D', $value[ $key ] ) ) { $state[ $key ] = $value[ $key ]; } }
  if ( isset( $value['imprint_page'] ) && is_int( $value['imprint_page'] ) && $value['imprint_page'] >= 0 ) { $state['imprint_page'] = $value['imprint_page']; }
  foreach ( array( 'privacy', 'imprint' ) as $kind ) { if ( isset( $value['confirmed'][ $kind ] ) && is_string( $value['confirmed'][ $kind ] ) && preg_match( '/^[a-f0-9]{64}$/D', $value['confirmed'][ $kind ] ) ) { $state['confirmed'][ $kind ] = $value['confirmed'][ $kind ]; } }
  return $state;
 }
 /** Invalidate an indexing decision whenever the actual native setting changes.
  * @param mixed $old Previous option value.
  * @param mixed $value New option value.
  * @return void
  */
 public function index_changed( $old, $value ): void {
  if ( (string) $old === (string) $value ) { return; }
  $state = self::state(); unset( $state['index_ack'] ); $state['epoch'] = wp_generate_uuid4();
  update_option( self::OPTION, $state, false );
 }
 /** Detect a unique imprint by exact native title or slug; ambiguous matches require assignment. @return array<int,int> */
 private function imprint_candidates(): array {
  global $wpdb;
  return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM %i WHERE post_type = %s AND post_status NOT IN ('trash','auto-draft') AND (LOWER(post_title) IN (%s,%s) OR post_name IN (%s,%s)) ORDER BY ID LIMIT 3", $wpdb->posts, 'page', 'impressum', 'imprint', 'impressum', 'imprint' ) ) );
 }
 /** Inspect stored content only, with uncertain builder/dynamic content marked for review.
  * @param \WP_Post $page Selected page.
  * @return array{status:string,hash:string}
  */
 public function content( $page ): array {
  $meta = array();
  foreach ( array( '_bricks_page_content_2', '_breakdance_data', 'ct_builder_shortcodes', '_ct_builder_json', '_oxygen_data', '_elementor_data', '_elementor_edit_mode' ) as $key ) { $value = get_post_meta( $page->ID, $key, true ); if ( ! empty( $value ) && '[]' !== $value && '{}' !== $value ) { $meta[ $key ] = $value; } }
  $raw = $page->post_content;
  $hash = hash( 'sha256', wp_json_encode( array( $page->ID, $page->post_status, $page->post_password, $page->post_modified_gmt, $raw, $meta ) ) );
  // Stored builder data cannot establish rendered completeness without invoking third-party code.
  if ( $meta || preg_match( '/\[[a-zA-Z][^\]]*\]/', $raw ) ) { return array( 'status' => 'review', 'hash' => $hash ); }
  $text = html_entity_decode( wp_strip_all_tags( preg_replace( '/<!--.*?-->/s', '', $raw ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
  if ( preg_match( '/[^\s\x{00a0}\x{200b}\x{feff}]/u', $text ) || preg_match( '/<(?:img|video|audio|iframe|svg|object|embed)\b/i', $raw ) ) { return array( 'status' => 'present', 'hash' => $hash ); }
  foreach ( parse_blocks( $raw ) as $block ) {
   if ( ! empty( $block['blockName'] ) && ! in_array( $block['blockName'], array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/group', 'core/columns', 'core/column', 'core/spacer', 'core/separator' ), true ) ) { return array( 'status' => 'review', 'hash' => $hash ); }
   if ( ! empty( $block['innerBlocks'] ) && $this->uncertain_blocks( $block['innerBlocks'] ) ) { return array( 'status' => 'review', 'hash' => $hash ); }
  }
  return array( 'status' => 'empty', 'hash' => $hash );
 }
 /** Identify nested dynamic or unsupported blocks without executing them.
  * @param array $blocks Nested parsed blocks.
  * @return bool
  */
 private function uncertain_blocks( array $blocks ): bool {
  foreach ( $blocks as $block ) {
   if ( ! empty( $block['blockName'] ) && ! in_array( $block['blockName'], array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/group', 'core/columns', 'core/column', 'core/spacer', 'core/separator' ), true ) ) { return true; }
   if ( ! empty( $block['innerBlocks'] ) && $this->uncertain_blocks( $block['innerBlocks'] ) ) { return true; }
  }
  return false;
 }
 /** Resolve the actual page and return structural publication/content status.
  * @param string $kind privacy or imprint.
  * @return array
  */
 public function page_status( string $kind ): array {
  $state = self::state(); $id = 'privacy' === $kind ? (int) get_option( 'wp_page_for_privacy_policy' ) : (int) ( $state['imprint_page'] ?? 0 );
  $ambiguous = false;
  if ( 'imprint' === $kind && ! $id ) { $ids = $this->imprint_candidates(); $id = count( $ids ) === 1 ? $ids[0] : 0; $ambiguous = count( $ids ) > 1; }
  $page = $id ? get_post( $id ) : null; $status = $ambiguous ? 'ambiguous' : 'missing'; $hash = '';
  if ( $page && 'page' === $page->post_type ) {
   $content = $this->content( $page ); $hash = $content['hash'];
   $status = 'publish' !== $page->post_status || '' !== $page->post_password ? 'unpublished' : $content['status'];
   if ( 'review' === $status && isset( $state['confirmed'][ $kind ] ) && hash_equals( $hash, (string) $state['confirmed'][ $kind ] ) ) { $status = 'confirmed'; }
  }
  return array( 'kind' => $kind, 'id' => $id, 'status' => $status, 'hash' => $hash );
 }
 /** Derive current administrator checks without external requests or content execution. @return array */
 public function status(): array {
  $state = self::state();
  return array( 'indexing' => '0' === (string) get_option( 'blog_public' ) ? ( ! empty( $state['index_ack'] ) && $state['index_ack'] === ( $state['epoch'] ?? '' ) ? 'confirmed' : 'discouraged' ) : 'allowed', 'privacy' => $this->page_status( 'privacy' ), 'imprint' => $this->page_status( 'imprint' ) );
 }
 /** Bind actions to the actual current local state and content fingerprint.
  * @param array $status Current checks.
  * @return string
  */
 public function revision( array $status ): string { return hash( 'sha256', wp_json_encode( array( self::state(), get_option( 'blog_public' ), get_option( 'wp_page_for_privacy_policy' ), $status ) ) ); }
 /** Render one compact warning for authorized local administrators only. @return void */
 public function notice(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { return; }
  if ( isset( $_GET['se_check_done'] ) && '1' === $_GET['se_check_done'] ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Website check decision saved.', 'site-essentials' ) . '</p></div>'; }
  if ( ! Settings::get( 'check_indexing' ) && ! Settings::get( 'check_privacy' ) && ! Settings::get( 'check_imprint' ) ) { return; }
  $status = $this->status(); $issues = array();
  if ( Settings::get( 'check_indexing' ) && 'discouraged' === $status['indexing'] ) { $issues[] = 'indexing'; }
  foreach ( array( 'privacy', 'imprint' ) as $kind ) { if ( Settings::get( 'check_' . $kind ) && ! in_array( $status[ $kind ]['status'], array( 'present', 'confirmed' ), true ) ) { $issues[] = $kind; } }
  if ( ! $issues ) { return; }
  echo '<div class="notice notice-warning" id="se-website-check-notice"><p><strong>' . esc_html__( 'Site Essentials: website checks', 'site-essentials' ) . '</strong></p><ul>';
  foreach ( $issues as $kind ) { echo '<li>' . esc_html( $this->message( $kind, $status ) ) . '</li>'; }
  echo '</ul><p><a class="button" href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-checks' ) ) . '">' . esc_html__( 'Review website checks', 'site-essentials' ) . '</a></p>';
  if ( in_array( 'indexing', $issues, true ) ) { $this->action_form( 'keep_indexing', $this->revision( $status ), __( 'Intentionally discouraged: keep this setting', 'site-essentials' ) ); $this->action_form( 'enable_indexing', $this->revision( $status ), __( 'Allow search engine indexing', 'site-essentials' ), true ); }
  echo '</div>';
 }
 /** Explain structural status without claiming legal compliance.
  * @param string $kind Check identifier.
  * @param array $status Current checks.
  * @return string
  */
 public function message( string $kind, array $status ): string {
  if ( 'indexing' === $kind ) { return __( 'WordPress currently asks search engines not to index this site. This does not technically block access.', 'site-essentials' ); }
  $label = 'privacy' === $kind ? __( 'Privacy page', 'site-essentials' ) : __( 'Imprint page', 'site-essentials' );
  $messages = array( 'missing' => __( 'No valid page is assigned or uniquely detected.', 'site-essentials' ), 'ambiguous' => __( 'Several imprint pages match. Choose the intended page.', 'site-essentials' ), 'unpublished' => __( 'The selected page is not publicly published.', 'site-essentials' ), 'empty' => __( 'The selected page has no recognizable content.', 'site-essentials' ), 'review' => __( 'Builder, shortcode or dynamic content needs a manual content check.', 'site-essentials' ), 'present' => __( 'Published page with recognizable content.', 'site-essentials' ), 'confirmed' => __( 'Published page: content manually confirmed.', 'site-essentials' ) );
  return $label . ': ' . ( $messages[ $status[ $kind ]['status'] ] ?? '' );
 }
 /** Render protected notice forms outside native edit forms.
  * @param string $operation Decision identifier.
  * @param string $revision Expected state fingerprint.
  * @param string $label Submit label.
  * @param bool $confirmation Whether changing visibility requires a checkbox.
  * @return void
  */
 private function action_form( string $operation, string $revision, string $label, bool $confirmation = false ): void {
  echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; $this->hidden( $operation, $revision );
  if ( $confirmation ) { echo '<p><label><input type="checkbox" name="confirm_indexing" value="1" required> ' . esc_html__( 'I want to change the WordPress visibility setting to allow indexing.', 'site-essentials' ) . '</label></p>'; }
  echo '<p><button type="submit" class="button">' . esc_html( $label ) . '</button></p></form>';
 }
 /** Output POST fields bound to this site and operation.
  * @param string $operation Decision identifier.
  * @param string $revision Expected state fingerprint.
  * @return void
  */
 private function hidden( string $operation, string $revision ): void {
  echo '<input type="hidden" name="action" value="site_essentials_check_action"><input type="hidden" name="operation" value="' . esc_attr( $operation ) . '"><input type="hidden" name="check_revision" value="' . esc_attr( $revision ) . '">'; wp_nonce_field( 'site_essentials_checks_' . get_current_blog_id() . '_' . $operation );
 }
 /** Render a review card inside the main settings layout using detached form controls. @return void */
 public function panel(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { return; }
  $status = $this->status();
  echo '<div class="se-card"><h3>' . esc_html__( 'Review website checks', 'site-essentials' ) . '</h3><p>' . esc_html__( 'These checks verify visibility, publication and recognizable content. They do not assess legal text quality. Page assignments and confirmations stay on this site and are not exported.', 'site-essentials' ) . '</p>';
  echo '<p>' . esc_html__( 'Indexing decision', 'site-essentials' ) . ': ' . esc_html( 'confirmed' === $status['indexing'] ? __( 'Intentionally discouraged', 'site-essentials' ) : ( 'allowed' === $status['indexing'] ? __( 'Indexing allowed', 'site-essentials' ) : __( 'Decision needed', 'site-essentials' ) ) ) . '</p>';
  echo '<p class="se-control-row"><label for="se-imprint-page">' . esc_html__( 'Explicit imprint page', 'site-essentials' ) . '</label> ';
  $dropdown = wp_dropdown_pages( array( 'name' => 'imprint_page', 'id' => 'se-imprint-page', 'selected' => (int) ( self::state()['imprint_page'] ?? 0 ), 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'show_option_none' => __( 'Detect Impressum or Imprint automatically', 'site-essentials' ), 'option_none_value' => '0', 'echo' => 0 ) );
  echo str_replace( '<select ', '<select form="se-check-assignment-form" ', $dropdown );
  echo '</p><p><button type="submit" form="se-check-assignment-form" class="button">' . esc_html__( 'Save imprint assignment', 'site-essentials' ) . '</button></p>';
  foreach ( array( 'privacy', 'imprint' ) as $kind ) {
   echo '<p>' . esc_html( $this->message( $kind, $status ) ) . ' ';
   $id = $status[ $kind ]['id']; if ( $id && current_user_can( 'edit_post', $id ) ) { echo '<a href="' . esc_url( get_edit_post_link( $id, 'raw' ) ) . '">' . esc_html__( 'Edit page', 'site-essentials' ) . '</a> '; if ( 'publish' === get_post_status( $id ) ) { echo '· <a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View page', 'site-essentials' ) . '</a> '; } }
   if ( 'review' === $status[ $kind ]['status'] ) { echo '<button type="submit" form="se-check-confirm-' . esc_attr( $kind ) . '-form" class="button">' . esc_html__( 'Content checked: confirm it is present', 'site-essentials' ) . '</button>'; }
   echo '</p>';
  }
  echo '<p><a href="' . esc_url( admin_url( 'options-reading.php' ) ) . '">' . esc_html__( 'Open WordPress reading settings', 'site-essentials' ) . '</a> · <a href="' . esc_url( admin_url( 'options-privacy.php' ) ) . '">' . esc_html__( 'Assign the native WordPress privacy page', 'site-essentials' ) . '</a> · <a href="' . esc_url( admin_url( 'post-new.php?post_type=page' ) ) . '">' . esc_html__( 'Create a page', 'site-essentials' ) . '</a></p><p><button type="submit" form="se-check-reset-form" class="button">' . esc_html__( 'Reset website check confirmations', 'site-essentials' ) . '</button></p><p id="se-check-draft-message" role="alert" tabindex="-1"></p></div>';
  PublicChecks::panel();
  $this->visitor_tests();
 }
 /** Offer ordinary public requests without changing frontend routing or stored data.
  * Random test values make accidental matches unlikely; existing site behavior remains authoritative.
  * @return void
  */
 private function visitor_tests(): void {
  $token = 'site-essentials-test-' . wp_generate_uuid4();
  $missing = add_query_arg( 'name', $token, home_url( '/' ) );
  $search = add_query_arg( 's', $token, home_url( '/' ) );
  echo '<div class="se-card"><h3>' . esc_html__( 'Test visitor guidance', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Open a deliberately unusual address or search term in a new tab. These are ordinary website requests: no content, settings or templates are changed. A random value makes an existing match unlikely, but results depend on the website.', 'site-essentials' ) . '</p><p class="se-control-row">';
  echo '<a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $missing ) . '">' . esc_html__( 'Test 404 page (new tab)', 'site-essentials' ) . '</a> ';
  echo '<a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $search ) . '">' . esc_html__( 'Test search without results (new tab)', 'site-essentials' ) . '</a></p>';
  echo '<p>' . esc_html__( 'Check the message, navigation and a useful next step. Also test while signed out; maintenance, redirects, disabled search and caches can affect the result. These links do not automatically assess the HTTP status or template quality.', 'site-essentials' ) . '</p>';
  if ( 'bricks' === strtolower( (string) get_template() ) ) {
   echo '<p>' . esc_html__( 'With Bricks, review the Error Page and Search Results templates and their conditions in the builder.', 'site-essentials' ) . '</p>';
  } elseif ( wp_is_block_theme() ) {
   echo '<p>' . esc_html__( 'With a block theme, review the 404 and Search Results templates in the Site Editor, if your permissions and website settings allow access.', 'site-essentials' ) . '</p>';
  } else {
   echo '<p>' . esc_html__( 'With a classic theme, the theme or an active builder controls these views. An individual 404 or search template is not required for a working fallback.', 'site-essentials' ) . '</p>';
  }
  echo '</div>';
 }
 /** Output detached review forms only on the authorized local settings page. @return void */
 public function forms(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || 'site-essentials' !== ( $_GET['page'] ?? '' ) ) { return; }
  $revision = $this->revision( $this->status() );
  foreach ( array( 'assignment' => 'assign_imprint', 'confirm-privacy' => 'confirm_privacy', 'confirm-imprint' => 'confirm_imprint', 'reset' => 'reset' ) as $id => $operation ) { echo '<form id="se-check-' . esc_attr( $id ) . '-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; $this->hidden( $operation, $revision ); echo '</form>'; }
 }
 /** Validate and apply a local administrator decision without altering page content. @return void */
 public function action(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You cannot manage website checks.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Use the website check form.', 'site-essentials' ), '', array( 'response' => 405 ) ); }
  $operation = $_POST['operation'] ?? ''; if ( ! is_string( $operation ) || ! in_array( $operation, array( 'keep_indexing', 'enable_indexing', 'assign_imprint', 'confirm_privacy', 'confirm_imprint', 'reset' ), true ) ) { wp_die( esc_html__( 'Invalid website check action.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  if ( ! is_string( $_REQUEST['_wpnonce'] ?? null ) ) { wp_die( esc_html__( 'The request could not be verified. Reload the page and try again.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( 'site_essentials_checks_' . get_current_blog_id() . '_' . $operation );
  $status = $this->status(); $revision = $_POST['check_revision'] ?? '';
  if ( ! is_string( $revision ) || ! hash_equals( $this->revision( $status ), wp_unslash( $revision ) ) ) { wp_die( esc_html__( 'Website checks changed. Reload before deciding.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $state = self::state();
  if ( 'enable_indexing' === $operation ) {
   if ( 'discouraged' !== $status['indexing'] || '1' !== ( $_POST['confirm_indexing'] ?? '' ) ) { wp_die( esc_html__( 'Confirm the indexing change explicitly.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
   update_option( 'blog_public', 1 ); if ( '1' !== (string) get_option( 'blog_public' ) ) { $this->write_error(); }
  } else {
   if ( 'keep_indexing' === $operation ) { if ( 'discouraged' !== $status['indexing'] ) { wp_die( '', '', array( 'response' => 409 ) ); } $state['epoch'] = $state['epoch'] ?? wp_generate_uuid4(); $state['index_ack'] = $state['epoch']; }
   elseif ( 'reset' === $operation ) { unset( $state['index_ack'], $state['confirmed'] ); }
   elseif ( 'assign_imprint' === $operation ) {
    $raw = $_POST['imprint_page'] ?? ''; if ( ! is_string( $raw ) || ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $raw ) ) { wp_die( esc_html__( 'Choose a valid local page.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
    $id = (int) $raw; $page = $id ? get_post( $id ) : null;
    if ( $id && ( ! $page || 'page' !== $page->post_type || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( esc_html__( 'Choose a valid local page.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
    $state['imprint_page'] = $id; unset( $state['confirmed']['imprint'] );
   } else { $kind = 'confirm_privacy' === $operation ? 'privacy' : 'imprint'; if ( 'review' !== $status[ $kind ]['status'] || ! current_user_can( 'edit_post', $status[ $kind ]['id'] ) ) { wp_die( esc_html__( 'Only uncertain published page content can be confirmed.', 'site-essentials' ), '', array( 'response' => 400 ) ); } $state['confirmed'][ $kind ] = $status[ $kind ]['hash']; }
   if ( isset( $state['confirmed'] ) && ! $state['confirmed'] ) { unset( $state['confirmed'] ); }
   update_option( self::OPTION, $state, false ); if ( self::state() != $state ) { $this->write_error(); }
  }
  wp_safe_redirect( admin_url( 'options-general.php?page=site-essentials&se_check_done=1#se-checks' ) ); exit;
 }
 /** Report failed writes without claiming a successful decision. @return never */
 private function write_error(): never { wp_die( esc_html__( 'The website check decision could not be saved.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
}
