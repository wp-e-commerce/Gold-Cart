jQuery(document).ready(function($){
	jQuery(function() {
		jQuery('.wpsc_live_search').each(function(){
			jQuery(this).keyup(function(event){
				if(!event){
					event=window.event;
				}
				if(event.keyCode){
					keyPressed=event.keyCode;
				}else if(event.which){
					keyPressed=event.which;
				}
				str = event.target.value;
				element = jQuery(event.target).parent().parent().find('.blind_down');
				if (str != '') {
					ajax.post("index.php",function(results){ 
						element.html(results);
						if (element.css('display')!='block') {
							element.slideDown(200);
						}
						return true;
					},"wpsc_live_search=true&wpsc_search_widget=true&keyword="+str);
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
				width = Math.floor(container_width / WPSC_ITEMS_PER_ROW - border - margin - padding);
			
				$('.product_grid_item').css('width', width + 'px');
			}
		}
	
		if ( WPSC_DISPLAY_MODE == 'grid' ) {
			adjust_item_width();
		}
	});
});