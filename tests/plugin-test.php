<?php
/**
 * Tests for settings, schedules, sanitization and admin rendering.
 *
 * @package FestiveSnowfall
 */

require __DIR__ . '/bootstrap.php';

$f = 'Festive_Snowfall';

/* 1. Defaults */
$o = $f::get_options();
check( 1 === count( $o['schedules'] ) && 'Christmas' === $o['schedules'][0]['label'], 'one default schedule' );
check( '2026-12-01' === $o['schedules'][0]['start_date'] && '2027-01-06' === $o['schedules'][0]['end_date'], 'default dates' );

/* 2. Migration from 2.0 flat format */
fesn_t(
	'option',
	array(
		'enabled'         => 1,
		'start_date'      => '2026-12-10',
		'end_date'        => '2026-12-31',
		'repeat'          => 0,
		'admin_preview'   => 1,
		'flake_types'     => array( 'star' ),
		'banner_type'     => 'lights',
		'banner_position' => 'selector',
		'banner_selector' => '#header',
	)
);
$o = $f::get_options();
check( 1 === count( $o['schedules'] ), 'migrated to one schedule' );
check( '2026-12-10' === $o['schedules'][0]['start_date'] && 'lights' === $o['schedules'][0]['banner_type'], 'migrated values kept' );
check( 1 === $o['schedules'][0]['test'], 'admin_preview becomes test flag' );
check( '#header' === $o['banner_selector'] && 'selector' === $o['banner_position'], 'global banner position migrated' );
check( ! isset( $o['start_date'] ), 'flat keys dropped' );

/* 3. Hostile input */
fesn_t( 'option', array() );
$out = $f::sanitize(
	array(
		'enabled'         => '1',
		'banner_position' => 'fixed',
		'banner_selector' => 'header</script><script>alert(1)</script>' . str_repeat( 'a', 500 ),
		'schedules'       => array(
			'n9' => array(
				'label'        => '<b>Advent</b>' . str_repeat( 'x', 200 ),
				'start_date'   => '2026-13-45',
				'end_date'     => '2026-12-23',
				'repeat'       => '1',
				'test'         => '1',
				'flake_types'  => array( 'crystal', '<img>', 'dot', 'dot', 'evil', array( 'x' ) ),
				'colors'       => array( '#ff0000', 'red', '#FFF"onload=x', '#00ff00', '#ff0000', array( 'x' ) ),
				'count'        => '99999',
				'count_mobile' => '-5',
				'size_min'     => '30',
				'size_max'     => '10',
				'speed'        => 'abc',
				'banner_type'  => 'lights" onmouseover',
				'banner_height' => '1000',
			),
			'2'  => array(
				'label'       => 'Eve',
				'start_date'  => '2026-12-24',
				'end_date'    => '2026-12-25',
				'test'        => '1',
				'banner_type' => 'gingerbread',
			),
			'x'  => 'not an array',
		),
	)
);
$s0 = $out['schedules'][0];
$s1 = $out['schedules'][1];
check( 2 === count( $out['schedules'] ), 'non-array schedule skipped, keys reindexed' );
check( 80 === mb_strlen( $s0['label'] ) && false === strpos( $s0['label'], '<' ), 'label stripped and truncated' );
check( '2026-12-01' === $s0['start_date'], 'invalid date replaced by default' );
check( 'fesn_date_1' === fesn_t( 'errors' )[0]['code'], 'error message names schedule 1' );
check( array( 'crystal', 'dot' ) === $s0['flake_types'], 'flake whitelist' );
check( array( '#ff0000', '#00ff00' ) === $s0['colors'], 'colours filtered and deduplicated' );
check( 150 === $s0['count'] && 0 === $s0['count_mobile'], 'counts clamped' );
check( 30 === $s0['size_min'] && 30 === $s0['size_max'], 'size_max >= size_min' );
check( 5 === $s0['speed'], 'speed fallback' );
check( 'holly' === $s0['banner_type'], 'banner whitelist' );
check( 80 === $s0['banner_height'], 'banner height clamped' );
check( 1 === $s0['test'] && 0 === $s1['test'], 'only one testing schedule' );
check( 'gingerbread' === $s1['banner_type'], 'new banner type accepted' );
check( strlen( $out['banner_selector'] ) <= 200 && false === strpos( $out['banner_selector'], '<' ), 'selector sanitized' );
fesn_t( 'option', $out );
check( $f::sanitize( $out ) == $out, 'sanitize is idempotent' ); // phpcs:ignore Universal.Operators.StrictComparisons
check( array() === $f::sanitize( 'garbage' )['schedules'], 'non-array input' );

