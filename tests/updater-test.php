<?php
/**
 * Tests for the GitHub release updater.
 *
 * @package FestiveSnowfall
 */

require __DIR__ . '/bootstrap.php';

$u     = 'Festive_Snowfall_Updater';
$base  = 'https://github.com/cedlelensois-lang/festive-snowfall/releases/download/v2.2.0/';
$file  = 'festive-snowfall/festive-snowfall.php';
$good  = array(
	'tag_name'     => 'v2.2.0',
	'draft'        => false,
	'prerelease'   => false,
	'published_at' => '2026-12-01T10:00:00Z',
	'body'         => "## What's new\n- **Snow** on `body`\n- <script>alert(1)</script> fixed",
	'assets'       => array(
		array( 'name' => 'other.zip', 'browser_download_url' => $base . 'other.zip' ),
		array( 'name' => 'festive-snowfall.zip', 'browser_download_url' => $base . 'festive-snowfall.zip' ),
	),
);
function respond( $data, $code = 200 ) {
	fesn_t( 'response', array( 'code' => $code, 'body' => json_encode( $data ) ) );
	$GLOBALS['fesn_test']['transients'] = array();
	$GLOBALS['fesn_test']['http']       = array();
}

/* 1. Parsing */
$r = $u::parse_release( $good );
check( '2.2.0' === $r['version'], 'version from tag' );
check( $base . 'festive-snowfall.zip' === $r['package'], 'picks the plugin asset' );
check( null === $u::parse_release( array_merge( $good, array( 'prerelease' => true ) ) ), 'prerelease ignored' );
check( null === $u::parse_release( array_merge( $good, array( 'draft' => true ) ) ), 'draft ignored' );
check( null === $u::parse_release( array_merge( $good, array( 'tag_name' => 'latest' ) ) ), 'non-numeric tag ignored' );
check( null === $u::parse_release( array_merge( $good, array( 'tag_name' => '2.2.0<script>' ) ) ), 'malicious tag ignored' );
check( null === $u::parse_release( array_merge( $good, array( 'assets' => array() ) ) ), 'no asset, no update' );
$evil = $good;
$evil['assets'] = array( array( 'name' => 'festive-snowfall.zip', 'browser_download_url' => 'https://evil.example/festive-snowfall.zip' ) );
check( null === $u::parse_release( $evil ), 'asset from another host rejected' );
$evil['assets'] = array( array( 'name' => 'festive-snowfall.zip', 'browser_download_url' => 'https://github.com/attacker/festive-snowfall/releases/download/v9/festive-snowfall.zip' ) );
check( null === $u::parse_release( $evil ), 'asset from another repository rejected' );
check( null === $u::parse_release( 'garbage' ), 'garbage payload' );

/* 2. Update check + caching */
respond( $good );
$upd = $u::check_update( false, array(), $file );
check( is_array( $upd ) && '2.2.0' === $upd['version'] && $upd['package'] === $base . 'festive-snowfall.zip', 'update offered' );
check( 'https://api.github.com/repos/cedlelensois-lang/festive-snowfall/releases/latest' === $GLOBALS['fesn_test']['http'][0][0], 'GitHub API URL' );
check( ! isset( $GLOBALS['fesn_test']['http'][0][1]['body'] ), 'nothing sent about the site' );
check( 43200 === $GLOBALS['fesn_test']['transients']['festive_snowfall_release']['ttl'], 'cached 12 h' );
$u::check_update( false, array(), $file );
check( 1 === count( $GLOBALS['fesn_test']['http'] ), 'second check served from cache' );
check( 'x' === $u::check_update( 'x', array(), 'other/other.php' ), 'other plugins untouched' );

/* 3. Failures are cached for 1 h and do not offer updates */
respond( array( 'message' => 'rate limited' ), 403 );
check( false === $u::check_update( false, array(), $file ), 'no update on API error' );
check( 3600 === $GLOBALS['fesn_test']['transients']['festive_snowfall_release']['ttl'], 'failure cached 1 h' );
$u::check_update( false, array(), $file );
check( 1 === count( $GLOBALS['fesn_test']['http'] ), 'failure not retried immediately' );
fesn_t( 'response', new WP_Error( 'http' ) );
$GLOBALS['fesn_test']['transients'] = array();
check( false === $u::check_update( false, array(), $file ), 'no update on network error' );

/* 4. Plugin details popup */
respond( $good );
$info = $u::plugin_info( false, 'plugin_information', (object) array( 'slug' => 'festive-snowfall' ) );
check( is_object( $info ) && '2.2.0' === $info->version, 'details popup' );
check( false === strpos( $info->sections['changelog'], '<script' ), 'release notes sanitized' );
check( false !== strpos( $info->sections['changelog'], '<strong>Snow</strong>' ) && false !== strpos( $info->sections['changelog'], '<li>' ), 'markdown converted' );
check( 'r' === $u::plugin_info( 'r', 'plugin_information', (object) array( 'slug' => 'akismet' ) ), 'other slugs untouched' );
check( 'r' === $u::plugin_info( 'r', 'query_plugins', (object) array( 'slug' => 'festive-snowfall' ) ), 'other actions untouched' );

/* 5. Repository filter validation */
check( 'cedlelensois-lang/festive-snowfall' === $u::repo(), 'default repository' );

/* 6. Cache cleared after our update only */
set_site_transient( 'festive_snowfall_release', array( 'version' => '2.2.0' ), 10 );
$u::after_upgrade( null, array( 'type' => 'plugin', 'plugins' => array( 'akismet/akismet.php' ) ) );
check( false !== get_site_transient( 'festive_snowfall_release' ), 'cache kept for other plugins' );
$u::after_upgrade( null, array( 'type' => 'plugin', 'plugins' => array( $file ) ) );
check( false === get_site_transient( 'festive_snowfall_release' ), 'cache cleared after update' );

/* 7. Row meta link */
$meta = $u::row_meta( array(), $file );
check( 1 === count( $meta ) && false !== strpos( $meta[0], 'force-check=1' ), 'check for updates link' );

done( 'updater' );
