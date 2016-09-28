<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping_Zone class.
 *
 * Controls a single zone, loads shipping methods, and registers them for use.
 */
class WC_Shipping_Zone {

    var $zone_id;
    var $zone_name;
    var $zone_enabled;
    var $zone_type;
    var $zone_order;
    var $shipping_methods = array();
    var $exists = false;

    /**
     * Constructor
     * @param  $zone_id ID of the zone we're retrieving
     */
	public function __construct( $zone_id ) {
        $this->zone_id = $zone_id;
        $this->init();
        $this->find_shipping_methods();
    }

    /**
     * Does this zone exist?
     * @return bool
     */
    public function exists() {
	    return $this->exists;
    }

	/**
	 * Register zone shipping methods for use.
	 */
	public function register_shipping_methods() {
		foreach ( $this->shipping_methods as $shipping_method ) {
			if ( is_callable( $shipping_method['callback'] ) ) {
				$method = call_user_func( $shipping_method['callback'], $shipping_method['number'] );

				if ( $method->enabled == 'yes' ) {
					WC()->shipping->register_shipping_method( $method );
				}
			}
		}
	}

	/**
	 * Add a shipping method to this zone.
	 * @param string $type
	 * @return int
	 */
	public function add_shipping_method( $type ) {
		global $wpdb;

		if ( ! $type ) {
			return 0;
		}

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_shipping_zone_shipping_methods',
			array(
				'shipping_method_type'	=> $type,
				'zone_id' 				=> $this->zone_id,
				'shipping_method_order'	=> 0
			),
			array(
				'%s',
				'%d',
				'%d'
			)
		);

		return $wpdb->insert_id;
	}


	/**
	 * Delete a shipping method belonging to this zone.
	 * @param int $id
	 */
	public function delete_shipping_method( $id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_shipping_methods', array( 'shipping_method_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_table_rates', array( 'shipping_method_id' => $id ) );

		delete_option( 'woocommerce_table_rate_priorities_' . $id );
		delete_option( 'woocommerce_table_rate_default_priority_' . $id );
	}

    /**
     * Init the zone data
     */
    private function init() {
	    if ( $this->zone_id > 0 ) {
	    	global $wpdb;

		    $zone = $wpdb->get_row( $wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones
				WHERE zone_id = %d LIMIT 1
			", $this->zone_id ) );

			if ( $zone ) {
				$this->zone_name    = $zone->zone_name;
				$this->zone_enabled = $zone->zone_enabled;
				$this->zone_type    = $zone->zone_type;
				$this->zone_order   = $zone->zone_order;
				$this->exists       = true;
			}
		} else {
			$this->zone_name    = __( 'Everywhere else', SHIPPING_ZONES_TEXTDOMAIN );
			$this->zone_enabled = 1;
			$this->zone_type    = '';
			$this->zone_order   = '';
			$this->exists       = true;
		}
    }

	/**
	 * Find shipping methods for this zone.
	 */
	private function find_shipping_methods() {
		global $wpdb;

		$zone_methods = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods
			WHERE zone_id = %s
			ORDER BY shipping_method_order ASC
		", $this->zone_id ) );

		foreach ( $zone_methods as $method ) {
			$this->shipping_methods[] = array(
				'number'   => $method->shipping_method_id, // Instance number for the method
				'callback' => 'woocommerce_get_shipping_method_' . $method->shipping_method_type // Callback function to init the method class
			);
		}
	}
}