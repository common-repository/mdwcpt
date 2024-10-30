<?php
/**
 * Hook to woocommerce settings
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Classes
 */  

if ( ! defined('ABSPATH') ) die;

if ( ! class_exists( 'MDWCPT_Settings_Page' ) ) :

	/**
	 * Woocommerce settings page class
	 */    
	class MDWCPT_Settings_Page extends WC_Settings_Page implements MDWCPT_Object {

        public function __construct() {
            $this->id = 'mdwcpt';
			$this->label = 'WC Price Tracking';
        }

		/**
		 * Hookup to wp
		 *
		 * @since      0.1.0
		 * @return     void
		 */	        
		public function hookup() {
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 50 );
            add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
            add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output') );
            add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_filter( 'woocommerce_admin_settings_sanitize_option' , array( $this, 'sanitize_option' ), 10, 3 );       
		}		

		/**
		 * Alter how woocommerce handle sanitization some of our settings
		 *
		 * @since         0.1.0
		 * @param mixed   $value
		 * @param array   $option
		 * @param mixed   $raw_value
		 * @return mixed
		 */	
		public function sanitize_option( $value, $option, $raw_value ) {
			//not our case
			if ( 0 !== strpos( $option['id'], 'mdwcpt_' ) || 'title' === $option['type'] || 'sectionend' === $option['type'] )
				return $value;
			
			$atts = isset( $option['custom_attributes'] ) && is_array( $option['custom_attributes'] ) ? $option['custom_attributes'] : [];
			
			if ( 'number' === $option['type'] ) {				
				if ( ! is_numeric( $value ) ) {
					$value = '';
				} else {
					$value = ( isset( $atts['min'] ) && is_numeric( $atts['min'] ) && $value < $atts['min'] ) ? $atts['min'] : $value;
					$value = ( isset( $atts['max'] ) && is_numeric( $atts['max'] ) && $value > $atts['max'] ) ? $atts['max'] : $value;					
				}
			}
			
			//update scheduled sent subscriptions deletion event on option update
			if ( 'mdwcpt_sent_subscriptions_lifetime' === $option['id'] ) {
				if ( $value && MDWCPT_Utils::is_absint( $value ) ) {
					
					$old_next_run = wp_next_scheduled( MDWCPT()->get('emailer')->cron_hook_identifier . '_cleanup_sent' );

					if ( $old_next_run && ( $old_next_run - current_time( 'timestamp' ) ) > DAY_IN_SECONDS )
						MDWCPT()->get('emailer')->reschedule_sent_subscriptions_cron( (int) $value );				
				}	
			}
			
			//required cannot be empty
			$value = empty( $value ) && 0 !== $value && '0' !== $value && isset( $atts['required'], $option['default'] ) ? $option['default'] : $value;
		
            return $value;
        }
		
		/**
		 * Get settings sections
		 *
		 * @since      0.1.0
		 * @return     array
		 */	
		public function get_sections() {

            $sections = array(
                ''       => __( 'General', 'mdwcpt' ),
				'labels' => __( 'Form labels', 'mdwcpt' ),
                'emails' => __( 'Emails', 'mdwcpt' ),
            );

            return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
        }

		/**
		 * Output fields
		 *
		 * @since      0.1.0
		 * @return     string
		 */        
		public function output() {
            global $current_section;
            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::output_fields( $settings );
        }

		/**
		 * Save fields
		 *
		 * @since      0.1.0
		 * @return     void
		 */ 
        public function save() {           
			global $current_section;
            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::save_fields( $settings );       
		}

		/**
		 * Get settings
		 *
		 * @since      0.1.0
		 * @param string  $section
		 * @return void
		 */        
		public function get_settings( $section = '' ) {
			
			$defaults = MDWCPT_Utils::settings_def();
			
			if ( '' === $section ) {
				
				$settings = array();
				
				$settings[] = array(
					//'name' => __( 'General', 'mdwcpt' ),
					'type' => 'title',
					'id'   => 'mdwcpt_form_visibility_section' 
				);					
				$settings[] = array(
					'name'    => __( 'Tracking form available for:', 'mdwcpt' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => $defaults['mdwcpt_visible_for'],
					'options' => array (
						'all' => __( 'Guests and customers', 'mdwcpt' ),
						'customers' => __( 'Only for customers', 'mdwcpt' ),
						'guests' => __( 'Only for guests', 'mdwcpt' ),
					),
					'id'   => 'mdwcpt_visible_for' 
				);
				$settings[] = array(
					//'name' => __( 'Hide for outofstock products', 'mdwcpt' ),
					'type' => 'checkbox',
					'default' => $defaults['mdwcpt_hide_out_of_stock'],
					'desc' => __( 'Hide subscription form if product is out of stock', 'mdwcpt' ),
					'id'   => 'mdwcpt_hide_out_of_stock'
				);
				$settings[] = array(
					'name'     => __( 'Modal form trigger link position on product page', 'mdwcpt' ),
					'type'     => 'select',
					'default' => $defaults['mdwcpt_form_position'],
					'desc_tip' => __( 'Choose where modal subscription form trigger button will be rendered on product page. You can also use [mdwcpt_form product_id=12345] shortcode to display form anywhere.', 'mdwcpt' ),
					'class'   => 'wc-enhanced-select',						
					'options'  => array (
						'before_price' => __( 'Before product price', 'mdwcpt' ),							
						'after_price'  => __( 'After product price', 'mdwcpt' ),
						'before_cart_button' => __( 'Before ADD TO CART button', 'mdwcpt' ),
						'after_cart_button'  => __( 'After ADD TO CART button', 'mdwcpt' ),
						'before_cart'  => __( 'Before ADD TO CART form', 'mdwcpt' ),	
						'after_cart'   => __( 'After ADD TO CART form', 'mdwcpt' ),
						'before_desc'  => __( 'Before product description', 'mdwcpt' ),	
						'after_desc'   => __( 'After product description', 'mdwcpt' ),
						'before_meta'  => __( 'Before product meta', 'mdwcpt' ),
						'after_meta'   => __( 'After product meta', 'mdwcpt' ),
						'no'           => __( 'Hide for shortcode usage', 'mdwcpt' ),
					),
					'id'   => 'mdwcpt_form_position' 
				);
				$settings[] = array(
					'name'     => __( 'Modal form trigger link position on products archives', 'mdwcpt' ),
					'type'     => 'select',
					'default' => $defaults['mdwcpt_form_position_shop'],
					'desc_tip' => __( 'Choose where modal subscription form trigger button will be rendered on shop page and product archives.', 'mdwcpt' ),
					'class'   => 'wc-enhanced-select',						
					'options'  => array(
						'no'                 => __( 'Hide on product archives', 'mdwcpt' ),
						'before_cart_button' => __( 'Before ADD TO CART button', 'mdwcpt' ),
						'after_cart_button'  => __( 'After ADD TO CART button', 'mdwcpt' ),							
					),
					'id'   => 'mdwcpt_form_position_shop' 
				);
				$settings[] = array(
					'name'    => __( 'Enable password check', 'mdwcpt' ),
					'type'    => 'checkbox',
					'default' => $defaults['mdwcpt_force_pass'],			
					'desc'    => __( 'Request a password if the submitter is logged out and the entered email address belongs to a registered customer.', 'mdwcpt' ),
					'id'      => 'mdwcpt_force_pass'
				);
				$settings[] = array(
					'name'     => __( 'Google reCAPTCHA verification', 'mdwcpt' ),
					'type'     => 'select',
					'default' => $defaults['mdwcpt_recaptcha']['enabled'],
					'desc_tip' => __( 'Set when to use Google reCAPTCHA to protect the form', 'mdwcpt' ),
					'class'   => 'wc-enhanced-select',						
					'options'  => array (
						'no'           => __( 'Disabled', 'mdwcpt' ),
						'guests'       => __( 'Enabled only for guests', 'mdwcpt' ),							
						'all'          => __( 'Enabled for all', 'mdwcpt' )
					),
					'id'   => 'mdwcpt_recaptcha[enabled]' 
				);
				$settings[] = array(
					'name'    => __( 'Google reCAPTCHA site key', 'mdwcpt' ),
					'type'    => 'text',
					'css'     =>  'min-width:398px;',
					'default' => $defaults['mdwcpt_recaptcha']['site_key'],
					'desc'    => sprintf( '<br /><a href="https://www.google.com/recaptcha/">%s</a>', __( 'Get Google reCAPTCHA key', 'mdwcpt' ) ),
					'id'	  => 'mdwcpt_recaptcha[site_key]'
				);
				$settings[] = array(
					'name'    => __( 'Google reCAPTCHA secret key', 'mdwcpt' ),
					'type'    => 'text',
					'css'     =>  'min-width:398px;',
					'default' => $defaults['mdwcpt_recaptcha']['secret_key'],
					'desc'    => sprintf( '<br /><a href="https://www.google.com/recaptcha/">%s</a>', __( 'Get Google reCAPTCHA key', 'mdwcpt' ) ),						
					'id'	  => 'mdwcpt_recaptcha[secret_key]'
				);
				if ( current_user_can( 'delete_plugins' ) ) {
					$settings[] = array(
						'name'    => __( 'Delete plugin data during uninstall', 'mdwcpt' ),
						'type'    => 'checkbox',
						'default' => $defaults['mdwcpt_uninstall_cleanup'],
						'desc'    => __( 'Enable to delete all plugin data, including all subscriptions and all settings, during plugin uninstall.', 'mdwcpt' ),
						'id'      => 'mdwcpt_uninstall_cleanup',
						'autoload'=> false,
					);
				}				
				$settings[] = array( 'type' => 'sectionend', 'id' => 'mdwcpt_form_visibility_section' );
				
			} elseif( 'labels' === $section ) {
				
				$settings = array (
					array(
						'name' => __( 'Field names', 'mdwcpt' ),
						'type' => 'title',
						'desc' => __( 'Adjust labels and placeholders for the fields and the form itself', 'mdwcpt' ),
						'id'   => 'mdwcpt_fields_atts_section' 
					),
					array(
						'name'    => __( 'Form title', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_form']['title'],
						'id'	  => 'mdwcpt_form[title]'
					),	
					array(
						'name'    => __( 'Email field label', 'mdwcpt' ),
						'default' => $defaults['mdwcpt_form']['email_title'],	
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'id'	  => 'mdwcpt_form[email_title]'
					),
					array(
						'name' => __( 'Email field placeholder', 'mdwcpt' ),
						'type' => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_form']['email_placeholder'],
						'id'	=> 'mdwcpt_form[email_placeholder]'
					),
					array(
						'name'    => __( 'Title for variations dropdown', 'mdwcpt' ),
						'default' => $defaults['mdwcpt_form']['variations_title'],			
						'desc_tip' => __( 'This field is for variable and grouped products', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'id'	  => 'mdwcpt_form[variations_title]'
					),
					array(
						'name'    => __( 'Default option for variations dropdown', 'mdwcpt' ),
						'default' => $defaults['mdwcpt_form']['variations_def_option'],
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'id'	  => 'mdwcpt_form[variations_def_option]'
					),					
					array(
						'name'    => __( 'Price field label', 'mdwcpt' ),
						'default' => $defaults['mdwcpt_form']['price_title'],			
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'id'	  => 'mdwcpt_form[price_title]'
					),
					array(
						'name'    => __( 'Password field label', 'mdwcpt' ),
						'default' => $defaults['mdwcpt_form']['pass_title'],	
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'id'	  => 'mdwcpt_form[pass_title]'
					),
					array(
						'name'    => __( 'Privacy policy text', 'mdwcpt' ),
						'default' => $defaults['mdwcpt_form']['privacy_title'],	
						'desc_tip' => sprintf( __( 'This text will be rendered only if the Privacy Policy page in main woocommerce settings is set. Available placeholders: %s', 'mdwcpt' ), '{terms_url}' ),						
						'css'     => 'min-height:100px;min-width:398px;',
						'type'    => 'textarea',
						'id'	  => 'mdwcpt_form[privacy_title]'
					),					
					array(
						'name'    => __( 'Submit button label', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_form']['submit_label'],			
						'id'	  => 'mdwcpt_form[submit_label]'
					),					
					array( 'type' => 'sectionend', 'id' => 'mdwcpt_fields_atts_section' ),
					
					array(
						'name' => __( 'Submit messages', 'mdwcpt' ),
						'type' => 'title',
						'desc' => __( 'Adjust success and error messages of subscription form', 'mdwcpt' ),
						'id'   => 'mdwcpt_form_meassages_section' 
					),
					array(
						'name'    => __( 'Subscription success message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['success'],
						'id'	  => 'mdwcpt_messages[success]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Subscription updated message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['updated'],
						'id'	  => 'mdwcpt_messages[updated]',
						'autoload'=> false,
					),					
					array(
						'name'    => __( 'Invalid email message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['invalid_email'],
						'id'	  => 'mdwcpt_messages[invalid_email]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Invalid password message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['invalid_pass'],
						'id'	  => 'mdwcpt_messages[invalid_pass]',
						'autoload'=> false,
					),					
					array(
						'name'    => __( 'Invalid price message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['invalid_price'],
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{current_price}' ),
						'id'	  => 'mdwcpt_messages[invalid_price]',
						'autoload'=> false,
					),				
					array(
						'name'    => __( 'Unsubscribe success message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['unsubscribe_success'],
						'id'	  => 'mdwcpt_messages[unsubscribe_success]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Unsubscribe error message', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						'default' => $defaults['mdwcpt_messages']['unsubscribe_error'],
						'id'	  => 'mdwcpt_messages[unsubscribe_error]',
						'autoload'=> false,
					),					
					array( 'type' => 'sectionend', 'id' => 'mdwcpt_form_meassages_section' ),
				);
			} elseif( 'emails' === $section ) {
				
				$settings = array (					
					array(
						'name' => __( 'Subscription success email', 'mdwcpt' ),
						'type' => 'title',
						'id'   => 'mdwcpt_success_email_section'
					),					
					array(
						'name'    => __( 'Subject', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{shop_name}, {product_name}, {user_name}' ),
						'default' => $defaults['mdwcpt_success_email']['subject'],
						'id'	  => 'mdwcpt_success_email[subject]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Title', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{shop_name}, {product_name}, {user_name}' ),			
						'default' => $defaults['mdwcpt_success_email']['title'],
						'id'	  => 'mdwcpt_success_email[title]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Message', 'mdwcpt' ),
						'type'    => 'textarea',
						'css'     => 'min-height:100px;min-width:398px;',
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{shop_name}, {user_name}, {product_name}, {product_url}, {current_price}, {expected_price}, {unsubscribe_url}' ),			
						'default' => $defaults['mdwcpt_success_email']['message'],
						'id'	  => 'mdwcpt_success_email[message]',
						'autoload'=> false,
					),
					array(
						//'name'    => __( 'Enable admin notification', 'mdwcpt' ),
						'type'    => 'checkbox',
						'default' => $defaults['mdwcpt_success_notify_admin'],
						'desc'    => __( 'Notify administrator by email if new user is subscribed', 'mdwcpt' ),
						'id'      => 'mdwcpt_success_notify_admin',
						'autoload'=> false,
					),					
					array( 'type' => 'sectionend', 'id' => 'mdwcpt_success_email_section' ),
					
					array (
						'name' => __( 'Price cheapening email', 'mdwcpt' ),
						'type' => 'title',
						'id'   => 'mdwcpt_cheapening_email_section',
						'autoload'=> false,
					),					
					array(
						'name'    => __( 'Subject', 'mdwcpt' ),
						'type'    => 'text',
						'css'     => 'min-width:398px;',
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{shop_name}, {product_name}, {user_name}' ),
						'default' => $defaults['mdwcpt_cheapening_email']['subject'],
						'id'	  => 'mdwcpt_cheapening_email[subject]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Title', 'mdwcpt' ),
						'type'    => 'text',
						'css'     =>  'min-width:398px;',
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{shop_name}, {product_name}, {user_name}' ),			
						'default' => $defaults['mdwcpt_cheapening_email']['title'],
						'id'	  => 'mdwcpt_cheapening_email[title]',
						'autoload'=> false,
					),
					array(
						'name'    => __( 'Message', 'mdwcpt' ),
						'type'    => 'textarea',
						'css'     => 'min-height:100px;min-width:398px;',
						/* translators: %s: placeholders list */
						'desc_tip'=> sprintf( __( 'Available placeholders: %s', 'mdwcpt' ), '{shop_name}, {user_name}, {product_name}, {product_url}, {current_price}, {expected_price}, {cart_url}, {resubscribe_url}' ),			
						'default' => $defaults['mdwcpt_cheapening_email']['message'],
						'id'	  => 'mdwcpt_cheapening_email[message]',
						'autoload'=> false,
					),
					array (
						'name'              => __( 'Delete sent subscriptions after:', 'mdwcpt' ),
						'type'              => 'number',
						'desc'              => __( 'day(s)', 'mdwcpt' ),
						'desc_tip'          => __( 'After that period sent subscriptions will be automatically deleted. Don\'t fill this to disable automatic deletion', 'mdwcpt' ),
						'default'           => $defaults['mdwcpt_sent_subscriptions_lifetime'],
						'id'	            => 'mdwcpt_sent_subscriptions_lifetime',
						'custom_attributes' => array( 'min' => '1' ),
						'autoload'          => false,
					),
					array(
						'name'              => __( 'Max sending limit', 'mdwcpt' ),
						'type'              => 'number',
						'desc_tip'          => __( 'Limit the number of emails that can be sent by the plugin per minute. Don\'t fill this for unlimited speed', 'mdwcpt' ),
						'desc'              => __( 'emails/minute', 'mdwcpt' ),						
						'default'           => $defaults['mdwcpt_emailing_limit'],
						'id'	            => 'mdwcpt_emailing_limit',
						'custom_attributes' => array( 'min' => '1' ),
						'autoload'          => false,
					),					
					array( 'type' => 'sectionend', 'id' => 'mdwcpt_cheapening_email_section' ),	
				);				
			} else {
				$settings = array();
			}
			
			//prevent woocommerce from storing "array like" options in database during wc instalation
			if ( defined( 'WC_INSTALLING' ) && 'yes' === get_transient( 'wc_installing' ) ) {
				foreach( $settings as $k=>$setting ) {
					if ( false === strpos( $setting['id'], '[' ) )
						continue;
					
					unset( $settings[ $k ] );
					list( $key, $subkey ) = explode( '[', trim( $setting['id'], ']' ) );
					
					$settings[ $key ]['id'] = $key;
					if ( isset( $setting['default'] ) )	
						$settings[ $key ]['default'][ $subkey ] = $setting['default'];
					if ( isset( $setting['autoload'] ) )
						$settings[ $key ]['autoload'] = $setting['autoload'];
				}
			}
        
			$settings = apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $section );
		
			return $settings;
		}

    }

endif;