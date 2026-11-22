<?php
/**
 * Date period logic (no WordPress dependency, unit-testable).
 *
 * @package FestiveSnowfall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks whether a day falls inside a display period.
 */
final class Festive_Snowfall_Period {

	/**
	 * Validates a Y-m-d date string.
	 *
	 * @param string $date Date.
	 * @return bool
	 */
	public static function is_valid_date( $date ) {
		if ( ! is_string( $date ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})\z/', $date, $m ) ) {
			return false;
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
	}

	/**
	 * Whether $today is within [$start, $end] (both inclusive).
	 *
	 * When $repeat is true only month and day are compared, and a period that
	 * crosses New Year (e.g. 12-01 → 01-06) is handled.
	 *
	 * @param string $today  Current day, Y-m-d.
	 * @param string $start  Start day, Y-m-d.
	 * @param string $end    End day, Y-m-d.
	 * @param bool   $repeat Repeat every year.
	 * @return bool
	 */
	public static function contains( $today, $start, $end, $repeat ) {
		if ( ! self::is_valid_date( $today ) || ! self::is_valid_date( $start ) || ! self::is_valid_date( $end ) ) {
			return false;
		}

		if ( ! $repeat ) {
			// ISO dates compare correctly as strings.
			return $today >= $start && $today <= $end;
		}

		$t = substr( $today, 5 );
		$s = substr( $start, 5 );
		$e = substr( $end, 5 );

		if ( $s <= $e ) {
			return $t >= $s && $t <= $e;
		}
		// Period crossing New Year.
		return $t >= $s || $t <= $e;
	}
}
