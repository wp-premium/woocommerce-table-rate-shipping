<div class="wrap woocommerce">
	<h2><?php _e( 'Shipping Zones', SHIPPING_ZONES_TEXTDOMAIN ); ?></h2>
	<div class="wc-col-container">
		<div class="wc-col-right">
			<div class="wc-col-wrap">
				<?php self::list_shipping_zones(); ?>
			</div>
		</div>
		<div class="wc-col-left">
			<div class="wc-col-wrap">
				<?php self::add_shipping_zone_form(); ?>
			</div>
		</div>
	</div>
</div>