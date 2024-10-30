<?php
/**
 * Slightly adapted registry pattern... act as container for main plugin objects
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Classes
 */	

if ( ! defined('ABSPATH') ) die;

if ( ! class_exists( 'MDWCPT_Registry' ) ) { 
 
	/**
	 * Registry class
	 */	
	class MDWCPT_Registry {

		/**
		 * Storage for objects
		 *
		 * @var        array
		 * @since      0.1.0
		 * @access     private
		 */		
		private $storage;
		
		public function __construct() {			
			$this->storage = array();
		}

		/**
		 * Adds object to storage if key not already there
		 *
		 * @since                0.1.0	 
		 * @param string         $id
		 * @param MDWCPT_Object    $object
		 * @return null|MDWCPT_Object
		 */	
		public function add( $id, MDWCPT_Object $object ) {
			
			if ( is_string( $id ) && ! isset( $this->storage[$id] ) ) {
				$this->storage[$id] = $object;
				$this->storage[$id]->hookup();
				return $this->storage[$id];
			}
			
			return null;
		}

		/**
		 * Get MDWCPT_Object
		 *
		 * @since                0.1.0	 
		 * @param string         $id
		 * @param MDWCPT_Object    $object
		 * @return null|MDWCPT_Object
		 */		
		public function get( $id ) {
			
			return is_string( $id ) && isset( $this->storage[$id] ) ? $this->storage[$id] : null;
		
		}
	}
}