<?php
/** Settings screens and protected actions. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Presents accessible grouped controls and validates every mutation. */
final class Admin {
 /** Register site and network screens and protected POST handlers. @return void */
 public function boot(): void {
  add_action( 'admin_menu', array( $this, 'menu' ) );
  add_action( 'network_admin_menu', array( $this, 'network_menu' ) );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_action( 'wp_ajax_site_essentials_access_search', array( $this, 'access_users_search' ) );
  add_action( 'admin_post_site_essentials_save', array( $this, 'save' ) );
  add_action( 'admin_post_site_essentials_restore', array( $this, 'restore' ) );
  add_action( 'admin_post_site_essentials_export', array( $this, 'export' ) );
  add_action( 'admin_post_site_essentials_preset_create', array( $this, 'preset_create' ) );
  add_action( 'admin_post_site_essentials_preset_edit', array( $this, 'preset_edit' ) );
  add_action( 'admin_post_site_essentials_preset_remove', array( $this, 'preset_remove' ) );
  add_action( 'wp_ajax_site_essentials_collection_review', array( $this, 'collection_review' ) );
  add_action( 'admin_post_site_essentials_collection_export', array( $this, 'collection_export' ) );
  add_action( 'admin_post_site_essentials_collection_import', array( $this, 'collection_import' ) );
  add_action( 'admin_post_site_essentials_preset_export', array( $this, 'preset_export' ) );
  add_action( 'wp_ajax_site_essentials_diagnostic', array( $this, 'diagnostic' ) );
  add_action( 'admin_post_site_essentials_clear_local', array( $this, 'clear_local' ) );
  add_action( 'admin_post_site_essentials_clear_network', array( $this, 'clear_network' ) );
  add_action( 'admin_post_site_essentials_network', array( $this, 'network_save' ) );
  add_filter( 'plugin_action_links_' . plugin_basename( SITE_ESSENTIALS_FILE ), array( $this, 'links' ) );
  add_filter( 'network_admin_plugin_action_links_' . plugin_basename( SITE_ESSENTIALS_FILE ), array( $this, 'links' ) );
 }
 /** Add the local settings screen. @return void */
 public function menu(): void { add_options_page( 'Site Essentials', 'Site Essentials', 'manage_options', 'site-essentials', array( $this, 'page' ) ); }
 /** Add only the explicitly supported network policy screen. @return void */
 public function network_menu(): void {
  if ( Settings::network_active() ) { add_submenu_page( 'settings.php', 'Site Essentials', 'Site Essentials', 'manage_network_options', 'site-essentials', array( $this, 'network_page' ) ); }
 }
 /** Place the settings link before deactivation, honoring the current scope.
  * @param array $links Existing action links.
  * @return array
  */
 public function links( array $links ): array {
  $network = is_network_admin();
  if ( $network && ! Settings::network_active() ) { return $links; }
  if ( ! current_user_can( $network ? 'manage_network_options' : 'manage_options' ) ) { return $links; }
  $url = $network ? network_admin_url( 'settings.php?page=site-essentials' ) : admin_url( 'options-general.php?page=site-essentials' );
  return array( 'site-essentials-settings' => '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'site-essentials' ) . '</a>' ) + $links;
 }
 /** Enqueue assets on this plugin's own pages only.
  * @param string $hook Admin hook suffix.
  * @return void
  */
 public function assets( string $hook ): void {
  if ( ! in_array( $hook, array( 'settings_page_site-essentials', 'settings_page_site-essentials-network' ), true ) ) { return; }
  wp_enqueue_style( 'site-essentials-admin', plugins_url( 'assets/admin.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION );
  wp_enqueue_script( 'site-essentials-admin', plugins_url( 'assets/admin.js', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION, true );
  if ( 'settings_page_site-essentials' === $hook ) {
   wp_enqueue_script( 'site-essentials-access', plugins_url( 'assets/access.js', SITE_ESSENTIALS_FILE ), array( 'site-essentials-admin' ), SITE_ESSENTIALS_VERSION, true );
   wp_localize_script( 'site-essentials-access', 'siteEssentialsAccess', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( self::nonce_action( 'site_essentials_access_search' ) ), 'searching' => __( 'Searching site users…', 'site-essentials' ), 'empty' => __( 'No matching site users found.', 'site-essentials' ), 'failed' => __( 'User lookup failed. Try again.', 'site-essentials' ), 'short' => __( 'Enter at least two characters.', 'site-essentials' ) ) );
   wp_enqueue_script( 'site-essentials-checks', plugins_url( 'assets/checks.js', SITE_ESSENTIALS_FILE ), array( 'site-essentials-admin' ), SITE_ESSENTIALS_VERSION, true );
   wp_localize_script( 'site-essentials-checks', 'siteEssentialsChecks', array( 'draft' => __( 'Save or discard the settings draft before changing website check decisions.', 'site-essentials' ) ) );
   wp_enqueue_script( 'site-essentials-collections', plugins_url( 'assets/collections.js', SITE_ESSENTIALS_FILE ), array( 'site-essentials-admin' ), SITE_ESSENTIALS_VERSION, true );
   wp_localize_script( 'site-essentials-collections', 'siteEssentialsCollections', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( self::nonce_action( 'site_essentials_collection_review' ) ), 'maxBytes' => Collections::MAX_BYTES, 'strings' => array(
    'error' => __( 'The collection could not be reviewed. Check the file and try again.', 'site-essentials' ),
    'limit' => __( 'Each collection can contain up to 20 profiles and 256 KiB.', 'site-essentials' ),
    'draft' => __( 'Finish or clear the collection draft before saving other changes.', 'site-essentials' ),
    'collision' => __( 'Each selected copy needs a unique name. Existing presets are never overwritten.', 'site-essentials' ),
    'capacity' => __( 'Selected: %1$d. Available local slots: %2$d.', 'site-essentials' ),
    'renamed' => __( 'Name already used; a distinct copy name is suggested.', 'site-essentials' ),
    'name' => __( 'Name for the imported copy', 'site-essentials' ),
    'rules' => __( 'Review saved preset values', 'site-essentials' ),
    'choose' => __( 'Select at least one custom preset to export.', 'site-essentials' ),
   ) ) );
  }
  wp_localize_script( 'site-essentials-admin', 'siteEssentials', array(
   'profileStorageKey' => 'site-essentials-profile-' . get_current_blog_id() . '-' . wp_create_nonce( 'site_essentials_profile_draft' ), 'previous' => null === Settings::previous() ? null : Settings::portable( Settings::previous() ), 'reportUrl' => admin_url( 'admin-ajax.php' ), 'reportNonce' => wp_create_nonce( self::nonce_action( 'site_essentials_diagnostic' ) ), 'schema' => Schema::fields(), 'multisite' => is_multisite(), 'availableTypes' => array_values( get_post_types() ), 'groups' => Schema::groups(), 'defaults' => Settings::portable( Schema::defaults() ), 'presets' => Presets::portable(), 'local' => Settings::portable( Settings::local() ), 'effective' => Settings::portable( Settings::effective() ), 'availableTargetTypes' => array_values( array_diff( get_post_types( array( 'public' => true, 'show_ui' => true ) ), array( 'attachment', 'revision', 'nav_menu_item' ) ) ), 'targetProvider' => AlternativeTargets::provider(), 'networkLocked' => Settings::language_locked(),
   'strings' => array(
    'ruleTargetOff' => __( 'Alternative destination processing is disabled or has no public content types.', 'site-essentials' ), 'ruleTargetProvider' => __( 'Page Links To is active; processing is paused.', 'site-essentials' ), 'ruleActive' => __( 'Active', 'site-essentials' ), 'ruleOff' => __( 'Off or neutral', 'site-essentials' ), 'ruleDormant' => __( 'Stored; not applied in this context', 'site-essentials' ), 'ruleNetwork' => __( 'Network policy', 'site-essentials' ), 'ruleLocal' => __( 'Local setting', 'site-essentials' ), 'ruleSingle' => __( 'Single-site only', 'site-essentials' ), 'ruleComments' => __( 'Overridden by complete comment shutdown', 'site-essentials' ), 'ruleNames' => __( 'Requires enabled renaming and both names', 'site-essentials' ), 'ruleTypes' => __( 'Selected content types are currently unavailable', 'site-essentials' ), 'ruleFooter' => __( 'Custom footer text takes priority', 'site-essentials' ), 'ruleCommentTypes' => __( 'Requires selected comment content types', 'site-essentials' ), 'ruleBlog' => __( 'Native post management is disabled', 'site-essentials' ), 'ruleCounts' => __( '%1$d active · %2$d stored but not applied · %3$d unsaved changes', 'site-essentials' ), 'ruleMatches' => __( '%d matching settings', 'site-essentials' ), 'ruleSaved' => __( 'Saved', 'site-essentials' ), 'ruleDraft' => __( 'Draft', 'site-essentials' ), 'ruleJump' => __( 'Open setting', 'site-essentials' ),
    'accessModes' => array( __( 'Keep WordPress permissions', 'site-essentials' ), __( 'Block everyone', 'site-essentials' ), __( 'Choose allowed roles and users', 'site-essentials' ) ),
    'invalid' => __( 'Use a valid Site Essentials JSON configuration.', 'site-essentials' ),
    'large' => __( 'The configuration is too large.', 'site-essentials' ),
    'unsaved' => __( 'Unsaved changes', 'site-essentials' ), 'saved' => __( 'Saved configuration', 'site-essentials' ),
    'none' => __( 'No changes to apply.', 'site-essentials' ), 'changed' => __( 'Selected changes are ready. Save to apply them.', 'site-essentials' ),
    'before' => __( 'Current', 'site-essentials' ), 'after' => __( 'Proposed', 'site-essentials' ),
    'replaceProfile' => __( 'Replace the unsaved new-preset name and note with the recovered details?', 'site-essentials' ),
    'discardRecovery' => __( 'Discard the recovered preset details?', 'site-essentials' ),
    'discardEdit' => __( 'Discard unsaved name and note edits for this preset?', 'site-essentials' ),
    'draftDiscarded' => __( 'Draft discarded. Saved settings are unchanged.', 'site-essentials' ), 'profileUnsaved' => __( 'Profile details are not saved as a preset yet.', 'site-essentials' ), 'profileStorageError' => __( 'This tab cannot retain profile details. Copy or discard them before saving settings.', 'site-essentials' ), 'draftFirst' => __( 'Save or discard unsaved settings and profile details before this action.', 'site-essentials' ),
    'profileName' => __( 'Preset name', 'site-essentials' ), 'profileNote' => __( 'Configuration note', 'site-essentials' ),
    'copySuccess' => __( 'Report copied.', 'site-essentials' ), 'reportError' => __( 'The report could not be loaded. Reload the page and try again.', 'site-essentials' ), 'copyFallback' => __( 'Select and copy the report text manually.', 'site-essentials' ),
    'searchNone' => __( 'No settings match this search.', 'site-essentials' ),
    'presetSaved' => __( 'Saved site settings only; save your draft first.', 'site-essentials' ),
    'removePreset' => __( 'Remove this preset? Site settings will stay unchanged.', 'site-essentials' ),
    'network' => __( 'Network policy', 'site-essentials' ), 'localSource' => __( 'Local setting', 'site-essentials' ),
    'noDifferences' => __( 'Saved local settings match this preset.', 'site-essentials' ),
    'differences' => __( '%d local settings differ from this preset.', 'site-essentials' ),
    'true' => __( 'Enabled', 'site-essentials' ), 'false' => __( 'Disabled', 'site-essentials' ),
   ),
  ) );
 }
 /** Bind an action token to its current site or network, independently of capabilities.
  * @param string $action Stable operation name.
  * @param bool $network Whether the operation manages network policy.
  * @return string Scoped WordPress nonce action.
  */
 public static function nonce_action( string $action, bool $network = false ): string {
  return $action . ( $network ? '_network_' . get_current_network_id() : '_site_' . get_current_blog_id() );
 }
 /** Check authorization and CSRF for all action endpoints.
  * @param string $action Nonce action.
  * @param bool $network Require network authorization.
  * @return void
  */
 private function authorize( string $action, bool $network = false ): void {
  if ( ! current_user_can( $network ? 'manage_network_options' : 'manage_options' ) ) { wp_die( esc_html__( 'You do not have permission to change these settings.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Use the settings form to perform this action.', 'site-essentials' ), '', array( 'response' => 405 ) ); }
  if ( ! is_string( $_REQUEST['_wpnonce'] ?? null ) ) { wp_die( esc_html__( 'The request could not be verified. Reload the page and try again.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  check_admin_referer( self::nonce_action( $action, $network ) );
 }
 /** Reject stale browser forms before replacing settings changed elsewhere. @return void */
 private function check_revision(): void {
  $revision = isset( $_POST['revision'] ) && is_string( $_POST['revision'] ) ? wp_unslash( $_POST['revision'] ) : '';
  if ( ! hash_equals( hash( 'sha256', wp_json_encode( Settings::local() ) ), $revision ) ) { wp_die( esc_html__( 'Settings changed in another session. Reload this page before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
 }
 /** Validate the submitted form and save only known local settings. @return void */
 public function save(): void {
  $this->authorize( 'site_essentials_save' ); $this->check_revision();
  if ( isset( $_POST['exceptions_revision'] ) && '1' !== ( $_POST['se_form_complete'] ?? null ) ) { wp_die( esc_html__( 'The settings form was incomplete. Increase the input limit or reduce the selected entries, then retry.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  $input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
  foreach ( array( 'widgets', 'customizer' ) as $area ) { if ( isset( $input[ 'access_' . $area . '_mode' ] ) && is_scalar( $input[ 'access_' . $area . '_mode' ] ) ) { $input[ $area . '_admin' ] = '1' === (string) $input[ 'access_' . $area . '_mode' ]; } }
  if ( isset( $_POST['exceptions'] ) && ! is_array( $_POST['exceptions'] ) ) { wp_die( esc_html__( 'Use valid personal access exceptions.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  $exceptions = isset( $_POST['exceptions'] ) && is_array( $_POST['exceptions'] ) ? wp_unslash( $_POST['exceptions'] ) : null;
  $old_personal = get_option( Access::OPTION, false );
  $personal = Access::exceptions();
  if ( null !== $exceptions ) {
   $revision = $_POST['exceptions_revision'] ?? '';
   if ( ! is_string( $revision ) || ! hash_equals( Access::revision(), wp_unslash( $revision ) ) ) { wp_die( esc_html__( 'Personal exceptions changed elsewhere. Reload before saving.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
   if ( array_diff( array_keys( $exceptions ), array( 'widgets', 'customizer', 'menus' ) ) ) { wp_die( esc_html__( 'Use valid personal access exceptions.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
   foreach ( array( 'widgets', 'customizer', 'menus' ) as $area ) {
    if ( ! is_array( $exceptions[ $area ] ?? null ) ) { wp_die( esc_html__( 'Use valid personal access exceptions.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
    $personal[ $area ] = Access::validate_users( $exceptions[ $area ] );
    if ( is_wp_error( $personal[ $area ] ) ) { wp_die( esc_html( $personal[ $area ]->get_error_message() ), '', array( 'response' => 400 ) ); }
   }
  }
  $clean = Settings::validate( $input );
  if ( is_wp_error( $clean ) ) { wp_die( esc_html( $clean->get_error_message() ), '', array( 'response' => 400 ) ); }
  $values = array_replace( Schema::defaults(), $clean );
  if ( Settings::language_locked() ) { $values['login_language'] = Settings::local()['login_language']; }
  $old_settings = get_option( Settings::OPTION, false ); $old_previous = get_option( Settings::PREVIOUS, false );
  if ( ! Settings::save( $values ) ) { wp_die( esc_html__( 'Settings could not be saved. Your previous configuration is retained.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  if ( null !== $exceptions ) {
   update_option( Access::OPTION, $personal, false );
   if ( get_option( Access::OPTION ) !== $personal ) {
    foreach ( array( Settings::OPTION => $old_settings, Settings::PREVIOUS => $old_previous, Access::OPTION => $old_personal ) as $option => $old ) { false === $old ? delete_option( $option ) : update_option( $option, $old, false ); }
    wp_die( esc_html__( 'Personal exceptions could not be saved. Reload to verify the retained configuration.', 'site-essentials' ), '', array( 'response' => 500 ) );
   }
  }
  $this->redirect( 'saved' );
 }
 /** Restore only local plugin settings after authorization. @return void */
 public function restore(): void {
  $this->authorize( 'site_essentials_restore' ); $this->check_revision();
  $snapshot = isset( $_POST['snapshot_revision'] ) && is_string( $_POST['snapshot_revision'] ) ? wp_unslash( $_POST['snapshot_revision'] ) : '';
  if ( ! hash_equals( hash( 'sha256', wp_json_encode( get_option( Settings::PREVIOUS, false ) ) ), $snapshot ) ) { wp_die( esc_html__( 'The previous configuration changed. Reload before restoring it.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  if ( null === Settings::previous() ) { $this->redirect( 'no_snapshot' ); }
  $this->redirect( Settings::restore() ? 'restored' : 'restore_failed' );
 }
 /** Download local settings as portable JSON. @return void */
 public function export(): void {
  $this->authorize( 'site_essentials_export' );
  nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' );
  header( 'Content-Disposition: attachment; filename="site-essentials-settings.json"' );
  echo wp_json_encode( Settings::export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); exit;
 }
 /** Change the network login policy without touching any local settings. @return void */
 public function network_save(): void {
  $this->authorize( 'site_essentials_network', true );
  if ( ! Settings::network_active() ) { wp_die( esc_html__( 'Network activation is required for network policies.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $mode = isset( $_POST['language_policy'] ) && is_string( $_POST['language_policy'] ) ? wp_unslash( $_POST['language_policy'] ) : '';
  if ( ! in_array( $mode, array( 'inherit', 'hide' ), true ) ) { wp_die( esc_html__( 'Invalid network policy.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
  $revision = $_POST['policy_revision'] ?? '';
  if ( ! is_string( $revision ) || ! hash_equals( hash( 'sha256', wp_json_encode( get_site_option( Settings::NETWORK, 'inherit' ) ) ), wp_unslash( $revision ) ) ) { wp_die( esc_html__( 'Network policy changed. Reload before changing it.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  update_site_option( Settings::NETWORK, $mode );
  if ( get_site_option( Settings::NETWORK ) !== $mode ) { wp_die( esc_html__( 'Network policy could not be saved.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  $this->redirect( 'saved', true );
 }
 /** Redirect after a mutation to avoid duplicate submissions/notices.
  * @param string $result Allowlisted notice code.
  * @param bool $network Return to Network Admin.
  * @return void
  */
 private function redirect( string $result, bool $network = false ): void {
  $url = $network ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
  $args = array( 'page' => 'site-essentials', 'se_result' => $result );
  // This receipt binds tab-draft acknowledgement to a successful POST; it grants no permission.
  $receipt = isset( $_POST['draft_receipt'] ) && is_string( $_POST['draft_receipt'] ) ? wp_unslash( $_POST['draft_receipt'] ) : '';
  if ( in_array( $result, array( 'preset_saved', 'preset_edited' ), true ) && preg_match( '/^[a-f0-9]{32}$/D', $receipt ) ) { $args['se_receipt'] = $receipt; }
  wp_safe_redirect( add_query_arg( $args, $url ) ); exit;
 }
 /** Render the header and exactly one plugin action result. @return void */
 private function header(): void {
  echo '<header class="se-header"><img class="se-brand-icon" src="' . esc_url( plugins_url( 'assets/images/icon.svg', SITE_ESSENTIALS_FILE ) ) . '" width="56" height="56" alt="" aria-hidden="true"><div><h1>Site Essentials <small>' . esc_html( SITE_ESSENTIALS_VERSION ) . '</small></h1><p>' . esc_html__( 'Your WordPress. Your settings.', 'site-essentials' ) . '</p></div></header><hr class="wp-header-end">';
  $code = isset( $_GET['se_result'] ) && is_string( $_GET['se_result'] ) ? sanitize_key( wp_unslash( $_GET['se_result'] ) ) : '';
  $messages = array( 'saved' => __( 'Settings saved.', 'site-essentials' ), 'restore_failed' => __( 'The previous configuration could not be restored. Reload to check the saved state.', 'site-essentials' ), 'restored' => __( 'Previous configuration restored.', 'site-essentials' ), 'no_snapshot' => __( 'No previous configuration is available.', 'site-essentials' ), 'preset_saved' => __( 'Custom preset saved. Site settings are unchanged.', 'site-essentials' ), 'preset_edited' => __( 'Preset name and note updated. Site settings are unchanged.', 'site-essentials' ), 'collection_imported' => __( 'Selected preset copies imported. Active settings are unchanged.', 'site-essentials' ), 'preset_removed' => __( 'Custom preset removed. Site settings are unchanged.', 'site-essentials' ), 'local_cleared' => __( 'Local data cleared. WordPress defaults are active; network policy is unchanged.', 'site-essentials' ), 'access_saved' => __( 'Personal access exceptions saved. Portable role settings are unchanged.', 'site-essentials' ), 'network_cleared' => __( 'Network policy cleared. Each site keeps its local configuration.', 'site-essentials' ) );
  if ( isset( $messages[ $code ] ) ) { echo '<div class="notice inline notice-' . ( 'restore_failed' === $code ? 'error' : ( 'no_snapshot' === $code ? 'warning' : 'success' ) ) . '"><p>' . esc_html( $messages[ $code ] ) . '</p></div>'; }
 }
 /** Render all local controls in one progressively enhanced form. @return void */
 public function page(): void {
  if ( ! current_user_can( 'manage_options' ) ) { return; }
  $local = Settings::local(); $groups = Schema::groups();
  echo '<div class="wrap se-root">'; $this->header();
  echo '<form id="se-settings" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
  wp_nonce_field( self::nonce_action( 'site_essentials_save' ) );
  echo '<input type="hidden" name="exceptions_revision" value="' . esc_attr( Access::revision() ) . '"><input type="hidden" name="action" value="site_essentials_save"><input type="hidden" name="revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( $local ) ) ) . '">';
  echo '<div class="se-layout"><nav class="se-nav" aria-label="' . esc_attr__( 'Settings sections', 'site-essentials' ) . '">';
  echo '<label for="se-search">' . esc_html__( 'Search settings', 'site-essentials' ) . '</label><input type="search" id="se-search" autocomplete="off" placeholder="' . esc_attr__( 'Search core settings…', 'site-essentials' ) . '"><p id="se-search-status" role="status"></p>';
  echo '<label for="se-rule-filter">' . esc_html__( 'Show settings', 'site-essentials' ) . '</label><select id="se-rule-filter"><option value="all">' . esc_html__( 'All settings', 'site-essentials' ) . '</option><option value="active">' . esc_html__( 'Active settings', 'site-essentials' ) . '</option><option value="different">' . esc_html__( 'Draft differs from reference', 'site-essentials' ) . '</option><option value="saved-different">' . esc_html__( 'Saved values differ from reference', 'site-essentials' ) . '</option><option value="pending">' . esc_html__( 'Unsaved setting changes', 'site-essentials' ) . '</option><option value="dormant">' . esc_html__( 'Stored but not applied', 'site-essentials' ) . '</option></select><label for="se-rule-reference">' . esc_html__( 'Reference profile', 'site-essentials' ) . '</label><select id="se-rule-reference"><option value="">' . esc_html__( 'Plugin defaults', 'site-essentials' ) . '</option>';
  foreach ( Presets::catalog() as $id => $preset ) { echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $preset['label'] ) . '</option>'; }
  echo '</select><p>' . esc_html__( 'Filters and references only change this view.', 'site-essentials' ) . '</p>';
  foreach ( $groups as $id => $label ) { echo '<a href="#se-' . esc_attr( $id ) . '" data-section="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</a>'; }
  echo '</nav><main class="se-main">';
  foreach ( $groups as $group => $title ) {
   echo '<section id="se-' . esc_attr( $group ) . '" class="se-panel" aria-labelledby="se-title-' . esc_attr( $group ) . '"><h2 id="se-title-' . esc_attr( $group ) . '" tabindex="-1">' . esc_html( $title ) . '</h2>';
   if ( 'overview' === $group ) { $this->overview(); }
   elseif ( 'reports' === $group ) { $this->reports(); }
   elseif ( 'avatars' === $group ) { foreach ( Schema::fields() as $key => $field ) { if ( 'avatars' === $field['group'] ) { $this->field( $key, $field, $local[ $key ] ); } } echo '<p><a href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html__( 'Assign an avatar in my profile', 'site-essentials' ) . '</a></p>'; }
   elseif ( 'data' === $group ) { $this->data_controls(); echo '<p>' . esc_html__( 'Content destinations remain stored when site configuration is cleared; change them in the content editor.', 'site-essentials' ) . '</p>'; }
   elseif ( 'collections' === $group ) { $this->collections(); }
   elseif ( 'transfer' === $group ) { $this->transfer(); }
   else { foreach ( Schema::fields() as $key => $field ) {
    if ( $field['group'] !== $group || in_array( $key, array( 'post_plural', 'post_singular', 'heartbeat_admin', 'heartbeat_editor', 'heartbeat_types', 'revision_types', 'classic_widgets', 'core_widgets', 'access_widgets_mode', 'access_widgets_roles', 'access_customizer_roles', 'access_menus_roles', 'menu_bricks', 'target_types' ), true ) ) { continue; }
    if ( 'targets' === $key ) { $this->grouped_fields( 'targets', __( 'Alternative post destinations', 'site-essentials' ), array( 'targets', 'target_types' ), $local ); }
    elseif ( 'post_rename' === $key ) {
     echo '<div class="se-card se-rename-group">'; $this->field( $key, $field, $local[ $key ], true ); echo '<div id="se-post-names">';
     foreach ( array( 'post_plural', 'post_singular' ) as $name_key ) { $this->field( $name_key, Schema::fields()[ $name_key ], $local[ $name_key ], true ); }
     echo '</div></div>';
    } elseif ( 'widgets_admin' === $key ) { $this->grouped_fields( 'widgets', __( 'Widgets', 'site-essentials' ), array( 'widgets_admin', 'access_widgets_mode', 'access_widgets_roles', 'classic_widgets', 'core_widgets' ), $local ); }
    elseif ( 'access_customizer_mode' === $key ) { $this->grouped_fields( 'customizer', __( 'Customizer', 'site-essentials' ), array( 'access_customizer_mode', 'access_customizer_roles' ), $local ); }
    elseif ( 'access_menus_mode' === $key ) { $this->grouped_fields( 'menus', __( 'Classic menu editing', 'site-essentials' ), array( 'access_menus_mode', 'access_menus_roles' ), $local ); }
    elseif ( 'menu_fields' === $key ) { $this->grouped_fields( 'menu_interface', __( 'Classic menu editor: interface', 'site-essentials' ), array( 'menu_fields', 'menu_bricks' ), $local ); }
    elseif ( 'heartbeat_front' === $key ) { $this->grouped_fields( 'heartbeat', __( 'Heartbeat API', 'site-essentials' ), array( 'heartbeat_front', 'heartbeat_admin', 'heartbeat_editor', 'heartbeat_types' ), $local ); }
    elseif ( 'revisions' === $key ) { $this->grouped_fields( 'revisions', __( 'Revisions', 'site-essentials' ), array( 'revisions', 'revision_types' ), $local ); }
    else { $this->field( $key, $field, $local[ $key ] ); }
   } }
   if ( 'targets' === $group ) { if ( AlternativeTargets::provider() ) { echo '<p role="status">' . esc_html__( 'Page Links To is active; processing is paused.', 'site-essentials' ) . '</p>'; } echo '<div class="se-card"><p>' . esc_html__( 'Review and copy existing Page Links To destinations in a separate administrator-only preview. Nothing is selected or copied automatically.', 'site-essentials' ) . '</p><a class="button" href="' . esc_url( admin_url( 'tools.php?page=site-essentials-targets' ) ) . '">' . esc_html__( 'Review Page Links To destinations', 'site-essentials' ) . '</a></div>'; }
   if ( 'checks' === $group ) { ( new WebsiteChecks() )->panel(); }
   echo '</section>';
  }
  echo '</main></div><div class="se-save"><button type="button" class="button" id="se-review-draft">' . esc_html__( 'Review unsaved changes', 'site-essentials' ) . '</button><button class="button button-primary" type="submit">' . esc_html__( 'Save all settings', 'site-essentials' ) . '</button><button type="button" class="button" id="se-discard-draft">' . esc_html__( 'Discard draft', 'site-essentials' ) . '</button><span id="se-status" role="status">' . esc_html__( 'Saved configuration', 'site-essentials' ) . '</span></div><input type="hidden" name="se_form_complete" value="1"></form>';
  echo '<div class="se-secondary">';
  echo '<button type="button" class="button" id="se-restore-preview"' . ( null === Settings::previous() ? ' disabled' : '' ) . '>' . esc_html__( 'Review previous configuration', 'site-essentials' ) . '</button><p id="se-restore-draft-note" role="status" tabindex="-1"></p>';
  $this->action_form( 'restore', __( 'Restore previous configuration', 'site-essentials' ), true );
  echo '<p>' . esc_html__( 'Personal access exceptions stay on this site and are excluded from every settings, preset and collection export.', 'site-essentials' ) . '</p>';
  $this->action_form( 'export', __( 'Export saved configuration', 'site-essentials' ) );
  echo '</div>'; $this->collection_forms(); $this->preset_forms(); $this->clear_form( false ); $this->footer(); echo '</div>';
 }
 /** Render preset selection without an automatic apply action. @return void */
 private function overview(): void {
  echo '<div class="se-card" id="se-rule-overview"><h3>' . esc_html__( 'Rule overview', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Current draft values with effective network policy and known dependencies. Active includes default reminders. This is configuration context, not a runtime or compatibility test.', 'site-essentials' ) . '</p><p id="se-rule-summary" role="status"></p><details id="se-rule-details"><summary>' . esc_html__( 'Rule details and setting shortcuts', 'site-essentials' ) . '</summary><div class="se-status-scroll"><table class="widefat striped se-status-table"><thead><tr>';
  foreach ( array( __( 'Setting', 'site-essentials' ), __( 'Group', 'site-essentials' ), __( 'Saved local value', 'site-essentials' ), __( 'Draft local value', 'site-essentials' ), __( 'Effective value', 'site-essentials' ), __( 'Reference value', 'site-essentials' ), __( 'State and source', 'site-essentials' ), __( 'Editing state', 'site-essentials' ) ) as $label ) { echo '<th scope="col">' . esc_html( $label ) . '</th>'; }
  echo '</tr></thead><tbody id="se-rule-rows"></tbody></table></div></details><noscript><p>' . esc_html__( 'Interactive rule filters require JavaScript. All settings remain available without JavaScript.', 'site-essentials' ) . '</p></noscript></div>';
  echo '<div class="se-card"><h3>' . esc_html__( 'Choose a starting point', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Presets prepare a draft. Review the changes, then save. All controls remain individually adjustable.', 'site-essentials' ) . '</p><label for="se-preset">' . esc_html__( 'Preset', 'site-essentials' ) . '</label> <select id="se-preset">';
  foreach ( Presets::catalog() as $id => $preset ) { echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $preset['label'] ) . '</option>'; }
  echo '</select> <button type="button" class="button" id="se-preset-preview">' . esc_html__( 'Review preset', 'site-essentials' ) . '</button><p>' . esc_html__( 'Applying a preset replaces the draft with that preset. You can select which changes to take over in the preview.', 'site-essentials' ) . '</p><p id="se-preset-note" class="description"></p></div>';
  echo '<div class="se-card"><h3>' . esc_html__( 'Scope', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Settings apply to this site. Activation alone does not enable cleanup. Deactivation preserves your configuration.', 'site-essentials' ) . '</p><p>' . esc_html__( 'Heartbeat stays enabled. Presets do not disable updates, REST access or password reset emails.', 'site-essentials' ) . '</p></div>';
  $this->preset_manager();
 }
 /** Reject preset management forms based on a changed store. @return void */
 private function check_presets_revision(): void {
  $revision = isset( $_POST['presets_revision'] ) && is_string( $_POST['presets_revision'] ) ? wp_unslash( $_POST['presets_revision'] ) : '';
  if ( ! hash_equals( hash( 'sha256', wp_json_encode( Presets::custom() ) ), $revision ) ) { wp_die( esc_html__( 'Presets changed in another session. Reload this page before continuing.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
 }
 /** Save a named snapshot of saved site settings, never an unsubmitted draft. @return void */
 public function preset_create(): void {
  $this->authorize( 'site_essentials_preset_create' ); $this->check_revision(); $this->check_presets_revision();
  $name = isset( $_POST['preset_name'] ) && is_string( $_POST['preset_name'] ) ? wp_unslash( $_POST['preset_name'] ) : '';
  $description = isset( $_POST['preset_description'] ) && is_string( $_POST['preset_description'] ) ? wp_unslash( $_POST['preset_description'] ) : '';
  $result = Presets::create( $name, $description );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) ); }
  $this->redirect( 'preset_saved' );
 }
 /** Edit only custom preset metadata with capability, nonce and stale-store protection. @return void */
 public function preset_edit(): void {
  $this->authorize( 'site_essentials_preset_edit' ); $this->check_presets_revision();
  $id = isset( $_POST['preset_id'] ) && is_string( $_POST['preset_id'] ) ? wp_unslash( $_POST['preset_id'] ) : '';
  $name = isset( $_POST['preset_name'] ) && is_string( $_POST['preset_name'] ) ? wp_unslash( $_POST['preset_name'] ) : '';
  $note = isset( $_POST['preset_description'] ) && is_string( $_POST['preset_description'] ) ? wp_unslash( $_POST['preset_description'] ) : '';
  $result = Presets::update_metadata( $id, $name, $note );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) ); }
  $this->redirect( 'preset_edited' );
 }
 /** Remove only a selected custom preset after authorization. @return void */
 public function preset_remove(): void {
  $this->authorize( 'site_essentials_preset_remove' ); $this->check_presets_revision();
  $id = isset( $_POST['preset_id'] ) && is_string( $_POST['preset_id'] ) ? wp_unslash( $_POST['preset_id'] ) : '';
  $result = Presets::remove( $id );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) ); }
  $this->redirect( 'preset_removed' );
 }
 /** Export one named preset without applying it to the site. @return void */
 public function preset_export(): void {
  $this->authorize( 'site_essentials_preset_export' );
  $id = isset( $_POST['preset_id'] ) && is_string( $_POST['preset_id'] ) ? wp_unslash( $_POST['preset_id'] ) : '';
  $result = Presets::export( $id );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 404 ) ); }
  nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' );
  header( 'Content-Disposition: attachment; filename="site-essentials-preset.json"' );
  echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); exit;
 }
 /** Provide detached action forms so controls inside the settings layout never nest forms. @return void */
 private function preset_forms(): void {
  foreach ( array( 'create', 'remove', 'export', 'edit' ) as $action ) {
   echo '<form id="se-preset-' . esc_attr( $action ) . '-form" class="se-preset-action" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
   wp_nonce_field( self::nonce_action( 'site_essentials_preset_' . $action ) );
   echo '<input type="hidden" name="action" value="site_essentials_preset_' . esc_attr( $action ) . '">';
   echo '<input type="hidden" name="presets_revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( Presets::custom() ) ) ) . '">';
   if ( 'create' === $action ) { echo '<input type="hidden" name="revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( Settings::local() ) ) ) . '">'; }
   echo '</form>';
  }
 }
 /** Render site-local preset management and a saved-state comparison. @return void */
 private function preset_manager(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Your presets', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Create a named preset from the saved local configuration. Unsaved changes and network policies are not included.', 'site-essentials' ) . '</p><div class="se-control-row"><label for="se-preset-name">' . esc_html__( 'Preset name', 'site-essentials' ) . '</label> <input type="text" id="se-preset-name" name="preset_name" maxlength="80" form="se-preset-create-form" required> <button type="submit" form="se-preset-create-form" class="button">' . esc_html__( 'Save as a custom preset', 'site-essentials' ) . '</button></div><p><label for="se-preset-description">' . esc_html__( 'Configuration note', 'site-essentials' ) . '</label><textarea id="se-preset-description" name="preset_description" maxlength="500" rows="3" form="se-preset-create-form"></textarea></p><p>' . esc_html__( 'Names and notes are included in preset exports. Use neutral configuration notes; do not enter domains, customer details or credentials.', 'site-essentials' ) . '</p><p id="se-preset-draft-note" role="status" tabindex="-1"></p>';
  echo '<p id="se-profile-state" role="status" tabindex="-1"></p><p>' . esc_html__( 'Unsaved profile details can survive saving settings in this tab for up to 30 minutes. This temporary browser storage sends no background requests; preset creation remains explicit.', 'site-essentials' ) . '</p>';
  echo '<section id="se-profile-recovery" hidden aria-labelledby="se-recovery-title"><h4 id="se-recovery-title">' . esc_html__( 'Recovered preset details', 'site-essentials' ) . '</h4><p>' . esc_html__( 'The original preset changed or is no longer available. These temporary details were not applied. Copy them or prepare a new preset; its settings will come from the saved local configuration.', 'site-essentials' ) . '</p><p><label for="se-recovery-name">' . esc_html__( 'Preset name', 'site-essentials' ) . '</label><input id="se-recovery-name" type="text" readonly></p><p><label for="se-recovery-note">' . esc_html__( 'Configuration note', 'site-essentials' ) . '</label><textarea id="se-recovery-note" rows="3" readonly></textarea></p><button type="button" id="se-recovery-use" class="button">' . esc_html__( 'Prepare as a new preset', 'site-essentials' ) . '</button> <button type="button" id="se-recovery-discard" class="button">' . esc_html__( 'Discard recovered details', 'site-essentials' ) . '</button></section>';
  $custom = Presets::custom();
  if ( $custom ) {
   echo '<div class="se-control-row"><label for="se-preset-remove">' . esc_html__( 'Custom preset to remove', 'site-essentials' ) . '</label> <select id="se-preset-remove" name="preset_id" form="se-preset-remove-form">';
   foreach ( $custom as $id => $item ) { echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $item['label'] ) . '</option>'; }
   echo '</select> <button type="submit" form="se-preset-remove-form" class="button">' . esc_html__( 'Remove custom preset', 'site-essentials' ) . '</button></div>';
  }
  if ( $custom ) { $this->preset_editor( $custom ); }
  echo '<p>' . esc_html__( 'Custom presets belong to this site and remain stored when the plugin is deactivated or uninstalled.', 'site-essentials' ) . '</p></div>';
  echo '<div class="se-card"><h3>' . esc_html__( 'Saved configuration status', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Compare saved local settings with a preset. Effective values include Site Essentials network policies; other plugins may impose stricter behavior.', 'site-essentials' ) . '</p><div class="se-control-row"><label for="se-compare-preset">' . esc_html__( 'Compare with', 'site-essentials' ) . '</label> <select id="se-compare-preset">';
  foreach ( Presets::catalog() as $id => $item ) { echo '<option value="' . esc_attr( $id ) . '" ' . selected( $id, 'defaults', false ) . '>' . esc_html( $item['label'] ) . '</option>'; }
  echo '</select></div><p id="se-compare-summary" role="status"></p><details><summary>' . esc_html__( 'Show saved settings and their source', 'site-essentials' ) . '</summary><p><label><input type="checkbox" id="se-compare-only-differences" checked> ' . esc_html__( 'Only differences from the preset', 'site-essentials' ) . '</label></p><div class="se-status-scroll"><table class="widefat striped se-status-table"><thead><tr>';
  foreach ( array( __( 'Setting', 'site-essentials' ), __( 'Local value', 'site-essentials' ), __( 'Effective value', 'site-essentials' ), __( 'Preset value', 'site-essentials' ), __( 'Source', 'site-essentials' ) ) as $label ) { echo '<th scope="col">' . esc_html( $label ) . '</th>'; }
  echo '</tr></thead><tbody id="se-compare-rows"></tbody></table></div></details><p>' . esc_html__( 'The status reflects saved settings. Save your draft to update it.', 'site-essentials' ) . '</p></div>';
 }
 /** Render JSON import controls with explicit merge/replace semantics. @return void */
 private function transfer(): void {
  echo '<div class="se-card"><p>' . esc_html__( 'Import local settings only. Network policies and previous configurations are not transferred.', 'site-essentials' ) . '</p><label for="se-file">' . esc_html__( 'JSON file', 'site-essentials' ) . '</label><input id="se-file" type="file" accept=".json,application/json"><label for="se-import">' . esc_html__( 'Configuration JSON', 'site-essentials' ) . '</label><textarea id="se-import" rows="10" spellcheck="false"></textarea><div class="se-control-row"><label for="se-import-mode">' . esc_html__( 'Import mode', 'site-essentials' ) . '</label><select id="se-import-mode"><option value="merge">' . esc_html__( 'Merge supplied settings', 'site-essentials' ) . '</option><option value="replace">' . esc_html__( 'Replace with imported settings', 'site-essentials' ) . '</option></select> <button type="button" class="button" id="se-import-preview">' . esc_html__( 'Review import', 'site-essentials' ) . '</button></div><p id="se-import-error" role="alert"></p></div>';
  echo '<div class="se-card"><h3>' . esc_html__( 'Export a preset', 'site-essentials' ) . '</h3><div class="se-control-row"><label for="se-preset-export">' . esc_html__( 'Preset to export', 'site-essentials' ) . '</label> <select id="se-preset-export" name="preset_id" form="se-preset-export-form">';
  foreach ( Presets::catalog() as $id => $item ) { echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $item['label'] ) . '</option>'; }
  echo '</select> <button type="submit" form="se-preset-export-form" class="button">' . esc_html__( 'Export selected preset', 'site-essentials' ) . '</button></div><p>' . esc_html__( 'Exported presets use the same format as configuration imports. Import, review and save on another site.', 'site-essentials' ) . '</p></div>';

 }
 /** Render collection transfer independently of the active settings form. @return void */
 private function collections(): void {
  $custom = Presets::custom();
  echo '<div class="se-card"><h3>' . esc_html__( 'Export a preset collection', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Choose your own saved presets. Names, notes and saved preset values are included; active settings and network policies are not gathered. Use neutral profile details without credentials or customer secrets.', 'site-essentials' ) . '</p><fieldset><legend>' . esc_html__( 'Custom presets to export', 'site-essentials' ) . '</legend>';
  foreach ( $custom as $id => $item ) { echo '<label class="se-choice"><input type="checkbox" name="collection_ids[]" value="' . esc_attr( $id ) . '" form="se-collection-export-form"> ' . esc_html( $item['label'] ) . '</label>'; }
  if ( ! $custom ) { echo '<p>' . esc_html__( 'Save a custom preset before exporting a collection.', 'site-essentials' ) . '</p>'; }
  echo '</fieldset><p><button type="submit" form="se-collection-export-form" class="button"' . ( $custom ? '' : ' disabled' ) . '>' . esc_html__( 'Export selected custom presets', 'site-essentials' ) . '</button></p></div>';
  echo '<div class="se-card"><h3>' . esc_html__( 'Import a preset collection', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Review the profiles, select copies and adjust conflicting names. Existing presets are never replaced. Imported copies receive new local identifiers and creation dates. Active settings change only when you later apply a preset.', 'site-essentials' ) . '</p><label for="se-collection-file">' . esc_html__( 'Collection JSON file', 'site-essentials' ) . '</label><input id="se-collection-file" type="file" accept=".json,application/json"><label for="se-collection-json">' . esc_html__( 'Preset collection JSON', 'site-essentials' ) . '</label><textarea id="se-collection-json" rows="8" spellcheck="false"></textarea><p>' . esc_html__( 'Up to 20 profiles per collection, 20 custom presets per site and 256 KiB per file. Collection drafts are not stored in the browser or on the server.', 'site-essentials' ) . '</p><p><button type="button" class="button" id="se-collection-review">' . esc_html__( 'Review collection', 'site-essentials' ) . '</button> <button type="button" class="button" id="se-collection-clear">' . esc_html__( 'Clear collection draft', 'site-essentials' ) . '</button></p><p id="se-collection-error" role="alert" tabindex="-1"></p><div id="se-collection-rows"></div><p id="se-collection-summary" role="status"></p><p><button type="submit" form="se-collection-import-form" id="se-collection-commit" class="button button-primary" disabled>' . esc_html__( 'Import selected preset copies', 'site-essentials' ) . '</button></p><noscript><p>' . esc_html__( 'Collection review and import require JavaScript. Export works through the normal form.', 'site-essentials' ) . '</p></noscript></div>';
 }
 /** Keep collection forms detached to avoid nested forms and accidental active-setting submissions. @return void */
 private function collection_forms(): void {
  foreach ( array( 'export', 'import' ) as $action ) {
   echo '<form id="se-collection-' . esc_attr( $action ) . '-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
   wp_nonce_field( self::nonce_action( 'site_essentials_collection_' . $action ) );
   echo '<input type="hidden" name="action" value="site_essentials_collection_' . esc_attr( $action ) . '">';
   if ( 'import' === $action ) { echo '<input type="hidden" name="collection" id="se-collection-payload"><input type="hidden" name="presets_revision" id="se-collection-revision">'; }
   echo '</form>';
  }
 }
 /** Protected no-write AJAX review; no uploaded file is retained. @return void */
 public function collection_review(): void {
  $this->authorize( 'site_essentials_collection_review' ); nocache_headers();
  $json = isset( $_POST['collection'] ) && is_string( $_POST['collection'] ) ? wp_unslash( $_POST['collection'] ) : '';
  $result = Collections::review( $json );
  if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
  wp_send_json_success( $result );
 }
 /** Download selected custom presets only after authorization. @return void */
 public function collection_export(): void {
  $this->authorize( 'site_essentials_collection_export' );
  $ids = isset( $_POST['collection_ids'] ) && is_array( $_POST['collection_ids'] ) ? wp_unslash( $_POST['collection_ids'] ) : array();
  $result = Collections::export( $ids );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) ); }
  nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="site-essentials-preset-collection.json"' );
  echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); exit;
 }
 /** Commit one reviewed site-local batch without changing active settings or rollback history. @return void */
 public function collection_import(): void {
  $this->authorize( 'site_essentials_collection_import' );
  $json = isset( $_POST['collection'] ) && is_string( $_POST['collection'] ) ? wp_unslash( $_POST['collection'] ) : '';
  $selected = isset( $_POST['collection_pick'] ) && is_array( $_POST['collection_pick'] ) ? wp_unslash( $_POST['collection_pick'] ) : array();
  $names = isset( $_POST['collection_names'] ) && is_array( $_POST['collection_names'] ) ? wp_unslash( $_POST['collection_names'] ) : array();
  $revision = isset( $_POST['presets_revision'] ) && is_string( $_POST['presets_revision'] ) ? wp_unslash( $_POST['presets_revision'] ) : '';
  $result = Collections::import( $json, $selected, $names, $revision );
  if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 'collection_stale' === $result->get_error_code() ? 409 : 400 ) ); }
  wp_safe_redirect( add_query_arg( array( 'page' => 'site-essentials', 'se_result' => 'collection_imported' ), admin_url( 'options-general.php' ) ) . '#se-collections' ); exit;
 }
 /** Render name/note editing without replacing preset settings.
  * @param array $custom Validated site-local presets.
  * @return void
  */
 private function preset_editor( array $custom ): void {
  echo '<details id="se-edit-profile"><summary>' . esc_html__( 'Edit preset name and note', 'site-essentials' ) . '</summary><p>' . esc_html__( 'Preset settings, identity and creation date stay unchanged.', 'site-essentials' ) . '</p><div class="se-control-row"><label for="se-preset-edit">' . esc_html__( 'Custom preset', 'site-essentials' ) . '</label> <select id="se-preset-edit" name="preset_id" form="se-preset-edit-form">';
  foreach ( $custom as $id => $item ) { echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $item['label'] ) . '</option>'; }
  echo '</select></div><p class="se-control-row"><label for="se-preset-edit-name">' . esc_html__( 'Preset name', 'site-essentials' ) . '</label> <input id="se-preset-edit-name" name="preset_name" maxlength="80" form="se-preset-edit-form" required></p><p><label for="se-preset-edit-description">' . esc_html__( 'Configuration note', 'site-essentials' ) . '</label><textarea id="se-preset-edit-description" name="preset_description" rows="3" maxlength="500" form="se-preset-edit-form"></textarea></p><button type="submit" form="se-preset-edit-form" class="button">' . esc_html__( 'Save preset name and note', 'site-essentials' ) . '</button></details>';
 }
 /** Render related settings in one card with individual subheadings.
  * @param string $id Stable group identifier.
  * @param string $title Translated group heading.
  * @param array $keys Ordered setting identifiers.
  * @param array $values Current local values.
  * @return void
  */
 private function grouped_fields( string $id, string $title, array $keys, array $values ): void {
  echo '<div class="se-card se-field-group" data-field-group="' . esc_attr( $id ) . '" aria-labelledby="se-group-' . esc_attr( $id ) . '"><h3 id="se-group-' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h3>';
  foreach ( $keys as $key ) { $this->field( $key, Schema::fields()[ $key ], $values[ $key ], true, 4 ); }
  if ( in_array( $id, array( 'widgets', 'customizer', 'menus' ), true ) ) { $this->access_panel( $id ); }
  echo '</div>';
 }
 /** Render an accessible control card with stable identifiers.
  * @param string $key Setting identifier.
  * @param array $field Field definition.
  * @param mixed $value Stored local value.
  * @param bool $nested Render a field inside an existing white card.
  * @param int $level Internal heading level, three or four.
  * @return void
  */
 private function field( string $key, array $field, $value, bool $nested = false, int $level = 3 ): void {
  $name = 'settings[' . $key . ']'; $id = 'se-field-' . $key; $locked = 'login_language' === $key && Settings::language_locked();
  $heading = 4 === $level ? 'h4' : 'h3';
  echo '<div class="' . ( $nested ? 'se-subfield' : 'se-card' ) . ( in_array( $key, array( 'widgets_admin', 'customizer_admin' ), true ) ? ' se-legacy-setting' : '' ) . '" data-setting="' . esc_attr( $key ) . '"><' . $heading . ' id="' . esc_attr( $id ) . '-title">';
  if ( in_array( $field['type'], array( 'list', 'map' ), true ) ) { echo esc_html( $field['label'] ); }
  else { echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>'; }
  echo '</' . $heading . '>';
  if ( 'bool' === $field['type'] ) {
   echo '<div class="se-checkbox-row"><input type="hidden" name="' . esc_attr( $name ) . '" value="0"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( $locked || $value, true, false ) . ( $locked ? ' disabled' : '' ) . ' aria-labelledby="' . esc_attr( $id ) . '-title" aria-describedby="' . esc_attr( $id ) . '-help"><label class="description" id="' . esc_attr( $id ) . '-help" for="' . esc_attr( $id ) . '">' . esc_html( $field['help'] ) . '</label></div>';
   if ( $locked ) { echo '<p class="se-policy">' . esc_html__( 'Required by the network. Your local choice is preserved.', 'site-essentials' ) . '</p>'; }
  } elseif ( 'text' === $field['type'] ) {
   echo '<input type="text" maxlength="' . esc_attr( $field['max_chars'] ?? 500 ) . '" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
  } elseif ( 'int' === $field['type'] ) {
   if ( preg_match( '/^access_(widgets|customizer|menus)_mode$/', $key ) ) {
    echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-access-mode="' . esc_attr( substr( $key, 7, -5 ) ) . '">';
    foreach ( array( __( 'Keep WordPress permissions', 'site-essentials' ), __( 'Block everyone', 'site-essentials' ), __( 'Choose allowed roles and users', 'site-essentials' ) ) as $mode => $label ) { echo '<option value="' . esc_attr( $mode ) . '" ' . selected( $value, $mode, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</select>';
   } elseif ( str_starts_with( $key, 'heartbeat_' ) ) { $this->interval( $id, $name, $value ); }
   else { echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" min="-1" max="100" value="' . esc_attr( $value ) . '">'; }
  } else {
   $choices = $field['choices'] ?? array();
   if ( 'roles' === ( $field['dynamic'] ?? '' ) ) { $choices = array_keys( wp_roles()->roles ); }
   elseif ( isset( $field['dynamic'] ) ) {
    $objects = get_post_types( array( 'show_ui' => true ), 'objects' );
    foreach ( $objects as $type => $object ) {
     if ( $object->_builtin && ! in_array( $type, array( 'post', 'page', 'attachment' ), true ) ) { continue; }
     $support = 'revisions' === $field['dynamic'] ? 'revisions' : ( 'comment_types' === $field['dynamic'] ? 'comments' : 'editor' );
     if ( 'classic_editor' === $field['dynamic'] && ( 'attachment' === $type || ! $object->show_in_rest ) ) { continue; }
     if ( 'target_types' === $field['dynamic'] ? ( $object->public && 'attachment' !== $type ) : post_type_supports( $type, $support ) ) { $choices[] = $type; }
    }
   }
   $choice_labels = array( 'link-target' => __( 'Show link target', 'site-essentials' ), 'css-classes' => __( 'Show CSS classes', 'site-essentials' ), 'xfn' => __( 'Show link relationship (XFN)', 'site-essentials' ), 'title-attribute' => __( 'Hide title attribute', 'site-essentials' ), 'description' => __( 'Hide description', 'site-essentials' ), 'post_by_email' => __( 'Post via email', 'site-essentials' ), 'update_services' => __( 'Update services', 'site-essentials' ), 'available_tools' => __( 'Available tools', 'site-essentials' ), 'core_import' => __( 'Import', 'site-essentials' ), 'core_export' => __( 'Export', 'site-essentials' ), 'welcome_panel' => __( 'Welcome panel', 'site-essentials' ), 'archives' => __( 'Archives', 'site-essentials' ), 'calendar' => __( 'Calendar', 'site-essentials' ), 'meta' => __( 'Meta', 'site-essentials' ), 'search' => __( 'Search', 'site-essentials' ), 'recent-posts' => __( 'Recent posts', 'site-essentials' ), 'recent-comments' => __( 'Recent comments', 'site-essentials' ), 'categories' => __( 'Categories', 'site-essentials' ), 'tag-cloud' => __( 'Tag cloud', 'site-essentials' ), 'rss' => __( 'RSS', 'site-essentials' ), 'dashboard_activity' => __( 'Activity', 'site-essentials' ), 'dashboard_right_now' => __( 'At a glance', 'site-essentials' ), 'dashboard_quick_press' => __( 'Quick draft', 'site-essentials' ), 'dashboard_primary' => __( 'WordPress events and news', 'site-essentials' ), 'dashboard_site_health' => __( 'Site health', 'site-essentials' ), 'wp-logo' => __( 'WordPress logo', 'site-essentials' ), 'comments' => __( 'Comments', 'site-essentials' ), 'new-content' => __( 'New content', 'site-essentials' ), 'updates' => __( 'Updates', 'site-essentials' ) );
   echo '<fieldset aria-describedby="' . esc_attr( $id ) . '-help" id="' . esc_attr( $id ) . '"><legend class="screen-reader-text">' . esc_html( $field['label'] ) . '</legend>';
   foreach ( $choices as $choice ) {
    $object = get_post_type_object( $choice ); $label = 'roles' === ( $field['dynamic'] ?? '' ) ? translate_user_role( wp_roles()->role_names[ $choice ] ?? $choice ) : ( isset( $field['dynamic'] ) && $object ? $object->labels->name : ( $choice_labels[ $choice ] ?? ( $object ? $object->labels->name : $choice ) ) );
    echo '<label class="se-choice">';
    if ( 'list' === $field['type'] ) { echo '<input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $choice ) . '" ' . checked( in_array( $choice, $value, true ), true, false ) . '> ' . esc_html( $label ); }
    else {
     echo '<span>' . esc_html( $label ) . '</span> ';
     if ( 'heartbeat' === $field['dynamic'] ) { $this->interval( $id . '-' . $choice, $name . '[' . $choice . ']', $value[ $choice ] ?? 0 ); }
     else { echo '<input aria-label="' . esc_attr( $label ) . '" type="number" min="-1" max="100" name="' . esc_attr( $name . '[' . $choice . ']' ) . '" value="' . esc_attr( $value[ $choice ] ?? -1 ) . '">'; }
    }
    echo '</label>';
   }
   // Keep settings for content types not currently registered, without exposing controls for them.
   foreach ( $value as $choice => $stored ) {
    $type = 'list' === $field['type'] ? $stored : $choice;
    if ( in_array( $type, $choices, true ) ) { continue; }
    echo '<input type="hidden" name="' . esc_attr( 'list' === $field['type'] ? $name . '[]' : $name . '[' . $type . ']' ) . '" value="' . esc_attr( $stored ) . '">';
   }
   echo '</fieldset>';
  }
  if ( isset( Schema::impacts()[ $key ] ) ) { echo '<details class="se-impact"><summary>' . esc_html__( 'Impact and recovery', 'site-essentials' ) . '</summary><p>' . esc_html( Schema::impacts()[ $key ] ) . '</p></details>'; }
  if ( 'bool' !== $field['type'] ) { echo '<p class="description" id="' . esc_attr( $id ) . '-help">' . esc_html( $field['help'] ) . '</p>'; }
  echo '</div>';
 }

 /** Load a data-minimal diagnostic after POST authorization. @return void */
 public function diagnostic(): void { $this->authorize( 'site_essentials_diagnostic' ); nocache_headers(); wp_send_json_success( Reports::diagnostic() ); }
 /** Hash all local data that can be explicitly cleared. @return string */
 private function data_revision(): string { return hash( 'sha256', wp_json_encode( array( Settings::local(), get_option( Settings::PREVIOUS, false ), Presets::custom(), Avatars::revision(), WebsiteChecks::state(), get_option( Access::OPTION, false ) ) ) ); }
 /** Clear only this site's settings, snapshot and presets after typed confirmation. @return void */
 public function clear_local(): void {
  $this->authorize( 'site_essentials_clear_local' );
  $revision = isset( $_POST['data_revision'] ) && is_string( $_POST['data_revision'] ) ? wp_unslash( $_POST['data_revision'] ) : '';
  if ( ! hash_equals( $this->data_revision(), $revision ) ) { wp_die( esc_html__( 'Local data changed. Reload before clearing it.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $this->check_clear_confirmation();
  // Neutral settings prevent an installed startup preset from silently reapplying after clearing.
  update_option( Settings::OPTION, Schema::defaults(), false ); delete_option( Settings::PREVIOUS ); delete_option( Presets::OPTION ); $avatars_cleared = Avatars::clear(); $media_cleared = Media::clear(); delete_option( WebsiteChecks::OPTION ); delete_option( Access::OPTION );
  if ( false !== get_option( Access::OPTION, false ) || false !== get_option( WebsiteChecks::OPTION, false ) || ! $avatars_cleared || ! $media_cleared || get_option( Settings::OPTION ) !== Schema::defaults() || false !== get_option( Settings::PREVIOUS, false ) || false !== get_option( Presets::OPTION, false ) ) { wp_die( esc_html__( 'Some local data could not be cleared. Reload to check the remaining data.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  $this->redirect( 'local_cleared' );
 }
 /** Clear only the current network policy, never site data or other networks. @return void */
 public function clear_network(): void {
  $this->authorize( 'site_essentials_clear_network', true );
  if ( ! Settings::network_active() ) { wp_die( esc_html__( 'Network activation is required for network policies.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $revision = isset( $_POST['data_revision'] ) && is_string( $_POST['data_revision'] ) ? wp_unslash( $_POST['data_revision'] ) : '';
  if ( ! hash_equals( hash( 'sha256', wp_json_encode( get_site_option( Settings::NETWORK, 'inherit' ) ) ), $revision ) ) { wp_die( esc_html__( 'Network policy changed. Reload before changing it.', 'site-essentials' ), '', array( 'response' => 409 ) ); }
  $this->check_clear_confirmation(); delete_site_option( Settings::NETWORK );
  if ( false !== get_site_option( Settings::NETWORK, false ) ) { wp_die( esc_html__( 'Network policy could not be cleared.', 'site-essentials' ), '', array( 'response' => 500 ) ); }
  $this->redirect( 'network_cleared', true );
 }
 /** Require an explicit per-scope typed confirmation for data removal. @return void */
 private function check_clear_confirmation(): void {
  if ( ( $_POST['confirm_clear'] ?? '' ) !== 'RESET' ) { wp_die( esc_html__( 'Type RESET to confirm clearing the selected scope.', 'site-essentials' ), '', array( 'response' => 400 ) ); }
 }
 /** Render a detached clearing form, avoiding nested forms. @param bool $network Network policy scope. @return void */
 private function clear_form( bool $network ): void {
  $scope = $network ? 'network' : 'local';
  echo '<form class="se-clear-action" id="se-clear-' . esc_attr( $scope ) . '-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
  wp_nonce_field( self::nonce_action( 'site_essentials_clear_' . $scope, 'network' === $scope ) );
  echo '<input type="hidden" name="action" value="site_essentials_clear_' . esc_attr( $scope ) . '"><input type="hidden" name="data_revision" value="' . esc_attr( $network ? hash( 'sha256', wp_json_encode( get_site_option( Settings::NETWORK, 'inherit' ) ) ) : $this->data_revision() ) . '"></form>';
 }
 /** Present local clearing controls without touching other sites or network policy. @return void */
 private function data_controls(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Clear local plugin data', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Remove this site’s custom presets and previous configuration, and replace its saved settings with neutral WordPress defaults. This cannot be undone. Export first if needed. Other sites, network policies and WordPress content stay unchanged.', 'site-essentials' ) . '</p><p>' . esc_html__( 'Local avatar assignments are also removed; uploaded media files stay in WordPress.', 'site-essentials' ) . ' ' . esc_html__( 'Website check assignments and confirmations are also cleared; WordPress visibility and privacy settings stay unchanged.', 'site-essentials' ) . '</p><p>' . esc_html__( 'A neutral default configuration remains while the plugin is active, preventing a startup preset from returning. Normal uninstall still retains your configuration.', 'site-essentials' ) . '</p><div class="se-control-row"><label for="se-confirm-local">' . esc_html__( 'Type RESET to confirm', 'site-essentials' ) . '</label> <input id="se-confirm-local" name="confirm_clear" form="se-clear-local-form" autocomplete="off" required pattern="RESET"> <button type="submit" form="se-clear-local-form" class="button">' . esc_html__( 'Clear this site’s data', 'site-essentials' ) . '</button></div><p id="se-clear-draft-note" role="status" tabindex="-1"></p></div>';
 }
 /** Present a separate current-network policy clearing action. @return void */
 private function network_data_controls(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Clear current network policy', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Release the login language policy for this network. Local site settings and other networks stay unchanged.', 'site-essentials' ) . '</p><div class="se-control-row"><label for="se-confirm-network">' . esc_html__( 'Type RESET to confirm', 'site-essentials' ) . '</label> <input id="se-confirm-network" name="confirm_clear" form="se-clear-network-form" required pattern="RESET" autocomplete="off"> <button type="submit" form="se-clear-network-form" class="button">' . esc_html__( 'Clear network policy', 'site-essentials' ) . '</button></div></div>';
 }
 /** Render on-demand diagnosis and a read-only customer handover. @return void */
 private function reports(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Configuration diagnosis', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Generate a report only when requested. It contains versions, scope and configured rules; no domains, user accounts, content, preset notes or custom footer text. It performs no runtime tests or external requests.', 'site-essentials' ) . '</p><button type="button" class="button" id="se-report-generate">' . esc_html__( 'Generate report', 'site-essentials' ) . '</button> <button type="button" class="button" id="se-report-copy" disabled>' . esc_html__( 'Copy report', 'site-essentials' ) . '</button><label for="se-report-output" class="screen-reader-text">' . esc_html__( 'Configuration report', 'site-essentials' ) . '</label><textarea id="se-report-output" readonly rows="12" hidden></textarea><p id="se-report-status" role="status"></p></div>';
  Reports::handover();
 }
 /** Render the allowed heartbeat interval choices.
  * @param string $id Control ID.
  * @param string $name Submitted field name.
  * @param int $value Interval; zero inherits.
  * @return void
  */
 private function interval( string $id, string $name, int $value ): void {
  echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
  foreach ( array( 0, 15, 30, 60, 120 ) as $interval ) { echo '<option value="' . esc_attr( $interval ) . '" ' . selected( $value, $interval, false ) . '>' . esc_html( $interval ? sprintf( __( '%d seconds', 'site-essentials' ), $interval ) : __( 'Inherit defaults', 'site-essentials' ) ) . '</option>'; }
  echo '</select>';
 }
 /** Render a protected auxiliary action form, never nest forms.
  * @param string $action Restore or export.
  * @param string $label Button text.
  * @param bool $revision Include the concurrency token.
  * @return void
  */
 private function action_form( string $action, string $label, bool $revision = false ): void {
  echo '<form id="se-action-' . esc_attr( $action ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'; wp_nonce_field( self::nonce_action( 'site_essentials_' . $action ) );
  echo '<input type="hidden" name="action" value="site_essentials_' . esc_attr( $action ) . '">';
  if ( $revision ) { echo '<input type="hidden" name="revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( Settings::local() ) ) ) . '">'; }
  if ( 'restore' === $action ) { echo '<input type="hidden" name="snapshot_revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( get_option( Settings::PREVIOUS, false ) ) ) ) . '">'; }
  echo '<button class="button" type="submit"' . ( 'restore' === $action && null === Settings::previous() ? ' disabled' : '' ) . '>' . esc_html( $label ) . '</button></form>';
 }
 /** Render the one supported policy in Network Admin. @return void */
 public function network_page(): void {
  if ( ! current_user_can( 'manage_network_options' ) ) { return; }
  echo '<div class="wrap se-root">'; $this->header();
  echo '<div class="se-card"><h2>' . esc_html__( 'Network login policy', 'site-essentials' ) . '</h2><p>' . esc_html__( 'All other settings remain local to each site. Network activation does not apply a shared preset.', 'site-essentials' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
  wp_nonce_field( self::nonce_action( 'site_essentials_network', true ) );
  echo '<input type="hidden" name="policy_revision" value="' . esc_attr( hash( 'sha256', wp_json_encode( get_site_option( Settings::NETWORK, 'inherit' ) ) ) ) . '">';
  echo '<input type="hidden" name="action" value="site_essentials_network"><label for="se-language-policy">' . esc_html__( 'Login language selector', 'site-essentials' ) . '</label> <select id="se-language-policy" name="language_policy">';
  foreach ( array( 'inherit' => __( 'Let each site decide', 'site-essentials' ), 'hide' => __( 'Hide on every site', 'site-essentials' ) ) as $mode => $label ) { echo '<option value="' . esc_attr( $mode ) . '" ' . selected( get_site_option( Settings::NETWORK, 'inherit' ), $mode, false ) . '>' . esc_html( $label ) . '</option>'; }
  echo '</select>'; submit_button( __( 'Save network policy', 'site-essentials' ) ); echo '</form></div>';
  $this->network_data_controls(); $this->clear_form( true ); $this->footer(); echo '</div>';
 }
 /** Render local documentation and a structured keyboard-accessible changelog. @return void */
 private function footer(): void {
  echo '<footer class="se-footer"><div><strong>Site Essentials</strong> ' . esc_html( SITE_ESSENTIALS_VERSION ) . '<p>' . esc_html__( 'Your WordPress. Your settings.', 'site-essentials' ) . '</p></div><div><button class="button-link" type="button" data-dialog="se-documentation">' . esc_html__( 'Documentation', 'site-essentials' ) . '</button> · <button class="button-link" type="button" data-dialog="se-history">' . esc_html__( 'Changelog', 'site-essentials' ) . '</button><p>© 2026 <a href="https://github.com/deckerweb">David Decker – DECKERWEB</a></p></div></footer>';
  foreach ( array( 'se-documentation' => __( 'Documentation', 'site-essentials' ), 'se-history' => __( 'Changelog', 'site-essentials' ), 'se-preview' => __( 'Review changes', 'site-essentials' ) ) as $id => $title ) {
   echo '<dialog class="se-dialog" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id ) . '-title"><header><h2 id="' . esc_attr( $id ) . '-title">' . esc_html( $title ) . '</h2><button type="button" class="button se-close">' . esc_html__( 'Close', 'site-essentials' ) . '</button></header><div class="se-dialog-body" tabindex="0">';
   $content = json_decode( (string) file_get_contents( dirname( SITE_ESSENTIALS_FILE ) . '/content.json' ), true );
   if ( 'se-history' === $id ) {
    foreach ( $content['history'] as $release ) {
     echo '<h3>' . esc_html( $release['version'] ) . '</h3><p>' . esc_html( $release['date'] ) . '</p>';
     $categories = array( 'new' => __( 'New:', 'site-essentials' ), 'improved' => __( 'Improved:', 'site-essentials' ), 'fixed' => __( 'Fixed:', 'site-essentials' ), 'misc' => __( 'Misc:', 'site-essentials' ) );
     foreach ( $categories as $category => $label ) {
      if ( empty( $release[ $category ] ) ) { continue; }
      echo '<h4><span class="se-badge">' . esc_html( $label ) . '</span></h4><ul>';
      foreach ( $release[ $category ] as $entry ) { echo '<li>' . esc_html( __( $entry, 'site-essentials' ) ) . '</li>'; }
      echo '</ul>';
     }
    }
   } elseif ( 'se-documentation' === $id ) {
    foreach ( $content['help'] as $entry ) { echo '<h3>' . esc_html( __( $entry['title'], 'site-essentials' ) ) . '</h3><p>' . esc_html( __( $entry['text'], 'site-essentials' ) ) . '</p>'; }
   } else { echo '<div id="se-preview-list"></div><button type="button" id="se-preview-apply" class="button button-primary">' . esc_html__( 'Take selected changes into the draft', 'site-essentials' ) . '</button>'; }
   echo '</div></dialog>';
  }
 }
 /** Render admin-only local exceptions in the main save form.
  * @param string $area Stable area.
  * @return void
  */
 private function access_panel( string $area ): void {
  if ( ! current_user_can( 'manage_options' ) ) { return; }
  $personal = Access::exceptions()[ $area ];
  echo '<div class="se-access-personal" data-access-personal="' . esc_attr( $area ) . '"><h4>' . esc_html__( 'Personal exceptions on this site', 'site-essentials' ) . '</h4><p>' . esc_html__( 'Personal access exceptions stay on this site and are excluded from every settings, preset and collection export.', 'site-essentials' ) . '</p><p>' . esc_html__( 'An explicit block takes priority over role permission. Personal exceptions apply only to selected-role mode. Save all settings applies both role rules and these local exceptions.', 'site-essentials' ) . '</p>';
  foreach ( array( 'allow' => __( 'Additionally allow', 'site-essentials' ), 'deny' => __( 'Explicitly block', 'site-essentials' ) ) as $kind => $label ) {
   $id = 'se-access-' . $area . '-' . $kind;
   echo '<div class="se-access-picker" data-area="' . esc_attr( $area ) . '" data-kind="' . esc_attr( $kind ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label><input type="hidden" name="exceptions[' . esc_attr( $area ) . '][' . esc_attr( $kind ) . '][]" value=""><div class="se-access-selected">';
   foreach ( $personal[ $kind ] as $uid ) {
    $user = get_userdata( $uid ); if ( ! $user || ( is_multisite() && ! is_user_member_of_blog( $uid ) ) ) { continue; }
    echo '<label class="se-choice"><input type="checkbox" name="exceptions[' . esc_attr( $area ) . '][' . esc_attr( $kind ) . '][]" value="' . esc_attr( $uid ) . '" checked> ' . esc_html( $user->display_name . ' (' . $user->user_login . ')' ) . '</label>';
   }
   echo '</div><div class="se-control-row"><input type="search" class="se-access-search" id="' . esc_attr( $id ) . '" autocomplete="off" placeholder="' . esc_attr__( 'Search site users…', 'site-essentials' ) . '"><button type="button" class="button se-access-find">' . esc_html__( 'Find users', 'site-essentials' ) . '</button></div><p class="se-access-status" role="status"></p><div class="se-access-results"></div></div>';
  }
  echo '<p>' . esc_html__( 'For comprehensive role and capability management, use a dedicated plugin such as Members. These controls cover native appearance areas only.', 'site-essentials' ) . ' <a href="https://wordpress.org/plugins/members/" target="_blank" rel="noopener noreferrer">Members</a></p></div>';
 }
 /** Search current-site users only after administrator and scoped nonce checks. @return void */
 public function access_users_search(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ! current_user_can( 'list_users' ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_send_json_error( null, 403 ); }
  $nonce = $_POST['nonce'] ?? ''; $term = $_POST['term'] ?? '';
  if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), self::nonce_action( 'site_essentials_access_search' ) ) ) { wp_send_json_error( null, 403 ); }
  if ( ! is_string( $term ) || strlen( $term ) > 160 ) { wp_send_json_error( null, 400 ); }
  $term = sanitize_text_field( wp_unslash( $term ) );
  if ( mb_strlen( $term ) < 2 ) { wp_send_json_success( array() ); }
  $query = new \WP_User_Query( array( 'blog_id' => get_current_blog_id(), 'number' => 20, 'count_total' => false, 'orderby' => 'display_name', 'search' => '*' . $term . '*', 'search_columns' => array( 'user_login', 'display_name' ) ) );
  $users = array();
  foreach ( $query->get_results() as $user ) { $users[] = array( 'id' => (int) $user->ID, 'label' => $user->display_name . ' (' . $user->user_login . ')' ); }
  wp_send_json_success( $users );
 }

}
