<?php
/** Preserve configuration and clean component-owned temporary data. @package SiteEssentials */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
$site_essentials_updater_class = require __DIR__ . '/includes/deckerweb-github-release-updater-v2.php';
$site_essentials_updater_class::clear_repository_cache( 'https://github.com/deckerweb/site-essentials' );
$site_essentials_update_key = 'ddw_ghru_' . substr( md5( 'https://github.com/deckerweb/site-essentials' ), 0, 24 );
delete_site_transient( $site_essentials_update_key );
if ( is_multisite() ) {
 foreach ( get_networks( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_essentials_network_id ) {
  $site_essentials_versioned_key = 'ddw_ghru_' . substr( md5( 'https://github.com/deckerweb/site-essentials|' . $site_essentials_updater_class::IMPLEMENTATION_VERSION ), 0, 24 );
  foreach ( array( $site_essentials_update_key, $site_essentials_versioned_key ) as $site_essentials_cache_key ) {
   foreach ( array( '', '_private' ) as $site_essentials_cache_suffix ) {
    delete_network_option( $site_essentials_network_id, '_site_transient_' . $site_essentials_cache_key . $site_essentials_cache_suffix );
    delete_network_option( $site_essentials_network_id, '_site_transient_timeout_' . $site_essentials_cache_key . $site_essentials_cache_suffix );
   }
  }
  delete_network_option( $site_essentials_network_id, '_site_transient_' . $site_essentials_update_key );
  delete_network_option( $site_essentials_network_id, '_site_transient_timeout_' . $site_essentials_update_key );
 }
}
require_once __DIR__ . '/src/Storage110.php';
$site_essentials_cleanup_sites = is_multisite() ? get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => 0 ) ) : array( get_current_blog_id() );
$site_essentials_cleanup_offset = 0;
do {
 foreach ( $site_essentials_cleanup_sites as $site_essentials_site_id ) {
  if ( is_multisite() ) { switch_to_blog( (int) $site_essentials_site_id ); }
  \Deckerweb\SiteEssentials\Storage110::clear();
  delete_metadata( 'user', 0, '_site_essentials_last_login_' . get_current_blog_id(), '', true );
  if ( is_multisite() ) { restore_current_blog(); }
 }
 $site_essentials_cleanup_offset += 100;
 $site_essentials_cleanup_sites = is_multisite() && count( $site_essentials_cleanup_sites ) === 100 ? get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $site_essentials_cleanup_offset ) ) : array();
} while ( $site_essentials_cleanup_sites );
require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v3( __DIR__ . '/site-essentials.php' );
