<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wc_table_rate_admin_shipping_rows function.
 *
 * @access public
 * @param WC_Shipping_Table_Rate $instance
 */
function wc_table_rate_admin_shipping_rows( $instance ) {
	wp_enqueue_script( 'woocommerce_shipping_table_rate_rows' );

	// Get shipping classes
	$shipping_classes = get_terms( 'product_shipping_class', 'hide_empty=0' );
	?>
	<table id="shipping_rates" class="shippingrows widefat" cellspacing="0" style="position:relative;">
		<thead>
			<tr>
				<th class="check-column"><input type="checkbox"></th>
				<?php if ( sizeof( $shipping_classes ) ) : ?>
					<th><?php _e('Shipping Class', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Shipping class this rate applies to.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<?php endif; ?>
				<th><?php _e('Condition', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Condition vs. destination', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th><?php _e('Min&ndash;Max', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Bottom and top range for the selected condition. ', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th width="1%" class="checkbox"><?php _e('Break', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Break at this point. For per-order rates, no rates other than this will be offered. For calculated rates, this will stop any further rates being matched.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th width="1%" class="checkbox"><?php _e('Abort', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Enable this option to disable all rates/this shipping method if this row matches any item/line/class being quoted.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th class="cost"><?php _e('Row cost', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Cost for shipping the order, excluding tax.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th class="cost cost_per_item"><?php _e('Item cost', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Cost per item, excluding tax.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th class="cost cost_per_weight"><?php echo get_option('woocommerce_weight_unit') . ' ' . __('cost', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Cost per weight unit, excluding tax.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th class="cost cost_percent"><?php echo __('% cost', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Percentage of total to charge.', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
				<th class="shipping_label"><?php _e('Label', 'woocommerce-table-rate-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Label for the shipping method which the user will be presented. ', 'woocommerce-table-rate-shipping'); ?>">[?]</a></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th colspan="2"><a href="#" class="add-rate button button-primary"><?php _e('Add Shipping Rate', 'woocommerce-table-rate-shipping'); ?></a></th>
				<th colspan="9"><span class="description"><?php _e('Define your table rates here in order of priority.', 'woocommerce-table-rate-shipping'); ?></span> <a href="#" class="dupe button"><?php _e('Duplicate selected rows', 'woocommerce-table-rate-shipping'); ?></a> <a href="#" class="remove button"><?php _e('Delete selected rows', 'woocommerce-table-rate-shipping'); ?></a></th>
			</tr>
		</tfoot>
		<tbody class="table_rates" data-rates="<?php echo esc_attr( wp_json_encode( $instance->get_shipping_rates() ) ); ?>"></tbody>
	</table>
	<script type="text/template" id="tmpl-table-rate-shipping-row-template">
		<tr class="table_rate">
			<td class="check-column">
				<input type="checkbox" name="select" />
				<input type="hidden" class="rate_id" name="rate_id[{{{ data.index }}}]" value="{{{ data.rate.rate_id }}}" />
			</td>
			<?php if ( sizeof( $shipping_classes ) ) : ?>
				<td>
					<select class="select" name="shipping_class[{{{ data.index }}}]" style="min-width:100px;">
						<option value="" <# if ( "" === data.rate.rate_class ) { #>selected="selected"<# } #>><?php _e( 'Any class', 'woocommerce-table-rate-shipping' ); ?></option>
						<option value="0" <# if ( "0" === data.rate.rate_class ) { #>selected="selected"<# } #>><?php _e( 'No class', 'woocommerce-table-rate-shipping' ); ?></option>
						<?php foreach ( $shipping_classes as $class ) : ?>
							<option value="<?php echo esc_attr( $class->term_id ); ?>" <# if ( "<?php echo esc_attr( $class->term_id ); ?>" === data.rate.rate_class ) { #>selected="selected"<# } #>><?php echo esc_html( $class->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			<?php endif; ?>
			<td>
				<select class="select" name="shipping_condition[{{{ data.index }}}]" style="min-width:100px;">
					<option value="" <# if ( "" === data.rate.rate_condition ) { #>selected="selected"<# } #>><?php _e( 'None', 'woocommerce-table-rate-shipping' ); ?></option>
					<option value="price" <# if ( "price" === data.rate.rate_condition ) { #>selected="selected"<# } #>><?php _e( 'Price', 'woocommerce-table-rate-shipping'); ?></option>
					<option value="weight" <# if ( "weight" === data.rate.rate_condition ) { #>selected="selected"<# } #>><?php _e( 'Weight', 'woocommerce-table-rate-shipping'); ?></option>
					<option value="items" <# if ( "items" === data.rate.rate_condition ) { #>selected="selected"<# } #>><?php _e( 'Item count', 'woocommerce-table-rate-shipping'); ?></option>
					<?php if ( sizeof( $shipping_classes ) ) : ?>
						<option value="items_in_class" <# if ( "items_in_class" === data.rate.rate_condition ) { #>selected="selected"<# } #>><?php _e( 'Item count (same class)', 'woocommerce-table-rate-shipping' ); ?></option>
					<?php endif; ?>
				</select>
			</td>
			<td class="minmax">
				<input type="text" class="text" value="{{{ data.rate.rate_min }}}" name="shipping_min[{{{ data.index }}}]" placeholder="<?php _e( 'n/a', 'woocommerce-table-rate-shipping' ); ?>" size="4" /><input type="text" class="text" value="{{{ data.rate.rate_max }}}" name="shipping_max[{{{ data.index }}}]" placeholder="<?php _e( 'n/a', 'woocommerce-table-rate-shipping' ); ?>" size="4" />
			</td>
			<td width="1%" class="checkbox"><input type="checkbox" <# if ( '1' === data.rate.rate_priority ) { #>checked="checked"<# } #> class="checkbox" name="shipping_priority[{{{ data.index }}}]" /></td>
			<td width="1%" class="checkbox"><input type="checkbox" <# if ( '1' === data.rate.rate_abort ) { #>checked="checked"<# } #> class="checkbox" name="shipping_abort[{{{ data.index }}}]" /></td>
			<td colspan="4" class="abort_reason">
				<input type="text" class="text" value="{{{ data.rate.rate_abort_reason }}}" placeholder="<?php _e( 'Optional abort reason text', 'woocommerce-table-rate-shipping' ); ?>" name="shipping_abort_reason[{{{ data.index }}}]" />
			</td>
			<td class="cost">
				<input type="text" class="text" value="{{{ data.rate.rate_cost }}}" name="shipping_cost[{{{ data.index }}}]" placeholder="<?php _e( '0', 'woocommerce-table-rate-shipping' ); ?>" size="4" />
			</td>
			<td class="cost cost_per_item">
				<input type="text" class="text" value="{{{ data.rate.rate_cost_per_item }}}" name="shipping_per_item[{{{ data.index }}}]" placeholder="<?php _e( '0', 'woocommerce-table-rate-shipping' ); ?>" size="4" />
			</td>
			<td class="cost cost_per_weight">
				<input type="text" class="text" value="{{{ data.rate.rate_cost_per_weight_unit }}}" name="shipping_cost_per_weight[{{{ data.index }}}]" placeholder="<?php _e( '0', 'woocommerce-table-rate-shipping' ); ?>" size="4" />
			</td>
			<td class="cost cost_percent">
				<input type="text" class="text" value="{{{ data.rate.rate_cost_percent }}}" name="shipping_cost_percent[{{{ data.index }}}]" placeholder="<?php _e( '0', 'woocommerce-table-rate-shipping' ); ?>" size="4" />
			</td>
			<td class="shipping_label">
				<input type="text" class="text" value="{{{ data.rate.rate_label }}}" name="shipping_label[{{{ data.index }}}]" size="8" />
			</td>
		</tr>
	</script>
	<?php
}

/**
 * wc_table_rate_admin_shipping_class_priorities function.
 *
 * @access public
 * @return void
 */
function wc_table_rate_admin_shipping_class_priorities( $shipping_method_id ) {
	$classes = WC()->shipping->get_shipping_classes();
	if (!$classes) :
		echo '<p class="description">' . __( 'No shipping classes exist - you can ignore this option :)', 'woocommerce-table-rate-shipping' ) . '</p>';
	else :
		$priority = get_option( 'woocommerce_table_rate_default_priority_' . $shipping_method_id ) != '' ? get_option( 'woocommerce_table_rate_default_priority_' . $shipping_method_id ) : 10;
		?>
		<table class="widefat shippingrows" style="position:relative;">
			<thead>
				<tr>
					<th><?php _e('Class', 'woocommerce-table-rate-shipping'); ?></th>
					<th><?php _e('Priority', 'woocommerce-table-rate-shipping'); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2">
						<span class="description per_order"><?php _e('When calculating shipping, the cart contents will be <strong>searched for all shipping classes</strong>. If all product shipping classes are <strong>identical</strong>, the corresponding class will be used.<br/><strong>If there are a mix of classes</strong> then the class with the <strong>lowest number priority</strong> (defined above) will be used.', 'woocommerce-table-rate-shipping'); ?></span>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<th><?php _e('Default', 'woocommerce-table-rate-shipping'); ?></th>
					<td><input type="text" size="2" name="woocommerce_table_rate_default_priority" value="<?php echo $priority; ?>" /></td>
				</tr>
				<?php
				$woocommerce_table_rate_priorities = get_option( 'woocommerce_table_rate_priorities_' . $shipping_method_id );
				foreach ($classes as $class) {
					$priority = (isset($woocommerce_table_rate_priorities[$class->slug])) ? $woocommerce_table_rate_priorities[$class->slug] : 10;

					echo '<tr><th>'.$class->name.'</th><td><input type="text" value="'.$priority.'" size="2" name="woocommerce_table_rate_priorities['.$class->slug.']" /></td></tr>';
				}
				?>
			</tbody>
		</table>
		<?php
	endif;
}

/**
 * wc_table_rate_admin_shipping_rows_process function.
 *
 * @access public
 * @return void
 */
function wc_table_rate_admin_shipping_rows_process( $shipping_method_id ) {
	global $wpdb;

	// Clear cache
	$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_ship_%')" );

	// Save class priorities
	if ( empty( $_POST['woocommerce_table_rate_calculation_type'] ) ) {

		if ( isset( $_POST['woocommerce_table_rate_priorities'] ) ) {
			$priorities = array_map('intval', (array) $_POST['woocommerce_table_rate_priorities']);
			update_option( 'woocommerce_table_rate_priorities_' . $shipping_method_id, $priorities );
		}

		if ( isset( $_POST['woocommerce_table_rate_default_priority'] ) ) {
			update_option('woocommerce_table_rate_default_priority_' . $shipping_method_id, (int) esc_attr( $_POST['woocommerce_table_rate_default_priority'] ) );
		}

	} else {
		delete_option( 'woocommerce_table_rate_priorities_' . $shipping_method_id );
		delete_option( 'woocommerce_table_rate_default_priority_' . $shipping_method_id );
	}

	// Save rates
	$rate_ids			 		= isset( $_POST['rate_id'] ) ? array_map( 'intval', $_POST['rate_id'] ) : array();
	$shipping_class 			= isset( $_POST['shipping_class'] ) ? array_map( 'wc_clean', $_POST['shipping_class'] ) : array();
	$shipping_condition 		= isset( $_POST['shipping_condition'] ) ? array_map( 'wc_clean', $_POST['shipping_condition'] ) : array();
	$shipping_min 				= isset( $_POST['shipping_min'] ) ? array_map( 'wc_clean', $_POST['shipping_min'] ) : array();
	$shipping_max 				= isset( $_POST['shipping_max'] ) ? array_map( 'wc_clean', $_POST['shipping_max'] ) : array();
	$shipping_cost 				= isset( $_POST['shipping_cost'] ) ? array_map( 'wc_clean', $_POST['shipping_cost'] ) : array();
	$shipping_per_item 			= isset( $_POST['shipping_per_item'] ) ? array_map( 'wc_clean', $_POST['shipping_per_item'] ) : array();
	$shipping_cost_per_weight	= isset( $_POST['shipping_cost_per_weight'] ) ? array_map( 'wc_clean', $_POST['shipping_cost_per_weight'] ) : array();
	$cost_percent				= isset( $_POST['shipping_cost_percent'] ) ? array_map( 'wc_clean', $_POST['shipping_cost_percent'] ) : array();
	$shipping_label 			= isset( $_POST['shipping_label'] ) ? array_map( 'wc_clean', $_POST['shipping_label'] ) : array();
	$shipping_priority 			= isset( $_POST['shipping_priority'] ) ? array_map( 'wc_clean', $_POST['shipping_priority'] ) : array();
	$shipping_abort      		= isset( $_POST['shipping_abort'] ) ? array_map( 'wc_clean', $_POST['shipping_abort'] ) : array();
	$shipping_abort_reason 		= isset( $_POST['shipping_abort_reason'] ) ? array_map( 'wc_clean', $_POST['shipping_abort_reason'] ) : array();

	// Get max key
	$max_key = ( $rate_ids ) ? max( array_keys( $rate_ids ) ) : 0;

	for ( $i = 0; $i <= $max_key; $i++ ) {

		if ( ! isset( $rate_ids[ $i ] ) ) continue;

		$rate_id                   = $rate_ids[ $i ];
		$rate_class                = isset( $shipping_class[ $i ] ) ? $shipping_class[ $i ] : '';
		$rate_condition            = $shipping_condition[ $i ];
		$rate_min                  = isset( $shipping_min[ $i ] ) ? $shipping_min[ $i ] : '';
		$rate_max                  = isset( $shipping_max[ $i ] ) ? $shipping_max[ $i ] : '';
		$rate_cost                 = isset( $shipping_cost[ $i ] ) ? rtrim( rtrim( number_format( (double) $shipping_cost[ $i ], 4, '.', '' ), '0' ), '.' ) : '';;
		$rate_cost_per_item        = isset( $shipping_per_item[ $i ] ) ? rtrim( rtrim( number_format( (double) $shipping_per_item[ $i ], 4, '.', '' ), '0' ), '.' ) : '';;
		$rate_cost_per_weight_unit = isset( $shipping_cost_per_weight[ $i ] ) ? rtrim( rtrim( number_format( (double) $shipping_cost_per_weight[ $i ], 4, '.', '' ), '0' ), '.' ) : '';;
		$rate_cost_percent         = isset( $cost_percent[ $i ] ) ? rtrim( rtrim( number_format( (double) str_replace( '%', '', $cost_percent[ $i ] ), 2, '.', '' ), '0' ), '.' ) : '';;
		$rate_label                = isset( $shipping_label[ $i ] ) ? $shipping_label[ $i ] : '';;
		$rate_priority             = isset( $shipping_priority[ $i ] ) ? 1 : 0;
		$rate_abort                = isset( $shipping_abort[ $i ] ) ? 1 : 0;
		$rate_abort_reason         = isset( $shipping_abort_reason[ $i ] ) ? $shipping_abort_reason[ $i ] : '';

		// Format min and max
		switch ( $rate_condition ) {
			case 'weight' :
			case 'price' :
				if ( $rate_min ) $rate_min = number_format( $rate_min, 2, '.', '' );
				if ( $rate_max ) $rate_max = number_format( $rate_max, 2, '.', '' );
			break;
			case 'items' :
			case 'items_in_class' :
				if ( $rate_min ) $rate_min = round( $rate_min );
				if ( $rate_max ) $rate_max = round( $rate_max );
			break;
			default :
				$rate_min = $rate_max = '';
			break;
		}

		if ( $rate_id > 0 ) {

			// Update row
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_shipping_table_rates',
				array(
					'rate_class'                => $rate_class,
					'rate_condition'            => sanitize_title( $rate_condition ),
					'rate_min'                  => $rate_min,
					'rate_max'                  => $rate_max,
					'rate_cost'                 => $rate_cost,
					'rate_cost_per_item'        => $rate_cost_per_item,
					'rate_cost_per_weight_unit' => $rate_cost_per_weight_unit,
					'rate_cost_percent'         => $rate_cost_percent,
					'rate_label'                => $rate_label,
					'rate_priority'             => $rate_priority,
					'rate_order'                => $i,
					'shipping_method_id'        => $shipping_method_id,
					'rate_abort'                => $rate_abort,
					'rate_abort_reason'         => $rate_abort_reason
				),
				array(
					'rate_id' => $rate_id
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
				),
				array(
					'%d'
				)
			);

		} else {

			// Insert row
			$result = $wpdb->insert(
				$wpdb->prefix . 'woocommerce_shipping_table_rates',
				array(
					'rate_class'				=> $rate_class,
					'rate_condition' 			=> sanitize_title( $rate_condition ),
					'rate_min'					=> $rate_min,
					'rate_max'					=> $rate_max,
					'rate_cost'					=> $rate_cost,
					'rate_cost_per_item'		=> $rate_cost_per_item,
					'rate_cost_per_weight_unit'	=> $rate_cost_per_weight_unit,
					'rate_cost_percent'			=> $rate_cost_percent,
					'rate_label'				=> $rate_label,
					'rate_priority'				=> $rate_priority,
					'rate_order'				=> $i,
					'shipping_method_id'		=> $shipping_method_id,
					'rate_abort'                => $rate_abort,
					'rate_abort_reason'         => $rate_abort_reason
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
				)
			);
		}
	}
}
