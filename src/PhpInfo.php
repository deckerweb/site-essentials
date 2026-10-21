<?php
/** Administrator-only allowlisted PHP diagnostics without phpinfo output. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Exposes current-request limits and available runtime support, never environment secrets. */
final class PhpInfo {
 /** Register the local information page and optional native footer summary. @return void */
 public function boot(): void {
  if ( ! is_admin() ) { return; }
  add_action( 'admin_menu', array( $this, 'menu' ) );
  add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
  add_filter( 'update_footer', array( $this, 'footer' ), 110 );
 }
 /** Add a read-only Tools page protected by the local administrator capability. @return void */
 public function menu(): void { add_management_page( 'Site Essentials: PHP', 'Site Essentials: PHP', 'manage_options', 'site-essentials-php', array( $this, 'page' ) ); }
 /** Load the existing own styles on this information page only.
  * @param string $hook Admin hook suffix.
  * @return void
  */
 public function assets( string $hook ): void { if ( 'tools_page_site-essentials-php' === $hook ) { wp_enqueue_style( 'site-essentials-admin', plugins_url( 'assets/admin.css', SITE_ESSENTIALS_FILE ), array(), SITE_ESSENTIALS_VERSION ); } }
 /** Read one explicitly selected directive; unavailable is distinct from zero.
  * @param string $key Allowlisted directive.
  * @return string
  */
 private function ini( string $key ): string { $value = function_exists( 'ini_get' ) ? ini_get( $key ) : false; return false === $value ? __( 'Unavailable', 'site-essentials' ) : ( '' === $value ? '0' : $value ); }
 /** Build the compact current-request memory summary without collecting server data. @return array */
 public function summary(): array { return array( 'PHP' => PHP_VERSION, 'memory_limit' => $this->ini( 'memory_limit' ), 'WP_MEMORY_LIMIT' => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Unavailable', 'site-essentials' ), 'WP_MAX_MEMORY_LIMIT' => defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : __( 'Unavailable', 'site-essentials' ) ); }
 /** Append a linked status line only for local administrators when explicitly enabled.
  * @param string $text Native WordPress version footer.
  * @return string
  */
 public function footer( string $text ): string {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ! Settings::get( 'php_footer' ) ) { return $text; }
  $s = $this->summary();
  /* translators: %1$s: PHP version; %2$s: PHP memory limit; %3$s: WordPress memory limit; %4$s: WordPress admin memory limit. */
  return $text . ' · <a href="' . esc_url( admin_url( 'tools.php?page=site-essentials-php' ) ) . '">' . esc_html( sprintf( __( 'PHP %1$s · PHP limit %2$s · WP %3$s / admin %4$s', 'site-essentials' ), $s['PHP'], $s['memory_limit'], $s['WP_MEMORY_LIMIT'], $s['WP_MAX_MEMORY_LIMIT'] ) ) . '</a>';
 }
 /** Collect only safe diagnostic values, with no paths, connection credentials or full environment. @return array */
 public function data(): array {
  $limits = $this->summary(); $limits['SAPI'] = PHP_SAPI; $limits['WordPress'] = get_bloginfo( 'version' ); $limits['Current request allocated memory'] = size_format( memory_get_usage( true ) ); $limits['Current request peak memory'] = size_format( memory_get_peak_usage( true ) );
  foreach ( array( 'upload_max_filesize', 'post_max_size', 'max_file_uploads', 'file_uploads', 'max_execution_time', 'max_input_time', 'max_input_vars', 'max_input_nesting_level', 'default_socket_timeout' ) as $key ) { $limits[ $key ] = $this->ini( $key ); }
  $limits['WordPress upload limit'] = size_format( wp_max_upload_size() );
  $extensions = array(); foreach ( array( 'curl', 'openssl', 'mbstring', 'intl', 'zip', 'gd', 'imagick', 'mysqli', 'pdo_mysql', 'exif', 'fileinfo' ) as $key ) { $extensions[ $key ] = extension_loaded( $key ) ? __( 'Available', 'site-essentials' ) : __( 'Not available', 'site-essentials' ); }
  $caches = array( 'OPcache extension' => extension_loaded( 'Zend OPcache' ) ? __( 'Available', 'site-essentials' ) : __( 'Not available', 'site-essentials' ), 'opcache.enable' => $this->ini( 'opcache.enable' ), 'opcache.enable_cli' => $this->ini( 'opcache.enable_cli' ), 'OPcache runtime' => __( 'Not checked', 'site-essentials' ), 'APCu extension' => extension_loaded( 'apcu' ) ? __( 'Available', 'site-essentials' ) : __( 'Not available', 'site-essentials' ), 'APCu runtime' => function_exists( 'apcu_enabled' ) ? ( apcu_enabled() ? __( 'Active', 'site-essentials' ) : __( 'Inactive', 'site-essentials' ) ) : __( 'Unavailable', 'site-essentials' ), 'Redis extension' => extension_loaded( 'redis' ) ? __( 'Available', 'site-essentials' ) : __( 'Not available', 'site-essentials' ), 'Memcached extension' => extension_loaded( 'memcached' ) ? __( 'Available', 'site-essentials' ) : __( 'Not available', 'site-essentials' ), 'WordPress external object cache' => wp_using_ext_object_cache() ? __( 'Active', 'site-essentials' ) : __( 'Inactive', 'site-essentials' ), 'WP_CACHE' => defined( 'WP_CACHE' ) && WP_CACHE ? '1' : '0' );
  // Restrict-api may deny inspection. Do not probe it or emit runtime warnings.
  if ( function_exists( 'opcache_get_status' ) && '0' === $this->ini( 'opcache.restrict_api' ) ) { $status = opcache_get_status( false ); $caches['OPcache runtime'] = is_array( $status ) && ! empty( $status['opcache_enabled'] ) ? __( 'Active', 'site-essentials' ) : __( 'Inactive', 'site-essentials' ); }
  $suhosin = array( 'Suhosin extension' => extension_loaded( 'suhosin' ) ? __( 'Available', 'site-essentials' ) : __( 'Not available', 'site-essentials' ), 'Suhosin patch' => defined( 'SUHOSIN_PATCH' ) ? __( 'Detected', 'site-essentials' ) : __( 'Not detected', 'site-essentials' ) );
  foreach ( array( 'suhosin.simulation', 'suhosin.request.max_vars', 'suhosin.post.max_vars', 'suhosin.request.max_array_depth', 'suhosin.post.max_array_depth', 'suhosin.request.max_totalname_length', 'suhosin.post.max_totalname_length' ) as $key ) { $suhosin[ $key ] = $this->ini( $key ); }
  return array( 'limits' => $limits, 'extensions' => $extensions, 'caches' => $caches, 'suhosin' => $suhosin );
 }
 /** Render an escaped accessible two-column table.
  * @param array $rows Safe display values.
  * @return void
  */
 private function table( array $rows ): void { echo '<table class="widefat striped"><tbody>'; foreach ( $rows as $label => $value ) { echo '<tr><th scope="row">' . esc_html( str_replace( array( ' extension', ' runtime' ), array( ' ' . __( 'extension', 'site-essentials' ), ' ' . __( 'runtime status', 'site-essentials' ) ), array( 'WordPress upload limit' => __( 'WordPress upload limit', 'site-essentials' ), 'WordPress external object cache' => __( 'WordPress external object cache', 'site-essentials' ), 'Current request allocated memory' => __( 'Current request allocated memory', 'site-essentials' ), 'Current request peak memory' => __( 'Current request peak memory', 'site-essentials' ), 'Suhosin patch' => __( 'Suhosin patch', 'site-essentials' ) )[ $label ] ?? $label ) ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>'; } echo '</tbody></table>'; }
 /** Render the administrator information page with optional cache and Suhosin details. @return void */
 public function page(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You cannot view PHP information.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $data = $this->data();
  echo '<div class="wrap se-root"><header class="se-header"><img class="se-brand-icon" src="' . esc_url( plugins_url( 'assets/images/icon.svg', SITE_ESSENTIALS_FILE ) ) . '" width="56" height="56" alt="" aria-hidden="true"><div><h1>' . esc_html__( 'PHP and WordPress limits', 'site-essentials' ) . '</h1><p>Site Essentials ' . esc_html( SITE_ESSENTIALS_VERSION ) . '</p></div></header><hr class="wp-header-end"><div class="se-card"><p>' . esc_html__( 'Current web request values. PHP limits and WordPress memory targets are configuration values, not available or reserved RAM. A PHP memory_limit of -1 means no PHP limit; hosting limits can still apply.', 'site-essentials' ) . '</p>'; $this->table( $data['limits'] );
  echo '<p>' . esc_html__( 'Large menus and forms can exceed max_input_vars or input nesting limits. Suhosin may add separate limits. These figures do not prove that a particular menu can be saved.', 'site-essentials' ) . '</p></div><details class="se-card"><summary>' . esc_html__( 'WordPress-related PHP extensions', 'site-essentials' ) . '</summary>'; $this->table( $data['extensions'] ); echo '</details><details class="se-card"><summary>' . esc_html__( 'Cache support', 'site-essentials' ) . '</summary><p>' . esc_html__( 'An available client extension does not mean that a cache service is connected. WP_CACHE does not prove that page caching works. No cache connections, resets or configuration changes are performed.', 'site-essentials' ) . '</p>'; $this->table( $data['caches'] );
  echo '</details><details class="se-card"><summary>Suhosin</summary><p>' . esc_html__( 'Reported only when the runtime exposes these legacy hardening directives. Missing values mean unavailable, not unlimited. Menu input limits are distinct from recursion or PHP execution limits.', 'site-essentials' ) . '</p>'; $this->table( $data['suhosin'] ); echo '</details><p><a href="' . esc_url( admin_url( 'site-health.php?tab=debug' ) ) . '">' . esc_html__( 'Open native Site Health information', 'site-essentials' ) . '</a></p></div>';
 }
}
