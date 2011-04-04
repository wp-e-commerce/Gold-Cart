/*globals ajax, WPSC_GoldCart*/
(function($){
	var searching = false;
	
	$('.wpsc_live_search_embed').live('keyup', function(){
		var t = $(this);
		function fetchItems() {
			searching = false;
			var str = t.val(),
				list = $('.' + WPSC_GoldCart.productListClass),
				data = {
					wpsc_gc_action : 'live_search_embed',
					product_search : str
				};
			$.query.SET('product_search', str);
			$('#wpsc-main-search select').each(function(){
				var t = $(this);
				if (t.val() !== '') {
					data[t.attr('name')] = t.val();
				}
			});
				
			$.get(
				location.href,
				data,
				function(response) {
					var results = $(response);
					// replace old list with new list
					$('.' + WPSC_GoldCart.productListClass).replaceWith(results.find('.' + WPSC_GoldCart.productListClass));
				}
			);
		}
		
		// not so fast, touch typers!
		if (searching) {
			clearTimeout(searching);
			searching = false;
		}
		searching = setTimeout(fetchItems, 500);
	});
	
	$('#wpsc-main-search select').live('change', function(){
		var t = $(this), qs;
		if (t.val() !== '') {
			location.search = $.query.SET(t.attr('name'), t.val());
		}
	});
	
	jQuery(document).ready(function($){
		// detect whether the current theme is not compatible with the new live search embed feature
		// if not, revert to the good ol' drop down live search
		if ($('.' + WPSC_GoldCart.productListClass).length === 0){
			$('.wpsc_live_search_embed').removeClass('.wpsc_live_search_embed').addClass('.wpsc_live_search');
		}		
		jQuery('.wpsc_live_search').each(function(){
			var t = $(this);
			t.keyup(function(){
				var str = t.val(),
					element = t.parent().parent().find('.blind_down');
				if (str !== '') {
					$.post(
						'index.php',
						{
							wpsc_live_search : 'true',
							wpsc_search_widget : 'true',
							keyword : str
						},
						function(results) {
							element.html(results);
							if (element.css('display')!='block') {
								element.slideDown(200);
							}	
						}
					);
				} else {
					element.slideUp(100);
				}
			});
		});
		function adjust_item_width() {
			var container_width = $('.product_grid_display').width(),
				dummy_item = $('.product_grid_item').eq(0),
				border, margin, padding, width;

			function toInt(s) {
				s = s || '';
				return + s.replace(/[^\d\.]/g, '');
			}

			if (dummy_item) {
				border = toInt(dummy_item.css('borderLeftWidth')) + toInt(dummy_item.css('borderRightWidth'));
				margin = toInt(dummy_item.css('marginLeft')) + toInt(dummy_item.css('marginRight'));
				padding = toInt(dummy_item.css('paddingLeft')) + toInt(dummy_item.css('paddingRight'));
				width = Math.floor(container_width / WPSC_GoldCart.itemsPerRow - border - margin - padding);

				$('.product_grid_item').css('width', width + 'px');
			}
		}

		if ( WPSC_GoldCart.displayMode == 'grid' ) {
			adjust_item_width();
		}
	});
})(jQuery);