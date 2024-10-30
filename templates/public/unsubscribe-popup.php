<?php
/**
 * Unsubscribe form
 *
 * @package MDWC_Price_Tracker/Templates/Public
 * @since 0.1.0
 */

if ( ! defined('ABSPATH') ) exit; 
?>

<div class="mdwcpt-bg mdwcpt-popup">
	<div class="mdwcpt-form">
		<i class="mdwcpt-close"></i>
		<h3 class="mdwcpt-form-head"><?php echo esc_html( $form_title );?></h3>
		<div class="mdwcpt-field">
			<div class="mdwcpt-msg <?php echo $class;?>">
				<?php echo esc_html( $message );?>
			</div>
		</div>
	</div>
</div>