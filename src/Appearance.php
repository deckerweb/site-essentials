<?php
/** Native appearance-screen controls. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Keeps frontend theme output intact while restricting selected native admin screens. */
final class Appearance {
 /** Register site-scoped menu, toolbar and direct-screen controls. @return void */
 public function boot(): void {
  add_action( 'admin_menu', array( $this, 'menus' ), PHP_INT_MAX );
  add_action( 'admin_head', array( $this, 'menus' ), PHP_INT_MAX );
  add_action( 'load-widgets.php', array( $this, 'widgets_access' ) );
  add_action( 'load-customize.php', array( $this, 'customizer_access' ) );
  add_action( 'admin_bar_menu', array( $this, 'toolbar' ), PHP_INT_MAX );
 }
 /** Detect a classic Bricks parent/child theme, not unrelated third-party plugins. @return bool */
 public function bricks(): bool { return 'bricks' === strtolower( (string) get_template() ) && ! wp_is_block_theme(); }
 /** Remove exact native screen targets, including query-string menu variants. @return void */
 public function menus(): void {
  if ( is_network_admin() ) { return; }
  global $submenu;
  foreach ( $submenu['themes.php'] ?? array() as $item ) {
   if ( ! isset( $item[2] ) || ! is_string( $item[2] ) ) { continue; }
   $target = strtok( html_entity_decode( $item[2], ENT_QUOTES, 'UTF-8' ), '?' );
   if ( ( 'site-editor.php' === $target && ( $this->bricks() || Settings::get( 'template_editor' ) ) ) ||
        ( 'nav-menus.php' === $target && false === Access::decision( 'menus' ) ) ||
        ( 'widgets.php' === $target && false === Access::decision( 'widgets' ) ) ||
        ( 'customize.php' === $target && false === Access::decision( 'customizer' ) ) ) { remove_submenu_page( 'themes.php', $item[2] ); }
  }
 }
 /** Block the selected native Widgets screen without unregistering widgets. @return void */
 public function widgets_access(): void { if ( ! is_network_admin() && false === Access::decision( 'widgets' ) ) { wp_die( esc_html__( 'The Widgets admin screen is disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); } }
 /** Block the selected native Customizer screen without changing stored theme options. @return void */
 public function customizer_access(): void { if ( ! is_network_admin() && false === Access::decision( 'customizer' ) ) { wp_die( esc_html__( 'The Customizer is disabled on this site.', 'site-essentials' ), '', array( 'response' => 403 ) ); } }
 /** Remove the native customization shortcut when the screen is disabled.
  * @param \WP_Admin_Bar $bar Current toolbar.
  * @return void
  */
 public function toolbar( \WP_Admin_Bar $bar ): void { if ( ! is_network_admin() && false === Access::decision( 'customizer' ) ) { $bar->remove_node( 'customize' ); } }
}
