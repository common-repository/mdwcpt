<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link              https://maxidevs.com/price-tracker-for-woocommerce/
 * @since             0.1.0
 * @package           MDWC_Price_Tracker
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! current_user_can( 'delete_plugins' ) ) {
	exit;
}

function mdwcpt_uninstall() {
	//require_once( dirname( __FILE__ ) . '/wc-price-tracker.php' );
	$cleanup = get_option( 'mdwcpt_uninstall_cleanup', false );
	if ( 'yes' !== $cleanup )
		return;
		
	global $wpdb;
	$table = $wpdb->prefix . 'mdwcpt_subscriptions';
	$wpdb->query( "DROP TABLE IF EXISTS $table" );	
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'mdwcpt_%'" );
}

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
    global $wpdb;
    foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
        switch_to_blog( $blog_id );
		mdwcpt_uninstall();
        restore_current_blog();       
    }
} else {    
    mdwcpt_uninstall();   
}