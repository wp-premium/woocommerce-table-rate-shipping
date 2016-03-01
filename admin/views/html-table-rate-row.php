<?php

echo '<tr class="table_rate">
	<td class="check-column">
		<input type="checkbox" name="select" />
		<input type="hidden" class="rate_id" name="rate_id[' . $i . ']" value="' . $rate->rate_id . '" />
	</td>';

if ( sizeof( $shipping_classes ) ) {
	echo '<td><select class="select" name="shipping_class[' . $i . ']" style="min-width:100px;">
		<option value="" ' . selected( $rate->rate_class == "", true, false ) . '>' . __('Any class', 'woocommerce-table-rate-shipping') . '</option>
		<option value="0" ' . selected( $rate->rate_class == '0', true, false ) . '>' . __('No class', 'woocommerce-table-rate-shipping') . '</option>';

    foreach ( $shipping_classes as $class ) {
    	echo '<option value="' . $class->term_id . '" ' . selected( $rate->rate_class, $class->term_id, false ) . '>' . $class->name . '</option>';
    }

    echo '</select></td>';
}

echo '
    <td><select class="select" name="shipping_condition[' . $i . ']" style="min-width:100px;">
    	<option value="">' . __('None', 'woocommerce-table-rate-shipping') . '</option>
        <option value="price" ' . selected( $rate->rate_condition, 'price', false ) . '>' . __('Price', 'woocommerce-table-rate-shipping') . '</option>
        <option value="weight" ' . selected( $rate->rate_condition, 'weight', false ) . '>' . __('Weight', 'woocommerce-table-rate-shipping') . '</option>
        <option value="items" ' . selected( $rate->rate_condition, 'items', false ) . '>' . __('Item count', 'woocommerce-table-rate-shipping') . '</option>
        ';

if ( sizeof( $shipping_classes ) ) {
    echo '<option value="items_in_class" ' . selected( $rate->rate_condition, 'items_in_class', false ) . '>' . __('Item count (same class)', 'woocommerce-table-rate-shipping') . '</option>';
}

echo '
    </select></td>
    <td class="minmax">
    	<input type="text" class="text" value="' . $rate->rate_min . '" name="shipping_min[' . $i . ']" placeholder="' . __('n/a', 'woocommerce-table-rate-shipping') . '" size="4" /><input type="text" class="text" value="' . $rate->rate_max . '" name="shipping_max[' . $i . ']" placeholder="' . __('n/a', 'woocommerce-table-rate-shipping') . '" size="4" />
    </td>
    <td width="1%" class="checkbox"><input type="checkbox" class="checkbox" ' . checked( $rate->rate_priority, 1, false ) . ' name="shipping_priority[' . $i . ']" /></td>
    <td width="1%" class="checkbox"><input type="checkbox" class="checkbox" ' . checked( $rate->rate_abort, 1, false ) . ' name="shipping_abort[' . $i . ']" /></td>
    <td colspan="4" class="abort_reason">
        <input type="text" class="text" value="' . $rate->rate_abort_reason . '" placeholder="' . __('Optional abort reason text', 'woocommerce-table-rate-shipping') . '" name="shipping_abort_reason[' . $i . ']" />
    </td>
    <td class="cost">
    	<input type="text" class="text" value="' . $rate->rate_cost . '" name="shipping_cost[' . $i . ']" placeholder="' . __('0', 'woocommerce-table-rate-shipping') . '" size="4" />
    </td>
	<td class="cost cost_per_item">
        <input type="text" class="text" value="' . $rate->rate_cost_per_item . '" name="shipping_per_item[' . $i . ']" placeholder="' . __('0', 'woocommerce-table-rate-shipping') . '" size="4" />
    </td>
	<td class="cost cost_per_weight">
        <input type="text" class="text" value="' . $rate->rate_cost_per_weight_unit . '" name="shipping_cost_per_weight[' . $i . ']" placeholder="' . __('0', 'woocommerce-table-rate-shipping') . '" size="4" />
    </td>
	<td class="cost cost_percent">
        <input type="text" class="text" value="' . $rate->rate_cost_percent . '" name="shipping_cost_percent[' . $i . ']" placeholder="' . __('0', 'woocommerce-table-rate-shipping') . '" size="4" />
    </td>
    <td class="shipping_label">
        <input type="text" class="text" value="' . $rate->rate_label . '" name="shipping_label[' . $i . ']" size="8" />
    </td>
</tr>';