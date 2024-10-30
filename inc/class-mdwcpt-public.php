<?php
/**
 * Subscibe and unsubscribe forms controller
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Public/Classes
 */	

if ( ! defined('ABSPATH') ) die;

if ( ! class_exists( 'MDWCPT_Public' ) ) {
 
	/**
	 * Forms class
	 */	
	class MDWCPT_Public implements MDWCPT_Object {	
		
		/**
		 * Wether do plugin loading or not
		 *
		 * @var        sting
		 * @since      0.1.0
		 * @access     protected
		 */		
		protected $unsubscribe_popup;
		
		/**
		 * Hookup to wp
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function hookup() {			
			add_action( 'wp', array( $this, 'load_subscribe_form' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
			add_action( 'woocommerce_shop_loop', array( $this, 'shop_loop' ), 20 );	
			add_shortcode( 'mdwcpt_form', array( $this, 'form_shortcode' ) );
		}

		/**
		 * Loads form on single product pages
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function load_subscribe_form() {	
			
			if ( is_singular('product') && is_main_query() && 'no' !== MDWCPT_Utils::get_option( 'form_position' ) ) {
				$product = wc_get_product( get_the_id() );				
				if ( $product instanceof WC_Product ) {
					$form = new MDWCPT_Subscribe_Form( $product, false );
					$form->hookup();
				}	
			}
			
			if ( is_singular('product') && is_main_query() && isset( $_GET['mdwcpt_unsubscribe'], $_GET['pid'], $_GET['email'] ) && MDWCPT_Utils::is_absint( $_GET['pid'] ) && is_email( $_GET['email'] ) && is_string( $_GET['mdwcpt_unsubscribe'] ) ) {
							
				global $wpdb;
				$table_name = $wpdb->prefix . 'mdwcpt_subscriptions';
				$product = wc_get_product( get_the_id() );
				if ( $product->is_type( 'variable' ) ) {
					$check = in_array( (int) $_GET['pid'], $product->get_visible_children() );
				} else {
					$check = ( (int) $_GET['pid'] === get_the_id() );
				}
				
				if ( $check ) {
					$hash = $wpdb->get_var( $wpdb->prepare( 
						"SELECT hash FROM $table_name WHERE product_id = %d AND email = %s AND status != 'sent'", (int) $_GET['pid'], $_GET['email']
					));
					
					if ( $hash && wp_check_password( $_GET['mdwcpt_unsubscribe'], $hash ) ) {
						$r = $wpdb->query( $wpdb->prepare(
							"DELETE FROM $table_name WHERE product_id = %d AND email = %s AND status != 'sent'", (int) $_GET['pid'], $_GET['email'] 
						));
						$this->unsubscribe_popup = ( $r ) ? 'unsubscribe_success' : $this->unsubscribe_popup;
					} else {
						$this->unsubscribe_popup = 'unsubscribe_error';
					}
				} else {
					$this->unsubscribe_popup = 'unsubscribe_error';
				}
				
				add_action( 'wp_footer', array( $this, 'unsubscribe_popup_html' ) );
			}
		}
		
		/**
		 * Detect where we should render trigger links and hooks to appropriate filters
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function shop_loop() {
			
			$pos = MDWCPT_Utils::get_option( 'mdwcpt_form_position_shop' );
			if ( 'no' === $pos ) return;
			
			$priority = false !== strpos( $pos, 'before_' ) ? 99 : 1;						
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'render_form' ), $priority, 2 );
		}		

		/**
		 * Adds supscription form in products archives
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function render_form( $html, $product ) {
			
			$priority = false !== strpos( MDWCPT_Utils::get_option( 'mdwcpt_form_position_shop' ), 'before_' ) ? 99 : 1;
			remove_filter( current_filter(), array( $this, 'render_form' ), $priority, 2 );
						
			$form_html = MDWCPT_Utils::get_form( $product->get_id() );
			if ( ! $form_html ) return $html;
			
			if ( 99 === $priority )
				$html = sprintf( '<div class="mdwcpt-inloop">%s</div>%s', $form_html, $html );
			else
				$html = sprintf( '%s<div class="mdwcpt-inloop">%s</div>', $html, $form_html );
			
			return $html;
		}		
		
		/**
		 * Registers scripts and styles
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function register_assets() {
			wp_register_style( 'mdwcpt', MDWCPT_URL . '/assets/css/mdwcpt.css', array(), MDWCPT_VERSION );
			$this->maybe_add_inline_styles();
			wp_register_script( 'mdwcpt', MDWCPT_URL . '/assets/js/mdwcpt.js', array( 'jquery' ), MDWCPT_VERSION, true );
			if ( in_array( $this->unsubscribe_popup, [ 'unsubscribe_success', 'unsubscribe_error' ] ) ) {
				wp_enqueue_style( 'mdwcpt' );
				wp_enqueue_script( 'mdwcpt' );
			}
		}

		/**
		 * Add some css adjustments based on popular active themes
		 *
		 * @since      0.1.0
		 * @return     void
		 */			
		protected function maybe_add_inline_styles() {
			$theme = get_template();
			$css = '';
			if( 'storefront' === $theme ) {
				$css = "
					.mdwcpt-input {
						padding: .6180469716em;
						background-color: #f2f2f2;
						color: #43454b;
						border: 0;
						-webkit-appearance: none;
						box-sizing: border-box;
						font-weight: 400;
						box-shadow: inset 0 1px 1px rgba(0,0,0,.125);
						outline: none!important;
					}
					select.mdwcpt-input {
						-webkit-appearance: menulist;
					}					
				";
			} elseif( 'twentynineteen' === $theme ) {
				$css = "
					.mdwcpt-form {
						font-size: 0.75em;
					}
					input.mdwcpt-input {
						padding: 0.25rem 0.5rem;
					}					
					select.mdwcpt-input {
						background: #fff;
						border: solid 1px #ccc;
						box-sizing: border-box;
						outline: none;
						padding: 0.4044rem 0.5rem;
						outline-offset: 0;
						border-radius: 0;
					}
					select.mdwcpt-input:focus {
						border-color: #0073aa;
						outline: thin solid rgba(0, 115, 170, 0.15);
						outline-offset: -4px;
					}
					.mdwcpt-field label {
						font-size: 0.9em;
					}					
				";				
			} elseif( 'twentyseventeen' === $theme ) {
				$css = "				
					.mdwcpt-form-head {
						line-height: 2em!important;
					}					
					select.mdwcpt-input {
						padding: 0.4044rem 0.5rem;
					}					
				";				
			} elseif( 'twentysixteen' === $theme ) {
				$css = "				
					a.mdwcpt-trigger {
						color: initial!important;
					}
					.mdwcpt-form-head {
						line-height: 2.4em!important;
					}					
					select.mdwcpt-input {
						background: #f7f7f7;
						background-image: -webkit-linear-gradient(rgba(255, 255, 255, 0), rgba(255, 255, 255, 0));
						border: 1px solid #d1d1d1;
						border-radius: 2px;
						color: #686868;
						padding: 0.563em 0.4375em;
						width: 100%;
					}
					select.mdwcpt-input:focus {
						background-color: #fff;
						border-color: #007acc;
						color: #1a1a1a;
						outline: 0;
					}
				";				
			}
			
			if ( $css )
				wp_add_inline_style( 'mdwcpt', $css );
		}
		
		/**
		 * Render footer popup
		 *
		 * @since      0.1.0
		 * @return     void
		 */	
		public function unsubscribe_popup_html() {
			if ( ! in_array( $this->unsubscribe_popup, [ 'unsubscribe_success', 'unsubscribe_error' ] ) )
				return;
			
			$message = MDWCPT_Utils::get_option( 'messages[' . $this->unsubscribe_popup . ']' );
			if ( ! is_string( $message ) )
				return;
			
			$class = ( false === strpos( $this->unsubscribe_popup, '_success' ) ) ? 'woocommerce-error' : 'woocommerce-message';
			$form_title = MDWCPT_Utils::get_option( 'form[title]' );
			
			ob_start();		
			include( MDWCPT_DIR . '/templates/public/unsubscribe-popup.php' );
			ob_end_flush();
		}

		/**
		 * Registers mdwcpt_form shortcode
		 *
		 * @since      0.1.0
		 * @param      array   $atts
		 * @return     string
		 */		
		public function form_shortcode( $atts ) {
			
			$atts = shortcode_atts( array (
				'product_id' => '',
			), $atts, 'mdwcpt_form' );

			if ( ! MDWCPT_Utils::is_absint( $atts['product_id'] ) ) {
				if ( in_the_loop() && 'product' === get_post_type() ) {
					$atts['product_id'] = get_the_id();
				} else {
					return '';					
				}
			}
			
			return MDWCPT_Utils::get_form( intval( $atts['product_id'] ) );
		}
	}
}
