<h3><?php _e( 'Add Shipping Zone', SHIPPING_ZONES_TEXTDOMAIN ); ?></h3>

<p><?php _e( 'Zones cover multiple countries (and states) and can have shipping methods assigned to them. Each customer will be assigned a single matching shipping zone in order of priority, and if no zones apply the "default zone" will be used.', SHIPPING_ZONES_TEXTDOMAIN ); ?></p>

<p><?php _e( 'If you wish to disable shipping for a location, add a zone and assign no shipping methods to it.', SHIPPING_ZONES_TEXTDOMAIN ); ?></p>

<div class="form-wrap">
	<form id="add-zone" method="post">
		<div class="form-field">
			<label for="zone_name"><?php _e( 'Zone Name', SHIPPING_ZONES_TEXTDOMAIN ); ?></label>
			<input type="text" name="zone_name" id="zone_name" class="input-text" placeholder="<?php echo esc_attr( __( 'Zone', SHIPPING_ZONES_TEXTDOMAIN ) . ' ' . ( $zone_count + 1 ) ); ?>" />
		</div>
		<div class="form-field">
			<label><?php _e( 'Zone Type', SHIPPING_ZONES_TEXTDOMAIN ); ?></label>
			<fieldset>
				<legend class="screen-reader-text"><span><?php _e( 'Zone Type', SHIPPING_ZONES_TEXTDOMAIN ); ?></span></legend>

				<p><label><input type="radio" name="zone_type" value="countries" id="zone_type" class="input-radio" checked="checked" /> <?php _e( 'This shipping zone is based on one or more countries', SHIPPING_ZONES_TEXTDOMAIN ); ?></label></p>

				<div class="zone_type_options zone_type_countries">
					<select multiple="multiple" name="zone_type_countries[]" data-placeholder="<?php _e('Choose countries&hellip;', SHIPPING_ZONES_TEXTDOMAIN); ?>" class="chosen_select wp-enhanced-select">
			        	<?php
			        		foreach ( $countries as $key => $val ) {
                    			echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $val ) . '</option>';
			        		}
                    	?>
			        </select>
			        <p><button class="select_all button"><?php _e( 'All', SHIPPING_ZONES_TEXTDOMAIN ); ?></button><button class="select_none button"><?php _e( 'None', SHIPPING_ZONES_TEXTDOMAIN ); ?></button><button class="button select_europe"><?php _e( 'EU States', SHIPPING_ZONES_TEXTDOMAIN ); ?></button></p>
		        </div>

				<p><label><input type="radio" name="zone_type" value="states" id="zone_type" class="input-radio" /> <?php _e( 'This shipping zone is based on one of more states and counties', SHIPPING_ZONES_TEXTDOMAIN ); ?></label></p>

				<div class="zone_type_options zone_type_states">
					<select multiple="multiple" name="zone_type_states[]" data-placeholder="<?php _e('Choose states/counties&hellip;', SHIPPING_ZONES_TEXTDOMAIN); ?>"  class="chosen_select">
                   		<?php
                   			foreach ( $countries as $key => $val ) {
                   				echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $val ) . '</option>';

			        			if ( $states = WC()->countries->get_states( $key ) ) {
			        				foreach ( $states as $state_key => $state_value ) {
			        					echo '<option value="' . esc_attr( $key . ':' . $state_key ) . '">' . esc_html( $val . ' &gt; ' . $state_value ) . '</option>';
						    		}
			        			}
                    		}
                   		?>
                	</select>
                	<p><button class="select_all button"><?php _e( 'All', SHIPPING_ZONES_TEXTDOMAIN ); ?></button><button class="select_none button"><?php _e( 'None', SHIPPING_ZONES_TEXTDOMAIN ); ?></button><button class="button select_us_states"><?php _e('US States', SHIPPING_ZONES_TEXTDOMAIN); ?></button><button class="button select_europe"><?php _e('EU States', SHIPPING_ZONES_TEXTDOMAIN); ?></button></p>
		        </div>

				<p><label><input type="radio" name="zone_type" value="postcodes" id="zone_type" class="input-radio" /> <?php _e( 'This shipping zone is based on one of more postcodes/zips', SHIPPING_ZONES_TEXTDOMAIN ); ?></label></p>

				<div class="zone_type_options zone_type_postcodes">
					<select name="zone_type_postcodes" data-placeholder="<?php _e('Choose countries&hellip;', SHIPPING_ZONES_TEXTDOMAIN); ?>" title="Country" class="chosen_select" style="width:95%">
			        	<?php
							foreach ( $countries as $key => $val ) {
                   				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $base, false ) . '>' . esc_html( $val ) . '</option>';

			        			if ( $states = WC()->countries->get_states( $key ) ) {
			        				foreach ( $states as $state_key => $state_value ) {
						    			echo '<option value="' . esc_attr( $key . ':' . $state_key ) . '">' . esc_html( $val . ' &gt; ' . $state_value ) . '</option>';
						    		}
			        			}
                    		}
                    	?>
			        </select>

			        <label for="postcodes"><?php _e( 'Postcodes', SHIPPING_ZONES_TEXTDOMAIN ); ?> <img class="help_tip" width="16" data-tip='<?php _e('List 1 postcode per line. Wildcards (*) and ranges (for numeric postcodes) are supported.', SHIPPING_ZONES_TEXTDOMAIN) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" /></label>
			        <textarea name="postcodes" id="postcodes" class="input-text large-text" cols="25" rows="5"></textarea>

		        </div>

			</fieldset>
		</div>
		<p class="submit"><input type="submit" class="button button-primary" name="add_zone" value="<?php _e( 'Add shipping zone', SHIPPING_ZONES_TEXTDOMAIN ); ?>" /></p>
		<?php wp_nonce_field( 'woocommerce_save_zone', 'woocommerce_save_zone_nonce' ); ?>
	</form>
</div>