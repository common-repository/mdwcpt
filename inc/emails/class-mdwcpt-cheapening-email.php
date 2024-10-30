<?php
/**
 * Generates and sends email to subscriber
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Classes/Emails
 */		

if ( ! defined('ABSPATH') ) exit;

require_once( MDWCPT_DIR . '/inc/abstracts/abstract-class-mdwcpt-email.php' );

if ( ! class_exists( 'MDWCPT_Cheapening_Email' )  ) :
	
	/**
	 * Cheapening email class
	 */		
	class MDWCPT_Cheapening_Email extends MDWCPT_Email {
		
		protected function get_option_key(){
			return 'cheapening';
		}
		
		protected function search_replace_vars( $key ) {
			$vars = parent::search_replace_vars( $key );
			
			if ( 'message' !== $key )
				return $vars;
			
			if ( $this->product->is_type('variation') ) {
				$vars['{cart_url}'] = esc_url( add_query_arg( 
					array( 'add-to-cart' => $this->product->get_parent_id(), 'variation_id' => $this->product->get_id() ),
					wc_get_cart_url()
				));		
			} else {
				$vars['{cart_url}'] = esc_url( add_query_arg( 
					array( 'add-to-cart' => $this->product->get_id() ),
					wc_get_cart_url()
				));		
			}
			
			$resubscribe_id = $this->product->is_type('variation') ? $this->product->get_parent_id() : $this->product->get_id();
			$vars['{resubscribe_url}'] = esc_url( $this->product->get_permalink() ) . '#mdwcpt-form-' . $resubscribe_id;
			
			return $vars;		
		}
	}

endif;