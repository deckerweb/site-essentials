<?php
/**
 * Plugin Name: Site Essentials
 * Plugin URI: https://github.com/deckerweb/site-essentials
 * Description: Configure WordPress core behavior with focused controls, reusable presets and site-specific settings.
 * Version: 1.0.0
 * Requires at least: 7.1
 * Requires PHP: 8.2
 * Author: David Decker – DECKERWEB
 * Author URI: https://github.com/deckerweb
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: site-essentials
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/site-essentials
 * GitHub Plugin URI: https://github.com/deckerweb/site-essentials
 *
 * Copyright © 2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'SITE_ESSENTIALS_FILE', __FILE__ );
define( 'SITE_ESSENTIALS_VERSION', '1.0.0' );
foreach ( array( 'Schema', 'Settings', 'Presets', 'Collections', 'Runtime', 'Media', 'AlternativeTargets', 'MenuInterface', 'Access', 'Appearance', 'CoreExtras', 'Comfort', 'Maintenance', 'Blog', 'PhpInfo', 'WebsiteChecks', 'PublicChecks', 'EditorEmbeds', 'SiteIdentity', 'CommentsOff', 'Avatars', 'Reports', 'Integrations', 'Updates', 'Admin' ) as $site_essentials_class ) {
 require_once __DIR__ . '/src/' . $site_essentials_class . '.php';
}
require_once __DIR__ . '/includes/deckerweb-plugin-library/bootstrap.php';
deckerweb_library_register_v2( __FILE__, array(), __DIR__ . '/includes/deckerweb-plugin-library' );
if ( ! class_exists( '\Deckerweb\GitHubReleaseUpdater\V2\Updater' ) ) {
 require_once __DIR__ . '/includes/deckerweb-github-release-updater-v2.php';
}
register_activation_hook( __FILE__, array( '\Deckerweb\SiteEssentials\Settings', 'activate' ) );
add_action( 'plugins_loaded', static function (): void {
 ( new \Deckerweb\SiteEssentials\Runtime() )->boot();
 ( new \Deckerweb\SiteEssentials\Media() )->boot();
 ( new \Deckerweb\SiteEssentials\AlternativeTargets() )->boot();
 ( new \Deckerweb\SiteEssentials\MenuInterface() )->boot();
 ( new \Deckerweb\SiteEssentials\Access() )->boot();
 ( new \Deckerweb\SiteEssentials\Appearance() )->boot();
 ( new \Deckerweb\SiteEssentials\CoreExtras() )->boot();
 ( new \Deckerweb\SiteEssentials\Comfort() )->boot();
 ( new \Deckerweb\SiteEssentials\Maintenance() )->boot();
 ( new \Deckerweb\SiteEssentials\Blog() )->boot();
 ( new \Deckerweb\SiteEssentials\PhpInfo() )->boot();
 ( new \Deckerweb\SiteEssentials\WebsiteChecks() )->boot();
 ( new \Deckerweb\SiteEssentials\PublicChecks() )->boot();
 ( new \Deckerweb\SiteEssentials\EditorEmbeds() )->boot();
 ( new \Deckerweb\SiteEssentials\SiteIdentity() )->boot();
 ( new \Deckerweb\SiteEssentials\CommentsOff() )->boot();
 ( new \Deckerweb\SiteEssentials\Avatars() )->boot();
 ( new \Deckerweb\SiteEssentials\Integrations() )->boot();
 if ( is_admin() ) { ( new \Deckerweb\SiteEssentials\Admin() )->boot(); }

} );
add_action( 'init', static function (): void {
 load_plugin_textdomain( 'site-essentials', false, dirname( plugin_basename( SITE_ESSENTIALS_FILE ) ) . '/languages/' );
} );

add_action( 'init', array( '\Deckerweb\SiteEssentials\Updates', 'register' ), 20 );
