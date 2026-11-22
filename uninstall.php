<?php
/**
 * Removes plugin data when the plugin is deleted from the Plugins screen.
 *
 * @package FestiveSnowfall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'festive_snowfall_options' );
delete_site_transient( 'festive_snowfall_release' );

if ( is_multisite() ) {
	$festive_snowfall_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $festive_snowfall_sites as $festive_snowfall_site_id ) {
		switch_to_blog( $festive_snowfall_site_id );
		delete_option( 'festive_snowfall_options' );
		restore_current_blog();
	}
}
