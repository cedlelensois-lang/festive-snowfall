<?php
/**
 * Updates from GitHub releases.
 *
 * WordPress (5.8+) calls the "update_plugins_{hostname}" filter for plugins
 * whose "Update URI" header points to that hostname. We answer it with the
 * latest GitHub release, so the normal update screens, one-click updates and
 * auto-updates work. No external library, no API key, nothing about the site
 * is sent to GitHub.
 *
 * @package FestiveSnowfall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GitHub release updater.
 */
final class Festive_Snowfall_Updater {

	const REPO      = 'cedlelensois-lang/festive-snowfall';
	const SLUG      = 'festive-snowfall';
	const ASSET     = 'festive-snowfall.zip';
	const CACHE     = 'festive_snowfall_release';
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;
	const RETRY_TTL = HOUR_IN_SECONDS;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check_update' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'after_upgrade' ), 10, 2 );
		add_action( 'load-update-core.php', array( __CLASS__, 'on_force_check' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	/**
	 * GitHub repository (owner/name). Filterable for forks.
	 *
	 * @return string
	 */
	public static function repo() {
		$repo = (string) apply_filters( 'festive_snowfall_update_repository', self::REPO );
		return preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo ) ? $repo : self::REPO;
	}

	/**
	 * Latest release, normalised, or null. Cached; failures are cached for a shorter time.
	 *
	 * @param bool $force Bypass the cache.
	 * @return array|null
	 */
	public static function latest_release( $force = false ) {
		$cached = $force ? false : get_site_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return empty( $cached['version'] ) ? null : $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::repo() . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Festive-Snowfall/' . FESN_VERSION,
				),
			)
		);

		$release = null;
		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$release = self::parse_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
		}

		// An empty array marks a failed lookup, so we do not hit the API on every page load.
		set_site_transient( self::CACHE, $release ? $release : array(), $release ? self::CACHE_TTL : self::RETRY_TTL );
		return $release;
	}

	/**
	 * Validates and normalises a GitHub release payload.
	 *
	 * @param mixed $data Decoded JSON.
	 * @return array|null
	 */
	public static function parse_release( $data ) {
		if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) || empty( $data['tag_name'] ) || ! is_string( $data['tag_name'] ) ) {
			return null;
		}

		$version = ltrim( $data['tag_name'], 'vV' );
		if ( ! preg_match( '/^\d+(\.\d+){1,3}$/', $version ) ) {
			return null;
		}

		// Only accept the packaged asset from this repository's own releases.
		$prefix  = 'https://github.com/' . self::repo() . '/releases/download/';
		$package = '';
		foreach ( isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array() as $asset ) {
			if ( is_array( $asset ) && isset( $asset['name'], $asset['browser_download_url'] )
				&& self::ASSET === $asset['name']
				&& is_string( $asset['browser_download_url'] )
				&& 0 === strpos( $asset['browser_download_url'], $prefix ) ) {
				$package = $asset['browser_download_url'];
				break;
			}
		}
		if ( '' === $package ) {
			return null;
		}

		$url = 'https://github.com/' . self::repo();
		return array(
			'version'   => $version,
			'package'   => $package,
			'url'       => $url,
			'notes'     => isset( $data['body'] ) && is_string( $data['body'] ) ? mb_substr( $data['body'], 0, 20000 ) : '',
			'published' => isset( $data['published_at'] ) && is_string( $data['published_at'] ) ? $data['published_at'] : '',
		);
	}

	/**
	 * Answers WordPress' update check for our plugin.
	 *
	 * @param array|false $update      Update data from other filters.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @return array|false
	 */
	public static function check_update( $update, $plugin_data, $plugin_file ) {
		if ( plugin_basename( FESN_FILE ) !== $plugin_file ) {
			return $update;
		}
		$release = self::latest_release();
		if ( ! $release ) {
			return $update;
		}
		return array(
			'id'           => $release['url'],
			'slug'         => self::SLUG,
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'new_version'  => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires'     => '6.3',
			'requires_php' => '7.4',
		);
	}

	/**
	 * "View details" popup on the Plugins and Updates screens.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action API action.
	 * @param object             $args   Arguments.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = self::latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Festive Snowfall',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => 'CGVL Merville',
			'homepage'      => $release['url'],
			'download_link' => $release['package'],
			'requires'      => '6.3',
			'requires_php'  => '7.4',
			'last_updated'  => $release['published'],
			'sections'      => array(
				'description' => wp_kses_post( wpautop( esc_html__( 'Falling snowflakes and festive banners (15 Christmas styles) with date-based schedules: each period can have its own flakes, colours, speed and banner.', 'festive-snowfall' ) ) ),
				'changelog'   => self::notes_to_html( $release['notes'] ),
			),
		);
	}

	/**
	 * Converts release notes (Markdown) into safe, simple HTML.
	 *
	 * @param string $markdown Notes.
	 * @return string
	 */
	public static function notes_to_html( $markdown ) {
		$html  = '';
		$in_ul = false;
		foreach ( preg_split( '/\R/', (string) $markdown ) as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^[-*]\s+(.*)$/', $line, $m ) ) {
				$html .= ( $in_ul ? '' : '<ul>' ) . '<li>' . self::inline( $m[1] ) . '</li>';
				$in_ul = true;
				continue;
			}
			if ( $in_ul ) {
				$html .= '</ul>';
				$in_ul = false;
			}
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^#{1,6}\s+(.*)$/', $line, $m ) ) {
				$html .= '<h4>' . self::inline( $m[1] ) . '</h4>';
			} else {
				$html .= '<p>' . self::inline( $line ) . '</p>';
			}
		}
		if ( $in_ul ) {
			$html .= '</ul>';
		}
		return wp_kses_post( $html );
	}

	/**
	 * Escapes a line and keeps **bold** and `code`.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function inline( $text ) {
		$text = esc_html( $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		return preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
	}

	/**
	 * Keeps the plugin folder name during updates (e.g. if installed as "festive-snowfall-main").
	 *
	 * @param string      $source        Extracted source folder.
	 * @param string      $remote_source Parent folder.
	 * @param WP_Upgrader $upgrader      Upgrader.
	 * @param array       $hook_extra    Extra data.
	 * @return string|WP_Error
	 */
	public static function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;
		if ( empty( $hook_extra['plugin'] ) || plugin_basename( FESN_FILE ) !== $hook_extra['plugin'] || ! $wp_filesystem ) {
			return $source;
		}
		$wanted = trailingslashit( $remote_source ) . dirname( $hook_extra['plugin'] ) . '/';
		if ( untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
			return $source;
		}
		if ( $wp_filesystem->move( $source, $wanted, true ) ) {
			return $wanted;
		}
		return new WP_Error( 'festive_snowfall_rename', __( 'Unable to rename the update folder.', 'festive-snowfall' ) );
	}

	/**
	 * Clears the cache after our plugin has been updated.
	 *
	 * @param WP_Upgrader $upgrader Upgrader.
	 * @param array       $extra    Extra data.
	 */
	public static function after_upgrade( $upgrader, $extra ) {
		if ( isset( $extra['type'], $extra['plugins'] ) && 'plugin' === $extra['type'] && in_array( plugin_basename( FESN_FILE ), (array) $extra['plugins'], true ) ) {
			delete_site_transient( self::CACHE );
		}
	}

	/**
	 * "Check again" on Dashboard > Updates also refreshes our cache.
	 */
	public static function on_force_check() {
		// Core verifies the request itself; we only drop a cache, no data is changed.
		if ( isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			delete_site_transient( self::CACHE );
		}
	}

	/**
	 * "Check for updates" link under the plugin description.
	 *
	 * @param array  $meta Links.
	 * @param string $file Plugin basename.
	 * @return array
	 */
	public static function row_meta( $meta, $file ) {
		if ( plugin_basename( FESN_FILE ) === $file && current_user_can( 'update_plugins' ) ) {
			$url    = wp_nonce_url( admin_url( 'update-core.php?force-check=1' ), 'upgrade-core' );
			$meta[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'festive-snowfall' ) . '</a>';
		}
		return $meta;
	}
}
