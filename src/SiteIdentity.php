<?php
/** Optional core favicon and login logo normalization. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Reads existing site identity without changing icons, names or login behavior. */
final class SiteIdentity {
 /** Register independent neutral controls through public core hooks. @return void */
 public function boot(): void {
  add_filter( 'login_headertext', array( $this, 'login_text' ), 100 );
  add_filter( 'login_headerurl', array( $this, 'login_url' ), 100 );
  add_filter( 'get_site_icon_url', array( $this, 'favicon' ), 100, 3 );
  add_action( 'do_faviconico', array( $this, 'favicon_request' ), 100 );
 }
 /** Use escaped text because core renders this filtered value directly inside the login link.
  * @param string $text Incoming link text.
  * @return string
  */
 public function login_text( string $text ): string {
  if ( ! Settings::get( 'login_logo_text' ) ) { return $text; }
  $name = (string) get_option( 'blogname', '' );
  return '' === trim( wp_strip_all_tags( $name ) ) ? $text : esc_html( $name );
 }
 /** Change the logo target, never authentication or post-login redirects.
  * @param string $url Incoming link URL.
  * @return string
  */
 public function login_url( string $url ): string { return Settings::get( 'login_logo_url' ) ? home_url( '/' ) : $url; }
 /** Remove exact core fallback URLs in the requested site's context; custom identity stays intact.
  * @param string $url Resolved favicon URL.
  * @param int $size Requested size.
  * @param int $blog_id Requested site, zero for current site.
  * @return string
  */
 public function favicon( string $url, int $size, int $blog_id ): string {
  // Core restores the caller's blog before running this filter for a requested subsite.
  $switched = is_multisite() && $blog_id > 0 && get_current_blog_id() !== $blog_id;
  if ( $switched ) { switch_to_blog( $blog_id ); }
  try {
   if ( $switched && ! Settings::network_active() && ! in_array( plugin_basename( SITE_ESSENTIALS_FILE ), get_option( 'active_plugins', array() ), true ) ) { return $url; }
   if ( ! Settings::get( 'favicon' ) || (int) get_option( 'site_icon' ) > 0 ) { return $url; }
   $defaults = array( includes_url( 'images/w-logo-blue-white-bg.png' ), includes_url( 'images/w-logo-gray-white-bg.png' ) );
   return in_array( $url, $defaults, true ) ? '' : $url;
  } finally {
   if ( $switched ) { restore_current_blog(); }
  }
 }
 /** End a core favicon request without an empty redirect when normalization removed its fallback. @return void */
 public function favicon_request(): void {
  if ( ! Settings::get( 'favicon' ) || '' !== get_site_icon_url( 32, includes_url( 'images/w-logo-gray-white-bg.png' ) ) ) { return; }
  status_header( 204 ); nocache_headers(); exit;
 }
}
