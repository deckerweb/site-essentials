<?php
/** On-demand, read-only public website inspection. @package SiteEssentials */
namespace Deckerweb\SiteEssentials;
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Keeps public checks bounded, anonymous and independent of stored configuration. */
final class PublicChecks {
 /** Register the detached manual-check form. @return void */
 public function boot(): void { if ( is_admin() ) { add_action( 'admin_footer', array( $this, 'form' ) ); } }
 /** Return the host-root robots address, including a configured nonstandard port.
  * @return string Public robots URL, independent of a subsite path.
  */
 public static function robots_url(): string {
  $parts = wp_parse_url( home_url( '/' ) );
  return $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) . '/robots.txt';
 }
 /** Fetch one fixed local-site address without credentials or automatic redirects.
  * @param string $url Website address chosen by this module.
  * @return array|\WP_Error Bounded native HTTP response or error.
  */
 private static function fetch( string $url ) {
  return wp_safe_remote_get( $url, array( 'timeout' => 5, 'redirection' => 0, 'limit_response_size' => 262144, 'cookies' => array(), 'headers' => array( 'Accept' => 'text/html, text/plain, application/xml;q=0.9' ), 'user-agent' => 'Site Essentials public website check' ) );
 }
 /** Normalize potentially repeated response headers without emitting array warnings.
  * @param array $response Native HTTP response.
  * @param string $name Header name.
  * @return string Bounded header text.
  */
 private static function header( array $response, string $name ): string {
  $value = wp_remote_retrieve_header( $response, $name );
  return substr( is_array( $value ) ? implode( ', ', $value ) : (string) $value, 0, 4096 );
 }
 /** Read the homepage redirect chain and robots output only on explicit invocation.
  * No arbitrary request URL is accepted. Cross-host redirects are displayed but not followed.
  * @return array Inspection facts, not a compliance or indexing verdict.
  */
 public static function inspect(): array {
  $result = array( 'responses' => array(), 'robots' => array(), 'sitemaps' => array(), 'meta' => array(), 'canonical' => array(), 'checked' => time(), 'html_checked' => false );
  $url = home_url( '/' ); $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ); $seen = array();
  for ( $i = 0; $i < 4; ++$i ) {
   if ( isset( $seen[ $url ] ) ) { break; } $seen[ $url ] = true;
   $response = self::fetch( $url );
   if ( is_wp_error( $response ) ) { $result['responses'][] = array( 'url' => $url, 'error' => $response->get_error_code() ); break; }
   $code = wp_remote_retrieve_response_code( $response ); $location = self::header( $response, 'location' );
   $result['responses'][] = array( 'url' => $url, 'status' => $code, 'type' => self::header( $response, 'content-type' ), 'robots' => self::header( $response, 'x-robots-tag' ), 'location' => $location );
   if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) && '' !== $location ) {
    $next = \WP_Http::make_absolute_url( $location, $url );
    if ( strtolower( (string) wp_parse_url( $next, PHP_URL_HOST ) ) !== $host || ! in_array( wp_parse_url( $next, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) { break; }
    $url = $next; continue;
   }
   if ( false !== stripos( self::header( $response, 'content-type' ), 'text/html' ) && '' !== wp_remote_retrieve_body( $response ) && class_exists( '\DOMDocument' ) ) {
    $dom = new \DOMDocument(); $previous = libxml_use_internal_errors( true );
    try {
     $result['html_checked'] = true;
     $dom->loadHTML( wp_remote_retrieve_body( $response ), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
     foreach ( $dom->getElementsByTagName( 'meta' ) as $node ) {
      $name = strtolower( $node->getAttribute( 'name' ) );
      if ( in_array( $name, array( 'robots', 'googlebot', 'bingbot' ), true ) ) { $result['meta'][] = $name . ': ' . $node->getAttribute( 'content' ); }
     }
     foreach ( $dom->getElementsByTagName( 'link' ) as $node ) {
      if ( in_array( 'canonical', preg_split( '/\s+/', strtolower( $node->getAttribute( 'rel' ) ) ), true ) ) { $result['canonical'][] = $node->getAttribute( 'href' ); }
     }
    } finally { libxml_clear_errors(); libxml_use_internal_errors( $previous ); }
   }
   break;
  }
  $response = self::fetch( self::robots_url() );
  if ( is_wp_error( $response ) ) { $result['robots']['error'] = $response->get_error_code(); }
  else {
   $result['robots'] = array( 'status' => wp_remote_retrieve_response_code( $response ), 'body' => substr( wp_remote_retrieve_body( $response ), 0, 65536 ), 'location' => self::header( $response, 'location' ) );
   if ( 200 === $result['robots']['status'] && preg_match_all( '/^\s*Sitemap:\s*(\S+)/mi', $result['robots']['body'], $matches ) ) {
    foreach ( array_slice( $matches[1], 0, 10 ) as $candidate ) { if ( in_array( wp_parse_url( $candidate, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) && wp_parse_url( $candidate, PHP_URL_HOST ) && ! wp_parse_url( $candidate, PHP_URL_USER ) && ! wp_parse_url( $candidate, PHP_URL_PASS ) ) { $result['sitemaps'][] = $candidate; } }
   }
  }
  return $result;
 }
 /** Print a safe link and a selectable URL for native copying.
  * @param string $label Human-readable destination.
  * @param string $url Public URL; never fetched by this renderer.
  * @return void
  */
 private static function link( string $label, string $url ): void {
  echo '<p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a><br><code class="se-public-url">' . esc_html( $url ) . '</code></p>';
 }
 /** Render local links, and results only after a protected administrator POST.
  * @return void
  */
 public static function panel(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) ) { return; }
  self::addresses();
  echo '<div class="se-card"><h3>' . esc_html__( 'Public website checks', 'site-essentials' ) . '</h3><p>' . esc_html__( 'Open links in a new tab; select their addresses to copy them. No files or settings are changed.', 'site-essentials' ) . '</p>';
  self::link( 'robots.txt', self::robots_url() );
  $server = function_exists( 'wp_sitemaps_get_server' ) ? wp_sitemaps_get_server() : null;
  if ( $server && $server->sitemaps_enabled() ) { self::link( __( 'WordPress XML sitemap', 'site-essentials' ), get_sitemap_url( 'index' ) ); }
  else { echo '<p>' . esc_html__( 'The native WordPress sitemap is not enabled. Another provider may supply a sitemap; this is not automatically an error.', 'site-essentials' ) . '</p>'; }
  self::link( __( 'Posts feed', 'site-essentials' ), get_feed_link() ); self::link( __( 'Comments feed', 'site-essentials' ), get_feed_link( 'comments_rss2' ) );
  if ( Settings::get( 'feeds' ) || Settings::get( 'comments_disabled' ) ) { echo '<p>' . esc_html__( 'Site Essentials restricts feeds or comments. Listed addresses can therefore intentionally return no feed.', 'site-essentials' ) . '</p>'; }
  echo '<p>' . esc_html__( 'robots.txt applies to the host root, also for sites in subdirectories. The manual check retrieves the public homepage and robots.txt without login cookies. Hosting, caches and security services can produce different results for other visitors.', 'site-essentials' ) . '</p><p><button class="button" type="submit" form="se-public-check-form">' . esc_html__( 'Check public homepage and robots.txt now', 'site-essentials' ) . '</button></p>';
  if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['se_public_check'] ) ) {
   $nonce = isset( $_POST['_se_public_nonce'] ) && is_string( $_POST['_se_public_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_se_public_nonce'] ) ) : null;
   if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'se_public_check_' . get_current_blog_id() ) ) { echo '<p>' . esc_html__( 'The check could not be authorized. Reload and try again.', 'site-essentials' ) . '</p>'; }
   else { self::results( self::inspect() ); }
  }
  echo '</div>';
 }
 /** Show effective website addresses without editable controls or stored changes.
  * @return void
  */
 private static function addresses(): void {
  echo '<div class="se-card"><h3>' . esc_html__( 'Website addresses at a glance', 'site-essentials' ) . '</h3>';
  $rows = array(
   'home' => array( __( 'Site Address (URL)', 'site-essentials' ), home_url(), __( 'The public address visitors use to open this website.', 'site-essentials' ) ),
   'wordpress' => array( __( 'WordPress Address (URL)', 'site-essentials' ), site_url(), __( 'The address where the WordPress installation files are located. It can differ from the public site address, for example when WordPress is installed in a subdirectory.', 'site-essentials' ) ),
  );
  foreach ( $rows as $key => $row ) {
   $id = 'se-address-' . $key;
   echo '<div class="se-address-row"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $row[0] ) . '</strong></label><div class="se-control-row"><input class="large-text" id="' . esc_attr( $id ) . '" type="text" readonly value="' . esc_attr( $row[1] ) . '" aria-describedby="' . esc_attr( $id ) . '-help"><button type="button" class="button" data-se-copy-address="' . esc_attr( $id ) . '">' . esc_html__( 'Copy address', 'site-essentials' ) . '</button></div><p id="' . esc_attr( $id ) . '-help">' . esc_html( $row[2] ) . '</p></div>';
  }
  echo '<p>' . esc_html__( 'Read-only information. Both addresses are often identical. Changes belong in WordPress settings or the network configuration; constants and filters can determine the effective values shown here.', 'site-essentials' ) . '</p><p role="status" aria-live="polite" id="se-address-copy-status"></p>';
  if ( current_user_can( 'view_site_health_checks' ) ) { echo '<p><a href="' . esc_url( admin_url( 'site-health.php' ) ) . '">' . esc_html__( 'Open Site Health', 'site-essentials' ) . '</a></p>'; }
  echo '</div>';
 }
 /** Render bounded inspection facts as escaped text, never remote markup.
  * @param array $result Facts returned by inspect().
  * @return void
  */
 private static function results( array $result ): void {
  /* translators: %s: inspection date and time in the website time zone. */
  echo '<p>' . esc_html( sprintf( __( 'Checked at %s (website time zone).', 'site-essentials' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $result['checked'] ) ) ) . '</p>';
  echo '<h4>' . esc_html__( 'Public response', 'site-essentials' ) . '</h4><p>' . esc_html__( 'At most four homepage requests and one robots.txt request, five seconds each. Cross-host redirects are not followed. Content is limited to 256 KB; robots.txt display to 64 KB. Missing values are not proof of an error or of indexability. JavaScript-generated markup is not evaluated.', 'site-essentials' ) . '</p>';
  $labels = array( 'url' => __( 'Address', 'site-essentials' ), 'status' => __( 'HTTP status', 'site-essentials' ), 'type' => __( 'Content type', 'site-essentials' ), 'robots' => 'X-Robots-Tag', 'location' => __( 'Redirect target', 'site-essentials' ), 'error' => __( 'Request error', 'site-essentials' ) );
  echo '<pre class="se-public-facts">';
  foreach ( $result['responses'] as $row ) { foreach ( $row as $key => $value ) { echo esc_html( $labels[ $key ] . ': ' . $value ) . "\n"; } echo "\n"; }
  foreach ( array( 'meta', 'canonical' ) as $kind ) { echo esc_html( ( 'meta' === $kind ? __( 'Robots meta tags', 'site-essentials' ) : __( 'Canonical address', 'site-essentials' ) ) . ': ' . ( $result[ $kind ] ? implode( '; ', $result[ $kind ] ) : ( $result['html_checked'] ? __( 'Not found in the inspected HTML.', 'site-essentials' ) : __( 'Not inspected: no analyzable HTML response was available.', 'site-essentials' ) ) ) ) . "\n"; }
  echo '</pre><h4>robots.txt</h4><pre class="se-public-facts">' . esc_html( isset( $result['robots']['error'] ) ? $result['robots']['error'] : 'HTTP ' . $result['robots']['status'] . "\n" . ( $result['robots']['location'] ?? '' ) . "\n" . $result['robots']['body'] ) . '</pre>';
  if ( isset( $result['robots']['error'] ) ) { echo '<p>' . esc_html__( 'robots.txt could not be retrieved. Sitemap discovery was not completed; no conclusion about a missing sitemap is possible.', 'site-essentials' ) . '</p>'; }
  elseif ( 200 !== $result['robots']['status'] ) { echo '<p>' . esc_html__( 'robots.txt did not return HTTP 200. Sitemap discovery was not completed; a redirect or an HTTP error is not proof of a missing sitemap.', 'site-essentials' ) . '</p>'; }
  elseif ( ! $result['sitemaps'] ) { echo '<p>' . esc_html__( 'No sitemap address was identified in the retrieved robots.txt. This does not prove that a sitemap is missing.', 'site-essentials' ) . '</p>'; }
  foreach ( array_unique( $result['sitemaps'] ) as $url ) { self::link( __( 'Sitemap listed in robots.txt', 'site-essentials' ), $url ); }
  echo '<details><summary>' . esc_html__( 'Copy compact check results', 'site-essentials' ) . '</summary><p>' . esc_html__( 'The summary excludes remote page contents and URL query strings. Review it before sharing.', 'site-essentials' ) . '</p><textarea readonly id="se-public-summary" class="large-text" rows="10" aria-label="' . esc_attr__( 'Compact check results', 'site-essentials' ) . '">' . esc_textarea( self::summary( $result ) ) . '</textarea><p><button type="button" class="button" id="se-public-copy">' . esc_html__( 'Copy results', 'site-essentials' ) . '</button></p><p id="se-public-copy-status" role="status" aria-live="polite"></p></details>';
 }
 /** Build a small shareable summary without remote bodies, arbitrary metadata or URL queries.
  * @param array $result Bounded inspection facts.
  * @return string Plain-text diagnostic summary.
  */
 public static function summary( array $result ): string {
  $lines = array( 'Site Essentials ' . SITE_ESSENTIALS_VERSION, wp_date( 'Y-m-d H:i:s T', $result['checked'] ) );
  foreach ( $result['responses'] as $row ) {
   $lines[] = preg_replace( '/[?#].*/', '', $row['url'] ) . ' — ' . ( isset( $row['error'] ) ? __( 'Request error', 'site-essentials' ) : 'HTTP ' . $row['status'] );
   if ( ! empty( $row['type'] ) ) { $lines[] = __( 'Content type', 'site-essentials' ) . ': ' . sanitize_mime_type( strtok( $row['type'], ';' ) ); }
   if ( ! empty( $row['location'] ) ) { $lines[] = __( 'Redirect target', 'site-essentials' ) . ': ' . preg_replace( '/[?#].*/', '', $row['location'] ); }
  }
  $lines[] = 'robots.txt: ' . ( isset( $result['robots']['error'] ) ? __( 'Request error', 'site-essentials' ) : 'HTTP ' . $result['robots']['status'] );
  $lines[] = __( 'HTML inspected:', 'site-essentials' ) . ' ' . ( $result['html_checked'] ? __( 'Yes', 'site-essentials' ) : __( 'No', 'site-essentials' ) );
  foreach ( array_unique( $result['sitemaps'] ) as $url ) { $lines[] = 'Sitemap: ' . preg_replace( '/[?#].*/', '', $url ); }
  foreach ( $result['canonical'] as $url ) { if ( in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) && ! wp_parse_url( $url, PHP_URL_USER ) ) { $lines[] = __( 'Canonical address', 'site-essentials' ) . ': ' . preg_replace( '/[?#].*/', '', $url ); } }
  $lines[] = __( 'Manual observation only; no indexing or template-quality verdict.', 'site-essentials' );
  return implode( "\n", $lines );
 }
 /** Output the detached form only on the local authorized plugin page.
  * @return void
  */
 public function form(): void {
  if ( is_network_admin() || ! current_user_can( 'manage_options' ) || 'site-essentials' !== ( $_GET['page'] ?? '' ) ) { return; }
  echo '<form id="se-public-check-form" method="post" action="' . esc_url( admin_url( 'options-general.php?page=site-essentials#se-checks' ) ) . '"><input type="hidden" name="se_public_check" value="1">';
  wp_nonce_field( 'se_public_check_' . get_current_blog_id(), '_se_public_nonce' ); echo '</form>';
 }
}
