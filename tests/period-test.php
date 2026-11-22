<?php
/**
 * Tests for Festive_Snowfall_Period.
 *
 * @package FestiveSnowfall
 */

require __DIR__ . '/bootstrap.php';

$p = 'Festive_Snowfall_Period';

$cases = array(
	// today, start, end, repeat, expected.
	array( '2026-12-01', '2026-12-01', '2027-01-06', true, true ),
	array( '2026-11-30', '2026-12-01', '2027-01-06', true, false ),
	array( '2027-01-06', '2026-12-01', '2027-01-06', true, true ),
	array( '2027-01-07', '2026-12-01', '2027-01-06', true, false ),
	array( '2030-12-25', '2026-12-01', '2027-01-06', true, true ),   // Repeat ignores the year.
	array( '2030-12-25', '2026-12-01', '2027-01-06', false, false ), // No repeat.
	array( '2026-12-25', '2026-12-01', '2027-01-06', false, true ),
	array( '2026-07-14', '2026-07-01', '2026-07-31', true, true ),
	array( '2026-08-01', '2026-07-01', '2026-07-31', true, false ),
	array( '2028-02-29', '2026-02-28', '2026-03-01', true, true ),   // Leap day.
	array( 'bad', '2026-12-01', '2027-01-06', true, false ),
	array( '2026-12-10', '2026-02-30', '2027-01-06', true, false ),  // Invalid start.
	array( '2026-12-10', '2027-01-06', '2026-12-01', false, false ), // Reversed, no repeat.
	array( '2026-12-24', '2026-12-24', '2026-12-24', true, true ),   // Single day.
);
foreach ( $cases as $c ) {
	check( $p::contains( $c[0], $c[1], $c[2], $c[3] ) === $c[4], 'contains ' . json_encode( $c ) );
}

$dates = array(
	'2026-12-01'   => true,
	'2026-13-01'   => false,
	'2026-2-1'     => false,
	"2026-12-01\n" => false,
	'2025-02-29'   => false,
	'2024-02-29'   => true,
);
foreach ( $dates as $d => $e ) {
	check( $p::is_valid_date( $d ) === $e, 'is_valid_date ' . json_encode( $d ) );
}

done( 'period' );
