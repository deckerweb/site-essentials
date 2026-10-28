<?php
/** Data-minimal configuration reporting. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Reads configured rules without inspecting content, accounts or external services. */
final class Reports {
 /** Reviewed support keys; new settings require explicit inclusion. */
 private const SUPPORT_KEYS = array( 'image_variants', 'upload_lowercase', 'admin_title_search', 'extra_columns', 'video_uploads', 'content_ids', 'last_login', 'environment_badge', 'media_trash_help', 'menu_duplicate', 'menu_visibility', 'storage_diagnosis', 'profile_fields', 'user_ids', 'avatar_column', 'format_help', 'css_width', 'hide_patterns_admin', 'hide_fonts_admin', 'targets', 'target_types', 'media_size', 'media_size_sort', 'media_types', 'php_footer', 'blog_admin', 'blog_public', 'post_rename', 'check_indexing', 'check_privacy', 'check_imprint', 'dashicons', 'pdf_previews', 'capital_p', 'toolbar_greeting', 'public_search', 'author_archives', 'update_notice', 'comments_disabled', 'comment_autolinks', 'core_widgets', 'login_language', 'admin_email', 'login_logo_text', 'login_logo_url', 'fullscreen', 'welcome', 'block_directory', 'core_patterns', 'remote_patterns', 'template_editor', 'classic_editor_types', 'access_widgets_mode', 'access_widgets_roles', 'access_customizer_mode', 'access_customizer_roles', 'access_menus_mode', 'access_menus_roles', 'menu_fields', 'menu_bricks', 'classic_widgets', 'auto_embeds', 'embed_provider', 'dashboard', 'writing_sections', 'tools_menu', 'adminbar', 'footer', 'favicon', 'rest_discovery', 'oembed_discovery', 'local_avatars', 'file_editors', 'generator', 'rsd', 'wlw', 'shortlink', 'feed_blog', 'feed_comments', 'feed_terms', 'feed_authors', 'feed_search', 'feed_types', 'feed_taxonomies', 'update_email_core', 'update_email_plugins', 'update_email_themes', 'feeds', 'comment_types', 'hide_comments', 'comment_reply', 'self_pingbacks', 'emoji', 'jquery_migrate', 'xmlrpc', 'core_ai', 'heartbeat_front', 'heartbeat_admin', 'heartbeat_editor', 'heartbeat_types', 'revisions', 'revision_types', 'password_admin_email' );
 /** Build active rule rows with explicit scope and applicability. @return array<int,array<string,mixed>> */
 public static function rows(): array {
  $rows = array(); $effective = Settings::effective();
  foreach ( Schema::fields() as $key => $field ) {
   if ( in_array( $key, array( 'feed_links', 'update_emails', 'widgets_admin', 'customizer_admin' ), true ) ) { continue; }
   $network = 'login_language' === $key && Settings::language_locked();
   if ( $effective[ $key ] === $field['default'] && ! $network ) { continue; }
   $rows[] = array( 'key' => $key, 'label' => $field['label'], 'group' => Schema::groups()[ $field['group'] ], 'value' => in_array( $key, array( 'footer_text', 'post_plural', 'post_singular' ), true ) ? __( 'Custom text omitted', 'site-essentials' ) : $effective[ $key ], 'scope' => $network ? __( 'Network policy', 'site-essentials' ) : __( 'This site', 'site-essentials' ), 'applicable' => '' === RuleStatus::code( $key, $effective ), 'reason' => RuleStatus::reason( $key, $effective ), 'reason_code' => RuleStatus::code( $key, $effective ) );
  }
  return $rows;
 }
 /** Generate a report only when explicitly requested by an authorized admin. @return array<string,mixed> */
 public static function diagnostic(): array {
  $rules = array();
  foreach ( self::rows() as $row ) {
   if ( ! in_array( $row['key'], self::SUPPORT_KEYS, true ) ) { continue; }
   $field = Schema::fields()[ $row['key'] ]; $value = $row['value'];
   if ( 'text' === $field['type'] ) { continue; }
   if ( is_array( $value ) ) { $value = array( 'configured_entries' => count( $value ) ); }
   $rules[] = array( 'setting' => $row['key'], 'value' => $value, 'scope' => $row['scope'], 'applicable' => $row['applicable'], 'reason' => $row['reason_code'], 'verification' => 'configuration-only' );
  }

  return array( 'avatar_provider' => Avatars::external_provider() ? 'external-provider' : ( Settings::get( 'local_avatars' ) ? 'site-essentials' : 'wordpress' ), 'plugin' => 'site-essentials', 'version' => SITE_ESSENTIALS_VERSION, 'wordpress' => get_bloginfo( 'version' ), 'php' => PHP_VERSION, 'scope' => is_multisite() ? 'multisite-current-site' : 'single-site', 'network_active' => Settings::network_active(), 'verification' => 'No runtime or compatibility tests performed by this report.', 'rules' => $rules );
 }
 /** Render a compact, escaped read-only handover table for existing administrators. @return void */
 public static function handover(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Customer handover', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Saved active rules and their responsibility. This is a read-only configuration view, not a security or compatibility certificate.', 'site-essentials' ) . '</p><div class="se-status-scroll"><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Setting', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Configured value', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Responsibility', 'site-essentials' ) . '</th><th scope="col">' . esc_html__( 'Verification', 'site-essentials' ) . '</th></tr></thead><tbody>';
  $rows = self::rows();
  foreach ( $rows as $row ) {
   $value = is_bool( $row['value'] ) ? ( $row['value'] ? __( 'Enabled', 'site-essentials' ) : __( 'Disabled', 'site-essentials' ) ) : ( is_array( $row['value'] ) ? wp_json_encode( $row['value'] ) : (string) $row['value'] );
   echo '<tr><th scope="row">' . esc_html( $row['label'] ) . '</th><td>' . esc_html( $value ) . '</td><td>' . esc_html( $row['scope'] ) . '</td><td>' . esc_html( $row['applicable'] ? __( 'Configured; not runtime-tested', 'site-essentials' ) : $row['reason'] ) . '</td></tr>';
  }
  if ( ! $rows ) { echo '<tr><td colspan="4">' . esc_html__( 'No active rules beyond WordPress defaults.', 'site-essentials' ) . '</td></tr>'; }
  echo '</tbody></table></div><p><button type="button" class="button" id="se-handover-print">' . esc_html__( 'Print handover', 'site-essentials' ) . '</button></p></div>';
 }
}
