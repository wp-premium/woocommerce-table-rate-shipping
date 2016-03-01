<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping_Zone_Admin class.
 */
class WC_Shipping_Zone_Admin {

	/**
	 * view_zone_page function.
	 */
	public static function view_zone_screen() {
		global $wpdb;

		$zone_id = absint( $_GET['zone'] );
		$zone    = new WC_Shipping_Zone( $zone_id );

		if ( ! $zone->exists() ) {
			echo '<div class="error"><p>' . sprintf( __( 'Invalid shipping zone. <a href="%s">Back to zones.</a>', SHIPPING_ZONES_TEXTDOMAIN ), esc_url( remove_query_arg( 'zone' ) ) ) . '</p></div>';
			return;
		}

		self::add_method( $zone );
		self::delete_method( $zone );

		if ( ! empty( $_GET['method'] )) {
			self::method_settings( $zone, absint( $_GET['method'] ) );
			return;
		} else {
			include( 'views/html-zone-methods.php' );
		}
	}

	/**
	 * Add shipping method to zone
	 */
	public static function add_method( $zone ) {
		if ( ! empty( $_GET['add_method'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce_add_method' ) ) {
			$type = wc_clean( $_GET['method_type'] );

			if ( $type && ( $method_id = $zone->add_shipping_method( $type ) ) ) {
				echo '<div class="updated fade"><p>' . sprintf( __( 'Shipping method successfully created. <a href="%s">View method.</a>', SHIPPING_ZONES_TEXTDOMAIN ), esc_url( add_query_arg( 'method', $method_id, add_query_arg( 'zone', $zone->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) ) ) . '</p></div>';
			} else {
				echo '<div class="error"><p>' . __( 'Invalid shipping method', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
			}
		}
	}

	/**
	 * Delete shipping method from zone.
	 */
	public static function delete_method( $zone ) {
		if ( ! empty( $_GET['delete_method'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce_delete_method' ) ) {
			$method_id = absint( $_GET['delete_method'] );

			if ( $zone->delete_shipping_method( $method_id ) ) {
				echo '<div class="updated success"><p>' . __( 'Shipping method successfully deleted', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
			}
		}
	}

	/**
	 * list_shipping_zone_methods function.
	 */
	public static function list_shipping_zone_methods() {
		if ( ! class_exists( 'WC_Shipping_Zone_Methods_Table' ) ) {
			require_once( 'list-tables/class-wc-shipping-zone-methods-table.php' );
		}
		echo '<form method="post">';
	 	$WC_Shipping_Zone_Methods_Table = new WC_Shipping_Zone_Methods_Table();
		$WC_Shipping_Zone_Methods_Table->prepare_items();
		$WC_Shipping_Zone_Methods_Table->display();
		echo '</form>';
	}

	/**
	 * Show settings for a method
	 */
	public static function method_settings( $zone, $method_id ) {
		global $wpdb;

		// Get method
		$method = $wpdb->get_row( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods WHERE shipping_method_id = %s
		", $method_id ) );

		$callback = 'woocommerce_get_shipping_method_' . $method->shipping_method_type;

		if ( ! function_exists( $callback ) ) {
			return;
		}

		// Construct method instance
		$shipping_method = $callback( $method_id );

		if ( ! empty( $_POST['save_method'] ) ) {

			if ( empty( $_POST['woocommerce_save_method_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_save_method_nonce'], 'woocommerce_save_method' )) {
				echo '<div class="updated error"><p>' . __( 'Edit failed. Please try again.', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';

			} elseif ( $shipping_method->process_instance_options() ) {

				// re-init so we re-load settings
				unset( $shipping_method );
				$shipping_method = $callback( $method_id );

				echo '<div class="updated success"><p>' . __( 'Shipping method saved successfully.', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
			}
		}

		include( 'views/html-zone-method-settings.php' );
	}
}