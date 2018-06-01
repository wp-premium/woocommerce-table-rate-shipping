<?php
if ( ! class_exists( 'WC_Abstract_Privacy' ) ) {
	return;
}

class WC_Shipping_Table_Rate_Privacy extends WC_Abstract_Privacy {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct( __( 'Table rates', 'woocommerce-table-rate-shipping' ) );
	}

	/**
	 * Gets the message of the privacy to display.
	 *
	 */
	public function get_privacy_message() {
		return wpautop( sprintf( __( 'By using this extension, you may be storing personal data or sharing data with an external service. <a href="%s" target="_blank">Learn more about how this works, including what you may want to include in your privacy policy.</a>', 'woocommerce-table-rate-shipping' ), 'https://docs.woocommerce.com/document/privacy-shipping/#woocommerce-table-rate-shipping' ) );
	}
}

new WC_Shipping_Table_Rate_Privacy();
