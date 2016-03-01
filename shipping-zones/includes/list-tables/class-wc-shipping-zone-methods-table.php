<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Shipping_Zone_Methods_Table class.
 *
 * @extends WP_List_Table
 */
class WC_Shipping_Zone_Methods_Table extends WP_List_Table {

    public $index = 0;
    public $zone_id = 0;

    /**
     * Constructor
     */
    public function __construct(){
        global $status, $page;

        $this->zone_id = (int) $_GET['zone'];
        $this->index = 0;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'Shipping Method',     //singular name of the listed records
            'plural'    => 'Shipping Methods',    //plural name of the listed records
            'ajax'      => false        		//does this table support ajax?
        ) );

      wc_enqueue_js( "
			jQuery('table.shippingmethods tbody th, table.shippingmethods tbody td').css('cursor','move');

			jQuery('table.shippingmethods tbody').sortable({
				items: 'tr:not(.inline-edit-row)',
				cursor: 'move',
				axis: 'y',
				containment: 'table.shippingmethods',
				scrollSensitivity: 40,
				helper: function(e, ui) {
					ui.children().each(function() { jQuery(this).width(jQuery(this).width()); });
					return ui;
				},
				start: function(event, ui) {
					if ( ! ui.item.hasClass('alternate') ) ui.item.css( 'background-color', '#ffffff' );
					ui.item.children('td,th').css('border-bottom-width','0');
					ui.item.css( 'outline', '1px solid #dfdfdf' );
				},
				stop: function(event, ui) {
					ui.item.removeAttr('style');
					ui.item.children('td,th').css('border-bottom-width','1px');
				},
				update: function(event, ui) {
					jQuery('table.shippingmethods tbody th, table.shippingmethods tbody td').css('cursor','default');
					jQuery('table.shippingmethods tbody').sortable('disable');

					var shipping_method_id = ui.item.find('.check-column input').val();
					var prev_shipping_method_id = ui.item.prev().find('.check-column input').val();
					var next_shipping_method_id = ui.item.next().find('.check-column input').val();

					// show spinner
					ui.item.find('.check-column input').hide().after('<img alt=\"processing\" src=\"images/wpspin_light.gif\" class=\"waiting\" style=\"margin-left: 6px;\" />');

					// go do the sorting stuff via ajax
					jQuery.post( ajaxurl, { action: 'woocommerce_shipping_method_ordering', security: '" . wp_create_nonce( 'shipping-zones' ) . "', shipping_method_id: shipping_method_id, prev_shipping_method_id: prev_shipping_method_id, next_shipping_method_id: next_shipping_method_id }, function(response) {
						ui.item.find('.check-column input').show().siblings('img').remove();
						jQuery('table.shippingmethods tbody th, table.shippingmethods tbody td').css('cursor','move');
						jQuery('table.shippingmethods tbody').sortable('enable');
					});

					// fix cell colors
					jQuery( 'table.shippingmethods tbody tr' ).each(function(){
						var i = jQuery('table.shippingmethods tbody tr').index(this);
						if ( i%2 == 0 ) jQuery(this).addClass('alternate');
						else jQuery(this).removeClass('alternate');
					});
				}
			});

        " );
    }

    /**
     * Checkbox column
     * @param string
     */
    public function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            'shipping_method_id',
            $item->number
        );
    }

    /**
     * Output the title column.
     * @param  object $item
     * @return string
     */
    public function column_title( $item ) {
        $title = $item->title;

        if ( ! $title ) {
            $title = ucwords( $item->method_title );
        }

        return '
            <strong><a href="' . esc_url( add_query_arg( 'method', $item->number, add_query_arg( 'zone',  $this->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) ) . '">' . esc_html( $title ) . '</a></strong>
            <div class="row-actions">
                <span class="id">ID: ' . esc_html( $item->instance_id ) . ' | </span><span><a href="' . esc_url( add_query_arg( 'method', $item->number, add_query_arg( 'zone', $this->zone_id, admin_url( 'admin.php?page=shipping_zones' ) ) ) ) . '">' . __( 'Edit' , SHIPPING_ZONES_TEXTDOMAIN ) . '</a> | </span><span class="trash"><a class="shipping-zone-delete" href="' . esc_url( wp_nonce_url( add_query_arg( 'delete_method', $item->number ), 'woocommerce_delete_method' ) ) . '" data-message="' . esc_attr( __( 'Are you sure you want to delete this method?', SHIPPING_ZONES_TEXTDOMAIN ) ) . '">' . __( 'Delete', SHIPPING_ZONES_TEXTDOMAIN ) . '</a></span>
            </div>';
    }

    /**
     * Output the type column.
     * @param  object $item
     * @return string
     */
    public function column_type( $item ) {
        return esc_html( $item->method_title );
    }

    /**
     * Output the enabled column.
     * @param  object $item
     * @return string
     */
    public function column_enabled( $item ) {
        return 'yes' === $item->enabled ? '&#10004;' : '&ndash;';
    }

    /**
     * get_columns function.
     * @return array
     */
    public function get_columns(){
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'title'   => __( 'Method Title', SHIPPING_ZONES_TEXTDOMAIN ),
            'type'    => __( 'Method Type', SHIPPING_ZONES_TEXTDOMAIN ),
            'enabled' => __( 'Enabled', SHIPPING_ZONES_TEXTDOMAIN ),
        );
        return $columns;
    }

     /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        $actions = array(
            'delete'    => __('Delete', SHIPPING_ZONES_TEXTDOMAIN)
        );
        return $actions;
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        global $wpdb;

        if ( ! isset( $_POST['shipping_method_id'] ) ) {
            return;
        }

        $items = array_filter( array_map( 'absint', $_POST['shipping_method_id'] ) );

        if ( ! $items ) {
            return;
        }

        if ( 'delete' === $this->current_action() ) {
        	foreach ( $items as $id ) {
                $wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_zone_shipping_methods', array( 'shipping_method_id' => $id ) );
                $wpdb->delete( $wpdb->prefix . 'woocommerce_shipping_table_rates', array( 'shipping_method_id' => $id ) );
                delete_option( 'woocommerce_table_rate_priorities_' . $id );
        		delete_option( 'woocommerce_table_rate_default_priority_' . $id );
        	}

            echo '<div class="updated success"><p>' . __( 'Shipping methods deleted', SHIPPING_ZONES_TEXTDOMAIN ) . '</p></div>';
        }
    }

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		echo '<p>' . __( 'No shipping methods found.', SHIPPING_ZONES_TEXTDOMAIN ) . '</p>';
	}

    /**
     * Get shipping methods to display for this zone.
     */
    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = array( $this->get_columns(), array(), array() );
        $this->process_bulk_action();

		$shipping_methods = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods
			WHERE zone_id = %s
			ORDER BY `shipping_method_order` ASC
		", $this->zone_id ) );

		foreach ( $shipping_methods as $method ) {
			$class_callback = 'woocommerce_get_shipping_method_' . $method->shipping_method_type;

			if ( function_exists( $class_callback ) ) {
				$this->items[] = call_user_func( $class_callback, $method->shipping_method_id );
			}
		}
    }
}