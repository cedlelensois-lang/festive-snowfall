<?php
/**
 * Core: settings model, schedule selection and front-end assets.
 *
 * @package FestiveSnowfall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class Festive_Snowfall {

	const OPTION        = 'festive_snowfall_options';
	const MAX_COLORS    = 6;
	const MAX_SCHEDULES = 20;

	const FLAKE_TYPES   = array( 'crystal', 'ornate', 'fine', 'star', 'asterisk', 'dot' );
	const BANNER_TYPES  = array(
		'none',
		'holly',
		'pine',
		'lights',
		'baubles',
		'icicles',
		'canes',
		'stars',
		'gingerbread',
		'mistletoe',
		'tinsel',
		'gifts',
		'trees',
		'snowmen',
		'ribbon',
		'poinsettia',
	);
	const BANNER_PLACES = array( 'top', 'fixed', 'selector' );

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_front' ) );
	}

	/**
	 * Loads bundled translations (needed when installed from GitHub).
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'festive-snowfall', false, dirname( plugin_basename( FESN_FILE ) ) . '/languages' );
	}

	/* --------------------------------------------------------------------
	 * Settings model
	 * ------------------------------------------------------------------ */

	/**
	 * Default values for one schedule.
	 *
	 * @return array
	 */
	public static function schedule_defaults() {
		$year = (int) wp_date( 'Y' );
		return array(
			'label'         => '',
			'start_date'    => $year . '-12-01',
			'end_date'      => ( $year + 1 ) . '-01-06',
			'repeat'        => 1,
			'test'          => 0,
			'snow_enabled'  => 1,
			'flake_types'   => array( 'crystal' ),
			'colors'        => array( '#9FC3DA', '#FFFFFF' ),
			'count'         => 35,
			'count_mobile'  => 15,
			'size_min'      => 8,
			'size_max'      => 22,
			'speed'         => 5,
			'banner_type'   => 'holly',
			'banner_height' => 34,
		);
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		$first          = self::schedule_defaults();
		$first['label'] = __( 'Christmas', 'festive-snowfall' );
		return array(
			'enabled'         => 1,
			'banner_position' => 'top',
			'banner_selector' => 'header',
			'schedules'       => array( $first ),
		);
	}

	/**
	 * Returns the saved settings merged with defaults, migrating older formats.
	 *
	 * @return array
	 */
	public static function get_options() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? self::migrate( $saved ) : array();
		$o     = wp_parse_args( $saved, self::defaults() );

		$schedules = array();
		foreach ( (array) $o['schedules'] as $schedule ) {
			if ( is_array( $schedule ) ) {
				$schedules[] = wp_parse_args( $schedule, self::schedule_defaults() );
			}
		}
		$o['schedules'] = $schedules;
		return $o;
	}

	/**
	 * Converts the 2.0 single-period format into a schedule list.
	 *
	 * @param array $saved Saved option.
	 * @return array
	 */
	public static function migrate( array $saved ) {
		if ( isset( $saved['schedules'] ) || ! isset( $saved['start_date'] ) ) {
			return $saved;
		}
		$schedule = array_intersect_key( $saved, self::schedule_defaults() );
		if ( ! empty( $saved['admin_preview'] ) ) {
			$schedule['test'] = 1;
		}
		$schedule['label'] = __( 'Christmas', 'festive-snowfall' );

		return array(
			'enabled'         => isset( $saved['enabled'] ) ? $saved['enabled'] : 1,
			'banner_position' => isset( $saved['banner_position'] ) ? $saved['banner_position'] : 'top',
			'banner_selector' => isset( $saved['banner_selector'] ) ? $saved['banner_selector'] : 'header',
			'schedules'       => array( $schedule ),
		);
	}

	/* --------------------------------------------------------------------
	 * Schedule selection
	 * ------------------------------------------------------------------ */

	/**
	 * Index of the schedule matching a day: the first one in list order wins.
	 *
	 * @param array  $schedules Schedules.
	 * @param string $today     Day, Y-m-d.
	 * @return int|null
	 */
	public static function matching_index( array $schedules, $today ) {
		foreach ( array_values( $schedules ) as $i => $s ) {
			if ( Festive_Snowfall_Period::contains( $today, $s['start_date'], $s['end_date'], ! empty( $s['repeat'] ) ) ) {
				return $i;
			}
		}
		return null;
	}

	/**
	 * Index of the schedule flagged for admin testing.
	 *
	 * @param array $schedules Schedules.
	 * @return int|null
	 */
	public static function test_index( array $schedules ) {
		foreach ( array_values( $schedules ) as $i => $s ) {
			if ( ! empty( $s['test'] ) ) {
				return $i;
			}
		}
		return null;
	}

	/**
	 * The schedule to display on the current request, or null.
	 *
	 * @param array $o Settings.
	 * @return array|null
	 */
	public static function current_schedule( array $o ) {
		if ( empty( $o['enabled'] ) || empty( $o['schedules'] ) || is_admin() || is_embed() || is_feed() ) {
			return null;
		}
		$schedules = array_values( $o['schedules'] );

		$index = null;
		if ( current_user_can( 'manage_options' ) ) {
			$index = self::test_index( $schedules );
		}
		if ( null === $index ) {
			$index = self::matching_index( $schedules, wp_date( 'Y-m-d' ) );
		}
		$schedule = null === $index ? null : $schedules[ $index ];

		/**
		 * Filters the schedule displayed on the current page.
		 *
		 * Return null to hide the decoration.
		 *
		 * @param array|null $schedule Schedule settings, or null.
		 * @param array      $o        All plugin settings.
		 */
		$schedule = apply_filters( 'festive_snowfall_schedule', $schedule, $o );

		/**
		 * Filters whether Festive Snowfall is displayed on the current page.
		 *
		 * @param bool  $display Whether to display the decoration.
		 * @param array $o       Plugin settings.
		 */
		if ( ! apply_filters( 'festive_snowfall_should_display', is_array( $schedule ), $o ) ) {
			return null;
		}
		return is_array( $schedule ) ? $schedule : null;
	}

	/**
	 * Builds the JS configuration for a schedule.
	 *
	 * @param array $s Schedule.
	 * @param array $o Settings (for global banner position).
	 * @return array
	 */
	public static function js_config( array $s, array $o ) {
		$config = array(
			'snow'   => null,
			'banner' => null,
		);

		if ( ! empty( $s['snow_enabled'] ) ) {
			$config['snow'] = array(
				'types'        => array_values( (array) $s['flake_types'] ),
				'colors'       => array_values( (array) $s['colors'] ),
				'count'        => (int) $s['count'],
				'count_mobile' => (int) $s['count_mobile'],
				'size_min'     => (int) $s['size_min'],
				'size_max'     => (int) $s['size_max'],
				'speed'        => (int) $s['speed'],
			);
		}

		if ( 'none' !== $s['banner_type'] ) {
			$config['banner'] = array(
				'type'     => $s['banner_type'],
				'height'   => (int) $s['banner_height'],
				'position' => $o['banner_position'],
				'selector' => $o['banner_selector'],
			);
		}
		return $config;
	}

	/**
	 * Enqueues front-end assets only when a schedule is active.
	 */
	public static function enqueue_front() {
		$o        = self::get_options();
		$schedule = self::current_schedule( $o );
		if ( ! $schedule ) {
			return;
		}

		$config = self::js_config( $schedule, $o );
		if ( ! $config['snow'] && ! $config['banner'] ) {
			return;
		}

		wp_enqueue_style( 'festive-snowfall', FESN_URL . 'assets/festive-snowfall.css', array(), FESN_VERSION );
		wp_enqueue_script(
			'festive-snowfall',
			FESN_URL . 'assets/festive-snowfall.js',
			array(),
			FESN_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script(
			'festive-snowfall',
			'window.festiveSnowfallConfig = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
			'before'
		);
	}

	/* --------------------------------------------------------------------
	 * Sanitization
	 * ------------------------------------------------------------------ */

	/**
	 * Sanitizes the whole option.
	 *
	 * @param mixed $input Raw input (already unslashed by options.php).
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		$out = array(
			'enabled'         => empty( $input['enabled'] ) ? 0 : 1,
			'banner_position' => self::choice( $input, 'banner_position', self::BANNER_PLACES, 'top' ),
			'banner_selector' => substr( sanitize_text_field( isset( $input['banner_selector'] ) && is_string( $input['banner_selector'] ) ? $input['banner_selector'] : '' ), 0, 200 ),
			'schedules'       => array(),
		);

		$raw_schedules = isset( $input['schedules'] ) && is_array( $input['schedules'] ) ? array_values( $input['schedules'] ) : array();
		$has_test      = false;

		foreach ( array_slice( $raw_schedules, 0, self::MAX_SCHEDULES ) as $n => $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$s = self::sanitize_schedule( $raw, $n + 1 );
			// Only one schedule can be in testing mode.
			if ( $s['test'] && $has_test ) {
				$s['test'] = 0;
			}
			$has_test           = $has_test || (bool) $s['test'];
			$out['schedules'][] = $s;
		}

		if ( count( $raw_schedules ) > self::MAX_SCHEDULES ) {
			/* translators: %d: maximum number of schedules. */
			self::notice( 'fesn_max', sprintf( __( 'Only the first %d schedules have been kept.', 'festive-snowfall' ), self::MAX_SCHEDULES ), 'warning' );
		}

		return $out;
	}

	/**
	 * Sanitizes one schedule.
	 *
	 * @param array $in     Raw schedule.
	 * @param int   $number Position (for messages).
	 * @return array
	 */
	public static function sanitize_schedule( array $in, $number ) {
		$d   = self::schedule_defaults();
		$out = array();

		$label        = isset( $in['label'] ) && is_string( $in['label'] ) ? sanitize_text_field( $in['label'] ) : '';
		$out['label'] = mb_substr( $label, 0, 80 );

		foreach ( array( 'repeat', 'test', 'snow_enabled' ) as $key ) {
			$out[ $key ] = empty( $in[ $key ] ) ? 0 : 1;
		}

		foreach ( array( 'start_date', 'end_date' ) as $key ) {
			$value = isset( $in[ $key ] ) && is_string( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : '';
			if ( Festive_Snowfall_Period::is_valid_date( $value ) ) {
				$out[ $key ] = $value;
			} else {
				$out[ $key ] = $d[ $key ];
				/* translators: %d: schedule number. */
				self::notice( 'fesn_date_' . $number, sprintf( __( 'Schedule %d: invalid date, the default date has been used.', 'festive-snowfall' ), $number ) );
			}
		}
		if ( ! $out['repeat'] && $out['end_date'] < $out['start_date'] ) {
			/* translators: %d: schedule number. */
			self::notice( 'fesn_order_' . $number, sprintf( __( 'Schedule %d: the end date is before the start date, it will never be displayed.', 'festive-snowfall' ), $number ), 'warning' );
		}

		$types              = isset( $in['flake_types'] ) ? array_map( 'sanitize_key', array_filter( (array) $in['flake_types'], 'is_string' ) ) : array();
		$types              = array_values( array_intersect( self::FLAKE_TYPES, $types ) );
		$out['flake_types'] = $types ? $types : array( 'crystal' );

		$colors = array();
		foreach ( (array) ( isset( $in['colors'] ) ? $in['colors'] : array() ) as $color ) {
			$color = is_string( $color ) ? sanitize_hex_color( trim( $color ) ) : null;
			if ( $color && ! in_array( $color, $colors, true ) ) {
				$colors[] = $color;
			}
		}
		$out['colors'] = $colors ? array_slice( $colors, 0, self::MAX_COLORS ) : array( '#9FC3DA' );

		$out['count']         = self::int_in( $in, 'count', 0, 150, $d['count'] );
		$out['count_mobile']  = self::int_in( $in, 'count_mobile', 0, 150, $d['count_mobile'] );
		$out['size_min']      = self::int_in( $in, 'size_min', 4, 60, $d['size_min'] );
		$out['size_max']      = max( $out['size_min'], self::int_in( $in, 'size_max', 4, 60, $d['size_max'] ) );
		$out['speed']         = self::int_in( $in, 'speed', 1, 10, $d['speed'] );
		$out['banner_type']   = self::choice( $in, 'banner_type', self::BANNER_TYPES, 'holly' );
		$out['banner_height'] = self::int_in( $in, 'banner_height', 16, 80, $d['banner_height'] );

		return $out;
	}

	/**
	 * Registers an admin notice once (sanitize may run twice on first save).
	 *
	 * @param string $code    Code.
	 * @param string $message Message.
	 * @param string $type    Type.
	 */
	private static function notice( $code, $message, $type = 'error' ) {
		if ( ! function_exists( 'add_settings_error' ) || ! function_exists( 'get_settings_errors' ) ) {
			return;
		}
		foreach ( get_settings_errors( self::OPTION ) as $existing ) {
			if ( $existing['code'] === $code ) {
				return;
			}
		}
		add_settings_error( self::OPTION, $code, $message, $type );
	}

	/**
	 * Reads a bounded integer.
	 *
	 * @param array  $input    Input.
	 * @param string $key      Key.
	 * @param int    $min      Minimum.
	 * @param int    $max      Maximum.
	 * @param int    $fallback Default.
	 * @return int
	 */
	private static function int_in( array $input, $key, $min, $max, $fallback ) {
		if ( ! isset( $input[ $key ] ) || ! is_numeric( $input[ $key ] ) ) {
			return $fallback;
		}
		return min( $max, max( $min, (int) $input[ $key ] ) );
	}

	/**
	 * Reads a whitelisted value.
	 *
	 * @param array  $input    Input.
	 * @param string $key      Key.
	 * @param array  $allowed  Allowed values.
	 * @param string $fallback Default.
	 * @return string
	 */
	private static function choice( array $input, $key, array $allowed, $fallback ) {
		$value = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
