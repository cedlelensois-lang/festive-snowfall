<?php
/**
 * Settings page (Settings > Festive Snowfall).
 *
 * @package FestiveSnowfall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screens.
 */
final class Festive_Snowfall_Admin {

	const PAGE  = 'festive-snowfall';
	const GROUP = 'festive_snowfall';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FESN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Adds the settings page.
	 */
	public static function add_page() {
		add_options_page(
			__( 'Festive Snowfall', 'festive-snowfall' ),
			__( 'Festive Snowfall', 'festive-snowfall' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Registers the option (Settings API: nonce and capability checks by core).
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			Festive_Snowfall::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Festive_Snowfall', 'sanitize' ),
				'default'           => Festive_Snowfall::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'festive-snowfall' ) . '</a>' );
		return $links;
	}

	/**
	 * Admin assets, only on our page.
	 *
	 * @param string $hook Current page hook.
	 */
	public static function assets( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'festive-snowfall', FESN_URL . 'assets/festive-snowfall.css', array(), FESN_VERSION );
		wp_enqueue_style( 'festive-snowfall-admin', FESN_URL . 'assets/admin.css', array(), FESN_VERSION );
		wp_enqueue_script( 'festive-snowfall', FESN_URL . 'assets/festive-snowfall.js', array(), FESN_VERSION, true );
		wp_enqueue_script( 'festive-snowfall-admin', FESN_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker', 'festive-snowfall' ), FESN_VERSION, true );

		$l10n = array(
			'untitled'      => __( 'Schedule', 'festive-snowfall' ),
			'everyYear'     => __( 'every year', 'festive-snowfall' ),
			'copyOf'        => __( 'Copy of %s', 'festive-snowfall' ),
			'confirmDelete' => __( 'Delete this schedule?', 'festive-snowfall' ),
			'max'           => Festive_Snowfall::MAX_SCHEDULES,
			'maxReached'    => __( 'Maximum number of schedules reached.', 'festive-snowfall' ),
		);
		wp_add_inline_script( 'festive-snowfall-admin', 'window.fesnAdmin = ' . wp_json_encode( $l10n, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
	}

	/**
	 * Flake shape labels.
	 *
	 * @return array
	 */
	public static function flake_labels() {
		return array(
			'crystal'  => array( '❄', __( 'Classic crystal', 'festive-snowfall' ) ),
			'ornate'   => array( '❅', __( 'Ornate crystal', 'festive-snowfall' ) ),
			'fine'     => array( '❆', __( 'Fine crystal', 'festive-snowfall' ) ),
			'star'     => array( '✦', __( 'Star', 'festive-snowfall' ) ),
			'asterisk' => array( '✻', __( 'Asterisk', 'festive-snowfall' ) ),
			'dot'      => array( '●', __( 'Round snowball', 'festive-snowfall' ) ),
		);
	}

	/**
	 * Banner labels.
	 *
	 * @return array
	 */
	public static function banner_labels() {
		return array(
			'none'        => __( 'No banner', 'festive-snowfall' ),
			'holly'       => __( 'Holly garland', 'festive-snowfall' ),
			'pine'        => __( 'Pine branches', 'festive-snowfall' ),
			'lights'      => __( 'Twinkling light string', 'festive-snowfall' ),
			'baubles'     => __( 'Hanging baubles', 'festive-snowfall' ),
			'icicles'     => __( 'Snow and icicles', 'festive-snowfall' ),
			'canes'       => __( 'Candy canes', 'festive-snowfall' ),
			'stars'       => __( 'Golden stars', 'festive-snowfall' ),
			'gingerbread' => __( 'Gingerbread men', 'festive-snowfall' ),
			'mistletoe'   => __( 'Mistletoe', 'festive-snowfall' ),
			'tinsel'      => __( 'Gold tinsel', 'festive-snowfall' ),
			'gifts'       => __( 'Hanging gifts', 'festive-snowfall' ),
			'trees'       => __( 'Christmas trees', 'festive-snowfall' ),
			'snowmen'     => __( 'Snowmen', 'festive-snowfall' ),
			'ribbon'      => __( 'Red ribbon and bows', 'festive-snowfall' ),
			'poinsettia'  => __( 'Poinsettias', 'festive-snowfall' ),
		);
	}

	/**
	 * Renders the page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o         = Festive_Snowfall::get_options();
		$name      = Festive_Snowfall::OPTION;
		$schedules = array_values( $o['schedules'] );
		$today_i   = Festive_Snowfall::matching_index( $schedules, wp_date( 'Y-m-d' ) );
		$test_i    = Festive_Snowfall::test_index( $schedules );
		$open_i    = null !== $today_i ? $today_i : 0;
		?>
		<div class="wrap fesn-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php self::status_notice( $o, $schedules, $today_i, $test_i ); ?>

			<div class="fesn-layout">
				<form method="post" action="options.php" class="fesn-form" id="fesn-form">
					<?php settings_fields( self::GROUP ); ?>

					<h2><?php esc_html_e( 'General', 'festive-snowfall' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Decoration', 'festive-snowfall' ); ?></th>
							<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $o['enabled'] ); ?>> <?php esc_html_e( 'Enable Festive Snowfall', 'festive-snowfall' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Banner position', 'festive-snowfall' ); ?></th>
							<td><fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Banner position', 'festive-snowfall' ); ?></legend>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[banner_position]" value="top" <?php checked( $o['banner_position'], 'top' ); ?>> <?php esc_html_e( 'Top of the page (scrolls with the content)', 'festive-snowfall' ); ?></label><br>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[banner_position]" value="fixed" <?php checked( $o['banner_position'], 'fixed' ); ?>> <?php esc_html_e( 'Fixed at the top of the screen', 'festive-snowfall' ); ?></label><br>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[banner_position]" value="selector" <?php checked( $o['banner_position'], 'selector' ); ?>> <?php esc_html_e( 'Inside a theme element (CSS selector below)', 'festive-snowfall' ); ?></label>
							</fieldset></td>
						</tr>
						<tr>
							<th scope="row"><label for="fesn-sel"><?php esc_html_e( 'CSS selector', 'festive-snowfall' ); ?></label></th>
							<td><input type="text" id="fesn-sel" class="regular-text code" maxlength="200" name="<?php echo esc_attr( $name ); ?>[banner_selector]" value="<?php echo esc_attr( $o['banner_selector'] ); ?>" placeholder="header, #header, .site-header">
							<p class="description"><?php esc_html_e( 'The banner is inserted at the start of the first matching element. If none is found, it falls back to the top of the page.', 'festive-snowfall' ); ?></p></td>
						</tr>
					</table>

					<h2><?php esc_html_e( 'Schedules', 'festive-snowfall' ); ?></h2>
					<p class="description fesn-intro"><?php esc_html_e( 'Each schedule has its own dates and style. When several schedules overlap, the first one in the list wins: put short periods (e.g. 24–25 December) above longer ones.', 'festive-snowfall' ); ?></p>

					<div id="fesn-schedules" class="fesn-schedules">
						<?php
						foreach ( $schedules as $i => $s ) {
							self::schedule_card( (string) $i, $s, $i === $open_i, $i === $today_i );
						}
						?>
					</div>

					<p><button type="button" class="button" id="fesn-add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Add a schedule', 'festive-snowfall' ); ?></button></p>

					<template id="fesn-schedule-template">
						<?php self::schedule_card( '__i__', Festive_Snowfall::schedule_defaults(), true, false ); ?>
					</template>

					<?php submit_button(); ?>
				</form>

				<aside class="fesn-preview-box" aria-label="<?php esc_attr_e( 'Live preview', 'festive-snowfall' ); ?>">
					<h2><?php esc_html_e( 'Live preview', 'festive-snowfall' ); ?></h2>
					<p class="fesn-preview-name"><strong id="fesn-preview-name"></strong></p>
					<div id="fesn-preview" class="fesn-preview-stage is-dark">
						<div class="fesn-preview-header" id="fesn-preview-header"><span><?php esc_html_e( 'Your site header', 'festive-snowfall' ); ?></span></div>
					</div>
					<p><button type="button" class="button" id="fesn-preview-bg"><?php esc_html_e( 'Toggle light / dark background', 'festive-snowfall' ); ?></button></p>
					<p class="description"><?php esc_html_e( 'Shows the schedule you are editing. Remember to save.', 'festive-snowfall' ); ?></p>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Status notice at the top of the page.
	 *
	 * @param array    $o         Settings.
	 * @param array    $schedules Schedules.
	 * @param int|null $today_i   Schedule matching today.
	 * @param int|null $test_i    Schedule in testing mode.
	 */
	private static function status_notice( array $o, array $schedules, $today_i, $test_i ) {
		if ( empty( $o['enabled'] ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The decoration is disabled.', 'festive-snowfall' ) . '</p></div>';
			return;
		}
		if ( null !== $today_i ) {
			/* translators: %s: schedule name. */
			$msg = sprintf( __( 'Today, visitors see the schedule “%s”.', 'festive-snowfall' ), self::schedule_title( $schedules[ $today_i ], $today_i ) );
			echo '<div class="notice notice-success inline"><p>' . esc_html( $msg ) . '</p></div>';
		} else {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No schedule matches today: nothing is displayed to visitors.', 'festive-snowfall' ) . '</p></div>';
		}
		if ( null !== $test_i ) {
			/* translators: %s: schedule name. */
			$msg = sprintf( __( 'Testing mode: administrators see the schedule “%s” whatever the date.', 'festive-snowfall' ), self::schedule_title( $schedules[ $test_i ], $test_i ) );
			echo '<div class="notice notice-warning inline"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	/**
	 * Display title of a schedule.
	 *
	 * @param array $s Schedule.
	 * @param int   $i Index.
	 * @return string
	 */
	private static function schedule_title( array $s, $i ) {
		/* translators: %d: schedule number. */
		return '' !== $s['label'] ? $s['label'] : sprintf( __( 'Schedule %d', 'festive-snowfall' ), $i + 1 );
	}

	/**
	 * Renders one schedule card.
	 *
	 * @param string $i        Index (or "__i__" in the template).
	 * @param array  $s        Schedule settings.
	 * @param bool   $open     Expanded.
	 * @param bool   $is_today Matches today.
	 */
	private static function schedule_card( $i, array $s, $open, $is_today ) {
		$n   = Festive_Snowfall::OPTION . '[schedules][' . $i . ']';
		$id  = 'fesn-s' . $i;
		$num = is_numeric( $i ) ? (int) $i + 1 : 0;
		?>
		<details class="fesn-schedule"<?php echo $open ? ' open' : ''; ?>>
			<summary>
				<span class="fesn-schedule-title"><?php echo esc_html( $num ? self::schedule_title( $s, $num - 1 ) : __( 'New schedule', 'festive-snowfall' ) ); ?></span>
				<span class="fesn-schedule-dates"></span>
				<?php if ( $is_today ) : ?>
					<span class="fesn-badge fesn-badge-today"><?php esc_html_e( 'Today', 'festive-snowfall' ); ?></span>
				<?php endif; ?>
				<span class="fesn-badge fesn-badge-test"<?php echo empty( $s['test'] ) ? ' hidden' : ''; ?>><?php esc_html_e( 'Testing', 'festive-snowfall' ); ?></span>
			</summary>

			<div class="fesn-schedule-body">
				<div class="fesn-toolbar">
					<button type="button" class="button button-small" data-fesn-action="up"><span aria-hidden="true">↑</span> <?php esc_html_e( 'Move up', 'festive-snowfall' ); ?></button>
					<button type="button" class="button button-small" data-fesn-action="down"><span aria-hidden="true">↓</span> <?php esc_html_e( 'Move down', 'festive-snowfall' ); ?></button>
					<button type="button" class="button button-small" data-fesn-action="duplicate"><?php esc_html_e( 'Duplicate', 'festive-snowfall' ); ?></button>
					<button type="button" class="button button-small button-link-delete" data-fesn-action="delete"><?php esc_html_e( 'Delete', 'festive-snowfall' ); ?></button>
				</div>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $id ); ?>-label"><?php esc_html_e( 'Name', 'festive-snowfall' ); ?></label></th>
						<td><input type="text" id="<?php echo esc_attr( $id ); ?>-label" class="regular-text" maxlength="80" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( $s['label'] ); ?>" data-fesn="label" placeholder="<?php esc_attr_e( 'e.g. Advent, Christmas Eve, New Year', 'festive-snowfall' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Dates', 'festive-snowfall' ); ?></th>
						<td>
							<label><?php esc_html_e( 'From', 'festive-snowfall' ); ?> <input type="date" name="<?php echo esc_attr( $n ); ?>[start_date]" value="<?php echo esc_attr( $s['start_date'] ); ?>" data-fesn="start_date" required></label>
							<label><?php esc_html_e( 'to', 'festive-snowfall' ); ?> <input type="date" name="<?php echo esc_attr( $n ); ?>[end_date]" value="<?php echo esc_attr( $s['end_date'] ); ?>" data-fesn="end_date" required></label>
							<p><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[repeat]" value="1" <?php checked( $s['repeat'] ); ?> data-fesn="repeat"> <?php esc_html_e( 'Repeat every year (only day and month are used)', 'festive-snowfall' ); ?></label></p>
							<p><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[test]" value="1" <?php checked( $s['test'] ); ?> data-fesn="test"> <?php esc_html_e( 'Testing: always show this schedule to administrators', 'festive-snowfall' ); ?></label></p>
							<p class="description"><?php esc_html_e( 'Both days are included. The site time zone (Settings > General) is used.', 'festive-snowfall' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Snowflakes', 'festive-snowfall' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[snow_enabled]" value="1" <?php checked( $s['snow_enabled'] ); ?> data-fesn="snow_enabled"> <?php esc_html_e( 'Show falling snowflakes', 'festive-snowfall' ); ?></label>
							<fieldset class="fesn-types">
								<legend><?php esc_html_e( 'Flake shapes (select several to mix them)', 'festive-snowfall' ); ?></legend>
								<?php foreach ( self::flake_labels() as $key => $label ) : ?>
									<label class="fesn-type">
										<input type="checkbox" name="<?php echo esc_attr( $n ); ?>[flake_types][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, (array) $s['flake_types'], true ) ); ?> data-fesn="flake_types">
										<span class="fesn-glyph" aria-hidden="true"><?php echo esc_html( $label[0] ); ?></span>
										<?php echo esc_html( $label[1] ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Colours', 'festive-snowfall' ); ?></th>
						<td><div class="fesn-colors">
							<?php for ( $c = 0; $c < Festive_Snowfall::MAX_COLORS; $c++ ) : ?>
								<input type="text" class="fesn-color" name="<?php echo esc_attr( $n ); ?>[colors][]" value="<?php echo esc_attr( isset( $s['colors'][ $c ] ) ? $s['colors'][ $c ] : '' ); ?>" data-fesn="colors" aria-label="<?php /* translators: %d: colour number. */ echo esc_attr( sprintf( __( 'Colour %d', 'festive-snowfall' ), $c + 1 ) ); ?>">
							<?php endfor; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Up to 6 colours; each flake picks one at random. Leave empty to skip.', 'festive-snowfall' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $id ); ?>-speed"><?php esc_html_e( 'Speed', 'festive-snowfall' ); ?></label></th>
						<td><input type="range" id="<?php echo esc_attr( $id ); ?>-speed" min="1" max="10" step="1" name="<?php echo esc_attr( $n ); ?>[speed]" value="<?php echo esc_attr( $s['speed'] ); ?>" data-fesn="speed">
							<output class="fesn-speed-out" for="<?php echo esc_attr( $id ); ?>-speed"><?php echo esc_html( $s['speed'] ); ?></output> / 10
							<p class="description"><?php esc_html_e( '1 = very slow, 10 = very fast.', 'festive-snowfall' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quantity', 'festive-snowfall' ); ?></th>
						<td>
							<label><?php esc_html_e( 'Computer', 'festive-snowfall' ); ?> <input type="number" class="small-text" min="0" max="150" name="<?php echo esc_attr( $n ); ?>[count]" value="<?php echo esc_attr( $s['count'] ); ?>" data-fesn="count"></label>
							<label><?php esc_html_e( 'Mobile', 'festive-snowfall' ); ?> <input type="number" class="small-text" min="0" max="150" name="<?php echo esc_attr( $n ); ?>[count_mobile]" value="<?php echo esc_attr( $s['count_mobile'] ); ?>" data-fesn="count_mobile"></label>
							<p class="description"><?php esc_html_e( '0 disables snow on that device type.', 'festive-snowfall' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Size (px)', 'festive-snowfall' ); ?></th>
						<td>
							<label><?php esc_html_e( 'Min', 'festive-snowfall' ); ?> <input type="number" class="small-text" min="4" max="60" name="<?php echo esc_attr( $n ); ?>[size_min]" value="<?php echo esc_attr( $s['size_min'] ); ?>" data-fesn="size_min"></label>
							<label><?php esc_html_e( 'Max', 'festive-snowfall' ); ?> <input type="number" class="small-text" min="4" max="60" name="<?php echo esc_attr( $n ); ?>[size_max]" value="<?php echo esc_attr( $s['size_max'] ); ?>" data-fesn="size_max"></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $id ); ?>-banner"><?php esc_html_e( 'Banner style', 'festive-snowfall' ); ?></label></th>
						<td>
							<select id="<?php echo esc_attr( $id ); ?>-banner" name="<?php echo esc_attr( $n ); ?>[banner_type]" data-fesn="banner_type">
								<?php foreach ( self::banner_labels() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['banner_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<label><?php esc_html_e( 'Height (px)', 'festive-snowfall' ); ?> <input type="number" class="small-text" min="16" max="80" name="<?php echo esc_attr( $n ); ?>[banner_height]" value="<?php echo esc_attr( $s['banner_height'] ); ?>" data-fesn="banner_height"></label>
						</td>
					</tr>
				</table>
			</div>
		</details>
		<?php
	}
}
