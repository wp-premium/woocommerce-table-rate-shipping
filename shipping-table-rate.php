<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backwards compat
 */
$active_plugins = get_option( 'active_plugins', array() );
foreach ( $active_plugins as $key => $active_plugin ) {
	if ( strstr( $active_plugin, '/shipping-table-rate.php' ) ) {
		$active_plugins[ $key ] = str_replace( '/shipping-table-rate.php', '/woocommerce-table-rate-shipping.php', $active_plugin );
	}
}
update_option( 'active_plugins', $active_plugins );