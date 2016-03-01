<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping_Zones_Ajax_Handler
 * @version 2.0
 */
class WC_Shipping_Zones_Ajax_Handler {

	/**
	 * Init Shipping Zones Ajax
	 */
	public static function init() {
		add_action( 'wp_ajax_woocommerce_zone_ordering', array( __CLASS__, 'shipping_zone_ordering' ) );
		add_action( 'wp_ajax_woocommerce_shipping_method_ordering', array( __CLASS__, 'shipping_method_ordering' ) );
	}

	public static function shipping_zone_ordering() {
		check_ajax_referer( 'shipping-zones', 'security' );

		global $wpdb;

		$zones = $_POST['zone_ids'];
		$zones = array_map( 'intval', $zones );

		foreach ( $zones as $i => $zone ) {

			if ( $zone > 0 ) {

				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_zones',
					array(
						'zone_order' => $i,
					),
					array( 'zone_id' => $zone ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}

		die();
	}

	public static function shipping_method_ordering() {
		check_ajax_referer( 'shipping-zones', 'security' );

		global $wpdb;

		$shipping_method_id			= isset( $_POST['shipping_method_id'] ) ? absint( $_POST['shipping_method_id'] ) : false;
		$prev_shipping_method_id	= isset( $_POST['prev_shipping_method_id'] ) ? absint( $_POST['prev_shipping_method_id'] ) : false;
		$next_shipping_method_id 	= isset( $_POST['next_shipping_method_id'] ) ? absint( $_POST['next_shipping_method_id'] ) : false;

		if ( ! $shipping_method_id )
			die( -1 );

		$zone_id = absint( $wpdb->get_var( "SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods WHERE shipping_method_id = " . $shipping_method_id ) );

		$siblings = $wpdb->get_results("
			SELECT shipping_method_id, shipping_method_order FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods
			WHERE zone_id = " . $zone_id . "
			AND shipping_method_id NOT IN (" . $shipping_method_id . ")
			ORDER BY shipping_method_order ASC
		");

		$new_positions = array(); // store new positions for ajax
		$menu_order = 0;

		foreach( $siblings as $sibling ) {

			// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
			if ( $next_shipping_method_id && $next_shipping_method_id == $sibling->shipping_method_id ) {
				$wpdb->update(
					$wpdb->prefix . "woocommerce_shipping_zone_shipping_methods",
					array(
						'shipping_method_order' => $menu_order
					),
					array( 'shipping_method_id' => $shipping_method_id ),
					array( '%d' ),
					array( '%d' )
				);
				$new_positions[ $shipping_method_id ] = $menu_order;
				$menu_order++;
			}

			// if repositioned post has been set, and new items are already in the right order, we can stop
			if ( isset( $new_positions[ $shipping_method_id ] ) && $sibling->shipping_method_order >= $menu_order )
				break;

			// set the menu order of the current sibling and increment the menu order
			$wpdb->update(
				$wpdb->prefix . "woocommerce_shipping_zone_shipping_methods",
				array(
					'shipping_method_order' => $menu_order
				),
				array( 'shipping_method_id' => $sibling->shipping_method_id ),
				array( '%d' ),
				array( '%d' )
			);
			$new_positions[ $sibling->shipping_method_id ] = $menu_order;
			$menu_order++;

			if ( ! $next_shipping_method_id && $prev_shipping_method_id == $sibling->shipping_method_id ) {
				$wpdb->update(
					$wpdb->prefix . "woocommerce_shipping_zone_shipping_methods",
					array(
						'shipping_method_order' => $menu_order
					),
					array( 'shipping_method_id' => $shipping_method_id ),
					array( '%d' ),
					array( '%d' )
				);
				$new_positions[ $shipping_method_id ] = $menu_order;
				$menu_order++;
			}
		}

		die();
	}
}

WC_Shipping_Zones_Ajax_Handler::init();