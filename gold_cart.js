/*globals ajax, WPSC_GoldCart*/
jQuery(document).ready(function($){
	jQuery(function() {
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
});