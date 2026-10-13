<?php
/** Focused editor selection and directional WordPress embedding controls. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Uses core decisions without rewriting posts, widgets or other plugins' settings. */
final class EditorEmbeds {
 /** Register lazy, site-local decisions; all shipped defaults are neutral. @return void */
 public function boot(): void {
  add_filter( 'use_block_editor_for_post_type', array( $this, 'block_editor' ), 100, 2 );
  add_filter( 'use_widgets_block_editor', array( $this, 'widget_editor' ), 100 );
  add_filter( 'is_post_embeddable', array( $this, 'embeddable' ), 100 );
  add_action( 'init', array( $this, 'automatic_embeds' ), 100 );
  if ( is_admin() ) { add_action( 'admin_notices', array( $this, 'editor_notice' ) ); }
  add_action( 'template_redirect', array( $this, 'embed_request' ), 0 );
 }
 /** Keep incoming editor restrictions and use the classic editor only for selected types.
  * @param bool $enabled Incoming block editor decision.
  * @param string $post_type Registered content type.
  * @return bool
  */
 public function block_editor( bool $enabled, string $post_type ): bool {
  return in_array( $post_type, Settings::get( 'classic_editor_types' ), true ) ? false : $enabled;
 }
 /** Warn only while editing existing block content through an explicitly selected classic interface. @return void */
 public function editor_notice(): void {
  $screen = get_current_screen(); $post = $GLOBALS['post'] ?? null;
  if ( ! $screen || 'post' !== $screen->base || $screen->is_block_editor() || ! $post instanceof \WP_Post || ! current_user_can( 'edit_post', $post->ID ) || ! in_array( $post->post_type, Settings::get( 'classic_editor_types' ), true ) || ! has_blocks( $post->post_content ) ) { return; }
  echo '<div class="notice notice-warning"><p>' . esc_html__( 'This post contains blocks. Site Essentials selected the classic editor for this content type. Check complex block content before saving; editing it here may change its markup.', 'site-essentials' );
  if ( current_user_can( 'manage_options' ) ) { echo ' <a href="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-editor' ) ) . '">' . esc_html__( 'Review editor selection', 'site-essentials' ) . '</a>'; }
  echo '</p></div>';
 }
 /** Replace the widget editing interface without changing stored widgets or theme support.
  * @param bool $enabled Incoming widget editor decision.
  * @return bool
  */
 public function widget_editor( bool $enabled ): bool { return Settings::get( 'classic_widgets' ) ? false : $enabled; }
 /** Let core omit discovery and reject own public oEmbed data while preserving stronger restrictions.
  * @param bool $enabled Incoming embeddability.
  * @return bool
  */
 public function embeddable( bool $enabled ): bool { return Settings::get( 'embed_provider' ) ? false : $enabled; }
 /** Remove only automatic URL conversion; authored embeds and shortcode handlers stay registered. @return void */
 public function automatic_embeds(): void {
  global $wp_embed;
  if ( ! Settings::get( 'auto_embeds' ) || ! $wp_embed instanceof \WP_Embed ) { return; }
  foreach ( array( 'the_content', 'widget_text_content', 'widget_block_content' ) as $hook ) {
   remove_filter( $hook, array( $wp_embed, 'autoembed' ), 8 );
   add_filter( $hook, array( $this, 'automatic_url' ), 8 );
  }
 }
 /** Preserve block-based documents while stopping automatic URLs in explicitly configured classic content.
  * @param string $content Filtered content; shortcode expansion has already run.
  * @return string
  */
 public function automatic_url( string $content ): string {
  global $wp_embed;
  if ( ! $wp_embed instanceof \WP_Embed || ( Settings::get( 'auto_embeds' ) && ! has_blocks( $content ) ) ) { return $content; }
  return $wp_embed->autoembed( $content );
 }
 /** Deny the own core embed document before canonical redirection or template rendering. @return void */
 public function embed_request(): void {
  if ( Settings::get( 'embed_provider' ) && is_embed() ) {
   wp_die( esc_html__( 'WordPress post embeddings are disabled on this site.', 'site-essentials' ), '', array( 'response' => 404 ) );
  }
 }
}
