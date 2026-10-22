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
require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v3( __DIR__ . '/site-essentials.php' );
