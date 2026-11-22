<?php
/**
 * Plugin Name:       Festive Snowfall
 * Plugin URI:        https://github.com/cedlelensois-lang/festive-snowfall
 * Description:       Falling snowflakes and festive banners (15 Christmas styles) with date-based schedules: each period can have its own flakes, colours, speed and banner.
 * Version:           2.1.1
 * Requires at least: 6.3
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Cédric Malvache
 * Author URI:        https://github.com/cedlelensois-lang
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       festive-snowfall
 * Domain Path:       /languages
 * Update URI:        https://github.com/cedlelensois-lang/festive-snowfall
 *
 * @package FestiveSnowfall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FESN_VERSION', '2.1.1' );
define( 'FESN_FILE', __FILE__ );
define( 'FESN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/class-festive-snowfall-period.php';
require_once __DIR__ . '/includes/class-festive-snowfall.php';
require_once __DIR__ . '/includes/class-festive-snowfall-updater.php';

Festive_Snowfall::init();
// Loaded on every request: WordPress also checks for updates from WP-Cron.
Festive_Snowfall_Updater::init();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-festive-snowfall-admin.php';
	Festive_Snowfall_Admin::init();
}
