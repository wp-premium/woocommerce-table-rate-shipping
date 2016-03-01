<?php

/**
 * wc_table_rate_admin_shipping_rows function.
 *
 * @access public
 * @param mixed $table_rate_shipping
 */
function wc_table_rate_admin_shipping_rows( $table_rate_shipping ) {
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
				<th colspan="2"><a href="#" class="add button button-primary"><?php _e('Add Shipping Rate', 'woocommerce-table-rate-shipping'); ?></a></th>
				<th colspan="9"><span class="description"><?php _e('Define your table rates here in order of priority.', 'woocommerce-table-rate-shipping'); ?></span> <a href="#" class="dupe button"><?php _e('Duplicate selected rows', 'woocommerce-table-rate-shipping'); ?></a> <a href="#" class="remove button"><?php _e('Delete selected rows', 'woocommerce-table-rate-shipping'); ?></a></th>
			</tr>
		</tfoot>
		<tbody class="table_rates">
    	<?php
    	$i = -1; foreach( $table_rate_shipping->get_shipping_rates() as $rate ) { $i++;
			include( 'views/html-table-rate-row.php' );
    	}
    	?>
    	</tbody>
    </table>

	<?php

	// Javascript for Table Rates admin
	ob_start();
	?>
	// Labels
	jQuery('label[for="woocommerce_table_rate_handling_fee"], label[for="woocommerce_table_rate_max_cost"], label[for="woocommerce_table_rate_min_cost"]').each(function( i, el ) {
		jQuery(el).data( 'o_label', jQuery(el).text() );
	});

	// Options which depend on calc type
	jQuery('#woocommerce_table_rate_calculation_type').change(function(){

		var selected = jQuery( this ).val();

		if ( selected == 'item' ) {
			jQuery( 'td.cost_per_item input' ).attr( 'disabled', 'disabled' ).addClass('disabled');
		} else {
			jQuery( 'td.cost_per_item input' ).removeAttr( 'disabled' ).removeClass('disabled');
		}

		if ( selected ) {
			jQuery( '#shipping_class_priorities' ).hide();
			jQuery( 'td.shipping_label, th.shipping_label' ).hide();
		} else {
			jQuery( '#shipping_class_priorities' ).show();
			jQuery( 'td.shipping_label, th.shipping_label' ).show();
		}

		if ( ! selected ) {
			jQuery( '#shipping_class_priorities span.description.per_order' ).show();
			jQuery( '#shipping_class_priorities span.description.per_class' ).hide();
		}

		var label_text = '<?php _e( 'Order', 'woocommerce-table-rate-shipping' ); ?>';

		if ( selected == 'item' ) {
			label_text = '<?php _e( 'Item', 'woocommerce-table-rate-shipping' ); ?>';
		} else if ( selected == 'line' ) {
			label_text = '<?php _e( 'Line Item', 'woocommerce-table-rate-shipping' ); ?>';
		} else if ( selected == 'class' ) {
			label_text = '<?php _e( 'Class', 'woocommerce-table-rate-shipping' ); ?>';
		}

		jQuery('label[for="woocommerce_table_rate_handling_fee"], label[for="woocommerce_table_rate_max_cost"], label[for="woocommerce_table_rate_min_cost"]').each(function( i, el ) {
			var text  = jQuery(el).data( 'o_label' );
			text = text.replace( '[item]', label_text );
			jQuery(el).text( text );
		});

	}).change();

	// shipping_condition select box
	jQuery('#shipping_rates').on( 'change', 'select[name^="shipping_condition"]', function() {
		var selected = jQuery( this ).val();
		var $row 	 = jQuery( this ).closest('tr');

		if ( selected == '' ) {

			$row.find('input[name^="shipping_min"], input[name^="shipping_max"]').val('').attr('disabled', 'disabled').addClass('disabled');

		} else {

			$row.find('input[name^="shipping_min"], input[name^="shipping_max"]').removeAttr('disabled').removeClass('disabled');

		}
	} );

	jQuery('select[name^="shipping_condition"]').change();

	// Abort select box
	jQuery('#shipping_rates').on( 'change', 'input[name^="shipping_abort["]', function() {
		var checked = jQuery( this ).is(':checked');
		var $row 	= jQuery( this ).closest('tr');

		if ( checked ) {

			$row.find('td.cost').hide();
			$row.find('td.abort_reason').show();
			$row.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').attr('disabled', 'disabled').addClass('disabled');

		} else {

			$row.find('td.cost').show();
			$row.find('td.abort_reason').hide();
			$row.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').removeAttr('disabled').removeClass('disabled');

		}

		jQuery('#woocommerce_table_rate_calculation_type').change();
	});
	jQuery('input[name^="shipping_abort["]').change();

	// Add rates
	jQuery('#shipping_rates').on( 'click', 'a.add', function() {

		var size = jQuery('tbody.table_rates .table_rate').size();

		jQuery('<?php
			$rate                            = new stdClass();
			$rate->rate_id                   = '';
			$rate->rate_class                = '';
			$rate->rate_condition            = '';
			$rate->rate_min                  = '';
			$rate->rate_max                  = '';
			$rate->rate_priority             = '';
			$rate->rate_abort                = '';
			$rate->rate_abort_reason         = '';
			$rate->rate_cost                 = '';
			$rate->rate_cost_per_item        = '';
			$rate->rate_cost_per_weight_unit = '';
			$rate->rate_cost_percent         = '';
			$rate->rate_label                = '';
			$i                               = "{ROW_I}";
			ob_start();
			include( 'views/html-table-rate-row.php' );
			echo str_replace( '{ROW_I}', "' + size + '", addslashes( str_replace( array( "\r", "\t", "\n" ), "",  ob_get_clean() ) ) );
		?>').appendTo('#shipping_rates tbody.table_rates');

		jQuery('#woocommerce_table_rate_calculation_type').change();
		jQuery('select[name^="shipping_condition"]').change();
		jQuery('input[name^="shipping_abort"]').change();

		return false;
	});

	// Remove rows
	jQuery('#shipping_rates').on( 'click', 'a.remove', function() {
		var answer = confirm("<?php _e('Delete the selected rates?', 'woocommerce-table-rate-shipping'); ?>")
		if (answer) {

			var rate_ids  = [];

			jQuery('#shipping_rates tbody.table_rates tr td.check-column input:checked').each(function(i, el){

				var rate_id = jQuery(el).closest('tr.table_rate').find('.rate_id').val();

				rate_ids.push( rate_id );

				jQuery(el).closest('tr.table_rate').addClass('deleting');

			});

			var data = {
				action: 'woocommerce_table_rate_delete',
				rate_id: rate_ids,
				security: '<?php echo wp_create_nonce("delete-rate"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
				jQuery('tr.deleting').fadeOut('300', function(){
					jQuery(this).remove();
				});
			});
		}
		return false;
	});

	// Dupe row
	jQuery('#shipping_rates').on( 'click', 'a.dupe', function() {
		var answer = confirm("<?php _e('Duplicate the selected rates?', 'woocommerce-table-rate-shipping'); ?>")
		if (answer) {
			jQuery('#shipping_rates tbody.table_rates tr td.check-column input:checked').each(function(i, el){
				var dupe = jQuery(el).closest('tr').clone();

				dupe.find('.rate_id').val('0');

				// Append
				jQuery('#shipping_rates tbody.table_rates').append( dupe );
			});

			// Re-index keys
			var loop = 0;
			jQuery('tbody.table_rates .table_rate').each(function( index, row ){
				jQuery('input, select', row).each(function( i, el ){

					var t = jQuery(el);
					t.attr('name', t.attr('name').replace(/\[([^[]*)\]/, "[" + loop + "]"));

				});
				loop++;
			});
		}
		return false;
	});

	// Rate ordering
	jQuery('#shipping_rates tbody.table_rates').sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		handle: 'td',
		scrollSensitivity:40,
		helper:function(e,ui){
			ui.children().each(function(){
				jQuery(this).width(jQuery(this).width());
			});
			ui.css('left', '0');
			return ui;
		},
		start:function(event,ui){
			ui.item.css('background-color','#f6f6f6');
		},
		stop:function(event,ui){
			ui.item.removeAttr('style');
			shipping_rates_row_indexes();
		}
	});

	function shipping_rates_row_indexes() {
		// Re-index keys
		var loop = 0;
		jQuery('#shipping_rates tr.table_rate').each(function( index, row ){
			jQuery('input.text, input.checkbox, select.select', row).each(function( i, el ){

				var t = jQuery(el);
				t.attr('name', t.attr('name').replace(/\[([^[]*)\]/, "[" + loop + "]"));

			});
			loop++;
		});
	};
	<?php
	wc_enqueue_js( ob_get_clean() );
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
		echo '<p class="description">' . __('No shipping classes exist - you can ignore this option :)', 'woocommerce-table-rate-shipping') . '</p>';
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
	$shipping_class 			= isset( $_POST['shipping_class'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_class'] ) : array();
	$shipping_condition 		= isset( $_POST['shipping_condition'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_condition'] ) : array();
	$shipping_min 				= isset( $_POST['shipping_min'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_min'] ) : array();
	$shipping_max 				= isset( $_POST['shipping_max'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_max'] ) : array();
	$shipping_cost 				= isset( $_POST['shipping_cost'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_cost'] ) : array();
	$shipping_per_item 			= isset( $_POST['shipping_per_item'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_per_item'] ) : array();
	$shipping_cost_per_weight	= isset( $_POST['shipping_cost_per_weight'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_cost_per_weight'] ) : array();
	$cost_percent				= isset( $_POST['shipping_cost_percent'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_cost_percent'] ) : array();
	$shipping_label 			= isset( $_POST['shipping_label'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_label'] ) : array();
	$shipping_priority 			= isset( $_POST['shipping_priority'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_priority'] ) : array();
	$shipping_abort      		= isset( $_POST['shipping_abort'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_abort'] ) : array();
	$shipping_abort_reason 		= isset( $_POST['shipping_abort_reason'] ) ? array_map( 'woocommerce_clean', $_POST['shipping_abort_reason'] ) : array();

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