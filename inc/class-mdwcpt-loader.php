<?php
/**
 * Class responsible for plugin loading
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Classes
 */

if ( ! defined('ABSPATH') ) die;

if ( ! class_exists( 'MDWCPT_Loader' ) ) { 
 
	/**
	 * Plugin loader class
	 */
	class MDWCPT_Loader implements MDWCPT_Object {		

		/**
		 * Whether to do full plugin loading or not
		 *
		 * @var        bool
		 * @since      0.1.0
		 * @access     private
		 */		
		private $do_loading;	
				
		/**
		 * Constructor.
		 *
		 * @since 0.1.0
		 */		
		public function __construct() {
			$this->do_loading = true;
			$this->maybe_upgarade();
		}

		/**
		 * Allow to get private and protected props
		 *
		 * @since 0.1.0		 
		 * @param string $prop
		 * @return mixed
		 */			
		public function __get( $prop ) {			
			if ( is_string( $prop ) && property_exists( $this, $prop ) )
				return $this->{$prop};
			
			return null;
		}
		
		/**
		 * Maybe run upgrade if version raised
		 *
		 * @since      0.1.1
		 * @return     void
		 */		
		private function maybe_upgarade() {			
			$upgrade = false;
			$db_version = get_option( 'mdwcpt_version', false );
			if ( $db_version && MDWCPT_VERSION !== $db_version ) {
				$upgrade = true;
				update_option( 'mdwcpt_version', MDWCPT_VERSION, true );
			}
            
            //alter price column for ver < 0.2.0
            if ( $upgrade && version_compare( $db_version, '0.2.0', '<' ) ) {
                global $wpdb;
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}mdwcpt_subscriptions MODIFY price decimal(32,5) unsigned NOT NULL default '0'" );
            }
		}		
	
		/**
		 * Load plugin textdomain
		 *
		 * @since      0.1.0
		 * @return     void
		 */		
		public function load_textdomain() {
			
			add_filter( 'plugin_locale', array( $this, 'determine_locale' ), 10, 2 );
			
			if ( ! load_plugin_textdomain( 'mdwcpt', false, '/languages/' ) ) {

				if ( function_exists( 'determine_locale' ) ) {
					$locale = determine_locale();
				} elseif ( is_admin() && function_exists( 'get_user_locale' ) ) {
					$locale = get_user_locale();
				} else {
					$locale = get_locale();
				}
				
				$locale = apply_filters( 'plugin_locale', $locale, 'mdwcpt' );
				$mofile = MDWCPT_DIR . '/languages/mdwcpt-' . $locale . '.mo';
				load_textdomain( 'mdwcpt', $mofile );
			}

			remove_filter( 'plugin_locale', array( $this, 'determine_locale' ), 10, 2 );
		}

		/**
		 * Load correct locale for ajax requests from frontend
		 *
		 * @since 0.1.0
		 * @param string $locale
		 * @param string $domain
		 * @return string
		 */		
		public function determine_locale( $locale, $domain ) {
			
			if ( 'mdwcpt' !== $domain )
				return $locale;
			
			$ref = wp_get_raw_referer();			
			if ( $ref && defined( 'DOING_AJAX' ) && DOING_AJAX && 0 !== strpos( $ref, admin_url() ) ) {
				$locale = get_locale();
			}
			
			return $locale;
		}		

		/**
		 * Displays notification if minimum requirements are not met.
		 *
		 * @since      0.1.0
		 * @return     void
		 */		
		public function admin_notices() {
			$msg = '';
			
			if ( version_compare( phpversion(), '5.5', '<' ) )
				$msg .= __( 'MD Price Tracker for WooCommerce plugin requires PHP 5.5 or greater. Ask your host about PHP upgrade.', 'mdwcpt' );
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4', '<' ) )				
				$msg .= ' ' . __( 'MD Price Tracker for WooCommerce plugin requires WordPress 4.4 or greater.', 'mdwcpt' );	
			
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) )				
				$msg .= ' ' . __( 'MD Price Tracker for WooCommerce plugin requires WooCommerce plugin to be installed and activated.', 'mdwcpt' );
			
			$msg = trim( $msg );
			
			if ( $msg )	
				printf( '<div class="error"><p>%s</p></div>', $msg );
		}
		
		/**
		 * Hookup to wp
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function hookup() {
			
			require_once( ABSPATH.'wp-admin/includes/plugin.php' );
			
			add_action( 'init', array( $this, 'load_textdomain' ), 1 );
			
			$php_fail = version_compare( phpversion(), '5.5', '<' );
			$wp_fail  = version_compare( get_bloginfo( 'version' ), '4.4', '<' );
			$woo_fail = ! is_plugin_active( 'woocommerce/woocommerce.php' );
			
			if ( $php_fail || $wp_fail || $woo_fail ) {
				
				$this->do_loading = false;
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );				
				$network_wide = ( $php_fail || $wp_fail || ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && is_plugin_active_for_network( MDWCPT_BASENAME ) ) ) ? null : false;
				deactivate_plugins( MDWCPT_BASENAME, false, $network_wide );
				unset( $_GET['activate'] );
				
			} else {				
				require_once( MDWCPT_DIR . '/inc/class-mdwcpt-utils.php' );
				require_once( MDWCPT_DIR . '/inc/class-mdwcpt-subscribe-form.php' );
				
				require_once( MDWCPT_DIR . '/inc/class-mdwcpt-background-emailer.php' );
				MDWCPT()->add( 'emailer', new MDWCPT_Background_Emailer() );				
				
				require_once MDWCPT_DIR . '/inc/class-mdwcpt-shared.php';
				MDWCPT()->add( 'shared', new MDWCPT_Shared() );
				
				if ( is_admin() ) {					
					require_once MDWCPT_DIR . '/inc/class-mdwcpt-admin.php';
					MDWCPT()->add( 'admin', new MDWCPT_Admin() );
					
					require_once MDWCPT_DIR . '/inc/class-mdwcpt-privacy.php';
					MDWCPT()->add( 'privacy', new MDWCPT_Privacy() );				
				} else {
					require_once MDWCPT_DIR . '/inc/class-mdwcpt-public.php';
					MDWCPT()->add( 'public', new MDWCPT_Public() );
				}			
			}			
		}	
	}
}