/* 4. Too many schedules */
fesn_t( 'errors', array() );
$many = $f::sanitize( array( 'schedules' => array_fill( 0, 25, array( 'start_date' => '2026-12-01', 'end_date' => '2026-12-02' ) ) ) );
check( 20 === count( $many['schedules'] ), 'max 20 schedules' );
check( 'fesn_max' === end( $GLOBALS['fesn_test']['errors'] )['code'], 'max warning' );

/* 5. Schedule selection: first match wins */
$sched = array(
	array_merge( $f::schedule_defaults(), array( 'label' => 'Eve', 'start_date' => '2026-12-24', 'end_date' => '2026-12-25', 'banner_type' => 'lights' ) ),
	array_merge( $f::schedule_defaults(), array( 'label' => 'Season', 'start_date' => '2026-12-01', 'end_date' => '2027-01-06' ) ),
	array_merge( $f::schedule_defaults(), array( 'label' => 'NY', 'start_date' => '2026-12-31', 'end_date' => '2027-01-01', 'repeat' => 0 ) ),
);
check( 0 === $f::matching_index( $sched, '2026-12-24' ), 'Eve wins on 24 Dec' );
check( 1 === $f::matching_index( $sched, '2026-12-10' ), 'Season on 10 Dec' );
check( 1 === $f::matching_index( $sched, '2026-12-31' ), 'Season listed before NY wins on 31 Dec' );
check( null === $f::matching_index( $sched, '2026-06-01' ), 'nothing in June' );

$opts = array_merge( $f::defaults(), array( 'schedules' => $sched ) );
fesn_t( 'today', '2026-12-24' );
check( 'Eve' === $f::current_schedule( $opts )['label'], 'current schedule' );
fesn_t( 'today', '2026-06-01' );
check( null === $f::current_schedule( $opts ), 'nothing displayed in June' );
$opts['schedules'][2]['test'] = 1;
check( 'NY' === $f::current_schedule( $opts )['label'], 'testing schedule for admins' );
fesn_t( 'cap', false );
check( null === $f::current_schedule( $opts ), 'testing schedule hidden from visitors' );
fesn_t( 'cap', true );
$opts['enabled'] = 0;
check( null === $f::current_schedule( $opts ), 'disabled' );

/* 6. JS config is script-safe */
$opts['banner_selector'] = '</script><b>';
$cfg  = $f::js_config( $sched[0], $opts );
$json = json_encode( $cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
check( false === strpos( $json, '<' ), 'JSON escaped' );
check( 'lights' === $cfg['banner']['type'] && is_array( $cfg['snow'] ), 'config content' );

/* 7. Admin page */
fesn_t( 'today', '2026-12-24' );
fesn_t( 'option', array_merge( $f::defaults(), array( 'banner_selector' => '"><script>x</script>', 'schedules' => $sched ) ) );
ob_start();
Festive_Snowfall_Admin::render();
$html = ob_get_clean();
check( false === strpos( $html, '<script>x' ), 'selector escaped' );
check( 4 === substr_count( $html, 'class="fesn-schedule"' ), '3 cards + template' );
check( 24 === substr_count( $html, 'class="fesn-color"' ), '6 colour fields per card' );
check( false !== strpos( $html, 'visitors see the schedule “Eve”' ), 'today notice' );
check( false !== strpos( $html, '[schedules][__i__][label]' ), 'template present' );
check( 16 === substr_count( $html, '<option value=' ) / 4, '16 banner options per card' );
file_put_contents( sys_get_temp_dir() . '/fesn-admin.html', $html );

fesn_t( 'cap', false );
ob_start();
Festive_Snowfall_Admin::render();
check( '' === ob_get_clean(), 'page hidden without capability' );

done( 'plugin' );
