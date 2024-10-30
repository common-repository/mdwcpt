<?php

/**
 * @link                 https://maxidevs.com/price-tracker-for-woocommerce/
 * @version              0.3.0
 * @package              MDWC_Price_Tracker
 *
 * @wordpress-plugin
 * Plugin Name:          MD Price Tracker for WooCommerce
 * Plugin URI:           https://maxidevs.com/price-tracker-for-woocommerce/
 * Description:          Boost woocommerce based shops sales by letting customers to subscribe on product cheapening
 * Version:              0.3.0
 * Author:               MaxiDevs
 * Author URI:           https://maxidevs.com
 * WC requires at least: 3.6
 * WC tested up to:      4.9.2
 * License:              GPLv3
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          mdwcpt
 * Domain Path:          /languages
 */

if ( ! defined('ABSPATH') ) die;

//consts
define( 'MDWCPT_VERSION', '0.3.0' );
define( 'MDWCPT_DIR', dirname( __FILE__ ) );
define( 'MDWCPT_URL', plugins_url( '', __FILE__ ) );
define( 'MDWCPT_BASENAME', plugin_basename( __FILE__ ) );

require_once MDWCPT_DIR . '/inc/class-mdwcpt-activator.php';
register_activation_hook( __FILE__, array( 'MDWCPT_Activator', 'activate' ) );

require_once MDWCPT_DIR . '/inc/class-mdwcpt-deactivator.php';
register_deactivation_hook( __FILE__, array( 'MDWCPT_Deactivator', 'deactivate' ) );

/**
 * This method allow global access to shared plugin objects without singletons or global variables
 * and also makes easier to unhook anything hooked by plugin
 *
 * @return MDWCPT_Registry
 */
function MDWCPT() {
	
	static $registry = null;
	
	if ( is_null( $registry ) ) {		
		require_once MDWCPT_DIR . '/inc/class-mdwcpt-registry.php';
		$registry = new MDWCPT_Registry();
	}
	
	return $registry;	
}
require_once( MDWCPT_DIR . '/inc/interfaces/interface-mdwcpt-object.php' );
require_once MDWCPT_DIR . '/inc/class-mdwcpt-loader.php';
MDWCPT()->add( 'loader', new MDWCPT_Loader() );