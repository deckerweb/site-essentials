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
 public function menu(): void { add_management_page( __( 'System information and storage', 'site-essentials' ), __( 'System information and storage', 'site-essentials' ), 'manage_options', 'site-essentials-php', array( $this, 'page' ) ); }
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
  return $text . ' · <a href="' . esc_url( admin_url( 'tools.php?page=site-essentials-php#se-system-memory' ) ) . '" title="' . esc_attr__( 'System information and storage', 'site-essentials' ) . '">' . esc_html( sprintf( __( 'PHP %1$s · PHP limit %2$s · WP %3$s / admin %4$s', 'site-essentials' ), $s['PHP'], $s['memory_limit'], $s['WP_MEMORY_LIMIT'], $s['WP_MAX_MEMORY_LIMIT'] ) ) . '</a>';
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
 /** Render selected diagnostic keys with human-readable labels and explicit units.
  * @param array $values Current allowlisted diagnostics.
  * @param array $labels Key-to-label mapping for this section.
  * @return void
  */
 private function section_table( array $values, array $labels ): void {
  echo '<table class="widefat striped se-system-table"><tbody>';
  foreach ( $labels as $key => $label ) {
   if ( ! array_key_exists( $key, $values ) ) { continue; }
   $value = (string) $values[ $key ];
   if ( 'file_uploads' === $key ) { $value = '1' === $value ? __( 'Allowed', 'site-essentials' ) : ( '0' === $value ? __( 'Disabled', 'site-essentials' ) : $value ); }
   if ( 'max_input_time' === $key && '-1' === $value ) { $value = __( 'Uses maximum execution time (-1)', 'site-essentials' ); }
   elseif ( 'max_execution_time' === $key && '0' === $value ) { $value = __( 'No PHP execution time limit (0)', 'site-essentials' ); }
   elseif ( in_array( $key, array( 'max_execution_time', 'max_input_time', 'default_socket_timeout' ), true ) && is_numeric( $value ) ) {
    /* translators: %s: configured number of seconds. */
    $value = sprintf( __( '%s seconds', 'site-essentials' ), $value );
   }
   echo '<tr><th scope="row">' . esc_html( $label );
   if ( preg_match( '/^(?:[a-z]+_|WP_)/', $key ) ) { echo '<br><code>' . esc_html( $key ) . '</code>'; }
   echo '</th><td>' . esc_html( $value ) . '</td></tr>';
  }
  echo '</tbody></table>';
 }
 /** Render read-only grouped diagnostics and cached upload storage with native anchor navigation. @return void */
 public function page(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You cannot view PHP information.', 'site-essentials' ), '', array( 'response' => 403 ) ); }
  $data = $this->data();
  $sections = array( 'se-system-versions' => __( 'WordPress and PHP', 'site-essentials' ), 'se-system-memory' => __( 'Memory and execution time', 'site-essentials' ), 'se-system-uploads' => __( 'Uploads and forms', 'site-essentials' ), 'se-upload-storage' => __( 'Upload file storage', 'site-essentials' ), 'se-system-details' => __( 'Further technical details', 'site-essentials' ) );
  echo '<div class="wrap se-root se-system"><header class="se-header"><img class="se-brand-icon" src="' . esc_url( plugins_url( 'assets/images/icon.svg', SITE_ESSENTIALS_FILE ) ) . '" width="56" height="56" alt="" aria-hidden="true"><div><h1>' . esc_html__( 'System information and storage', 'site-essentials' ) . '</h1><p>Site Essentials ' . esc_html( SITE_ESSENTIALS_VERSION ) . '</p></div></header><hr class="wp-header-end"><p>' . esc_html__( 'Information about WordPress, PHP, upload limits and the storage used by uploaded files. This overview does not change server settings.', 'site-essentials' ) . '</p><p><a href="' . esc_url( admin_url( 'site-health.php?tab=debug' ) ) . '">' . esc_html__( 'Open native Site Health information', 'site-essentials' ) . '</a></p><nav class="se-system-jumps" aria-label="' . esc_attr__( 'On this page', 'site-essentials' ) . '"><ul>';
  foreach ( $sections as $id => $title ) { echo '<li><a href="#' . esc_attr( $id ) . '">' . esc_html( $title ) . '</a></li>'; }
  echo '</ul></nav><section class="se-card" id="se-system-versions" aria-labelledby="se-system-versions-title"><h2 id="se-system-versions-title">' . esc_html( $sections['se-system-versions'] ) . '</h2>';
  $this->section_table( $data['limits'], array( 'WordPress' => __( 'WordPress version', 'site-essentials' ), 'PHP' => __( 'PHP version', 'site-essentials' ) ) );
  echo '</section><section class="se-card" id="se-system-memory" aria-labelledby="se-system-memory-title"><h2 id="se-system-memory-title">' . esc_html( $sections['se-system-memory'] ) . '</h2><p>' . esc_html__( 'Current web request values. PHP limits and WordPress memory targets are configuration values, not available or reserved RAM. A PHP memory_limit of -1 means no PHP limit; hosting limits can still apply.', 'site-essentials' ) . '</p>';
  $this->section_table( $data['limits'], array( 'memory_limit' => __( 'PHP memory limit', 'site-essentials' ), 'WP_MEMORY_LIMIT' => __( 'WordPress memory target', 'site-essentials' ), 'WP_MAX_MEMORY_LIMIT' => __( 'WordPress administration memory target', 'site-essentials' ), 'Current request allocated memory' => __( 'Memory allocated for this request', 'site-essentials' ), 'Current request peak memory' => __( 'Peak memory for this request', 'site-essentials' ), 'max_execution_time' => __( 'Maximum execution time', 'site-essentials' ), 'max_input_time' => __( 'Maximum input processing time', 'site-essentials' ), 'default_socket_timeout' => __( 'Default connection timeout', 'site-essentials' ) ) );
  echo '</section><section class="se-card" id="se-system-uploads" aria-labelledby="se-system-uploads-title"><h2 id="se-system-uploads-title">' . esc_html( $sections['se-system-uploads'] ) . '</h2>';
  $this->section_table( $data['limits'], array( 'WordPress upload limit' => __( 'Effective WordPress upload limit', 'site-essentials' ), 'upload_max_filesize' => __( 'Maximum size per uploaded file', 'site-essentials' ), 'post_max_size' => __( 'Maximum request size', 'site-essentials' ), 'max_file_uploads' => __( 'Maximum files per request', 'site-essentials' ), 'file_uploads' => __( 'File uploads', 'site-essentials' ), 'max_input_vars' => __( 'Maximum form fields', 'site-essentials' ), 'max_input_nesting_level' => __( 'Maximum input nesting depth', 'site-essentials' ) ) );
  echo '<p>' . esc_html__( 'Large menus and forms can exceed max_input_vars or input nesting limits. Suhosin may add separate limits. These figures do not prove that a particular menu can be saved.', 'site-essentials' ) . '</p></section>';
  Storage110::panel();
  echo '<section class="se-card" id="se-system-details" aria-labelledby="se-system-details-title"><h2 id="se-system-details-title">' . esc_html( $sections['se-system-details'] ) . '</h2><p>' . esc_html__( 'Technical runtime information for troubleshooting. Available extensions do not guarantee that an external service is connected.', 'site-essentials' ) . '</p>';
  $this->section_table( $data['limits'], array( 'SAPI' => __( 'PHP execution interface', 'site-essentials' ) ) );
  echo '<details><summary>' . esc_html__( 'WordPress-related PHP extensions', 'site-essentials' ) . '</summary>'; $this->table( $data['extensions'] );
  echo '</details><details><summary>' . esc_html__( 'Cache support', 'site-essentials' ) . '</summary><p>' . esc_html__( 'An available client extension does not mean that a cache service is connected. WP_CACHE does not prove that page caching works. No cache connections, resets or configuration changes are performed.', 'site-essentials' ) . '</p>'; $this->table( $data['caches'] );
  echo '</details>';
  echo '<details><summary>Suhosin</summary><p>' . esc_html__( 'Reported only when the runtime exposes these legacy hardening directives. Missing values mean unavailable, not unlimited. Menu input limits are distinct from recursion or PHP execution limits.', 'site-essentials' ) . '</p>'; $this->table( $data['suhosin'] ); echo '</details>';
  echo '</section><p><a href="' . esc_url( admin_url( 'site-health.php?tab=debug' ) ) . '">' . esc_html__( 'Open native Site Health information', 'site-essentials' ) . '</a></p></div>';
 }
}
