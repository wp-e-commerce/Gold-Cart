//alert("test");

function update_extra_preview_url(imageid)
  {
  image_height = document.getElementById("image_height").value;
  image_width = document.getElementById("image_width").value;
  image_link_id = "extra_preview_link_"+imageid;
  //alert(image_link_id);
  if(((image_height > 0) && (image_height <= 1024)) && ((image_width > 0) && (image_width <= 1024)))
    {
    new_url = "index.php?view_preview=true&imageid="+imageid+"&height="+image_height+"&width="+image_width+"";
    //alert(document.getElementById(image_link_id).getAttribute('href'));
    document.getElementById(image_link_id).setAttribute('href',new_url);
    }
    else
      {
      //alert(document.getElementById(image_link_id).getAttribute('href'));
      new_url = "index.php?view_preview=true&imageid="+imageid+"";
      document.getElementById(image_link_id).setAttribute('href',new_url);
      }
  return false;
  }


function add_image_upload_forms(state)
  {
  time = new Date();
  if(state == null)
    {
    state = '';
    }
  new_element_number = time.getTime();
  new_element_id = "image_form_id_"+new_element_number;
  
  form_html = "<table class='add_extra_images'>\n\r";
          
  form_html += "  <tr>\n\r";
  form_html += "    <td colspan='2'>\n\r";
  form_html += "    <hr class='image_seperator' />";
  form_html += "    </td>\n\r";
  form_html += "  </tr>\n\r";
  
  form_html += "  <tr>\n\r";
  form_html += "    <td colspan='2'>\n\r";
  form_html += "      <br />\n\r";
  form_html += "      <strong class='form_group'><?php echo TXT_WPSC_PRODUCTIMAGES;?></strong>\n\r";
  form_html += "    </td>\n\r";
  form_html += "  </tr>\n\r";
  form_html += "  <tr>\n\r";
  form_html += "    <td style='width: 120px;'>\n\r";
  form_html +=  TXT_WPSC_PRODUCTIMAGE +"\n\r";
  form_html += "    </td>\n\r";
  form_html += "    <td>\n\r";
  form_html += "      <input type='file' name='extra_image["+new_element_number+"]' value='' />\n\r";
  form_html += "    </td>\n\r";
  form_html += "  </tr>\n\r";  
  
  new_element = document.createElement('div');
  new_element.id = new_element_id;
   
  document.getElementById(state+"additional_images").appendChild(new_element);
  document.getElementById(new_element_id).innerHTML = form_html;
  return false;
  }
  


function change_order(order){
	url = window.location.href;
	
	if(url.search(/\?/) == -1) {
	  separator = '?';
	} else {
	  separator = '&';
	}
	if (order == 'ASC') {
		if (url.search(/order/)!=-1) {
			newurl = url.replace(/DESC/, "ASC");
		} else {
			newurl = url+separator+"order=ASC";
		}
	} else {
		if (url.search(/order/)!=-1) {
			newurl = url.replace(/ASC/, "DESC");
		} else {
			newurl = url+separator+"&order=DESC";
		}
	}
  //alert(newurl);
	window.location = newurl;
}



function change_perpage(num){
	url = window.location.href;
	if(url.search(/\?/) == -1) {
	  separator = '?';
	} else {
	  separator = '&';
	}
	
	if (url.search(/items_per_page/) ==-1) {
		url=url+separator+"items_per_page="+num;
	} else if (url.search(/items_per_page=all/) != -1){
		url=url.replace(/items_per_page=all/,"items_per_page="+num);
	} else {
		url=url.replace(/items_per_page=[0-9]{1,5}/,"items_per_page="+num);
	}
	window.location=url;
	return true;
}

function generate_affiliate_code(){
	product_id=document.getElementById('affliate_products').value;
	user_id=document.getElementById('userid').value;
	ajax.post("index.php",affiliate_code_results,"ajax=true&affiliate=true&prodid="+product_id+"&uid="+user_id);
}

var affiliate_code_results = function(results) {
	document.getElementById('affiliate_code').innerHTML=results;
}

function log_affiliate(uid, amount){
	ajax.post("index.php",log_affiliate_results,"ajax=true&log_affiliate=true&uid="+uid+"&amount="+amount);
	return false;
}

var log_affiliate_results = function(results) {
	eval(results);
	document.getElementById('aff_form_'+uid).submit();
}

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
	  if(jQuery('#affiliate_wrap > ul').tabs != null) {
		// set us up some mighty fine tabs for the options page
			jQuery('#affiliate_wrap > ul').tabs();
			
			// this here code handles remembering what tab you were on
			jQuery('#affiliate_wrap > ul').bind('tabsselect', function(event, ui) {
				
			});
		}

	function adjust_item_width() {
		var container_width = $('.product_grid_display').width(),
			dummy_item = $('.product_grid_item').eq(0),
			border, margin, padding, width;
		
		function toInt(s) {
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