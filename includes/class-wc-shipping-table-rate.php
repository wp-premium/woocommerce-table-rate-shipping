<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Shipping method class
*/
class WC_Shipping_Table_Rate extends WC_Shipping_Method {

	var $available_rates; // Available table rates titles and costs
	var $id;              // Method ID - should be unique to the shipping method
	var $instance_id;     // Instance ID number

	/**
	 * Constructor
	 */
	public function __construct( $instance_id = 0 ) {
		global $wpdb;

		$this->id                 = 'table_rate';
		$this->instance_id        = $instance_id;
		$this->method_title       = __( 'Table rates', 'woocommerce-table-rate-shipping' );
		$this->method_description = __( 'Table rates are dynamic rates based on a number of cart conditions.', 'woocommerce-table-rate-shipping' );
		$this->title              = $this->method_title;
		$this->has_settings       = false;
		$this->supports           = array( 'zones', 'shipping-zones', 'instance-settings' );
		$this->tax                = new WC_Tax();

		// Load the form fields.
		$this->init_form_fields();

		// Get settings
		$this->enabled            = 'yes';
		$this->title              = $this->get_option( 'title', __( 'Table Rate', 'woocommerce-table-rate-shipping' ) );
		$this->fee                = $this->get_option( 'handling_fee' );
		$this->order_handling_fee = $this->get_option( 'order_handling_fee' );
		$this->tax_status         = $this->get_option( 'tax_status' );
		$this->calculation_type   = $this->get_option( 'calculation_type' );
		$this->min_cost           = $this->get_option( 'min_cost' );
		$this->max_cost           = $this->get_option( 'max_cost' );
		$this->max_shipping_cost  = $this->get_option( 'max_shipping_cost' );

		// Table rate specific variables
		$this->rates_table     = $wpdb->prefix . 'woocommerce_shipping_table_rates';
		$this->available_rates = array();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * get_option function.
	 *
	 * Gets and option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key
	 * @param  mixed  $empty_value
	 * @return mixed  The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		// Instance options take priority over global options
		if ( in_array( $key, array_keys( $this->get_instance_form_fields() ) ) ) {
			return $this->get_instance_option( $key, $empty_value );
		}

		// Return global option
		return parent::get_option( $key, $empty_value );
	}

	/**
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key
	 * @param  mixed  $empty_value
	 * @return mixed  The value specified for the option or a default value for the option.
	 */
	public function get_instance_option( $key, $empty_value = null ) {
		if ( empty( $this->instance_settings ) ) {
			$this->init_instance_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->instance_settings[ $key ] ) ) {
			$form_fields = $this->get_instance_form_fields();

			if ( is_callable( array( $this, 'get_field_default' ) ) ) {
				$this->instance_settings[ $key ] = $this->get_field_default( $form_fields[ $key ] );
			} else {
				$this->instance_settings[ $key ] = empty( $form_fields[ $key ]['default'] ) ? '' : $form_fields[ $key ]['default'];
			}
		}

		if ( ! is_null( $empty_value ) && '' === $this->instance_settings[ $key ] ) {
			$this->instance_settings[ $key ] = $empty_value;
		}

