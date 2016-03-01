<?php
/*
Plugin Name: WooCommerce Table Rate Shipping
Plugin URI: http://www.woothemes.com/products/table-rate-shipping/
Description: Table rate shipping lets you define rates depending on location vs shipping class, price, weight, or item count.
Version: 2.9.2
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 4.0
Tested up to: 4.2

	Copyright: 2009-2015 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '3034ed8aff427b0f635fe4c86bbf008a', '18718' );

/**
 * Check if WooCommerce is active
 */
if ( is_woocommerce_active() ) {

	define( 'TABLE_RATE_SHIPPING_VERSION', '2.9.2' );

	if ( defined( 'WP_DEBUG' ) && 'true' == WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || 'true' == WP_DEBUG_DISPLAY ) ) {
		define( 'TABLE_RATE_SHIPPING_DEBUG', true );
	} else {
		define( 'TABLE_RATE_SHIPPING_DEBUG', false );
	}

	if ( ! defined( 'SHIPPING_ZONES_TEXTDOMAIN' ) ) {
		define( 'SHIPPING_ZONES_TEXTDOMAIN', 'woocommerce-table-rate-shipping' );
	}

	/**
	 * Localisation
	 */
	load_plugin_textdomain( 'woocommerce-table-rate-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * Installation
	 */
	register_activation_hook( __FILE__, 'install_table_rate_shipping' );

	function install_table_rate_shipping() {

		include_once( 'admin/table-rate-install.php' );

		wc_table_rate_install();

		update_option( 'table_rate_shipping_version', TABLE_RATE_SHIPPING_VERSION );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	function table_rate_shipping_plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_table_rate_shipping_docs_url', 'http://docs.woothemes.com/document/table-rate-shipping/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-table-rate-shipping' ) ) . '">' . __( 'Docs', 'woocommerce-table-rate-shipping' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_table_rate_support_url', 'http://support.woothemes.com/' ) ) . '" title="' . esc_attr( __( 'Visit Premium Customer Support Forum', 'woocommerce-table-rate-shipping' ) ) . '">' . __( 'Premium Support', 'woocommerce-table-rate-shipping' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}

	add_filter( 'plugin_row_meta', 'table_rate_shipping_plugin_row_meta', 10, 2 );

	/**
	 * AJAX Handlers
	 */
	if ( defined( 'DOING_AJAX' ) ) {
		include_once( 'admin/table-rate-ajax.php' );
	}

	/**
	 * Zones
	 */
	if ( ! class_exists( 'WC_Shipping_zone' ) ) {
		include_once( 'shipping-zones/class-wc-shipping-zones.php' );
	}

	/**
	 * Install check (for updates)
	 */
	if ( get_option( 'table_rate_shipping_version' ) < TABLE_RATE_SHIPPING_VERSION ) {
		install_table_rate_shipping();
	}

	/**
	 * Welcome notices
	 */
	if ( get_option( 'hide_table_rate_welcome_notice' ) == '' ) {
		add_action( 'admin_notices', 'woocommerce_table_rate_welcome_notice' );
	}

	function woocommerce_table_rate_welcome_notice() {
		wp_enqueue_style( 'woocommerce-activation', WC()->plugin_url() . '/assets/css/activation.css' );
		?>
		<div id="message" class="updated woocommerce-message wc-connect">
			<div class="squeezer">
				<h4><?php _e( '<strong>Table Rates is installed</strong> &#8211; Add some shipping zones to get started :)', 'woocommerce-table-rate-shipping' ); ?></h4>
				<p class="submit"><a href="<?php echo admin_url('admin.php?page=shipping_zones'); ?>" class="button-primary"><?php _e( 'Setup Zones', 'woocommerce-table-rate-shipping' ); ?></a> <a class="skip button-primary" href="http://docs.woothemes.com/document/table-rate-shipping/"><?php _e('Documentation', 'woocommerce-table-rate-shipping'); ?></a></p>
			</div>
		</div>
		<?php
		update_option( 'hide_table_rate_welcome_notice', 1 );
	}

	/**
	 * init_styles function.
	 */
	function woocommerce_shipping_table_rate_styles() {
	    wp_enqueue_style( 'woocommerce_shipping_table_rate_styles', plugins_url( '/assets/css/admin.css', __FILE__ ) );
	}
	add_action( 'woocommerce_shipping_zones_css', 'woocommerce_shipping_table_rate_styles' );

	/**
	 * woocommerce_init_shipping_table_rate function.
	 */
	function woocommerce_init_shipping_table_rate() {

		if ( ! class_exists( 'WC_Shipping_Table_Rate' ) ) :

		/**
	 	* Shipping method class
	 	*/
		class WC_Shipping_Table_Rate extends WC_Shipping_Method {

			var $available_rates; // Available table rates titles and costs
			var $instance_id;     // ID for the instance/shipping method. id-number
			var $id;              // Method ID - should be unique to the shipping method
			var $number;          // Instance ID number

			/**
			 * Constructor
			 */
			public function __construct( $instance = false ) {
				global $wpdb;

				$this->id           = 'table_rate';
				$this->method_title = __( 'Table rates', 'woocommerce-table-rate-shipping' );
				$this->title        = $this->method_title;
				$this->has_settings = false;
				$this->enabled      = 'yes';
				$this->supports     = array( 'zones' );
				$this->tax          = new WC_Tax();

				// Load the form fields.
				$this->init_form_fields();

				// Load any GLOBAL settings
				$this->init_settings();

				// If we have an instance, set the id
				if ( $instance !== FALSE ) {
					$this->_set( $instance );

					// Load INSTANCE settings
					$this->init_instance_settings();

					$this->title              = $this->get_option( 'title', __( 'Table Rate', 'woocommerce-table-rate-shipping' ) );
					$this->fee                = $this->get_option( 'handling_fee' );
					$this->order_handling_fee = $this->get_option( 'order_handling_fee' );
					$this->tax_status         = $this->get_option( 'tax_status' );
					$this->calculation_type   = $this->get_option( 'calculation_type' );
					$this->min_cost           = $this->get_option( 'min_cost' );
					$this->max_cost           = $this->get_option( 'max_cost' );
					$this->max_shipping_cost  = $this->get_option( 'max_shipping_cost' );
				}

				// Table rate specific variables
				$this->rates_table     = $wpdb->prefix . 'woocommerce_shipping_table_rates';
				$this->available_rates = array();
			}

			/**
			 * Instance related functions (not yet in core API's)
			 */
			private function _set( $number ) {
				$this->number = $number;
				$this->instance_id = $this->id . '-' . $number;
			}

			/**
			 * Initialise Instance Settings
			 */
			public function init_instance_settings() {

				// Load instance settings (if applicable)
				if ( ! empty( $this->instance_fields ) && ! empty( $this->instance_id ) ) {

					$instance_settings = ( array ) get_option( $this->plugin_id . $this->instance_id . '_settings' );

					if ( ! $instance_settings ) {

						// If there are no settings defined, load defaults
						foreach ( $this->instance_fields as $k => $v ) {
							$instance_settings[ $k ] = isset( $v['default'] ) ? $v['default'] : '';
						}

					} else {

						// Prevent "undefined index" errors.
						foreach ( $this->instance_fields as $k => $v ) {
							$instance_settings[ $k ] = isset( $instance_settings[ $k ] ) ? $instance_settings[ $k ] : $v['default'];
						}

					}

					// Set and decode escaped values
					$this->settings = array_merge( (array) $this->settings, array_map( array( $this, 'format_settings' ), $instance_settings ) );
				}

				if ( isset( $this->settings['enabled'] ) ) {
					$this->enabled = $this->settings['enabled'];
				}

				$this->settings = apply_filters( 'woocommerce_table_rate_instance_settings', $this->settings, $this->instance_id );
			}

			/**
			 * Initialise Gateway Settings Form Fields
			 */
			public function init_form_fields() {
		    	$this->form_fields     = array(); // No global options for table rates
		    	$this->instance_fields = array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'woocommerce-table-rate-shipping' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable this table rate', 'woocommerce-table-rate-shipping' ),
						'default' => 'yes'
					),
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
			 * admin_options function.
			 */
			public function instance_options() {
				include_once( 'admin/table-rate-rows.php' );
				?>
				<table class="form-table">
					<?php
					$this->generate_settings_html( $this->instance_fields );
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
								<?php wc_table_rate_admin_shipping_class_priorities( $this->number ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</table>
				<?php
			}

			/**
			 * Admin Panel Options Processing
			 * - Saves the options to the DB
			 *
			 * @since 1.0.0
			 */
			public function process_instance_options() {
				include_once( 'admin/table-rate-rows.php' );

				$this->validate_settings_fields( $this->instance_fields  );

				if ( count( $this->errors ) > 0 ) {
					$this->display_errors();
					return false;
				} else {
					wc_table_rate_admin_shipping_rows_process( $this->number  );
					update_option( $this->plugin_id . $this->instance_id . '_settings', $this->sanitized_fields );
					return true;
				}
			}

			/**
			 * is_available function.
			 *
			 * @param array $package
			 * @return bool
			 */
			public function is_available( $package ) {
				$available = true;

				if ( $this->enabled === "no" ) {
					$available = false;
				}

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
					$priority   = get_option('woocommerce_table_rate_default_priority_' . $this->number );
					$priorities = get_option( 'woocommerce_table_rate_priorities_' . $this->number );

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
					", $this->number )
				);

				return apply_filters( 'woocommerce_table_rate_query_rates', $rates );
			}

			/**
			 * get_rates function.
			 * @return array
			 */
			public function get_rates( $package ) {
				global $wpdb;

				if ( $this->enabled == "no" || ! $this->instance_id )
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
								'weight'            => $_product->get_weight(),
								'count'             => 1,
								'count_in_class'    => $this->count_items_in_class( $package, $_product->get_shipping_class_id() ),
								'shipping_class_id' => $_product->get_shipping_class_id()
							) );

							$item_weight = round( $_product->get_weight(), 2 );
							$item_fee    = $this->get_fee( $this->fee, $this->get_product_price( $_product ) );
							$item_cost   = 0;

							foreach ( $matching_rates as $rate ) {
								$item_cost += $rate->rate_cost;
								$item_cost += $rate->rate_cost_per_weight_unit * $item_weight;
								$item_cost += ( $rate->rate_cost_percent / 100 ) * $this->get_product_price( $_product );
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
								'id'    => $this->instance_id,
								'label' => __( $this->title, 'woocommerce-table-rate-shipping' ),
								'cost'  => $this->max_shipping_cost
							);
						} else {
							$rates[] = array(
								'id'       => $this->instance_id,
								'label'    => __( $this->title, 'woocommerce-table-rate-shipping' ),
								'cost'     => $costs,
								'calc_tax' => 'per_item'
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
								'weight'            => $_product->get_weight() * $values['quantity'],
								'count'             => $values['quantity'],
								'count_in_class'    => $this->count_items_in_class( $package, $_product->get_shipping_class_id() ),
								'shipping_class_id' => $_product->get_shipping_class_id()
							) );

							$item_weight = round( $_product->get_weight() * $values['quantity'], 2 );
							$item_fee    = $this->get_fee( $this->fee, $this->get_product_price( $_product, $values['quantity'] ) );
							$item_cost   = 0;

							foreach ( $matching_rates as $rate ) {
								$item_cost += $rate->rate_cost;
								$item_cost += $rate->rate_cost_per_item * $values['quantity'];
								$item_cost += $rate->rate_cost_per_weight_unit * $item_weight;
								$item_cost += ( $rate->rate_cost_percent / 100 ) * ( $this->get_product_price( $_product, $values['quantity'] ) );
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
								'id'    => $this->instance_id,
								'label' => __( $this->title, 'woocommerce-table-rate-shipping' ),
								'cost'  => $this->max_shipping_cost
							);
						} else {
							$rates[] = array(
								'id'       => $this->instance_id,
								'label'    => __( $this->title, 'woocommerce-table-rate-shipping' ),
								'cost'     => $costs,
								'calc_tax' => 'per_item'
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
							$classes[ $shipping_class ]->weight         += $_product->get_weight() * $values['quantity'];
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

								$class_cost = 0;
								$class_cost += $rate->rate_cost;
								$class_cost += $rate->rate_cost_per_item * $class->items_in_class;
								$class_cost += $rate->rate_cost_per_weight_unit * $class->weight;
								$class_cost += ( $rate->rate_cost_percent / 100 ) * $class->price;

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

								$class_fee	= $this->get_fee( $this->fee, $class->price );
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
							'id'    => $this->instance_id,
							'label' => __( $this->title, 'woocommerce-table-rate-shipping' ),
							'cost'  => $total_cost
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
							$weight += ( $_product->get_weight() * $values['quantity'] );
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

						$cost = $rate->rate_cost;
						$cost += $rate->rate_cost_per_item * $count;
						$cost += $this->get_fee( $this->fee, $price );
						$cost += $rate->rate_cost_per_weight_unit * $weight;
						$cost += ( $rate->rate_cost_percent / 100 ) * $price;

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
							'id'    => $this->instance_id . ' : ' . $rate->rate_id,
							'label' => __( $label, 'woocommerce-table-rate-shipping' ),
							'cost'  => $cost
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
			public function calculate_shipping( $package ) {
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
					WHERE shipping_method_id = {$this->number}
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

					$base_tax_rates = $this->tax->get_shop_base_rate( $_product->tax_class );
					$tax_rates      = $this->tax->get_rates( $_product->get_tax_class() );

					if ( $tax_rates !== $base_tax_rates ) {
						$base_taxes     = $this->tax->calc_tax( $row_base_price, $base_tax_rates, true, true );
						$modded_taxes   = $this->tax->calc_tax( $row_base_price - array_sum( $base_taxes ), $tax_rates, false );
						$row_base_price = ( $row_base_price - array_sum( $base_taxes ) ) + array_sum( $modded_taxes );
					}
				}

				return $row_base_price;
			}
		}

		endif;
	}
	add_action( 'woocommerce_shipping_init', 'woocommerce_init_shipping_table_rate' );

	/**
	 * woocommerce_register_table_rates function.
	 * @param array $package
	 */
	function woocommerce_register_table_rates( $package ) {
		// Register the main class
		woocommerce_register_shipping_method( 'WC_Shipping_Table_Rate' );

		if ( ! $package ) return;

		// Get zone for package
		$zone = woocommerce_get_shipping_zone( $package );

		if ( TABLE_RATE_SHIPPING_DEBUG ) {
			wc_add_notice( 'Customer matched shipping zone <strong>' . $zone->zone_name . '</strong> (#' . $zone->zone_id . ')', 'notice' );
		}

		if ( $zone->exists() ) {
			// Register zone methods
			$zone->register_shipping_methods();
		}
	}
	add_action( 'woocommerce_load_shipping_methods', 'woocommerce_register_table_rates' );

	/**
	 * Callback function for loading an instance of this method
	 *
	 * @param mixed $instance
	 * @param mixed $title
	 * @return WC_Shipping_Table_Rate
	 */
	function woocommerce_get_shipping_method_table_rate( $instance = false ) {
		return new WC_Shipping_Table_Rate( $instance );
	}
}
