<?php

/**
 * Get the shipping zone for a passed in package.
 *
 * @param array $package
 * @return WC_Shipping_Zone
 */
function wc_get_shipping_zone( $package ) {
	global $wpdb;

	$postcode        = $package['destination']['postcode'];
	$valid_postcodes = array( '*', $postcode );
	$valid_zone_ids  = array();

	// Work out possible valid wildcard postcodes
	$postcode_length	= strlen( $postcode );
	$wildcard_postcode	= $postcode;

	for ( $i = 0; $i < $postcode_length; $i ++ ) {
		$wildcard_postcode = substr( $wildcard_postcode, 0, -1 );
		$valid_postcodes[] = $wildcard_postcode . '*';
	}

	// Query range based postcodes to find matches
	if ( $postcode ) {
		$postcode_ranges = $wpdb->get_results( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
			WHERE location_type = 'postcode' AND location_code LIKE '%-%'
		" );

		if ( $postcode_ranges ) {
			$encoded_postcode     = wc_make_numeric_postcode( $postcode );
			$encoded_postcode_len = strlen( $encoded_postcode );

			foreach ( $postcode_ranges as $postcode_range ) {
				$range = array_map( 'trim', explode( '-', $postcode_range->location_code ) );

				if ( sizeof( $range ) != 2 ) {
					continue;
				}

				if ( is_numeric( $range[0] ) && is_numeric( $range[1] ) ) {
					$encoded_postcode = $postcode;
					$min              = $range[0];
					$max              = $range[1];
				} else {
					$min = wc_make_numeric_postcode( $range[0] );
					$max = wc_make_numeric_postcode( $range[1] );
					$min = str_pad( $min, $encoded_postcode_len, '0' );
					$max = str_pad( $max, $encoded_postcode_len, '9' );
				}

				if ( $encoded_postcode >= $min && $encoded_postcode <= $max ) {
					$valid_zone_ids[] = $postcode_range->zone_id;
				}
			}
		}
	}

	// Escape
	$country         = esc_sql( $package['destination']['country'] );
	$state           = esc_sql( $country . ':' . $package['destination']['state'] );
	$valid_postcodes = array_map( 'esc_sql', $valid_postcodes );
	$valid_zone_ids  = array_map( 'esc_sql', $valid_zone_ids );

	// Get matching zones
	$matching_zone_sql  = "
		SELECT zones.zone_id FROM {$wpdb->prefix}woocommerce_shipping_zones as zones
		LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zone_locations as locations ON zones.zone_id = locations.zone_id
		WHERE
		(
			(
				zone_type = 'countries'
				AND location_type = 'country'
				AND location_code = '{$country}'
			)
			OR
			(
				zone_type = 'states'
				AND
				(
					( location_type = 'state' AND location_code = '{$state}' )
					OR
					( location_type = 'country' AND location_code = '{$country}' )
				)
			)
			OR
			(
				zone_type = 'postcodes'
				AND
				(
					( location_type = 'state' AND location_code = '{$state}' )
					OR
					( location_type = 'country' AND location_code = '{$country}' )
				)
				AND
				(
					zones.zone_id IN (
						SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_locations
						WHERE location_type = 'postcode'
						AND location_code IN ('" . implode( "','", $valid_postcodes ) . "')
						)
					OR zones.zone_id IN ('" . implode( "','", $valid_zone_ids ) . "')
				)
			)
		)
		AND zone_enabled = 1
		ORDER BY zone_order ASC
		LIMIT 1
	";

	$matching_zone = $wpdb->get_var( $matching_zone_sql );

	return new WC_Shipping_Zone( $matching_zone ? $matching_zone : 0 );
}

/**
 * make_numeric_postcode function.
 *
 * Converts letters to numbers so we can do a simple range check on postcodes.
 *
 * E.g. PE30 becomes 16050300 (P = 16, E = 05, 3 = 03, 0 = 00)
 *
 * @access public
 * @param mixed $postcode
 * @return void
 */
function wc_make_numeric_postcode( $postcode ) {
	$postcode_length    = strlen( $postcode );
	$letters_to_numbers = array_merge( array( 0 ), range( 'A', 'Z' ) );
	$letters_to_numbers = array_flip( $letters_to_numbers );
	$numeric_postcode   = '';

	for ( $i = 0; $i < $postcode_length; $i ++ ) {
		if ( is_numeric( $postcode[ $i ] ) ) {
			$numeric_postcode .= str_pad( $postcode[ $i ], 2, '0', STR_PAD_LEFT );
		} elseif ( isset( $letters_to_numbers[ $postcode[ $i ] ] ) ) {
			$numeric_postcode .= str_pad( $letters_to_numbers[ $postcode[ $i ] ], 2, '0', STR_PAD_LEFT );
		} else {
			$numeric_postcode .= '00';
		}
	}

	return $numeric_postcode;
}

/**
 * Alias for wc_get_shipping_zone
 */
function woocommerce_make_numeric_postcode( $postcode ) {
	return wc_make_numeric_postcode( $postcode );
}

/**
 * Alias for wc_get_shipping_zone
 */
function woocommerce_get_shipping_zone( $package ) {
	return wc_get_shipping_zone( $package );
}
