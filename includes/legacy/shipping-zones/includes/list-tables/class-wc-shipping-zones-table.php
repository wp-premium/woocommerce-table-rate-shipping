<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Shipping_Zones_Table class.
 *
 * @extends WP_List_Table
 */
class WC_Shipping_Zones_Table extends WP_List_Table {

	public $index = 0;

	/**
	 * Constructor
	 */
	public function __construct(){
		parent::__construct( array(
			'singular' => 'Shipping Zone',
			'plural'   => 'Shipping Zones',
			'ajax'     => false
		) );
	}

	/**
	 * Output the zone name column.
	 * @param  object $item
	 * @return string
	 */
	public function column_zone_name( $item ) {
		$zone_name = '
			<strong>
			  <a href="' . esc_url( add_query_arg( 'zone', $item->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) . '" class="configure_methods">' . esc_html( $item->zone_name ) . '</a>
			</strong>
			<input type="hidden" class="zone_id" name="zone_id[]" value="' . esc_attr( $item->zone_id ) . '" />
			<div class="row-actions">';

		if ( $item->zone_id > 0 ) {
			$zone_name .= '<a href="' . esc_url( add_query_arg( 'edit_zone', $item->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) . '">' . __( 'Edit', SHIPPING_ZONES_TEXTDOMAIN ) . '</a> | <a href="' . esc_url( add_query_arg( 'zone', $item->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) . '" class="configure_methods">' . __( 'Configure shipping methods', SHIPPING_ZONES_TEXTDOMAIN ) . '</a>';
		} else {
			$zone_name .= '<a href="' . esc_url( add_query_arg( 'zone', $item->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) . '" class="configure_methods">' . __( 'Configure shipping methods', SHIPPING_ZONES_TEXTDOMAIN ) . '</a>';
		}
		$zone_name .= '</div>';
		return $zone_name;
	}

	/**
	 * Output the zone type column.
	 * @param  object $item
	 * @return string
	 */
	public function column_zone_type( $item ) {
		global $wpdb;

		if ( $item->zone_id == 0 ) {
			return __( 'Everywhere', SHIPPING_ZONES_TEXTDOMAIN );
		}

		$locations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE zone_id = %s;", $item->zone_id ) );

		$count = sizeof( $locations );

		if ( 'postcodes' === $item->zone_type ) {
			$count = $count - 1;
		}

		$locations_prepend = "";
		$locations_append  = "";
		$locations_list    = array();

		foreach ( $locations as $location ) {
			if ( sizeof( $locations_list ) >= 8 ) {
				$locations_append = ' ' . sprintf( __( 'and %s others', SHIPPING_ZONES_TEXTDOMAIN ), ( $count - 8 ) );
				break;
			}
			switch ( $location->location_type ) {
				case "country" :
				case "state" :

					if ( strstr( $location->location_code, ':' ) ) {
						$split_code = explode( ':', $location->location_code );
						if ( ! isset( WC()->countries->states[ $split_code[0] ][ $split_code[1] ] ) ) {
							continue;
						}
						$location_name = WC()->countries->states[ $split_code[0] ][ $split_code[1] ];
					} else {
						if ( ! isset( WC()->countries->countries[ $location->location_code ] ) ) {
							continue;
						}
						$location_name = WC()->countries->countries[ $location->location_code ];
					}

					if ( $item->zone_type == 'postcodes' ) {
						$locations_prepend = sprintf( __( 'Within %s:', SHIPPING_ZONES_TEXTDOMAIN ), $location_name ) . ' ';
					} else {
						$locations_list[] = $location_name;
					}
					break;
				case "postcode" :
					$locations_list[] = $location->location_code;
			}
		}

		switch ( $item->zone_type ) {
			case "countries" :
				return '<strong>' . __( 'Countries', SHIPPING_ZONES_TEXTDOMAIN ) . '</strong><br/>' . $locations_prepend . implode( ', ', $locations_list ) . $locations_append;
			case "states" :
				return '<strong>' . __( 'Countries and states', SHIPPING_ZONES_TEXTDOMAIN ) . '</strong><br/>' . $locations_prepend . implode( ', ', $locations_list ) . $locations_append;
			case "postcodes" :
				return '<strong>' . __( 'Postcodes', SHIPPING_ZONES_TEXTDOMAIN ) . '</strong><br/>' . $locations_prepend . implode( ', ', $locations_list ) . $locations_append;
		}
	}

	/**
	 * Output the zone enabled column.
	 * @param  object $item
	 * @return string
	 */
	public function column_enabled( $item ) {
		return $item->zone_enabled ? '&#10004;' : '&ndash;';
	}

	/**
	 * Output the zone methods column.
	 * @param  object $item
	 * @return string
	 */
	public function column_methods( $item ) {
		global $wpdb;

		$output_methods = array();

		$shipping_methods = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods
			WHERE zone_id = %s
			ORDER BY `shipping_method_order` ASC
		", $item->zone_id ) );

		if ( $shipping_methods ) {
			foreach ( $shipping_methods as $method ) {
				$class_callback = 'woocommerce_get_shipping_method_' . $method->shipping_method_type;

				if ( function_exists( $class_callback ) ) {
					$this_method      = call_user_func( $class_callback, $method->shipping_method_id );
					$output_methods[] = '<a href="' . esc_url( add_query_arg( 'method', $method->shipping_method_id, add_query_arg( 'zone', $item->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) ) . '">' . esc_html( $this_method->title ? $this_method->title : $this_method->id ) . '</a>';
				}
			}

			return implode( ', ', $output_methods );
		} else {
			return __( 'None', SHIPPING_ZONES_TEXTDOMAIN );
		}
	}

	/**
	 * Checkbox column
	 * @param string
	 */
	public function column_cb( $item ) {
		if ( ! $item->zone_id ) {
			return;
		}
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'zone_id_cb',
			$item->zone_id
		);
	}

	/**
	 * get_columns function.
	 * @return  array
	 */
	public function get_columns(){
		return array(
			'cb'        	=> '<input type="checkbox" />',
			'zone_name'     => __( 'Zone name', SHIPPING_ZONES_TEXTDOMAIN ),
			'zone_type'     => __( 'Zone type', SHIPPING_ZONES_TEXTDOMAIN ),
			'enabled'  		=> __( 'Enabled', SHIPPING_ZONES_TEXTDOMAIN ),
			'methods'  		=> __( 'Shipping Methods', SHIPPING_ZONES_TEXTDOMAIN )
		);
	}

	 /**
	 * Get bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'disable' => __('Disable', SHIPPING_ZONES_TEXTDOMAIN),
			'enable'  => __('Enable', SHIPPING_ZONES_TEXTDOMAIN),
			''        => '------',
			'delete'  => __('Delete', SHIPPING_ZONES_TEXTDOMAIN)
		);
		return $actions;
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {
		global $wpdb;

		if ( ! isset( $_POST['zone_id_cb'] ) ) {
			return;
		}

		$items = array_filter( array_map( 'absint', $_POST['zone_id_cb'] ) );

		if ( ! $items ) {
			return;
		}

		if ( 'delete' === $this->current_action() ) {

			foreach ( $items as $id ) {
				$methods = $wpdb->get_col( $wpdb->prepare( "SELECT shipping_method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods WHERE zone_id = %d", $id ) );

				$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_locations', array( 'zone_id' => $id ) );
				$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zones', array( 'zone_id' => $id ) );
				$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_shipping_methods', array( 'zone_id' => $id ) );

				foreach ( $methods as $method ) {
					$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_table_rates', array( 'shipping_method_id' => $method ) );
					delete_option( 'woocommerce_table_rate_priorities_' . $method );
					delete_option( 'woocommerce_table_rate_default_priority_' . $method );
				}
			}

			echo '<div class="updated success"><p>' . __( 'Shipping zones deleted', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';

		} elseif ( 'enable' === $this->current_action() ) {

			foreach ( $items as $id ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_zones',
					array(
						'zone_enabled' => 1
					),
					array( 'zone_id' => $id ),
					array( '%d' ),
					array( '%d' )
				);
			}

			echo '<div class="updated success"><p>' . __( 'Shipping zones enabled', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';

		} elseif ( 'disable' === $this->current_action() ) {

			foreach ( $items as $id ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_zones',
					array(
						'zone_enabled' => 0
					),
					array( 'zone_id' => $id ),
					array( '%d' ),
					array( '%d' )
				);
			}

			echo '<div class="updated success"><p>' . __( 'Shipping zones disabled', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
		}

	}

	/**
	 * Get Zones to display
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->process_bulk_action();

		$this->items = $wpdb->get_results( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones
			ORDER BY zone_order ASC
		" );

		$default               = new stdClass();
		$default->zone_id      = 0;
		$default->zone_name    = __( 'Default Zone (everywhere else)', SHIPPING_ZONES_TEXTDOMAIN );
		$default->zone_type    = __( 'All countries', SHIPPING_ZONES_TEXTDOMAIN );
		$default->zone_enabled = 1;
		$this->items[]         = $default;
	}
}
