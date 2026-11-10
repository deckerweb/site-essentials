<?php
/** Disposable local test helper; holds or probes one SE write lock. */
if ( PHP_SAPI !== 'cli' || getenv( 'SE_DISPOSABLE_TESTS' ) !== 'yes' || empty( $argv[1] ) ) { exit(1); }
$_SERVER['HTTP_HOST'] = getenv( 'SE_TEST_HOST' ) ?: '127.0.0.1';
require rtrim( $argv[1], '/' ) . '/wp-load.php';
if ( wp_get_environment_type() !== 'local' ) { exit(1); }
$locked = \Deckerweb\SiteEssentials\WriteLock::acquire();
echo $locked ? "ACQUIRED\n" : "BUSY\n"; flush();
if ( $locked && ( $argv[2] ?? '' ) === 'hold' ) { fgets(STDIN); }
\Deckerweb\SiteEssentials\WriteLock::release();
