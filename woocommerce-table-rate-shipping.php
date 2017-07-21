<?php
/**
 * Plugin Name: WooCommerce Table Rate Shipping
 * Plugin URI: https://woocommerce.com/products/table-rate-shipping/
 * Description: Table rate shipping lets you define rates depending on location vs shipping class, price, weight, or item count.
 * Version: 3.0.4
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Requires at least: 4.0
 * Tested up to: 4.7
 * Copyright: 2017 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /languages/
 *
 * Woo: 18718:3034ed8aff427b0f635fe4c86bbf008a
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	/**
	 * Main Class
	 */
	class WC_Table_Rate_Shipping {

		/**
		 * Constructor
		 */
		public function __construct() {
			define( 'TABLE_RATE_SHIPPING_VERSION', '3.0.4' );
			define( 'TABLE_RATE_SHIPPING_DEBUG', defined( 'WP_DEBUG' ) && 'true' == WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || 'true' == WP_DEBUG_DISPLAY ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			register_activation_hook( __FILE__, array( $this, 'install' ) );
		}

		/**
		 * Register method for usage
		 * @param  array $shipping_methods
		 * @return array
		 */
		public function woocommerce_shipping_methods( $shipping_methods ) {
			$shipping_methods['table_rate'] = 'WC_Shipping_Table_Rate';
			return $shipping_methods;
		}

		/**
		 * Init TRS
		 */
		public function init() {
			include_once( 'includes/functions-ajax.php' );
			include_once( 'includes/functions-admin.php' );

			/**
			 * Install check (for updates)
			 */
			if ( get_option( 'table_rate_shipping_version' ) < TABLE_RATE_SHIPPING_VERSION ) {
				$this->install();
			}

			// 2.6.0+ supports zones and instances
			if ( version_compare( WC_VERSION, '2.6.0', '>=' ) ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
			} else {
				if ( ! defined( 'SHIPPING_ZONES_TEXTDOMAIN' ) ) {
					define( 'SHIPPING_ZONES_TEXTDOMAIN', 'woocommerce-table-rate-shipping' );
				}
				if ( ! class_exists( 'WC_Shipping_zone' ) ) {
					include_once( 'includes/legacy/shipping-zones/class-wc-shipping-zones.php' );
				}
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'load_shipping_methods' ) );
				add_action( 'admin_notices', array( $this, 'welcome_notice' ) );
			}

			// Hooks
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_action( 'woocommerce_shipping_init', array( $this, 'shipping_init' ) );
		}

		/**
		 * Localisation
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-table-rate-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Row meta
		 * @param  array $links
		 * @param  string $file
		 * @return array
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( $file === plugin_basename( __FILE__ ) ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_table_rate_shipping_docs_url', 'http://docs.woothemes.com/document/table-rate-shipping/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-table-rate-shipping' ) ) . '">' . __( 'Docs', 'woocommerce-table-rate-shipping' ) . '</a>',
					'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_table_rate_support_url', 'http://support.woothemes.com/' ) ) . '" title="' . esc_attr( __( 'Visit Premium Customer Support Forum', 'woocommerce-table-rate-shipping' ) ) . '">' . __( 'Premium Support', 'woocommerce-table-rate-shipping' ) . '</a>',
				);
				return array_merge( $links, $row_meta );
			}
			return (array) $links;
		}

		/**
		 * Admin welcome notice
		 */
		public function welcome_notice() {
			if ( get_option( 'hide_table_rate_welcome_notice' ) ) {
				return;
			}
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
		 * Admin styles + scripts
		 */
		public function admin_enqueue_scripts() {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_style( 'woocommerce_shipping_table_rate_styles', plugins_url( '/assets/css/admin.css', __FILE__ ) );
			wp_register_script( 'woocommerce_shipping_table_rate_rows', plugins_url( '/assets/js/table-rate-rows' . $suffix . '.js', __FILE__ ), array( 'jquery', 'wp-util' ) );
			wp_localize_script( 'woocommerce_shipping_table_rate_rows', 'woocommerce_shipping_table_rate_rows', array(
				'i18n' => array(
					'order'        => __( 'Order', 'woocommerce-table-rate-shipping' ),
					'item'         => __( 'Item', 'woocommerce-table-rate-shipping' ),
					'line_item'    => __( 'Line Item', 'woocommerce-table-rate-shipping' ),
					'class'        => __( 'Class', 'woocommerce-table-rate-shipping' ),
					'delete_rates' => __( 'Delete the selected rates?', 'woocommerce-table-rate-shipping' ),
					'dupe_rates'   => __( 'Duplicate the selected rates?', 'woocommerce-table-rate-shipping' ),
				),
				'delete_rates_nonce' => wp_create_nonce( "delete-rate" ),
			) );
		}

		/**
		 * Load shipping class
		 */
		public function shipping_init() {
			include_once( 'includes/class-wc-shipping-table-rate.php' );
		}

		/**
		 * Load shipping methods
		 */
		public function load_shipping_methods( $package ) {
			// Register the main class
			woocommerce_register_shipping_method( 'WC_Shipping_Table_Rate' );

			if ( ! $package ) return;

			// Get zone for package
			$zone = woocommerce_get_shipping_zone( $package );

			if ( TABLE_RATE_SHIPPING_DEBUG ) {
				$notice_text = 'Customer matched shipping zone <strong>' . $zone->zone_name . '</strong> (#' . $zone->zone_id . ')';

				if ( ! wc_has_notice( $notice_text, 'notice' ) ) {
					wc_add_notice( $notice_text, 'notice' );
				}
			}

			if ( $zone->exists() ) {
				// Register zone methods
				$zone->register_shipping_methods();
			}
		}

		/**
		 * Installer
		 */
		public function install() {
			include_once( 'installer.php' );
			update_option( 'table_rate_shipping_version', TABLE_RATE_SHIPPING_VERSION );
		}

	}

	new WC_Table_Rate_Shipping();
}

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
