<div class="wrap woocommerce">
	<div class="icon32 icon32-woocommerce-delivery" id="icon-woocommerce"><br /></div>
	<h2><a href="<?php echo admin_url('admin.php?page=shipping_zones'); ?>"><?php _e( 'Shipping Zones', 'woocommerce-table-rate-shipping' ); ?></a> &gt; <a href="<?php echo esc_url( remove_query_arg( 'method' ) ); ?>"><?php echo $zone->zone_name ?></a> &gt; <?php echo $shipping_method->title; ?></h2><br class="clear" />
	<form id="add-method" method="post">
		<?php $shipping_method->instance_options(); ?>
		<p class="submit"><input type="submit" class="button" name="save_method" value="<?php _e('Save shipping method', 'woocommerce-table-rate-shipping'); ?>" /></p>
		<?php wp_nonce_field( 'woocommerce_save_method', 'woocommerce_save_method_nonce' ); ?>
	</form>
</div>