<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Shipping_zones class.
 */
class WC_Shipping_Zones_Admin {

	/**
	 * Output the Admin UI
	 */
	public static function output() {
		if ( method_exists( WC(), 'shipping' ) ) {
			WC()->shipping();
		}
		if ( ! empty( $_GET['edit_zone'] ) ) {
			self::edit_zone_screen();
		} elseif ( isset( $_GET['zone'] ) ) {
			include_once( 'class-wc-shipping-zone-admin.php' );
			WC_Shipping_Zone_Admin::view_zone_screen();
		} else {
			self::list_zones_screen();
		}
	}

	/**
	 * Save zone after editing
	 */
	private static function save_zone( $zone_id = 0 ) {
		global $wpdb;

		$editing = ! empty( $zone_id );

		if ( ! empty( $_POST['add_zone'] ) || ! empty( $_POST['edit_zone'] ) ) {

			if ( empty( $_POST['woocommerce_save_zone_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_save_zone_nonce'], 'woocommerce_save_zone' ) ) {
				echo '<div class="updated error"><p>' . __( 'Could not save zone. Please try again.', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
				return;
			}

			$fields = array(
				'zone_name',
				'zone_type',
				'zone_enabled',
				'zone_type_countries',
				'zone_type_states',
				'zone_type_postcodes',
				'postcodes'
			);

			$zone_count = $wpdb->get_var( "SELECT COUNT( zone_id ) FROM {$wpdb->prefix}woocommerce_shipping_zones" );
			$data       = array();

			foreach ( $fields as $field ) {
				$data[ $field ] = empty( $_POST[ $field ] ) ? '' : $_POST[ $field ];

				if ( 'postcodes' === $field ) {
					$data[ $field ] = array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $data[ $field ] ) ) );
				} else {
					$data[ $field ] = is_array( $data[ $field ] ) ? array_map( 'wc_clean', $data[ $field ] ) : wc_clean( $data[ $field ] );
				}
			}

			// If name is left blank...
			if ( empty( $data['zone_name'] ) ) {
				if ( $editing ) {
					echo '<div class="updated error"><p>' . __( 'Zone name is required', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
					return;
				} else {
					$data['zone_name'] = __( 'Zone', SHIPPING_ZONES_TEXTDOMAIN ) . ' ' . ( $zone_count + 1 );
				}
			}

			// Check required fields
			if ( empty( $data['zone_type'] ) ) {
				echo '<div class="updated error"><p>' . __(' Zone type is required', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
				return;
			}

			$data['zone_enabled'] = ( $data['zone_enabled'] || ! $editing ) ? 1 : 0;

			// Determine field we are saving
			$locations_field = 'zone_type_' . $data['zone_type'];

			// Get the countries into a nicely formatted array
			if ( ! $data[ $locations_field ] ) {
				$data[ $locations_field ] = array();
			}

			if ( is_array( $data[ $locations_field ] ) ) {
				$data[ $locations_field ] = array_filter( array_map( 'strtoupper', array_map( 'sanitize_text_field', $data[ $locations_field ] ) ) );
			} else {
				$data[ $locations_field ] = array( strtoupper( sanitize_text_field( $data[ $locations_field ] ) ) );
			}

			// Any set?
			if ( sizeof( $data[ $locations_field ] ) == 0 ) {
				echo '<div class="updated error"><p>' . __('You must choose at least 1 country to add a zone.', SHIPPING_ZONES_TEXTDOMAIN) . '</p></div>';
				return;
			}

			// If dealing with a postcode, grab that field too
			if ( $data['zone_type'] == 'postcodes' ) {

				$data['postcodes'] = array_filter( array_unique( $data['postcodes'] ) );

				if ( sizeof( $data['postcodes'] ) == 0 ) {
					echo '<div class="updated error"><p>' . __('You must choose at least 1 postcode to add postcode zone.', SHIPPING_ZONES_TEXTDOMAIN) . '</p></div>';
					return;
				}

			} else {
				$data['postcodes'] = array();
			}

			if ( $editing ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_zones',
					array(
						'zone_name'    => $data['zone_name'],
						'zone_enabled' => $data['zone_enabled'],
						'zone_type'    => $data['zone_type'],
					),
					array(
						'zone_id' => $zone_id
					)
				);
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'woocommerce_shipping_zones',
					array(
						'zone_name'    => $data['zone_name'],
						'zone_enabled' => $data['zone_enabled'],
						'zone_type'    => $data['zone_type'],
						'zone_order'   => $zone_count + 1
					),
					array(
						'%s',
						'%d',
						'%s',
						'%d'
					)
				);
				$zone_id = $wpdb->insert_id;
			}

