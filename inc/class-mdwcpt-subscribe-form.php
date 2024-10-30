<?php
/**
 * Handles subscribe form rendering on public area
 *
 * @link       https://maxidevs.com/price-tracker-for-woocommerce/
 * @since      0.1.0
 * @package    MDWC_Price_Tracker/Public/Classes
 */ 

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists( 'MDWCPT_Subscribe_Form' ) ) :

	/**
	 * Subscribe form class
	 */    
	class MDWCPT_Subscribe_Form {
		
		/**
		 * Form position
		 *
		 * @var        string
		 * @since      0.1.0
		 * @access     protected
		 */			
		protected $position;
		
		/**
		 * Form visibility based on current user and product
		 *
		 * @var        bool
		 * @since      0.1.0
		 * @access     protected
		 */			
		protected $is_visible;
		
		/**
		 * A product instance to which form is attached
		 *
		 * @var        WC_Product
		 * @since      0.1.0
		 * @access     private
		 */			
		private $product;
		
		/**
		 * Whether object is called as shortcode or based on settings
		 *
		 * @var        bool
		 * @since      0.1.0
		 * @access     private
		 */
		private $is_shortcode;
		
		/**
		 * Array of products ids of rendered forms
		 *
		 * @var        array
		 * @since      0.1.0
		 * @access     private
		 */		
		private static $_rendered = array();
		
		/**
		 * Constructor
		 *
		 * @since      0.1.0
		 */			
		public function __construct( WC_Product $product, $is_shortcode = true ) {
			if ( $product->is_type('variation') )
				return;
			
			//echo '<pre>'; var_dump( $product ); echo '</pre>';
			$this->is_shortcode = (bool) $is_shortcode;
			$this->product = $product;			
			$this->position = MDWCPT_Utils::get_option( 'form_position' );
			
			$visibility = MDWCPT_Utils::get_option( 'visible_for' );			
			$hide_outofstock = MDWCPT_Utils::get_option( 'hide_out_of_stock' );
			
			$this->is_visible = true;
			if ( ( 'customers' === $visibility && ! is_user_logged_in() ) || ( 'guests' === $visibility && is_user_logged_in() ) )
				$this->is_visible = false;
			elseif ( 'outofstock' === $product->get_stock_status() && 'yes' === $hide_outofstock )
				$this->is_visible = false;
        }

		/**
		 * Allow access to protected and private props
		 *
		 * @since        0.1.0		 
		 * @param string $prop
		 * @return       mixed
		 */			
		public function __get( $prop ) {
			if ( property_exists( $this, $prop ) )
				return $this->{$prop};
			
			return null;
		}
		
		/**
		 * Hookup to wp
		 *
		 * @since      0.1.0
		 * @return     void
		 */		
        public function hookup() {
			if ( ! $this->is_visible || apply_filters( 'mdwcpt_prevent_form_rendering', false, $this ) )
				return;
			
			if ( ! wp_style_is( 'mdwcpt' ) ) {
				if ( did_action( 'wp_enqueue_scripts' ) )	
					wp_enqueue_style( 'mdwcpt' );
				else
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 20 );
			}
			
			if ( ! wp_script_is( 'mdwcpt' ) ) {
				if ( did_action( 'wp_enqueue_scripts' ) ) {
					$this->localize_script();
					wp_enqueue_script( 'mdwcpt' );					
				} else {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );					
				}
			}			
			
			$outofstock = ( 'outofstock' === $this->product->get_stock_status() );
			
			if ( $this->is_shortcode ) {
				add_action( 'mdwcpt_shortcode', array( $this, 'trigger_button' ), 10 );
			} elseif ( 'after_price' === $this->position || 'before_price' === $this->position || ( $outofstock && false !== strpos( $this->position, '_cart' ) ) ) {
				add_filter( 'woocommerce_get_price_html', array( $this, 'filter_html' ), 10, 1 );
			} elseif ( 'after_desc' === $this->position || 'before_desc' === $this->position ) {
				add_filter( 'woocommerce_short_description', array( $this, 'filter_html' ), 10, 1 );
			} elseif ( 'after_cart' === $this->position && ! $outofstock ) {
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'trigger_button' ), 10 );
			} elseif ( 'before_cart' === $this->position && ! $outofstock ) {
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'trigger_button' ), 10 );
			} elseif ( 'after_cart_button' === $this->position && ! $outofstock ) {
				add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'trigger_button' ), 10 );
			} elseif ( 'before_cart_button' === $this->position && ! $outofstock ) {
				add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'trigger_button' ), 10 );
			} elseif ( 'after_meta' === $this->position ) {
				add_action( 'woocommerce_product_meta_end', array( $this, 'trigger_button' ), 10 );
			} elseif ( 'before_meta' === $this->position ) {
				add_action( 'woocommerce_product_meta_start', array( $this, 'trigger_button' ), 10 );
			}
        }
		
		/**
		 * Prepends or appends button to woocommerce generated html
		 *
		 * @since        0.1.0		 
		 * @param string $prop
		 * @return       string
		 */			
		public function filter_html( $woo_html ) {
			remove_filter( 'woocommerce_get_price_html', array( $this, 'filter_html' ), 10, 1 );
			if ( false !== strpos( $this->position, 'after_' ) )
				return $woo_html.$this->get_button_html();
			else
				return $this->get_button_html().$woo_html;
		}

		/**
		 * Renders button html
		 *
		 * @since        0.1.0
		 * @return       string
		 */			
		public function trigger_button() {
			remove_action( current_action(), array( $this, 'trigger_button' ), 10 );
			echo $this->get_button_html();
		}
		
		/**
		 * Get button html
		 *
		 * @since        0.1.0
		 * @access       protected
		 * @return       string
		 */			
		protected function get_button_html() {			

			if ( ! $this->is_visible )
				return '';

			$label = (string) MDWCPT_Utils::get_option( 'form[submit_label]' );
			$html = '<a class="mdwcpt-trigger" data-mdwcpt-form="' . esc_attr( $this->product->get_id() ) . '"><span class="mdwcpt-track-icon"></span>' . esc_html( $label ) . '</a>';			
			
			$html = (string) apply_filters( 'mdwcpt_trigger_button_html', $html, $this, $label );
			if ( $html ) {
				add_action( 'wp_footer', array( $this, 'subscribe_form' ), 1 );
			}	
			
			return $html;
		}

		/**
		 * Render form html
		 *
		 * @since        0.1.0
		 * @return       string
		 */			
		public function subscribe_form() {
			if ( ! $this->is_visible )
				return;
			
			$pid = $this->product->get_id();
			if ( in_array( $pid, self::$_rendered ) )
				return;
			
			self::$_rendered[] = $pid;
			$labels = MDWCPT_Utils::get_option( 'form' );
			
			$label = $labels['submit_label'];
			$form_title = $labels['title'];
			$email_title = $labels['email_title'];
			$pass_title = $labels['pass_title'];
			$email_palceholder = $labels['email_placeholder'];
			$price_title = $labels['price_title'];			
			$var_title = $labels['variations_title'];
			$var_def_option = $labels['variations_def_option'];
			$privacy_page_id = function_exists( 'wc_privacy_policy_page_id' ) ? wc_privacy_policy_page_id() : get_option( 'wp_page_for_privacy_policy', 0 );
			$privacy_url = is_int( $privacy_page_id ) && $privacy_page_id > 0 ? get_permalink( $privacy_page_id ) : '';
			$privacy_label = str_replace( '{terms_url}', esc_url( $privacy_url ), $labels['privacy_title'] );
			$privacy_label = wp_kses( $privacy_label, ['a' => [ 'href' => true, 'target' => true, 'class' => true, 'title' => true ] ], [ 'http' , 'https' ] );
			
			$force_pass = MDWCPT_Utils::get_option( 'force_pass' );
			$max_price = ( $this->product->is_type( 'variable' ) || $this->product->is_type( 'grouped' ) ) ? '' : $this->product->get_price( 'edit' );		

			//$variations = array();
			if ( $this->product->is_type( 'variable' ) || $this->product->is_type( 'grouped' ) ) {
				$var_options = $var_images = $placeholder_id ='';
				$rendered_images = array();
				$hide_outofstock = MDWCPT_Utils::get_option( 'mdwcpt_hide_out_of_stock' );				
				$method =  ( $this->product->is_type( 'variable' ) ) ? 'get_visible_children' : 'get_children';
				foreach( $this->product->{$method}() as $id ) {
					$variation = wc_get_product( $id );
					if ( 'yes' === $hide_outofstock && 'outofstock' === $variation->get_stock_status() )
						continue;
					//$variations[] = $variation;
					
					//$context ='edit' allow as to avoid returning image from parent product if child image not set
					$img_id = $variation->get_image_id( 'edit' );
					if ( ! MDWCPT_Utils::is_absint( $img_id ) ) {
						if ( empty( $placeholder_id ) ) {
							$placeholder_id = get_option( 'woocommerce_placeholder_image', false );
							$placeholder_id = ! is_numeric( $placeholder_id ) ? 0 : $placeholder_id;
						}
						$img_id = $placeholder_id;
					}
					
					$price = $variation->get_price();
					$var_options .= sprintf( 
						'<option data-mdwcpt-maxprice="%s" data-mdwcpt-img="%s" value="%s">%s - %s</option>', 
						esc_attr( $price ), esc_attr( $img_id ), $id,
						esc_html( $variation->get_name() ), 
						wp_strip_all_tags( wc_price( $price ) )
					);
					
					$img_url = ( 0 === $img_id ) ? wc_placeholder_img_src() : wp_get_attachment_image_url( (int) $img_id );
					if ( ! in_array( $img_id, $rendered_images ) && $img_url ) {
						$rendered_images[] = $img_id;
						$var_images .= sprintf( 
							'<img role="presentation" src="%s" data-mdwcpt-img="%s" class="mdwcpt-img wcpt-hidden" />', 
							esc_url( $img_url ),
							esc_attr( $img_id )
						);
					}
				}
			}			
			
			ob_start();
			//echo '<pre>'; var_dump( $variations ); echo '</pre>';			
			include( MDWCPT_DIR . '/templates/public/subscribe-form.php' );
			ob_end_flush();
		}

		public function enqueue_scripts() {
			$this->localize_script();
			wp_enqueue_script( 'mdwcpt' );		
		}
		
		protected function localize_script() {
			
			$captcha = MDWCPT_Utils::get_option( 'mdwcpt_recaptcha' );
			$render_captcha = ( 
				isset( $captcha['enabled'], $captcha['site_key'] ) && count( array_filter( $captcha ) ) >= 3 && count( array_filter( $captcha, 'is_string' ) ) >= 3 &&
				( 'all' === $captcha['enabled'] || ( 'guests' === $captcha['enabled'] && ! is_user_logged_in() ) )	
			);
			
			$captcha_key = $render_captcha ? $captcha['site_key'] : '0';
			
			wp_localize_script( 'mdwcpt', 'mdwcpt_l10n', array (
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'captcha_key' => esc_attr( $captcha_key ), 
			) );		
		}		

		public function enqueue_styles() {
			wp_enqueue_style( 'mdwcpt' );
		}			

    }

endif;