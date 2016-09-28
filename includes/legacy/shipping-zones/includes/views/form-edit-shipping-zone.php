<div class="wrap woocommerce">
	<h2><?php _e( 'Edit Shipping Zone', SHIPPING_ZONES_TEXTDOMAIN ); ?> &mdash; <?php echo esc_html( $zone->zone_name ) ?></h2><br class="clear" />
	<div class="form-wrap">
		<form id="add-zone" method="post">
			<table class="form-table">
				<tr>
					<th>
						<label for="zone_name"><?php _e( 'Name', SHIPPING_ZONES_TEXTDOMAIN ); ?></label>
					</th>
					<td>
						<input type="text" name="zone_name" id="zone_name" class="input-text" placeholder="<?php _e( 'Enter a name which describes this zone', SHIPPING_ZONES_TEXTDOMAIN ); ?>" value="<?php echo esc_attr( $zone->zone_name ) ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="zone_name"><?php _e( 'Enable', SHIPPING_ZONES_TEXTDOMAIN ); ?></label>
					</th>
					<td>
						<label><input type="checkbox" name="zone_enabled" value="1" id="zone_enabled" class="input-checkbox" <?php checked( $zone->zone_enabled, 1 ); ?> /> <?php _e( 'Enable this zone', SHIPPING_ZONES_TEXTDOMAIN ); ?></label>
					</td>
				</tr>
				<tr>
					<th>
						<?php _e( 'Zone Type', SHIPPING_ZONES_TEXTDOMAIN ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'Zone Type', SHIPPING_ZONES_TEXTDOMAIN ); ?></span></legend>

							<p><label><input type="radio" name="zone_type" value="countries" id="zone_type" class="input-radio" <?php checked( $zone->zone_type, 'countries' ); ?> /> <?php _e( 'This shipping zone is based on one or more countries', SHIPPING_ZONES_TEXTDOMAIN ); ?></label></p>

							<div class="zone_type_options zone_type_countries">
								<select multiple="multiple" name="zone_type_countries[]" style="width:450px;" data-placeholder="<?php _e('Choose countries&hellip;', SHIPPING_ZONES_TEXTDOMAIN); ?>" class="chosen_select">
						        	<?php
						        		foreach ( $countries as $key => $val ) {
			                    			echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $location_counties ) ) . '>' . esc_html( $val ) . '</option>';
						        		}
			                    	?>
						        </select>
						        <p><button class="select_all button"><?php _e('All', SHIPPING_ZONES_TEXTDOMAIN); ?></button><button class="select_none button"><?php _e('None', SHIPPING_ZONES_TEXTDOMAIN); ?></button><button class="button select_europe"><?php _e('EU States', SHIPPING_ZONES_TEXTDOMAIN); ?></button></p>
					        </div>

							<p><label><input type="radio" name="zone_type" value="states" id="zone_type" class="input-radio" <?php checked( $zone->zone_type, 'states' ); ?> /> <?php _e( 'This shipping zone is based on one or more states/counties', SHIPPING_ZONES_TEXTDOMAIN ); ?></label></p>

							<div class="zone_type_options zone_type_states">
								<select multiple="multiple" name="zone_type_states[]" style="width:450px;" data-placeholder="<?php _e('Choose states/counties&hellip;', SHIPPING_ZONES_TEXTDOMAIN); ?>"  class="chosen_select">
			                   		<?php
			                   			foreach ( $countries as $key => $val ) {
			                   				echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selected_states ), true, false ) . '>' . esc_html( $val ) . '</option>';

						        			if ( $states = WC()->countries->get_states( $key ) ) {
						        				foreach ( $states as $state_key => $state_value ) {
									    			echo '<option value="' . esc_attr( $key . ':' . $state_key  ) . '" ' . selected( in_array( $key . ':' . $state_key, $selected_states ), true, false ) . '>' . esc_html( $val . ' &gt; ' . $state_value ) . '</option>';
									    		}
						        			}
			                    		}
			                   		?>
			                	</select>
			                	<p><button class="select_all button"><?php _e( 'All', SHIPPING_ZONES_TEXTDOMAIN ); ?></button><button class="select_none button"><?php _e( 'None', SHIPPING_ZONES_TEXTDOMAIN ); ?></button><button class="button select_us_states"><?php _e( 'US States', 'wc-shipping-zones '); ?></button><button class="button select_europe"><?php _e( 'EU States', SHIPPING_ZONES_TEXTDOMAIN ); ?></button></p>
					        </div>

							<p><label><input type="radio" name="zone_type" value="postcodes" id="zone_type" class="input-radio" <?php checked( $zone->zone_type, 'postcodes' ); ?> /> <?php _e( 'This shipping zone is based on one or more postcodes/zips', SHIPPING_ZONES_TEXTDOMAIN ); ?></label></p>

							<div class="zone_type_options zone_type_postcodes">
								<select name="zone_type_postcodes" style="width:450px;" data-placeholder="<?php _e('Choose countries&hellip;', SHIPPING_ZONES_TEXTDOMAIN); ?>" title="Country" class="chosen_select">
						        	<?php
						        		foreach ( $countries as $key => $val ) {

			                   				echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selected_states ), true, false ) . '>' . esc_html( $val ) . '</option>';

						        			if ( $states = WC()->countries->get_states( $key ) ) {
						        				foreach ( $states as $state_key => $state_value ) {
									    			echo '<option value="' . esc_attr( $key . ':' . $state_key  ) . '" ' . selected( in_array( $key . ':' . $state_key, $selected_states ), true, false ) . '>' . esc_html( $val . ' &gt; ' . $state_value ) . '</option>';
									    		}
						        			}
			                    		}
			                    	?>
						        </select>

						        <p>
						        	<label for="postcodes"><?php _e( 'Postcodes', SHIPPING_ZONES_TEXTDOMAIN ); ?> <img class="help_tip" width="16" data-tip='<?php _e('List 1 postcode per line. Wildcards (*) and ranges (for numeric postcodes) are supported.', SHIPPING_ZONES_TEXTDOMAIN) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" /></label>
						        	<textarea name="postcodes" id="postcodes" class="input-text large-text" cols="25" rows="5"><?php
						        		foreach ( $location_postcodes as $location ) {
							        		echo esc_textarea( $location ) . "\n";
						        		}
						        	?></textarea>
						        </p>
					        </div>

						</fieldset>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button" name="edit_zone" value="<?php _e( 'Save changes', SHIPPING_ZONES_TEXTDOMAIN ); ?>" />
				<?php wp_nonce_field( 'woocommerce_save_zone', 'woocommerce_save_zone_nonce' ); ?>
			</p>
		</form>
	</div>
</div>