			$update_locations = true;

			if ( $editing ) {
				$locations = $wpdb->get_col( $wpdb->prepare( "
					SELECT location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
					WHERE zone_id = %d
				", $zone_id ) );

				$new_locations = array_merge( $data[ $locations_field ], $data['postcodes'] );

				if ( array_diff( $locations, $new_locations ) || array_diff( $new_locations, $locations ) ) {
					$update_locations = true;
				} else {
					$update_locations = false;
				}
			}

			if ( $update_locations ) {
				if ( $zone_id > 0 ) {

					// Remove locations
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_shipping_zone_locations WHERE zone_id = %s", $zone_id ) );

					// Insert locations which apply to this zone
					foreach ( $data[ $locations_field ] as $code ) {
						if ( $code ) {
							$wpdb->insert(
								$wpdb->prefix . 'woocommerce_shipping_zone_locations',
								array(
									'location_code'		=> $code,
									'location_type' 	=> strstr( $code, ':' ) ? 'state' : 'country',
									'zone_id'			=> $zone_id,
								),
								array(
									'%s',
									'%s',
									'%d'
								)
							);
						}
					}

					// Save postcodes
					if ( $data['zone_type'] == 'postcodes' ) {
						foreach ( $data['postcodes'] as $code ) {
							if ( $code ) {
								$wpdb->insert(
									$wpdb->prefix . 'woocommerce_shipping_zone_locations',
									array(
										'location_code'		=> $code,
										'location_type' 	=> 'postcode',
										'zone_id'			=> $zone_id,
									),
									array(
										'%s',
										'%s',
										'%d'
									)
								);
							}
						}
					}

				} else {
					echo '<div class="updated error"><p>' . __( 'Error saving zone.', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
					return;
				}
			}

			if ( $editing ) {
				echo '<div class="updated fade"><p>' . sprintf( __( 'Shipping zone saved. <a href="%s">Back to zones.</a>', SHIPPING_ZONES_TEXTDOMAIN ), esc_url( remove_query_arg( 'edit_zone' ) ) ) . '</p></div>';
			} else {
				echo '<div class="updated fade"><p>' . __( 'Shipping zone saved.', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
			}
		}
	}

	/**
	 * Edit Zone Screen
	 */
	public static function edit_zone_screen() {
		$zone_id = absint( $_GET['edit_zone'] );
		self::save_zone( $zone_id );
		self::edit_zone( $zone_id );
	}

	/**
	 * list_zone_page function.
	 */
	public static function list_zones_screen() {
		self::save_zone();
		include( 'views/html-zone-list.php' );
	}

	/**
	 * Edit zone form
	 */
	private static function edit_zone( $zone_id ) {
		global $wpdb;

		$countries  = WC()->countries->get_allowed_countries();
		$base       = WC()->countries->get_base_country();

		// Load details to edit
		$zone = $wpdb->get_row( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zones
			WHERE zone_id = %d LIMIT 1
		", $zone_id ) );

		$location_counties = $wpdb->get_col( $wpdb->prepare( "
			SELECT location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
			WHERE zone_id = %d AND location_type = 'country'
		", $zone_id ) );

		$location_states = $wpdb->get_col( $wpdb->prepare( "
			SELECT location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
			WHERE zone_id = %d AND location_type = 'state'
		", $zone_id ) );

		$location_postcodes = $wpdb->get_col( $wpdb->prepare( "
			SELECT location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
			WHERE zone_id = %d AND location_type = 'postcode'
		", $zone_id ) );

		$selected_states = array_merge( $location_states, $location_counties );

	 	include( 'views/form-edit-shipping-zone.php' );
	}

	/**
	 * list_shipping_zones function.
	 *
	 * @access public
	 * @return void
	 */
	public static function list_shipping_zones() {
		if ( ! class_exists( 'WC_Shipping_Zones_Table' ) ) {
			require_once( 'list-tables/class-wc-shipping-zones-table.php' );
		}

		echo '<form method="post">';
	 	$WC_Shipping_Zones_Table = new WC_Shipping_Zones_Table();
		$WC_Shipping_Zones_Table->prepare_items();
		$WC_Shipping_Zones_Table->display();
		echo '</form>';
	}

	/**
	 * add_shipping_zone_form function.
	 */
	public static function add_shipping_zone_form() {
		global $wpdb;

		$countries  = WC()->countries->get_allowed_countries();
		$base       = WC()->countries->get_base_country();
		$zone_count = $wpdb->get_var( "SELECT COUNT( zone_id ) FROM {$wpdb->prefix}woocommerce_shipping_zones;" );

		include( 'views/form-add-shipping-zone.php' );
	}
}