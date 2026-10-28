<?php
/** Known configuration relationships, without probing third-party callbacks. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
defined( 'ABSPATH' ) || exit;
/** Share saved-rule explanations across reports and Site Health. */
final class RuleStatus {
 /** Stable reason identifiers with translated descriptions. @return array<string,string> */
 public static function reasons(): array { return array(
  'avatars' => __( 'Another provider manages local avatars. Your settings and image assignments are retained.', 'site-essentials' ),
  'avatar_off' => __( 'The local avatar module is disabled', 'site-essentials' ),
  'bas' => __( 'Brand Admin Schemes manages the environment indicator. Your choice is retained.', 'site-essentials' ),
  'target_provider' => __( 'Page Links To manages destinations. Your choice is retained.', 'site-essentials' ),
  'target_off' => __( 'Alternative destinations require an enabled module and available content types.', 'site-essentials' ),
  'roles' => __( 'Allowed roles apply only when selected-role access is enabled.', 'site-essentials' ),
  'single' => __( 'The site-local file-editor setting does not apply on Multisite', 'site-essentials' ),
  'comments' => __( 'Overridden by complete comment shutdown', 'site-essentials' ),
  'comment_types' => __( 'No content types selected for comment restrictions', 'site-essentials' ),
  'footer' => __( 'Custom footer text takes priority', 'site-essentials' ),
  'names' => __( 'Requires enabled renaming and both names', 'site-essentials' ),
  'types' => __( 'Selected content types are currently unavailable', 'site-essentials' ),
  'blog' => __( 'Native post management is disabled', 'site-essentials' ),
 ); }
 /** Explain a known pause from supplied saved or draft values.
  * @param string $key Setting key.
  * @param array $values Normalized values.
  * @return string Stable reason, never a safety verdict.
  */
 public static function code( string $key, array $values ): string {
  if ( in_array( $key, array( 'local_avatars', 'avatar_column' ), true ) && Avatars::external_provider() ) { return 'avatars'; }
  if ( 'environment_badge' === $key && Essentials110::bas_provider() ) { return 'bas'; }
  if ( 'avatar_column' === $key && ! $values['local_avatars'] ) { return 'avatar_off'; }
  if ( in_array( $key, array( 'targets', 'target_types' ), true ) ) {
   if ( AlternativeTargets::provider() ) { return 'target_provider'; }
   if ( ! $values['targets'] || ! array_intersect( $values['target_types'], array_diff( get_post_types( array( 'public' => true, 'show_ui' => true ) ), array( 'attachment', 'revision', 'nav_menu_item' ) ) ) ) { return 'target_off'; }
  }
  if ( preg_match( '/^access_(widgets|customizer|menus)_roles$/', $key ) && 2 !== $values[ str_replace( '_roles', '_mode', $key ) ] ) { return 'roles'; }
  if ( 'file_editors' === $key && is_multisite() ) { return 'single'; }
  if ( $values['comments_disabled'] && in_array( $key, array( 'comment_types', 'hide_comments', 'comment_reply', 'self_pingbacks' ), true ) ) { return 'comments'; }
  if ( 'hide_comments' === $key && ! $values['comment_types'] ) { return 'comment_types'; }
  if ( 'footer' === $key && trim( $values['footer_text'] ) ) { return 'footer'; }
  if ( in_array( $key, array( 'post_rename', 'post_plural', 'post_singular' ), true ) && ( ! $values['post_rename'] || ! trim( $values['post_plural'] ) || ! trim( $values['post_singular'] ) ) ) { return 'names'; }
  if ( ! in_array( $key, array( 'classic_editor_types', 'comment_types', 'heartbeat_types', 'revision_types' ), true ) ) { return ''; }
  $field = Schema::fields()[ $key ] ?? array(); $dynamic = $field['dynamic'] ?? '';
  if ( $dynamic && ! in_array( $dynamic, array( 'roles', 'feed_types', 'feed_taxonomies' ), true ) && in_array( $field['type'], array( 'list', 'map' ), true ) ) {
   $types = 'list' === $field['type'] ? $values[ $key ] : array_keys( $values[ $key ] );
   if ( $types && ! array_intersect( $types, get_post_types() ) ) { return 'types'; }
   if ( 'classic_editor_types' === $key && $values['blog_admin'] && $types && ! array_diff( $types, array( 'post' ) ) ) { return 'blog'; }
  }
  return '';
 }
 /** Translate a known reason.
  * @param string $key Setting key.
  * @param array $values Normalized values.
  * @return string Description, empty if no known restriction.
  */
 public static function reason( string $key, array $values ): string { return self::reasons()[ self::code( $key, $values ) ] ?? ''; }
}
