<?php
/**
 * Minimal WordPress stubs so the plugin can be tested without a WordPress install.
 *
 * @package FestiveSnowfall
 */

error_reporting( E_ALL );
set_error_handler(
	static function ( $no, $str, $file, $line ) {
		throw new ErrorException( "$str @ $file:$line" );
	}
);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['fesn_test'] = array(
	'option'   => array(),
	'errors'   => array(),
	'cap'      => true,
	'today'    => '2026-12-15',
	'is_admin' => false,
);

function fesn_t( $key, $value = null ) {
	if ( null !== $value ) {
		$GLOBALS['fesn_test'][ $key ] = $value;
	}
	return $GLOBALS['fesn_test'][ $key ];
}

function add_action() {}
function add_filter() {}
function load_plugin_textdomain() {}
function register_setting() {}
function plugin_dir_url() { return 'https://example.test/wp-content/plugins/festive-snowfall/'; }
function plugin_basename() { return 'festive-snowfall/festive-snowfall.php'; }
function wp_date( $f ) { return 'Y' === $f ? substr( fesn_t( 'today' ), 0, 4 ) : fesn_t( 'today' ); }
function get_option( $k, $d = false ) { return fesn_t( 'option' ) ? fesn_t( 'option' ) : $d; }
function wp_parse_args( $a, $d ) { return array_merge( $d, $a ); }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_hex_color( $c ) { return preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $c ) ? $c : null; }
function add_settings_error( $s, $code, $m, $t = 'error' ) { $GLOBALS['fesn_test']['errors'][] = array( 'code' => $code, 'message' => $m, 'type' => $t ); }
function get_settings_errors() { return fesn_t( 'errors' ); }
function __( $s ) { return $s; }
function esc_html__( $s ) { return htmlspecialchars( $s ); }
function esc_html_e( $s ) { echo htmlspecialchars( $s ); }
function esc_attr__( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_attr_e( $s ) { echo htmlspecialchars( $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s ); }
function esc_url( $u ) { return $u; }
function admin_url( $p ) { return '/wp-admin/' . $p; }
function checked( $a, $b = true ) { if ( (string) $a === (string) $b ) { echo ' checked="checked"'; } }
function selected( $a, $b = true ) { if ( (string) $a === (string) $b ) { echo ' selected="selected"'; } }
function settings_fields() { echo '<input type="hidden" name="option_page" value="festive_snowfall">'; }
function submit_button() { echo '<p><button type="submit" class="button button-primary">Save Changes</button></p>'; }
function get_admin_page_title() { return 'Festive Snowfall'; }
function current_user_can() { return fesn_t( 'cap' ); }
function is_admin() { return fesn_t( 'is_admin' ); }
function is_embed() { return false; }
function is_feed() { return false; }
function apply_filters( $h, $v ) { return $v; }


define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['fesn_test']['transients'] = array();
$GLOBALS['fesn_test']['http']       = array();
$GLOBALS['fesn_test']['response']   = null;
function get_site_transient( $k ) { return isset( $GLOBALS['fesn_test']['transients'][ $k ] ) ? $GLOBALS['fesn_test']['transients'][ $k ]['v'] : false; }
function set_site_transient( $k, $v, $ttl ) { $GLOBALS['fesn_test']['transients'][ $k ] = array( 'v' => $v, 'ttl' => $ttl ); return true; }
function delete_site_transient( $k ) { unset( $GLOBALS['fesn_test']['transients'][ $k ] ); return true; }
function wp_remote_get( $url, $args ) { $GLOBALS['fesn_test']['http'][] = array( $url, $args ); return fesn_t( 'response' ); }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
class WP_Error { public function __construct( $c = '', $m = '' ) {} }
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? $r['code'] : 0; }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? $r['body'] : ''; }
function wp_kses_post( $h ) { return preg_replace( '#<(/?)(script|iframe)[^>]*>#i', '', $h ); }
function wpautop( $t ) { return '<p>' . $t . '</p>'; }
function wp_nonce_url( $u ) { return $u . '&_wpnonce=x'; }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }

require dirname( __DIR__ ) . '/includes/class-festive-snowfall-period.php';
define( 'FESN_VERSION', 'test' );
define( 'FESN_FILE', dirname( __DIR__ ) . '/festive-snowfall.php' );
define( 'FESN_URL', plugin_dir_url( FESN_FILE ) );
require dirname( __DIR__ ) . '/includes/class-festive-snowfall.php';
require dirname( __DIR__ ) . '/includes/class-festive-snowfall-admin.php';
require dirname( __DIR__ ) . '/includes/class-festive-snowfall-updater.php';

$GLOBALS['fesn_fail'] = 0;
function check( $cond, $msg ) {
	if ( ! $cond ) {
		$GLOBALS['fesn_fail']++;
		echo "FAIL: $msg\n";
	}
}
function done( $name ) {
	$f = $GLOBALS['fesn_fail'];
	echo $f ? "$f failure(s) in $name\n" : "All $name tests passed\n";
	exit( $f ? 1 : 0 );
}
