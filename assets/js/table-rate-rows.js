/*global $, woocommerce_shipping_table_rate_rows, ajaxurl */
( function( $, data, wp, ajaxurl ) {

	var wc_table_rate_rows_row_template = wp.template( 'table-rate-shipping-row-template' ),
		$rates_table                    = $( '#shipping_rates' ),
		$rates                          = $rates_table.find( 'tbody.table_rates' );

	var wc_table_rate_rows = {
		init: function() {
			$rates_table
				.on( 'change', '#woocommerce_table_rate_calculation_type', this.onCalculationTypeChange )
				.on( 'change', 'select[name^="shipping_condition"]', this.onShippingConditionChange )
				.on( 'change', 'input[name^="shipping_abort["]', this.onShippingAbortChange )
				.on( 'click', 'a.add-rate', this.onAddRate )
				.on( 'click', 'a.remove', this.onRemoveRate )
				.on( 'click', 'a.dupe', this.onDupeRate );

			var rates_data = $rates.data( 'rates' );

			$( rates_data ).each( function( i ) {
				var size = $rates.find( '.table_rate' ).length;
				$rates.append( wc_table_rate_rows_row_template( {
					rate:  rates_data[ i ],
					index: size
				} ) );
			} );

			$( 'label[for="woocommerce_table_rate_handling_fee"], label[for="woocommerce_table_rate_max_cost"], label[for="woocommerce_table_rate_min_cost"]', $rates_table ).each( function( i, el ) {
				$(el).data( 'o_label', $(el).text() );
			});

			$( '#woocommerce_table_rate_calculation_type, select[name^="shipping_condition"], input[name^="shipping_abort["]', $rates_table ).change();

			$rates.sortable( {
				items: 'tr',
				cursor: 'move',
				axis: 'y',
				handle: 'td',
				scrollSensitivity: 40,
				helper: function(e,ui){
					ui.children().each( function() {
						$( this ).width( $(this).width() );
					});
					ui.css( 'left', '0' );
					return ui;
				},
				start: function( event, ui ) {
					ui.item.css('background-color','#f6f6f6');
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
					wc_table_rate_rows.reindexRows();
				}
			} );

			$( document.body ).on( 'change', '#woocommerce_table_rate_calculation_type', function() {
				if ( '' === $( this ).val() ) {
					$( '#shipping_class_priorities' ).show();
				} else {
					$( '#shipping_class_priorities' ).hide();
				}
			});

			$( '#woocommerce_table_rate_calculation_type' ).change();
		},
		onCalculationTypeChange: function() {
			var selected = $( this ).val();

			if ( selected == 'item' ) {
				$( 'td.cost_per_item input' ).attr( 'disabled', 'disabled' ).addClass('disabled');
			} else {
				$( 'td.cost_per_item input' ).removeAttr( 'disabled' ).removeClass('disabled');
			}

			if ( selected ) {
				$( '#shipping_class_priorities' ).hide();
				$( 'td.shipping_label, th.shipping_label' ).hide();
			} else {
				$( '#shipping_class_priorities' ).show();
				$( 'td.shipping_label, th.shipping_label' ).show();
			}

			if ( ! selected ) {
				$( '#shipping_class_priorities span.description.per_order' ).show();
				$( '#shipping_class_priorities span.description.per_class' ).hide();
			}

			var label_text = data.i18n.order;

			if ( selected == 'item' ) {
				label_text = data.i18n.item;
			} else if ( selected == 'line' ) {
				label_text = data.i18n.line_item;
			} else if ( selected == 'class' ) {
				label_text = data.i18n.class;
			}

			$('label[for="woocommerce_table_rate_handling_fee"], label[for="woocommerce_table_rate_max_cost"], label[for="woocommerce_table_rate_min_cost"]').each(function( i, el ) {
				var text  = $(el).data( 'o_label' );
				text = text.replace( '[item]', label_text );
				$(el).text( text );
			});
		},
		onShippingConditionChange: function() {
			var selected = $( this ).val();
			var $row 	 = $( this ).closest('tr');

			if ( selected == '' ) {
				$row.find('input[name^="shipping_min"], input[name^="shipping_max"]').val( '' ).prop( 'disabled', true ).addClass( 'disabled' );
			} else {
				$row.find('input[name^="shipping_min"], input[name^="shipping_max"]').prop( 'disabled', false ).removeClass( 'disabled' );
			}
		},
		onShippingAbortChange: function() {
			var checked = $( this ).is( ':checked' );
			var $row 	= $( this ).closest( 'tr' );

			if ( checked ) {
				$row.find('td.cost').hide();
				$row.find('td.abort_reason').show();
				$row.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').prop( 'disabled', true ).addClass( 'disabled' );
			} else {
				$row.find('td.cost').show();
				$row.find('td.abort_reason').hide();
				$row.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').prop( 'disabled', false ).removeClass( 'disabled' );
			}

			$( '#woocommerce_table_rate_calculation_type' ).change();
		},
		onAddRate: function( event ) {
			event.preventDefault();
			var target = $rates;
			var size   = target.find( '.table_rate' ).length;

			target.append( wc_table_rate_rows_row_template( {
				rate:  {
					rate_id: '',
					rate_class: '',
					rate_condition: '',
					rate_min: '',
					rate_max: '',
					rate_priority: '',
					rate_abort: '',
					rate_abort_reason: '',
					rate_cost: '',
					rate_cost_per_item: '',
					rate_cost_per_weight_unit: '',
					rate_cost_percent: '',
					rate_label: ''
				},
				index: size
			} ) );

			$( '#woocommerce_table_rate_calculation_type, select[name^="shipping_condition"], input[name^="shipping_abort["]', $rates_table ).change();
		},
		onRemoveRate: function( event ) {
			event.preventDefault();
			if ( confirm( data.i18n.delete_rates ) ) {
				var rate_ids  = [];

				$rates.find( 'tr td.check-column input:checked' ).each( function( i, el ) {
					var rate_id = $(el).closest( 'tr.table_rate' ).find( '.rate_id' ).val();
					rate_ids.push( rate_id );
					$(el).closest( 'tr.table_rate' ).addClass( 'deleting' );
				});

				var ajax_data = {
					action: 'woocommerce_table_rate_delete',
					rate_id: rate_ids,
					security: data.delete_rates_nonce
				};

				$.post( ajaxurl, ajax_data, function(response) {
					$( 'tr.deleting').fadeOut( '300', function() {
						$( this ).remove();
					} );
				});
			}
		},
		onDupeRate: function( event ) {
			event.preventDefault();
			if ( confirm( data.i18n.dupe_rates ) ) {

				$rates.find( 'tr td.check-column input:checked' ).each( function( i, el ) {
					var dupe = $(el).closest( 'tr' ).clone();
					dupe.find( '.rate_id' ).val( '0' );
					$rates.append( dupe );
				});

				wc_table_rate_rows.reindexRows();
			}
		},
		reindexRows: function() {
			var loop = 0;
			$rates.find( 'tr' ).each( function( index, row ) {
				$('input.text, input.checkbox, select.select', row ).each( function( i, el ) {
					var t = $(el);
					t.attr( 'name', t.attr('name').replace(/\[([^[]*)\]/, "[" + loop + "]" ) );
				});
				loop++;
			});
		}
	};

	wc_table_rate_rows.init();

})( jQuery, woocommerce_shipping_table_rate_rows, wp, ajaxurl );
