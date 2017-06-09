jQuery(function($) {

	if ( ! wc_shipping_zones_params.supports_select2 ) {
		$("select.chosen_select").chosen();
	}

	$('body')

		.on( 'click', 'a.shipping-zone-delete', function(){
			var answer = confirm( $(this).data('message') );
			if ( answer ) {
				return true;
			} else {
				return false;
			}
		})

		.on( 'change', 'input[name=zone_type]', function() {
			if ( $(this).is(':checked') ) {
				var value = $(this).val();
				$( '.zone_type_options' ).slideUp();
				$( '.zone_type_' + value ).slideDown();
			}
		})

		.on( 'click', '.select_us_states', function(){
			$(this).closest('div').find('option[value="US:AK"], option[value="US:AL"], option[value="US:AZ"], option[value="US:AR"], option[value="US:CA"], option[value="US:CO"], option[value="US:CT"], option[value="US:DE"], option[value="US:DC"], option[value="US:FL"], option[value="US:GA"], option[value="US:HI"], option[value="US:ID"], option[value="US:IL"], option[value="US:IN"], option[value="US:IA"], option[value="US:KS"], option[value="US:KY"], option[value="US:LA"], option[value="US:ME"], option[value="US:MD"], option[value="US:MA"], option[value="US:MI"], option[value="US:MN"], option[value="US:MS"], option[value="US:MO"], option[value="US:MT"], option[value="US:NE"], option[value="US:NV"], option[value="US:NH"], option[value="US:NJ"], option[value="US:NM"], option[value="US:NY"], option[value="US:NC"], option[value="US:ND"], option[value="US:OH"], option[value="US:OK"], option[value="US:OR"], option[value="US:PA"], option[value="US:RI"], option[value="US:SC"], option[value="US:SD"], option[value="US:TN"], option[value="US:TX"], option[value="US:UT"], option[value="US:VT"], option[value="US:VA"], option[value="US:WA"], option[value="US:WV"], option[value="US:WI"], option[value="US:WY"]').attr("selected","selected");
			$(this).closest('div').find('select').trigger('chosen:updated').change();
			return false;
		})

		.on( 'click', '.select_europe', function(){
			$(this).closest('div').find('option[value="AL"], option[value="AD"], option[value="AM"], option[value="AT"], option[value="BY"], option[value="BE"], option[value="BA"], option[value="BG"], option[value="CH"], option[value="CY"], option[value="CZ"], option[value="DE"], option[value="DK"], option[value="EE"], option[value="ES"], option[value="FO"], option[value="FI"], option[value="FR"], option[value="GB"], option[value="GE"], option[value="GI"], option[value="GR"], option[value="HU"], option[value="HR"], option[value="IE"], option[value="IS"], option[value="IT"], option[value="LT"], option[value="LU"], option[value="LV"], option[value="MC"], option[value="MK"], option[value="MT"], option[value="NO"], option[value="NL"], option[value="PO"], option[value="PT"], option[value="RO"], option[value="RU"], option[value="SE"], option[value="SI"], option[value="SK"], option[value="SM"], option[value="TR"], option[value="UA"], option[value="VA"]').attr("selected","selected");
			$(this).closest('div').find('select').trigger('chosen:updated').change();
			return false;
		})

		.on( 'click', '.select_none', function(){
			$(this).closest('div').find('select option').removeAttr("selected");
			$(this).closest('div').find('select').trigger('chosen:updated').change();
			return false;
		})

		.on( 'click', '.select_all', function(){
			$(this).closest('div').find('select option').attr("selected","selected");
			$(this).closest('div').find('select').trigger('chosen:updated').change();
			return false;
		});

	// Sorting
	$('table.shippingzones tbody').sortable({
		items:'tr:not(:last-child)',
		cursor:'move',
		axis:'y',
		handle: 'td',
		scrollSensitivity:40,
		helper:function(e,ui){
			ui.children().each(function(){
				$(this).width($(this).width());
			});
			ui.css('left', '0');
			return ui;
		},
		start:function(event,ui){
			ui.item.css('background-color','#f6f6f6');
		},
		stop:function(event,ui){
			ui.item.removeAttr('style');
		},
		update: function(event, ui) {
			$('table.shippingzones tbody td').css('cursor','default');
			$('table.shippingzones tbody').sortable('disable');

			// show spinner
			ui.item.find('.check-column input').hide();
			ui.item.find('.check-column').append('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');

			// Parent
			var zone_ids = [];

			$(this).closest('form').find('input.zone_id').each(function(){
				var zone_id = $(this).val();
				zone_ids.push(zone_id);
			});

			// go do the sorting stuff via ajax
			$.post( ajaxurl, { action: 'woocommerce_zone_ordering', security: wc_shipping_zones_params.shipping_zones_nonce, zone_ids: zone_ids }, function(response) {
				ui.item.find('.check-column input').show();
				ui.item.find('.check-column').find('img').remove();
				$('table.shippingzones tbody td').css('cursor','move');
				$('table.shippingzones tbody').sortable('enable');

			});

			// fix cell colors
			$('table.shippingzones tbody tr').each(function(){
				var i = $('table.shippingzones tbody tr').index(this);
				if ( i%2 == 0 ) $(this).addClass('alternate');
				else $(this).removeClass('alternate');
			});
		}
	});

	$('.zone_type_options').hide();
	$('input[name=zone_type]').change();
});
