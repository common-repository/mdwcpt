<?php
/**
 * Interface MDWCPT_Object
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @version    0.1.0
 * @package    MDWC_Price_Tracker/Interfaces
 */

if ( ! defined('ABSPATH') ) die;

interface MDWCPT_Object {
	
	/**
	 * All class hooks should be placed in this method
	 *
	 * @return void
	 */	
	public function hookup();
}