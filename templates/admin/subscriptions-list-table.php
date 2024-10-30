<?php
/**
 * Subscriptions list Table
 *
 * @package MDWC_Price_Tracker/Templates/Admin
 * @since 0.1.0
 */

if ( ! defined('ABSPATH') ) exit; 
?>

<div class="wrap">
	<h2><?php _e('Price tracking subscriptions', 'mdwcpt')?></h2>
	<?php $this->table->views();?>
	<form id="mdwcpt-subscriptions-table" method="GET">
		<?php $this->table->search_box( __( 'Search by product name or id, user name or email', 'mdwcpt' ), 'subscription' );?>
		<?php if ( isset( $_REQUEST['status'] ) ) : ?>	
			<input type="hidden" name="status" value="<?php echo $_REQUEST['status'] ?>"/>
		<?php endif; ?>
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
		<?php $this->table->display() ?>
	</form>
</div>