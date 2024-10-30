<?php
/**
 * Handles hooking to wp GDPR functionality
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Admin/Classes
 */	

if ( ! defined('ABSPATH') ) die;

if ( ! class_exists( 'MDWCPT_Privacy' ) ) { 
 
	/**
	 * Privacy class
	 */	
	class MDWCPT_Privacy implements MDWCPT_Object {		

		/**
		 * Hookup to wp
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function hookup() {			
            add_action( 'admin_init', array( $this, 'privacy_help' ), 11 );
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter') );
            add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser') );
		}
		
		/**
		 * Adds exporter to the WordPress core exporters array
		 *
		 * @since      0.1.0
		 * @param array $exporters	 
		 * @return array
		 */	
		public function register_exporter( $exporters ) {			
            $exporters['mdwcpt-subscriptions'] = array(
                'exporter_friendly_name' => __( 'Price tracking subscriptions', 'mdwcpt'),
                'callback' => array( $this, 'exporter' ),
            );
            return $exporters;
		}

		/**
		 * Adds eraser method to the WordPress core eresers array
		 *
		 * @since      0.1.0
		 * @param array $erasers	 
		 * @return array
		 */	
		public function register_eraser( $erasers ) {			
            $erasers['mdwcpt-subscriptions'] = array(
                'exporter_friendly_name' => __( 'Price tracking subscriptions', 'mdwcpt'),
                'callback' => array( $this, 'eraser' ),
            );
            return $erasers;
		}
		
		/**
		 * Exports price tracking subscriptions data for the user.
		 *
		 * @since 0.1.0
		 * @param string $email 
		 * @param int    $page 
		 * @return array
		 */
		public function exporter( $email, $page ) {
			$page = (int) $page;
			$data_to_export = array();
			$limit = 100;
			$offset = ( $limit * $page ) - $limit;

			global $wpdb;
			$table = $wpdb->prefix . 'mdwcpt_subscriptions';
			$subscriptions = $wpdb->get_results( 
				$wpdb->prepare( 
					"SELECT id, email, price, product_id, status, created, updated, queued 
					FROM $table WHERE email = %s ORDER BY id ASC LIMIT %d OFFSET %d",
					$email, $limit, $offset
				), ARRAY_A 
			);
			
			$props = array(
				'email'       => __( 'Email address', 'mdwcpt' ), 
				'price'       => __( 'Expected price', 'mdwcpt' ),
				'product_id'  => __( 'Product', 'mdwcpt' ),
				'status'      => __( 'Status', 'mdwcpt' ), 
				'created'     => __( 'Date created', 'mdwcpt' ), 
				'updated'     => __( 'Date updated', 'mdwcpt' ),
				'queued'      => __( 'Date sent', 'mdwcpt' ),
			);

			if ( 0 < count( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription ) {
					$data = array();
					
					foreach( $props as $k => $name ) {
						
						switch ( $k ) {
							case 'email':
								$value = $subscription[ $k ];
								break;
							case 'price':
								$value = wc_price( $subscription[ $k ] );
								break;
							case 'product_id':
								$product = wc_get_product( (int) $subscription[ $k ] );
								if ( $product instanceof WC_Product && $product->is_visible() )
									$value = '<a href="' . esc_url( $product->get_permalink() ) . '" target="_blank">' . esc_html( $product->get_title() ) . '</a>';
								else
									$value = __( 'Hidden product', 'mdwcpt' );
								break;
							case 'status':
								$value = ucfirst( $subscription[ $k ] );
								break;
							case 'created':
							case 'updated':
							case 'queued':
								if ( 'sent' === $subscription['status'] && '0000-00-00 00:00:00' !== $subscription[ $k ] )	
									$value = mysql2date( get_option( 'date_format', 'd.m.Y' ) . ' ' . get_option( 'time_format', 'H:i:s' ) , $subscription[ $k ] );
								else
									$value = '';
								break;
						}
						
						if ( $value ) {						
							$data[] = array(
								'name'  => $name,
								'value' => $value
							);
						}
					}
					
					$data_to_export[] = array(
						'group_id'    => 'mdwcpt_subscriptions',
						'group_label' => __( 'Price tracking subscriptions', 'mdwcpt' ),
						'item_id'     => 'mdwcpt-subscription' . $subscription['id'],
						'data'        => $data,
					);
				}
				$done = $limit > count( $subscriptions );
			} else {
				$done = true;
			}

			return array(
				'data' => $data_to_export,
				'done' => $done,
			);
		}

		/**
		 * Erases price tracking subscriptions data of user.
		 *
		 * @since 0.1.0
		 * @param string $email 
		 * @param int    $page 
		 * @return array
		 */
		public function eraser( $email, $page ) {
			$page = (int) $page;
			$data_to_export = array();
			$limit = 100;

			global $wpdb;
			$table = $wpdb->prefix . 'mdwcpt_subscriptions';
			$count = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE email = %s LIMIT %d", $email, $limit) );

			return array(
				'items_removed'  => true,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => $limit > $count,
			);
		}		
		
		/**
		 * Adds example template of plugin privacy policy content to wp privacy policy help guide.
		 *
		 * @since 0.1.0
		 */
		public function privacy_help() {		
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) )
				return;
			
			$content = '
			<div class="mdwcpt-privacy-help">' .
				'<h2>' . __( 'What personal data we collect and store:', 'mdwcpt' ) . '</h2>' .
				'<strong>' . __( 'When you submit a price tracking subscription form, we collect filled data for the following purposes:', 'mdwcpt' ) . '</strong>' .
				'<ol>' .
					'<li>' . __( 'Expected price - we’ll store it to calculate whether to send you an email or not if product price is reduced someday', 'mdwcpt' ) . ';<br />' .					
					'<li>' . __( 'Your email adress - we’ll use it to send you an email as soon as the price of the product reduced to expected and also to send you several other transactional emails, for example, about a successful subscription', 'mdwcpt' ) . ';' .
					'<li>' . __( 'We may also ask unauthorized visitors to provide a password for security purposes if the entered email address belongs to a registered customer', 'mdwcpt' ) . ';' .		
				'</ol>' .				
				'<strong>' . __( 'We also collect some other data during subscription for the following purposes:', 'mdwcpt' ) . '</strong>' .
				'<ol>' .					
					'<li>' . __( 'Product that you viewed during subscription - we’ll use it to properly compose price tracking email', 'mdwcpt' ) . ';</li>' .
					'<li>' . __( 'Date and time of submission is collected for administrative tasks such as statistics', 'mdwcpt' ) . ';</li>' .
				'</ol>' .
				'<h2>' . __( 'For what reasons we collect mentioned above data:', 'mdwcpt' ) . '</h2>' .				
				'<ol>' .
					'<li>' . __( 'We store that data to send you promotional email if price of the product will be reduced to expected someday', 'mdwcpt' ) . ';<br />' .					
					'<li>' . __( 'Also, a promotional email can be sent to you at the time of sale if the promotional price of the product will be lower or equal to the expected price', 'mdwcpt' ) . ';' .
					'<li>' . __( 'And lastly, store managers can send a personal promocode to your email address if the expected price is considered satisfactory', 'mdwcpt' ) . ';' .		
				'</ol>' .				
				'<p>' .
					__( 'We store mentioned above data for as long as we need it for the purposes for which we collect and use it.', 'mdwcpt'	) .	
					' ' . __( 'This data can be deleted by unsubscribing from the price tracking by clicking on the link that we send to the specified email address during the subscription.', 'mdwcpt'	) .					
					' ' . __( 'Access to this data has Administrators and Shop Managers.', 'mdwcpt'	) .									
				'</p>' .
			'</div>';
			wp_add_privacy_policy_content( 'WC Price Tracker', $content );
		}
	}
}