		return $this->instance_settings[ $key ];
	}

	/**
	 * Get settings fields for instances of this shipping method (within zones).
	 * Should be overridden by shipping methods to add options.
	 * @since 3.0.0
	 * @return array
	 */
	public function get_instance_form_fields() {
		return apply_filters( 'woocommerce_shipping_instance_form_fields_' . $this->id, $this->instance_form_fields );
	}

	/**
	 * Return the name of the option in the WP DB.
	 * @since 3.0.0
	 * @return string
	 */
	public function get_instance_option_key() {
		return $this->instance_id ? $this->plugin_id . $this->id . '_' . $this->instance_id . '_settings' : '';
	}

	/**
	 * Initialise Settings for instances.
	 * @since 3.0.0
	 */
	public function init_instance_settings() {
		// 2nd option is for BW compat
		$this->instance_settings = get_option( $this->get_instance_option_key(), get_option( $this->plugin_id . $this->id . '-' . $this->instance_id . '_settings', null ) );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->instance_settings ) ) {
			$form_fields             = $this->get_instance_form_fields();
			$this->instance_settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields     = array(); // No global options for table rates
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Method Title', 'woocommerce-table-rate-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-table-rate-shipping' ),
				'default'     => __( 'Table Rate', 'woocommerce-table-rate-shipping' )
			),
			'tax_status' => array(
				'title'       => __( 'Tax Status', 'woocommerce-table-rate-shipping' ),
				'type'        => 'select',
				'description' => '',
				'desc_tip'    => true,
				'default'     => 'taxable',
				'options'     => array(
					'taxable' => __('Taxable', 'woocommerce-table-rate-shipping'),
					'none'    => __('None', 'woocommerce-table-rate-shipping')
				)
			),
			'order_handling_fee' => array(
				'title'       => __( 'Handling Fee', 'woocommerce-table-rate-shipping' ),
				'type'        => 'text',
				'desc_tip'    => __( 'Enter an amount, e.g. 2.50, or leave blank to disable. This cost is applied once for the order as a whole.', 'woocommerce-table-rate-shipping '),
				'default'     => '',
				'placeholder' => __( 'n/a', 'woocommerce-table-rate-shipping' )
			),
			'max_shipping_cost' => array(
				'title'       => __( 'Maximum Shipping Cost', 'woocommerce-table-rate-shipping' ),
				'type'        => 'text',
				'desc_tip'    => __( 'Maximum cost that the customer will pay after all the shipping rules have been applied. If the shipping cost calculated is bigger than this value, this cost will be the one shown.', 'woocommerce-table-rate-shipping '),
				'default'     => '',
				'placeholder' => __( 'n/a', 'woocommerce-table-rate-shipping' )
			),
			'rates' => array(
				'title'       => __( 'Rates', 'woocommerce-table-rate-shipping' ),
				'type'        => 'title',
				'description' => __( 'This is where you define your table rates which are applied to an order.', 'woocommerce-table-rate-shipping'),
				'default'     => ''
			),
			'calculation_type' => array(
				'title'       => __( 'Calculation Type', 'woocommerce-table-rate-shipping' ),
				'type'        => 'select',
				'description' => __( 'Per order rates will offer the customer all matching rates. Calculated rates will sum all matching rates and provide a single total.', 'woocommerce-table-rate-shipping' ),
				'default'     => '',
				'desc_tip'    => true,
				'options'     => array(
					''      => __( 'Per order', 'woocommerce-table-rate-shipping' ),
					'item'  => __( 'Calculated rates per item', 'woocommerce-table-rate-shipping' ),
					'line'  => __( 'Calculated rates per line item', 'woocommerce-table-rate-shipping' ),
					'class' => __( 'Calculated rates per shipping class', 'woocommerce-table-rate-shipping' )
				)
			),
			'handling_fee' => array(
				'title'       => __( 'Handling Fee Per [item]', 'woocommerce-table-rate-shipping' ),
				'type'        => 'text',
				'desc_tip'    => __( 'Handling fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable. Applied based on the "Calculation Type" chosen below.', 'woocommerce-table-rate-shipping '),
				'default'     => '',
				'placeholder' => __( 'n/a', 'woocommerce-table-rate-shipping' )
			),
			'min_cost' => array(
				'title'       => __( 'Minimum Cost Per [item]', 'woocommerce-table-rate-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __('Minimum cost for this shipping method (optional). If the cost is lower, this minimum cost will be enforced.', 'woocommerce-table-rate-shipping'),
				'default'     => '',
				'placeholder' => __( 'n/a', 'woocommerce-table-rate-shipping' )
			),
			'max_cost' => array(
				'title'       => __( 'Maximum Cost Per [item]', 'woocommerce-table-rate-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Maximum cost for this shipping method (optional). If the cost is higher, this maximum cost will be enforced.', 'woocommerce-table-rate-shipping'),
				'default'     => '',
				'placeholder' => __( 'n/a', 'woocommerce-table-rate-shipping' )
			),
		);

	}

	/**
	 * Admin options
	 */
	public function admin_options() {
		$this->instance_options();
	}

	/**
	 * Return admin options as a html string.
	 * @return string
	 */
	public function get_admin_options_html() {
		ob_start();
		$this->instance_options();
		return ob_get_clean();
	}

	/**
	 * admin_options function.
	 */
	public function instance_options() {
		?>
		<table class="form-table">
			<?php
			$this->generate_settings_html( $this->get_instance_form_fields() );
			?>
			<tr>
				<th><?php _e( 'Table Rates', 'woocommerce-table-rate-shipping' ); ?></th>
				<td>
					<?php wc_table_rate_admin_shipping_rows( $this ); ?>
				</td>
			</tr>
			<?php if ( sizeof( WC()->shipping->get_shipping_classes() ) ) : ?>
				<tr valign="top" id="shipping_class_priorities">
					<th scope="row" class="titledesc"><?php _e( 'Class Priorities', 'woocommerce-table-rate-shipping' ); ?></th>
					<td class="forminp" id="shipping_rates">
						<?php wc_table_rate_admin_shipping_class_priorities( $this->instance_id ); ?>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Process admin options.
	 */
	public function process_admin_options() {
		parent::process_admin_options();
		wc_table_rate_admin_shipping_rows_process( $this->instance_id );
	}

	/**
	 * is_available function.
	 *
	 * @param array $package
	 * @return bool
	 */
	public function is_available( $package ) {
		$available = true;

		if ( ! $this->get_rates( $package ) ) {
			$available = false;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this );
	}

	/**
	 * count_items_in_class function.
	 * @return int
	 */
	public function count_items_in_class( $package, $class_id ) {
		$count = 0;

		// Find shipping classes for products in the package
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() && $values['data']->get_shipping_class_id() == $class_id ) {
				$count += $values['quantity'];
			}
		}

		return $count;
	}

	/**
	 * get_cart_shipping_class_id function.
	 * @return int
	 */
	public function get_cart_shipping_class_id( $package ) {
		// Find shipping class for cart
		$found_shipping_classes = array();
		$shipping_class_id = 0;
		$shipping_class_slug = '';

		// Find shipping classes for products in the package
		if ( sizeof( $package['contents'] ) > 0 ) {
			foreach ( $package['contents'] as $item_id => $values ) {
				if ( $values['data']->needs_shipping() ) {
					$found_shipping_classes[ $values['data']->get_shipping_class_id() ] = $values['data']->get_shipping_class();
				}
			}
		}

		$found_shipping_classes = array_unique( $found_shipping_classes );

		if ( sizeof( $found_shipping_classes ) == 1 ) {
			$shipping_class_slug = current( $found_shipping_classes );
		} elseif ( $found_shipping_classes > 1 ) {

			// Get class with highest priority
			$priority   = get_option( 'woocommerce_table_rate_default_priority_' . $this->instance_id );
			$priorities = get_option( 'woocommerce_table_rate_priorities_' . $this->instance_id );

			foreach ( $found_shipping_classes as $class ) {
				if ( isset( $priorities[ $class ] ) && $priorities[ $class ] < $priority ) {
					$priority = $priorities[ $class ];
					$shipping_class_slug = $class;
				}
			}
		}

		$found_shipping_classes = array_flip( $found_shipping_classes );

		if ( isset( $found_shipping_classes[ $shipping_class_slug ] ) )
			$shipping_class_id = $found_shipping_classes[ $shipping_class_slug ];

		return $shipping_class_id;
	}

	/**
	 * query_rates function.
	 *
	 * @param array $args
	 * @return array
	 */
	public function query_rates( $args ) {
		global $wpdb;

		$defaults = array(
			'price'             => '',
			'weight'            => '',
			'count'             => 1,
			'count_in_class'    => 1,
			'shipping_class_id' => ''
		);

		$args = apply_filters( 'woocommerce_table_rate_query_rates_args', wp_parse_args( $args, $defaults ) );

		extract( $args, EXTR_SKIP );

		if ( $shipping_class_id == "" ) {
			$shipping_class_id_in = " AND rate_class IN ( '', '0' )";
		} else {
			$shipping_class_id_in = " AND rate_class IN ( '', '" . absint( $shipping_class_id ) . "' )";
		}

		$rates = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT rate_id, rate_cost, rate_cost_per_item, rate_cost_per_weight_unit, rate_cost_percent, rate_label, rate_priority, rate_abort, rate_abort_reason
				FROM {$this->rates_table}
				WHERE shipping_method_id IN ( %s )
				{$shipping_class_id_in}
				AND
				(
					rate_condition = ''
					OR
					(
						rate_condition = 'price'
						AND
						(
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) = '' )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) >=0 AND '{$price}' >= ( rate_min + 0 ) AND '{$price}' <= ( rate_max + 0 ) )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) = '' AND '{$price}' >= ( rate_min + 0 ) )
							OR
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) >= 0 AND '{$price}' <= ( rate_max + 0 ) )
						)
					)
					OR
					(
						rate_condition = 'weight'
						AND
						(
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) = '' )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) >=0 AND '{$weight}' >= ( rate_min + 0 ) AND '{$weight}' <= ( rate_max + 0 ) )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) = '' AND '{$weight}' >= ( rate_min + 0 ) )
							OR
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) >= 0 AND '{$weight}' <= ( rate_max + 0 ) )
						)
					)
					OR
					(
						rate_condition = 'items'
						AND
						(
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) = '' )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) >=0 AND '{$count}' >= ( rate_min + 0 ) AND '{$count}' <= ( rate_max + 0 ) )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) = '' AND '{$count}' >= ( rate_min + 0 ) )
							OR
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) >= 0 AND '{$count}' <= ( rate_max + 0 ) )
						)
					)
					OR
					(
						rate_condition = 'items_in_class'
						AND
						(
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) = '' )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) >= 0 AND '{$count_in_class}' >= ( rate_min + 0 ) AND '{$count_in_class}' <= ( rate_max + 0 ) )
							OR
							( ( rate_min + 0 ) >= 0 AND ( rate_max + 0 ) = '' AND '{$count_in_class}' >= ( rate_min + 0 ) )
							OR
							( ( rate_min + 0 ) = '' AND ( rate_max + 0 ) >= 0 AND '{$count_in_class}' <= ( rate_max + 0 ) )
						)
					)
				)
				ORDER BY rate_order ASC
			", $this->instance_id )
		);

		return apply_filters( 'woocommerce_table_rate_query_rates', $rates );
	}

	/**
	 * get_rates function.
	 * @return array
	 */
	public function get_rates( $package ) {
		global $wpdb;

		if ( ! $this->instance_id )
			return false;

		$rates = array();

		// Get rates, depending on type
		if ( $this->calculation_type == 'item' ) {

			// For each ITEM get matching rates
			$costs = array();

			$matched = false;

			foreach ( $package['contents'] as $item_id => $values ) {

				$_product = $values['data'];

				if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {

					$matching_rates = $this->query_rates( array(
						'price'             => $this->get_product_price( $_product ),
						'weight'            => (float) $_product->get_weight(),
						'count'             => 1,
						'count_in_class'    => $this->count_items_in_class( $package, $_product->get_shipping_class_id() ),
						'shipping_class_id' => $_product->get_shipping_class_id()
					) );

					$item_weight = round( (float) $_product->get_weight(), 2 );
					$item_fee    = (float) $this->get_fee( $this->fee, $this->get_product_price( $_product ) );
					$item_cost   = 0;

					foreach ( $matching_rates as $rate ) {
						$item_cost += (float) $rate->rate_cost;
						$item_cost += (float) $rate->rate_cost_per_weight_unit * $item_weight;
						$item_cost += ( (float) $rate->rate_cost_percent / 100 ) * $this->get_product_price( $_product );
						$matched = true;
						if ( $rate->rate_abort ) {
							if ( ! empty( $rate->rate_abort_reason ) ) {
								wc_add_notice( $rate->rate_abort_reason, 'notice' );
							}
							return;
						}
						if ( $rate->rate_priority )
							break;
					}

					$cost = ( $item_cost + $item_fee ) * $values['quantity'];

					if ( $this->min_cost && $cost < $this->min_cost ) {
						$cost = $this->min_cost;
					}
					if ( $this->max_cost && $cost > $this->max_cost ) {
						$cost = $this->max_cost;
					}

					$costs[ $item_id ] = $cost;

				}
			}

			if ( $matched ) {
				if ( $this->order_handling_fee ) {
					$costs['order'] = $this->order_handling_fee;
				} else {
					$costs['order'] = 0;
				}

				if ( $this->max_shipping_cost && ( $costs['order'] + array_sum( $costs ) ) > $this->max_shipping_cost ) {
					$rates[] = array(
						'id'    => is_callable( array( $this, 'get_rate_id' ) ) ? $this->get_rate_id() : $this->instance_id,
						'label' => __( $this->title, 'woocommerce-table-rate-shipping' ),
						'cost'  => $this->max_shipping_cost
					);
				} else {
					$rates[] = array(
						'id'       => is_callable( array( $this, 'get_rate_id' ) ) ? $this->get_rate_id() : $this->instance_id,
						'label'    => __( $this->title, 'woocommerce-table-rate-shipping' ),
						'cost'     => $costs,
						'calc_tax' => 'per_item',
						'package'  => $package,
					);
				}
			}

		} elseif ( $this->calculation_type == 'line' ) {

			// For each LINE get matching rates
			$costs = array();

			$matched = false;

			foreach ( $package['contents'] as $item_id => $values ) {

				$_product = $values['data'];

				if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {

					$matching_rates = $this->query_rates( array(
						'price'             => $this->get_product_price( $_product, $values['quantity'] ),
						'weight'            => (float) $_product->get_weight() * $values['quantity'],
						'count'             => $values['quantity'],
						'count_in_class'    => $this->count_items_in_class( $package, $_product->get_shipping_class_id() ),
						'shipping_class_id' => $_product->get_shipping_class_id()
					) );

					$item_weight = round( (float) $_product->get_weight() * $values['quantity'], 2 );
					$item_fee    = (float) $this->get_fee( $this->fee, $this->get_product_price( $_product, $values['quantity'] ) );
					$item_cost   = 0;

					foreach ( $matching_rates as $rate ) {
						$item_cost += (float) $rate->rate_cost;
						$item_cost += (float) $rate->rate_cost_per_item * $values['quantity'];
						$item_cost += (float) $rate->rate_cost_per_weight_unit * $item_weight;
						$item_cost += ( (float) $rate->rate_cost_percent / 100 ) * ( $this->get_product_price( $_product, $values['quantity'] ) );
						$matched = true;

						if ( $rate->rate_abort ) {
							if ( ! empty( $rate->rate_abort_reason ) ) {
								wc_add_notice( $rate->rate_abort_reason, 'notice' );
							}
							return;
						}
						if ( $rate->rate_priority )
							break;
					}

					$item_cost = $item_cost + $item_fee;

					if ( $this->min_cost && $item_cost < $this->min_cost ) {
						$item_cost = $this->min_cost;
					}
					if ( $this->max_cost && $item_cost > $this->max_cost ) {
						$item_cost = $this->max_cost;
					}

					$costs[ $item_id ] = $item_cost;
				}

			}

			if ( $matched ) {
				if ( $this->order_handling_fee ) {
					$costs['order'] = $this->order_handling_fee;
				} else {
					$costs['order'] = 0;
				}

				if ( $this->max_shipping_cost && ( $costs['order'] + array_sum( $costs ) ) > $this->max_shipping_cost ) {
					$rates[] = array(
						'id'      => is_callable( array( $this, 'get_rate_id' ) ) ? $this->get_rate_id() : $this->instance_id,
						'label'   => __( $this->title, 'woocommerce-table-rate-shipping' ),
						'cost'    => $this->max_shipping_cost,
						'package' => $package,
					);
				} else {
					$rates[] = array(
						'id'       => is_callable( array( $this, 'get_rate_id' ) ) ? $this->get_rate_id() : $this->instance_id,
						'label'    => __( $this->title, 'woocommerce-table-rate-shipping' ),
						'cost'     => $costs,
						'calc_tax' => 'per_item',
						'package'  => $package,
					);
				}
			}

		} elseif ( $this->calculation_type == 'class' ) {

			// For each CLASS get matching rates
			$total_cost	= 0;

			// First get all the rates in the table
			$all_rates = $this->get_shipping_rates();

			// Now go through cart items and group items by class
			$classes 	= array();

			foreach ( $package['contents'] as $item_id => $values ) {

				$_product = $values['data'];

				if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {

					$shipping_class = $_product->get_shipping_class_id();

					if ( ! isset( $classes[ $shipping_class ] ) ) {
						$classes[ $shipping_class ] = new stdClass();
						$classes[ $shipping_class ]->price = 0;
						$classes[ $shipping_class ]->weight = 0;
						$classes[ $shipping_class ]->items = 0;
						$classes[ $shipping_class ]->items_in_class = 0;
					}

					$classes[ $shipping_class ]->price          += $this->get_product_price( $_product, $values['quantity'] );
					$classes[ $shipping_class ]->weight         += (float) $_product->get_weight() * $values['quantity'];
					$classes[ $shipping_class ]->items          += $values['quantity'];
					$classes[ $shipping_class ]->items_in_class += $values['quantity'];
				}
			}

			$matched = false;
			$total_cost = 0;
			$stop = false;

			// Now we have groups, loop the rates and find matches in order
			foreach ( $all_rates as $rate ) {

				foreach ( $classes as $class_id => $class ) {

					if ( $class_id == "" ) {
						if ( $rate->rate_class != 0 && $rate->rate_class !== '' )
							continue;
					} else {
						if ( $rate->rate_class != $class_id && $rate->rate_class !== '' )
							continue;
					}

					$rate_match = false;

					switch ( $rate->rate_condition ) {
						case '' :
							$rate_match = true;
						break;
						case 'price' :
						case 'weight' :
						case 'items_in_class' :
						case 'items' :

							$condition = $rate->rate_condition;
							$value = $class->$condition;

							if ( $rate->rate_min === '' && $rate->rate_max === '' )
								$rate_match = true;

							if ( $value >= $rate->rate_min && $value <= $rate->rate_max )
								$rate_match = true;

							if ( $value >= $rate->rate_min && ! $rate->rate_max )
								$rate_match = true;

							if ( $value <= $rate->rate_max && ! $rate->rate_min )
								$rate_match = true;

						break;
					}

					// Rate matched class
					if ( $rate_match ) {
						$rate_label = ! empty( $rate->rate_label ) ? $rate->rate_label : $this->title;
						$class_cost = 0;
						$class_cost += (float) $rate->rate_cost;
						$class_cost += (float) $rate->rate_cost_per_item * $class->items_in_class;
						$class_cost += (float) $rate->rate_cost_per_weight_unit * $class->weight;
						$class_cost += ( (float) $rate->rate_cost_percent / 100 ) * $class->price;

						if ( $rate->rate_abort ) {
							if ( ! empty( $rate->rate_abort_reason ) ) {
								wc_add_notice( $rate->rate_abort_reason, 'notice' );
							}
							return;
						}

						if ( $rate->rate_priority ) {
							$stop = true;
						}

						$matched = true;

						$class_fee	= (float) $this->get_fee( $this->fee, $class->price );
						$class_cost += $class_fee;

						if ( $this->min_cost && $class_cost < $this->min_cost ) {
							$class_cost = $this->min_cost;
						}
						if ( $this->max_cost && $class_cost > $this->max_cost ) {
							$class_cost = $this->max_cost;
						}

						$total_cost += $class_cost;
					}
				}

				// Breakpoint
				if ( $stop ) {
					break;
				}
			}

			if ( $this->order_handling_fee ) {
				$total_cost += $this->order_handling_fee;
			}

			if ( $this->max_shipping_cost &&  $total_cost > $this->max_shipping_cost ) {
				$total_cost = $this->max_shipping_cost;
			}

			if ( $matched ) {
				$rates[] = array(
					'id'      => is_callable( array( $this, 'get_rate_id' ) ) ? $this->get_rate_id() : $this->instance_id,
					'label'   => __( $rate_label, 'woocommerce-table-rate-shipping' ),
					'cost'    => $total_cost,
					'package' => $package,
				);
			}

		} else {

			// For the ORDER get matching rates
			$shipping_class = $this->get_cart_shipping_class_id( $package );
			$price          = 0;
			$weight         = 0;
			$count          = 0;
			$count_in_class = 0;

			foreach ( $package['contents'] as $item_id => $values ) {

				$_product = $values['data'];

				if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
					$price  += ! empty( $values['line_total'] ) ? $values['line_total'] : $this->get_product_price( $_product, $values['quantity'] );
					$weight += (float) $_product->get_weight() * (float) $values['quantity'];
					$count  += $values['quantity'];

					if ( $_product->get_shipping_class_id() == $shipping_class )
						$count_in_class += $values['quantity'];

				}
			}

			$matching_rates = $this->query_rates( array(
				'price'             => $price,
				'weight'            => $weight,
				'count'             => $count,
				'count_in_class'    => $count_in_class,
				'shipping_class_id' => $shipping_class
			) );

			foreach ( $matching_rates as $rate ) {
				$label = $rate->rate_label;
				if ( ! $label )
					$label = $this->title;

				if ( $rate->rate_abort ) {
					if ( ! empty( $rate->rate_abort_reason ) ) {
						wc_add_notice( $rate->rate_abort_reason, 'notice' );
					}
					$rates = array(); // Clear rates
					break;
				}

				if ( $rate->rate_priority )
					$rates = array();

				$cost = (float) $rate->rate_cost;
				$cost += (float) $rate->rate_cost_per_item * $count;
				$cost += (float) $this->get_fee( $this->fee, $price );
				$cost += (float) $rate->rate_cost_per_weight_unit * $weight;
				$cost += ( (float) $rate->rate_cost_percent / 100 ) * $price;

				if ( $this->order_handling_fee ) {
					$cost += $this->order_handling_fee;
				}

				if ( $this->min_cost && $cost < $this->min_cost ) {
					$cost = $this->min_cost;
				}

				if ( $this->max_cost && $cost > $this->max_cost ) {
					$cost = $this->max_cost;
				}

				if ( $this->max_shipping_cost && $cost > $this->max_shipping_cost ) {
					$cost = $this->max_shipping_cost;
				}

				$rates[] = array(
					'id'      => is_callable( array( $this, 'get_rate_id' ) ) ? $this->get_rate_id( $rate->rate_id ) : $this->instance_id . ' : ' . $rate->rate_id,
					'label'   => __( $label, 'woocommerce-table-rate-shipping' ),
					'cost'    => $cost,
					'package' => $package,
				);

				if ( $rate->rate_priority ) {
					break;
				}
			}

		}

		// None found?
		if ( sizeof( $rates ) == 0 ) {
			return false;
		}

		// Set available
		$this->available_rates = $rates;

		return true;
	}

	/**
	 * calculate_shipping function.
	 * @param array $package
	 */
	public function calculate_shipping( $package = array() ) {
		if ( $this->available_rates ) {
			foreach ( $this->available_rates as $rate ) {
				$this->add_rate( $rate );
			}
		}
	}

	/**
	 * get_shipping_rates function.
	 * @param int $class (default: 0)
	 * @return array
	 */
	public function get_shipping_rates( ) {
		global $wpdb;

		return $wpdb->get_results( "
			SELECT * FROM {$this->rates_table}
			WHERE shipping_method_id = {$this->instance_id}
			ORDER BY rate_order ASC;
		" );
	}

	/**
	 * get_product_price function.
	 *
	 * @param object $_product
	 * @return array
	 */
	public function get_product_price( $_product, $qty = 1 ) {
		$row_base_price = $_product->get_price() * $qty;
		$row_base_price = apply_filters( 'woocommerce_table_rate_package_row_base_price', $row_base_price, $_product, $qty );

		if ( ! $_product->is_taxable() )
			return $row_base_price;

		if ( get_option('woocommerce_prices_include_tax') == 'yes' ) {

			$base_tax_rates = $this->tax->get_shop_base_rate( $_product->get_tax_class() );
			$tax_rates      = $this->tax->get_rates( $_product->get_tax_class() );

			if ( $tax_rates !== $base_tax_rates ) {
				$base_taxes     = $this->tax->calc_tax( $row_base_price, $base_tax_rates, true, true );
				$modded_taxes   = $this->tax->calc_tax( $row_base_price - array_sum( $base_taxes ), $tax_rates, false );
				$row_base_price = ( $row_base_price - array_sum( $base_taxes ) ) + array_sum( $modded_taxes );
			}
		}

		return $row_base_price;
	}

	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function process_instance_options() {
		$this->validate_settings_fields( $this->get_instance_form_fields() );

		if ( count( $this->errors ) > 0 ) {
			$this->display_errors();
			return false;
		} else {
			wc_table_rate_admin_shipping_rows_process( $this->instance_id );
			update_option( $this->get_instance_option_key(), $this->sanitized_fields );
			return true;
		}
	}
}
