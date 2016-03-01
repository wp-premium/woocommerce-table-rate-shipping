<div class="wrap woocommerce">
	<h2>
		<a href="<?php echo esc_url( admin_url('admin.php?page=shipping_zones') ); ?>"><?php _e( 'Shipping Zones', SHIPPING_ZONES_TEXTDOMAIN ); ?></a> &gt; <?php echo esc_html( $zone->zone_name ) ?>
		<form method="get" class="method_type_selector">
			<select name="method_type">
				<option value=""><?php _e( 'Choose a shipping method&hellip;', SHIPPING_ZONES_TEXTDOMAIN ); ?></option>
				<?php
					$shipping_methods = WC()->shipping->load_shipping_methods();

					foreach ( $shipping_methods as $method ) {
						if ( ! $method->supports( 'zones' ) )
							continue;

						echo '<option value="' . esc_attr( $method->id ) . '">' . esc_attr( $method->title ) . '</li>';
					}
				?>
			</select>
			<?php wp_nonce_field( 'woocommerce_add_method', '_wpnonce', false ); ?>
			<input type="hidden" name="add_method" value="true" />
			<input type="hidden" name="page" value="shipping_zones" />
			<input type="hidden" name="zone" value="<?php echo esc_attr( $zone_id ); ?>" />
			<input type="submit" class="add-new-h2" value="<?php _e( 'Add To Zone', SHIPPING_ZONES_TEXTDOMAIN ); ?>" />
		</form>
	</h2>
	<?php self::list_shipping_zone_methods(); ?>
</div>