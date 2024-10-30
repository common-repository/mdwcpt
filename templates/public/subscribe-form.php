<?php
/**
 * Price tracking subscription form
 *
 * @package MDWC_Price_Tracker/Templates/Public
 * @since 0.1.0
 */

if ( ! defined('ABSPATH') ) exit; 
?>

<div class="mdwcpt-bg" id="mdwcpt-form-<?php echo esc_attr( $pid );?>">
	<div class="mdwcpt-form">
		<i class="mdwcpt-close"></i>
		<h3 class="mdwcpt-form-head"><?php echo esc_html( $form_title );?></h3>
		<?php if( ! is_user_logged_in() ) : ?>
			<div class="mdwcpt-field mdwcpt-field-email">
				<label for="mdwcpt-email-<?php echo esc_attr( $pid );?>"><?php echo esc_html( $email_title );?><span class="mdwcpt-req">*</span></label>			
				<input required type="email" name="mdwcpt-email" id="mdwcpt-email-<?php echo esc_attr( $pid );?>" class="mdwcpt-input" placeholder="<?php echo esc_attr( $email_palceholder );?>" />
			</div>
			<?php if( 'yes' === $force_pass)  : ?>
				<div class="mdwcpt-field mdwcpt-field-pass wcpt-hidden">
					<label for="mdwcpt-pass-<?php echo esc_attr( $pid );?>"><?php echo esc_html( $pass_title );?></label>
					<input disabled required type="password" name="mdwcpt-pass" id="mdwcpt-pass-<?php echo esc_attr( $pid );?>" class="mdwcpt-input" />
				</div>
			<?php endif;?>
		<?php endif;?>		
		<?php if( isset( $var_options ) && ! empty( $var_options ) ) : ?>
			<div class="mdwcpt-field mdwcpt-field-variation">
				<label for="mdwcpt-variation-<?php echo esc_attr( $pid );?>"><?php echo esc_html( $var_title );?><span class="mdwcpt-req">*</span></label>			
				<select required class="mdwcpt-input" name="mdwcpt-variation" id="mdwcpt-variation-<?php echo esc_attr( $pid );?>">
					<option value=""><?php echo esc_html( $var_def_option );?></option>
					<?php echo $var_options;?>
				</select>
				<div class="mdwcpt-gal"><?php echo $var_images;?></div>
			</div>
		<?php endif;?>	
		<div class="mdwcpt-field mdwcpt-field-price">
			<label for="mdwcpt-price-<?php echo esc_attr( $pid );?>"><?php echo esc_html( $price_title );?><span class="mdwcpt-req">*</span></label>			
			<input required type="number" name="mdwcpt-price" id="mdwcpt-price-<?php echo esc_attr( $pid );?>" class="mdwcpt-input" min="0" max="<?php echo esc_attr( $max_price );?>" step="any" />
		</div>
        <?php
        /**
         * Fired after subscription form main fields
         *
         * @since 0.2.0
         * @param int  $pid  Product ID
         */
        do_action( 'mdwcpt_form_fields_after', $pid );?>
		<?php if( $privacy_url ) : ?>
			<div class="mdwcpt-field mdwcpt-field-privacy">
				<small class="mdwcpt-small"><?php echo $privacy_label;?></small>
			</div>
		<?php endif;?>	
		<div class="mdwcpt-field mdwcpt-field-submit">
			<input type="hidden" name="mdwcpt-pid" value="<?php echo esc_attr( $pid );?>" />
			<input type="hidden" name="mdwcpt-key" value="<?php echo wp_create_nonce( 'mdwcpt-subscription-' . $pid ) ;?>" />
			<input type="hidden" name="mdwcpt-captcha" value="" />
			<button type="submit" name="mdwcpt-submit"><?php echo esc_html( $label );?></button>
		</div>
	</div>
</div>