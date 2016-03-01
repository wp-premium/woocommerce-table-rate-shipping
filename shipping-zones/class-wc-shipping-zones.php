<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping_Zones Class
 * @version 2.0
 */
class WC_Shipping_Zones {

	/**
	 * Init Shipping Zones Support
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_item' ), 25 );
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_screen_id' ) );

		include_once( 'includes/wc-shipping-zone-functions.php' );
		include_once( 'includes/class-wc-shipping-zone.php' );

		if ( is_admin() ) {
			include_once( 'includes/class-wc-shipping-zones-admin.php' );
		}

		if ( defined( 'DOING_AJAX' ) ) {
			include_once( 'includes/class-wc-shipping-zones-ajax-handler.php' );
		}
	}

	/**
	 * Add a menu item for the shipping zones screen
	 */
	public static function add_menu_item() {
		$page = add_submenu_page( 'woocommerce', __( 'Shipping Zones', SHIPPING_ZONES_TEXTDOMAIN ), __( 'Shipping Zones', SHIPPING_ZONES_TEXTDOMAIN ) , 'manage_woocommerce', 'shipping_zones', array( 'WC_Shipping_Zones_Admin', 'output' ) );
		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'zones_page_scripts' ) );
	}

	/**
	 * Register the shipping zones screen ID
	 * @param array $ids array
	 * @return array
	 */
	public static function add_screen_id( $ids = array() ) {
		$ids[] = strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '_page_shipping_zones';
		return $ids;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public static function zones_page_scripts() {
		wp_enqueue_style( 'wc-shipping-zones-styles', plugins_url( '/shipping-zones/assets/css/shipping_zones.css', dirname( __FILE__ ) ) );
		wp_enqueue_script( SHIPPING_ZONES_TEXTDOMAIN, plugins_url( '/shipping-zones/assets/js/shipping-zone-admin.js', dirname( __FILE__ ) ), array( 'jquery' ), '2.0', true );
		wp_localize_script(
			SHIPPING_ZONES_TEXTDOMAIN,
			'wc_shipping_zones_params',
			array(
				'shipping_zones_nonce' => wp_create_nonce( 'shipping-zones' ),
				'supports_select2'     => version_compare( WC_VERSION, '2.3', '>' ) ? 1 : 0
			)
		);

		if ( version_compare( WC_VERSION, '2.3', '<' ) ) {
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'chosen' );
		}

		do_action( 'woocommerce_shipping_zones_css' );
	}
}

WC_Shipping_Zones::init();
