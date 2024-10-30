<?php

/**
 * This class is loaded during plugin activation
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Classes
 */

if ( ! defined('ABSPATH') ) die;

class MDWCPT_Activator {

	/**
	 * Handles plugin activation
	 *
	 * @since    0.1.0
	 */
	public static function activate( $network_wide ) {

		if ( is_multisite() && $network_wide ) {
			global $wpdb;
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( (int) $blog_id );
				self::blog_activation();
				restore_current_blog();                                      
			}
		} else {
			self::blog_activation();
		}
	}
	
	/**
	 * Creates database table for subscriptions and updates plugin version in wp_options table
	 *
	 * @since    0.1.0
	 */
	public static function blog_activation() {
	
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mdwcpt_subscriptions (
			id bigint(20) unsigned NOT NULL auto_increment,
			email varchar(100) NOT NULL default '',
			price decimal(32,5) unsigned NOT NULL default '0',
			product_id bigint(20) unsigned NOT NULL default '0',
			status varchar(255) NOT NULL default '',
			hash varchar(255) NOT NULL default '',
			created datetime NOT NULL default '0000-00-00 00:00:00',
			updated datetime NOT NULL default '0000-00-00 00:00:00',
			queued datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY product_id (product_id),
			KEY email (email),
			KEY status (status)
		) $charset;\n";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( 'mdwcpt_version', MDWCPT_VERSION, true );
		
		$lifetime = get_option( 'mdwcpt_sent_subscriptions_lifetime', false );
		if ( MDWCPT()->get('loader')->do_loading && MDWCPT_Utils::is_absint( $lifetime ) )		
			MDWCPT()->get('emailer')->reschedule_sent_subscriptions_cron( (int) $lifetime );
	}	
}