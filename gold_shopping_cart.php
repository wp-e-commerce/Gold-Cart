<?php
/*
Plugin Name: Gold Cart for WP e-Commerce
Plugin URI: http://www.getshopped.org
Description: Gold Cart extends your WP e-Commerce store by enabling additional features and functionality, including views, galleries, store search and payment gateways. See also: <a href="http://getshopped.org" target="_blank">GetShopped.org</a> | <a href="http://getshopped.org/resources/premium-support/" target="_blank">Premium Support</a> | <a href="http://getshopped.org/resources/docs/" target="_blank">Documentation</a>
Version: 2.8
Author: GetShopped.org
Author URI: http://www.getshopped.org
*/
/** this is the file that converts the normal shopping cart to the gold shopping cart */

$gold_shpcrt_active = get_option('activation_state');
define('WPSC_GOLD_MODULE_PRESENT', true);
define('WPSC_GOLD_FILE_PATH', dirname(__FILE__));
define('WPSC_GOLD_DIR_NAME', basename(WPSC_GOLD_FILE_PATH));
define('WPSC_GOLD_FILE_URL', get_plugin_url());
define('WPSC_GOLD_VERSION', '2.8' );
require(dirname(__FILE__)."/upgrade_panel.php");

//scribu function to find proper plugin url
function get_plugin_url() {
	// WP < 2.6
	if ( !function_exists('plugins_url') )
	return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));
	
	return plugins_url(plugin_basename(dirname(__FILE__)));
}

//check if newer version is available
function gold_check_plugin_version( $plugin ) {
  if( strpos( WPSC_GOLD_DIR_NAME.'/'.__FILE__,$plugin ) !== false ) {
    $checkfile = "http://getshopped.org/wp-content/uploads/wpsc/updates/wpsc_goldcart.chk";
    $vcheck = wp_remote_fopen($checkfile);
    if( $vcheck ) {
      $version = WPSC_GOLD_VERSION;
      $status = explode('@', $vcheck);
      $theVersion = $status[1];
      $theMessage = $status[3];
      if( (version_compare(strval($theVersion), strval($version), '>') == 1) ) {
        echo '
        <td colspan="5" class="plugin-update" style="line-height:1.2em; font-size:11px; padding:1px;">
          <div style="color:#000; font-weight:bold; margin:4px; padding:6px 5px; background-color:#fffbe4; border-color:#dfdfdf; border-width:1px; border-style:solid; -moz-border-radius:5px; -khtml-border-radius:5px; -webkit-border-radius:5px; border-radius:5px;">'.__("There is a new version of Gold Cart for WP e-Commerce available.", "gold").' <a href="'.$theMessage.'" target="_blank">View version '.$theVersion.' details</a>.</div	>
        </td>';
      } else {
        return;
      }
    }
  }
}
add_action( 'after_plugin_row', 'gold_check_plugin_version' );

//default install gold cart kept to preserve backwards compatibility
function gold_shpcrt_install() {
	global $wpdb, $user_level, $wp_rewrite;
}

//  if gold cart is activated add the necessary functions  
if($gold_shpcrt_active === 'true') {
	add_action('admin_head', 'gold_shpcrt_javascript', 20);
	add_action('wp_head', 'gold_shpcrt_javascript', 20);
	add_action('wp_print_styles', 'wpsc_gold_cart_styles' );
	
	function wpsc_gold_cart_styles() {
		wp_enqueue_style( 'wpsc-gold-cart', get_plugin_url() . '/gold_cart.css' );
		if ( get_option( 'product_view' ) == 'grid' )
			wp_enqueue_style( 'wpsc-gold-cart-grid-view', get_plugin_url() . '/grid_view.css', array( 'wpsc-gold-cart' ) );
	}
	
  //include necessary js and css files and dynamic JS
  function gold_shpcrt_javascript() {
    $siteurl = get_option('siteurl');
		if(is_ssl()) {
			$siteurl = str_replace("http://", "https://", $siteurl);
		}

    if ((get_option('show_search') == 1) && (get_option('show_live_search') == 1)) {
			if ( (float)WPSC_VERSION < 3.8 ):
				?>
				<script type="text/javascript" src="<?php echo $siteurl;?>/wp-content/plugins/<?php echo WPSC_DIR_NAME; ?>/js/iautocompleter.js"></script>
				<?php
			endif;
		}
		?>
    <script type='text/javascript'>
    <?php
	
	$product_view = get_option( 'product_view' );
	echo "var WPSC_DISPLAY_MODE = '" . esc_js( $product_view ) . "';";
	if ( $product_view == 'grid' ) {
		echo 'var WPSC_ITEMS_PER_ROW = ' . esc_js( get_option( 'grid_number_per_row' ) ) . ';';
	}
    ?>
    </script>
    <script src="<?php echo get_plugin_url(); ?>/gold_cart.js" type="text/javascript"></script>
    <?php
  }
  
  // function to display search bar and execute search
  function wpsc_gold_shpcrt_ajax($id) {
		global $wpdb;

		if(isset($_POST) && !empty($_POST))

		if( isset($_POST['wpsc_live_search']) && ($_POST['wpsc_live_search']==true) && (get_option('show_live_search') == 1 || true == $_POST['wpsc_search_widget']) && !empty($_POST['keyword'])){
			$keyword=$_POST['keyword'];
			$output =  "<ul>";
			if ( (float)WPSC_VERSION < 3.8 ) {
				$search_sql = gold_shpcrt_search_sql($keyword);
				$product_list = $wpdb->get_results("SELECT DISTINCT `".WPSC_TABLE_PRODUCT_LIST."`.* FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE `".WPSC_TABLE_PRODUCT_LIST."`.`active`='1' $search_sql ORDER BY `".WPSC_TABLE_PRODUCT_LIST."`.`name` ASC",ARRAY_A) ;
				if ($product_list != null) {
					foreach($product_list as $product) {
					   //filter out the HTML, otherwise we get partial tags and everything breaks
					  $product['description'] = wp_kses($product['description'], false);
					  
					  // shorten the description;
						if (strlen($product['description'])>68) {
							$product_description = substr($product['description'], 0, 68)."...";
						} else {
							$product_description = $product['description'];
						}
						//generate the HTML
						$output .= "<li>\n\r";
						$output .= "	<a href='".wpsc_product_url($product['id'])."'>\n\r";
						if ($product['image'] != '') {
							$output .= "				<img class='live-search-image' src='index.php?productid=".$product['id']."&amp;width=50&amp;height=50'>\n\r";
						} else {
							$output .= "				<img class='live-search-image' src='".get_option('siteurl')."/wp-content/plugins/".WPSC_DIR_NAME."/no-image-uploaded.gif' style='height: 50px; width: 50px;'>\n\r";
						}
						$output .= "				<div class='live-search-text'>\n\r";
						$output .= "					<strong>".$product['name']."</strong>\n\r";
						$output .= "					<div class='description'>".stripslashes($product_description)."</div>\n\r";
						$output .= "				</div>\n\r";
						$output .= "		    <br clear='both' />\n\r";
						$output .= "		</a>\n\r";
						$output .= "</li>\n\r";					
					}
				}
			} else {
				$keyword = '%' . urldecode($_POST['keyword']) . "%";
				echo $keyword.'<br />';
				/* 
				search query starts here. select all products with keyword-like name, description, additional description, category or tag
				maybe it should be converted to function later? wpsc_search_products($keyword); 
				 */
				$products = $wpdb->get_results( $wpdb->prepare( '
					SELECT 
						`p`.*
					FROM 
						`' . $wpdb->posts . '` `p` 
					WHERE 
						`p`.`post_type` = "wpsc-product" 
						AND 
						`p`.`post_status` = "publish" 
						AND 
						(
							`p`.`post_title` LIKE "%1$s" 
							OR 
							`p`.`post_content` LIKE "%1$s" 
							OR 
							`p`.`post_excerpt` LIKE "%1$s" 
							OR `p`.`id` IN 
							( 
								SELECT 
									`tr`.`object_id` 
								FROM 
									`' . $wpdb->term_relationships . '` `tr` 
								WHERE 
									`tr`.`term_taxonomy_id` IN 
									( 
										SELECT 
											`t`.`term_taxonomy_id` 
										FROM 
											`' . $wpdb->term_taxonomy . '` `t` 
										JOIN 
											`' . $wpdb->terms . '` `terms` 
										ON 
											`t`.`term_id` = `terms`.`term_id` 
										WHERE 
											`t`.`taxonomy` IN ( "product_tag", "wpsc_product_category" ) 
											AND 
											`terms`.`name` LIKE "%1$s" 
									) 
							) 
						) ', $keyword ), ARRAY_A );
				/* end of search query */
				
				foreach( (array)$products as $product ){
					$output .= "<li>\n\r";
					$output .= "	<a href='" . get_permalink( $product['ID'] ) . "'>\n\r";
					if ( wpsc_the_product_thumbnail( 50, 50, $product['ID'] ) ) {
						$output .= "				<img class='live-search-image' src='" . wpsc_the_product_thumbnail( 50, 50, $product['ID'], 'live-search' ) . "'>\n\r";
					} else {
						$output .= "				<img class='live-search-image' src='" . get_option('siteurl') . "/wp-content/plugins/".WPSC_DIR_NAME."/wpsc-theme/wpsc-images/noimage.png' style='height: 50px; width: 50px;'>\n\r";
					}
					$output .= "				<div class='live-search-text'>\n\r";
					$output .= "					<strong>" . $product['post_title'] . "</strong>\n\r";
					$output .= "					<div class='description'>" . $product['post_content'] . "</div>\n\r";
					$output .= "				</div>\n\r";
					$output .= "		    <br clear='both' />\n\r";
					$output .= "		</a>\n\r";
					$output .= "</li>\n\r";					
				}
			}
			$output .= "</ul>";
			if ( ! empty( $product_list ) )
				$_SESSION['live_search_results'] = $product_list;
			exit($output);
		}
		
		if ( isset($_POST['affiliate']) && $_POST['affiliate']==true) {
		  if(!function_exists('affiliate_text')) {
				function affiliate_text($id, $user) {
					$output = "<a target='_blank' title='Your Shopping Cart' href='".get_option('siteurl')."/?action=affiliate&p=$id&user_id=".$user."&height=400&width=600' class='thickbox'><img src='".WPSC_URL."/".$images_dir."/buynow.jpg'></a>";
					return $output;
				}
			}
	
		$id = $_POST['prodid'];
		$product = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE id='$id' LIMIT 1",ARRAY_A);
		$product = $product[0];
		$link = affiliate_text($id,$_POST['uid']);
		echo "<textarea class='affiliate_text' onclick='this.select();' >$link</textarea>";
		exit();
	}
	if (isset($_POST['log_affiliate']) && $_POST['log_affiliate']==true) {
		$uid = $_POST['uid'];
		$amount = $_POST['amount'];
		$product = $wpdb->query("UPDATE {$wpdb->prefix}wpsc_affiliates SET paid=paid+$amount  WHERE user_id='$uid'");
		echo "uid=".$uid;
		exit();
	}

  }
  
  
  // function to display additional images in the image gallery  
  function gold_shpcrt_display_gallery($product_id, $invisible = false) {
    global $wpdb;
    $output ='';
    $siteurl = get_option('siteurl');
	  /* No GD? No gallery.  No gallery option? No gallery.  Range variable set?  Apparently, no gallery. */
    if(get_option('show_gallery') == 1 && !isset($_GET['range']) && function_exists("getimagesize")) {
		if ( (float)WPSC_VERSION < 3.8 ) {
        /* get data about the base product image */
        $product = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE `id`='".$product_id."' LIMIT 1",ARRAY_A);
        $image_link = WPSC_IMAGE_URL.$product['image']."";    
		$image_file_name = $product['image'];
        $imagepath = WPSC_THUMBNAIL_DIR.$image_file_name;
        $base_image_size = @getimagesize($imagepath);
        
        /* get data about the extra product images */
        $images = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PRODUCT_IMAGES."` WHERE `product_id` = '$product_id' AND `id` NOT IN('$image_file_name')  ORDER BY `image_order` ASC",ARRAY_A);
        $output = "";      
		$new_height = get_option('wpsc_gallery_image_height');
		$new_width = get_option('wpsc_gallery_image_width'); 
		if(count($images) > 0) {
			/* display gallery */
			if($invisible == true) { 
				foreach($images as $image) {         
					$extra_imagepath = WPSC_IMAGE_DIR.$image['image']."";    
					$extra_image_size = @getimagesize($extra_imagepath); 
					$thickbox_link = WPSC_IMAGE_URL.$image['image']."";
					$image_link = "index.php?image_id=".$image['id']."&amp;width=".$new_width."&amp;height=".$new_height."";
					$output .= "<a href='".$thickbox_link."' class='thickbox hidden_gallery_link'  rel='".str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), $product['name'])."' rev='$image_link'>&nbsp;</a>";
				}
			} else {
				$output .= "<h2 class='prodtitles'>".__("Gallery")."</h2>";
				$output .= "<div class='wpcart_gallery'>";
				if($images != null) {
					foreach($images as $image) {         
						$extra_imagepath = WPSC_IMAGE_DIR.$image['image']."";    
						$extra_image_size = @getimagesize($extra_imagepath); 
						$thickbox_link = WPSC_IMAGE_URL.$image['image']."";
						$image_link = "index.php?image_id=".$image['id']."&amp;width=".$new_width."&amp;height=".$new_height."";       
						$output .= "<a href='".$thickbox_link."' class='thickbox'  rel='".str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), $product['name'])."'><img src='$image_link' alt='$product_name' title='$product_name' /></a>";
					}
				}		
				$output .= "</div>";
			}
        }
	}else {	
	//closes if < 3.8 condition
$output = '';

	$product_name = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM $wpdb->posts WHERE `ID`='".$product_id."' LIMIT 1"));
	$output .= "<div class='wpcart_gallery'>";
		$args = array(
						'post_type' => 'attachment',
						'post_parent' => $product_id,
						'post_mime_type' => 'image',
						'orderby'     => 'menu_order',
						'order'       => 'ASC',
						'numberposts' => -1
						); 
		$attachments = get_posts($args);
		$featured_img = get_post_meta($product_id, '_thumbnail_id');
		if ($attachments) {
			foreach ($attachments as $post) {
				if (in_array($post->ID, $featured_img))
					continue; 
				setup_postdata($post);
				$link = wp_get_attachment_link( $post->ID, 'gold-thumbnails' );
				$preview_link = wp_get_attachment_image_src( $post->ID, 'product-thumbnails');
				$link = str_replace( 'a href' , 'a rev="' . $preview_link[0] . '" class="thickbox" rel="' . $product_name . '" href' , $link );
				$output .= $link;
			}
		}
				$output .= "</div>";
				wp_reset_query();
	
	}	//closes if > 3.8 condition
  }	//closes if gallery setting condition
    return $output;
}
    
  //generated the sql statement used to search for products
  function gold_shpcrt_search_sql($search_string = '') {
    global $wpdb;
    
    if ( (float)WPSC_VERSION < 3.8 )
		$images_dir = 'images';
	else
		$images_dir = 'wpsc-core/images';
    
    $output = "";
    if($search_string == '') {
      $search_string = $_GET['product_search'];
    }
    if($search_string != '') {
      $brand_sql = '';
      $category_sql = '';
      $search_string_title = "%".$wpdb->escape(stripslashes($search_string))."%";
      $search_string_description = "%".$wpdb->escape(stripslashes($search_string))."%";


      $category_list = $wpdb->get_col("SELECT `id` FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `name` LIKE '".$search_string_title."'");
      
      $meta_list = $wpdb->get_col("SELECT DISTINCT `product_id` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_value` REGEXP '".$wpdb->escape(stripslashes($search_string))."' AND `custom` IN ('1')");
      if($category_list != null) {
				$category_assoc_list = $wpdb->get_col("SELECT DISTINCT `product_id` FROM `".WPSC_TABLE_ITEM_CATEGORY_ASSOC."` WHERE `category_id` IN ('".implode("', '", $category_list)."')");
				$category_sql = "OR `".WPSC_TABLE_PRODUCT_LIST."`.`id` IN ('".implode("', '", $category_assoc_list)."')";
      }  
      
      // this cannot currently list products that are associated with no categories      
      $output = "AND (`".WPSC_TABLE_PRODUCT_LIST."`.`name` LIKE '".$search_string_title."' OR `".WPSC_TABLE_PRODUCT_LIST."`.`description` LIKE '".$search_string_description."' OR `".WPSC_TABLE_PRODUCT_LIST."`.`id` IN ('".implode("','",$meta_list)."') OR `".WPSC_TABLE_PRODUCT_LIST."`.`additional_description` LIKE '".$search_string_description."' $category_sql )";
      //echo $output;
    }
    return $output;
  }

  // function to display search box
  function gold_shpcrt_search_form(){
  
	if ( (float)WPSC_VERSION < 3.8 )
		$images_dir = 'images';
	else
		$images_dir = 'wpsc-core/images';
  
		$siteurl = get_option('siteurl'); 
    $output = '';
    if(get_option('permalink_structure') != '') {
    	$seperator ="?";
    } else {
    	$seperator ="&amp;";
    }
    $output .= "<div class='wpsc_product_search'>";

    if($seperator == "&amp;") {
      $output .= "<form action='".get_option('product_list_url')."' method='GET' name='product_search'  class='product_search'>\n\r";
      $url_parameters = explode("&",$_SERVER['QUERY_STRING']);
      foreach($url_parameters as $url_parameter) {
        $split_parameter = explode("=",$url_parameter);
        if(($split_parameter[0] != "product_search") && ($split_parameter[0] != "search")) {
					if (isset($_GET['page_number']) || $split_parameter[0]=='page_id') {
						$output .= "  <input type='hidden' value='".$split_parameter[1]."' name='".$split_parameter[0]."' />\n\r";
					}
				}
			}
		} else {
			$output .= "<form action='".get_option('product_list_url')."' method='get' name='product_search' class='product_search'>\n\r";
		}
		//written by allen
		if (!isset($_GET['view_type'])){
			if(get_option('product_view')=='grid'){
				$_SESSION['customer_view'] = 'grid';
			} else {
				$_SESSION['customer_view'] = 'default';
			}
		} else {
			$_SESSION['customer_view'] = $_GET['view_type'];
		}
		$output .= "<div style='float:left;padding-top:2px; padding-right:10px;'>";
		$output .= "<div id='out_view_type' ><input type='hidden' id='view_type' name='view_type' value='".$_SESSION['customer_view']."'></div>";
		if (get_option('show_advanced_search')=='1') {
			if($_SESSION['customer_view'] =='grid'){
				$output .= "&nbsp;&nbsp;";

				
				$output .= "<a href='".add_query_arg('view_type', 'default', wpsc_this_page_url())."' id='out_default_pic'><img style='cursor:pointer;border:0px;' id='default_pic' src='".WPSC_URL."/".$images_dir."/default-off.gif'></a>";
				$output .= "  ";
				$output .= "<span id='out_grid_pic'><img id='grid_pic' style='border:0px;' src='".$siteurl."/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/grid-on.gif'></span>";
			} else {
				$output .= "&nbsp;&nbsp;";
				$output .= "<span  id='out_default_pic'><img id='default_pic' style='border:0px;' src='".$siteurl."/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/default-on.gif'></span>";
				$output .= "  ";
				$output .= "<a href='".add_query_arg('view_type', 'grid', wpsc_this_page_url())."' id='out_grid_pic'><img style='cursor:pointer;border:0px;' id='grid_pic' src='".WPSC_URL."/".$images_dir."/grid-off.gif'></a>";
			}
		}
		if (isset($_GET['order']) && $_GET['order']!=null) {
			$order = $_GET['order'];
		} else {
			$order = "ASC";
		}
		//$output.="<a style='cursor:pointer;' onclick='change_order(\"$order\")'>A</a>";
		
		$output.="</div>";
		$output.="<div style='float:left;'>";
		$output.="<div style='float:left;top:3px;'>Sort:&nbsp;</div> <div style='float:left;cursor:pointer;'>";
		$output .= '<select name="prod_order" style="margin:0px" onchange="if(this.value!=0){location.href=this.value;}" >';
		$output.="  <option value='". remove_query_arg('product_order') ."'>Select</option>\n\r";
		$output.="  <option ";
		if( isset($_GET['product_order']) && $_GET['product_order'] == 'ASC' )
			$output .= 'selected="selected"';
		$output.=" value='".add_query_arg('product_order', 'ASC', wpsc_this_page_url())."'>&raquo;&nbsp;Ascending</option>\n\r";
		$output.="  <option ";
		if( isset($_GET['product_order']) && $_GET['product_order'] == 'DESC' )
			$output .= 'selected="selected"';
		$output.=" value='".add_query_arg('product_order', 'DESC', wpsc_this_page_url())."'>&raquo;&nbsp;Descending</option>\n\r";
		$output.="</select>\n\r";
		
		$output.="</div>";
		$output.="</div>";
		
		if (isset($_GET['item_per_page'])){
			if ($_GET['item_per_page'] == 10){
				$selected1 = "selected = true";
			} else if($_GET['item_per_page'] == 20) {
				$selected2 = "selected = true";
			} else if($_GET['item_per_page'] == 50) {
				$selected3 = "selected = true";
			} else if($_GET['item_per_page'] == 0) {
				$selected4 = "selected = true";
			}
		}
		$output .= "<div style='float:left;'>";
		$output .= "<div style='float:left;top:3px;'>&nbsp;&nbsp;Show:&nbsp;&nbsp; </div>";
		$output .= '<select name="prod_per_page" style="margin:0px" onchange="if(this.value)location.href=this.value;" >';
		$output.="  <option value='". remove_query_arg('items_per_page') ."'>Select</option>\n\r";
		$output.="  <option ";
		if(isset($_GET['item_per_page']) && $_GET['items_per_page'] == '10' )
			$output .= 'selected="selected"';
		$output.=" value='".add_query_arg('items_per_page', '10', wpsc_this_page_url())."'>&raquo;&nbsp;10 per page</option>\n\r";
		$output.="  <option ";
		if(isset($_GET['item_per_page']) && $_GET['items_per_page'] == '20' )
			$output .= 'selected="selected"';
		$output.="  value='".add_query_arg('items_per_page', '20', wpsc_this_page_url())."'>&raquo;&nbsp;20 per page</option>\n\r";
		$output.="  <option ";
		if(isset($_GET['item_per_page']) && $_GET['items_per_page'] == '50' )
			$output .= 'selected="selected"';
		$output.="  value='".add_query_arg('items_per_page', '50', wpsc_this_page_url())."'>&raquo;&nbsp;50 per page</option>\n\r";
		$output.="  <option ";
		if(isset($_GET['item_per_page']) && $_GET['items_per_page'] == 'all' )
			$output .= 'selected="selected"';
		$output.="  value='".add_query_arg('items_per_page', 'all', wpsc_this_page_url())."'>&raquo;&nbsp;Show All</option>\n\r";
		$output.="</select>\n\r";
		
		$output .="</div>";
		if(isset($_GET['product_search']))
			$product_search = $_GET['product_search'];
		else
			$product_search = '';
		if(get_option('show_live_search') == 1) {
			$output .= "  <input type='text' value='".$product_search."' onkeyup='autocomplete(event)' autocomplete='off' name='product_search' class='wpsc_product_search wpsc_live_search' id='wpsc_search_autocomplete' />\n\r";
		} else {
			$output .= "  <input type='text' value='".$product_search."' name='product_search' class='wpsc_product_search' id='wpsc_search_autocomplete' />\n\r";
		}
		
		//$output .= "  <input type='submit' value='Search' name='product_search' class='submit' />\n\r";
		$output .= "</form>\n\r";
		$output .="<div class='blind_down'></div>"; //This div is for live searching, Please don't remove this line.
		$output .= "</div>";
		echo $output;
	}
  
  
  function product_display_list($product_list, $group_type, $group_sql = '', $search_sql = '')
    {
    global $wpdb;
    $siteurl = get_option('siteurl');
    
    if ( (float)WPSC_VERSION < 3.8 )
			$images_dir = 'images';
		else
			$images_dir = 'wpsc-core/images';
      
    if(get_option('permalink_structure') != '') {
      $seperator ="?";
		} else {
			$seperator ="&amp;";
		}
    
    $product_listing_data = wpsc_get_product_listing($product_list, $group_type, $group_sql, $search_sql);
    
    $product_list = $product_listing_data['product_list'];
    $output .= $product_listing_data['page_listing'];
		if($product_listing_data['category_id']) {
			$category_nice_name = $wpdb->get_var("SELECT `nice-name` FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `id` ='".(int)$product_listing_data['category_id']."' LIMIT 1");
		} else {
			$category_nice_name = '';
		}
      
    if($product_list != null) {
      $output .= "<table class='list_productdisplay $category_nice_name'>";
			$i=0;
      foreach($product_list as $product) {
	
        $num++;
				if ($i%2 == 1) {
					$output .= "    <tr class='product_view_{$product['id']}'>";
				} else {
					$output .= "    <tr class='product_view_{$product['id']}' style='background-color:#EEEEEE'>";
				}
				$i++;
				$output .= "      <td style='width: 9px;'>";
        if($product['description'] != null) {
          $output .= "<a href='#' class='additional_description_link' onclick='return show_additional_description(\"list_description_".$product['id']."\",\"link_icon".$product['id']."\");'>";
          $output .= "<img style='margin-top:3px;' id='link_icon".$product['id']."' src='$siteurl/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/icon_window_expand.gif' title='".$product['name']."' alt='".$product['name']."' />";
          $output .= "</a>";
				}
        $output .= "      </td>\n\r";
        $output .= "      <td width='55%'>";
        
        if($product['special'] == 1) {
          $special = "<strong class='special'>".TXT_WPSC_SPECIAL." - </strong>";
				} else {
					$special = "";
				}


        $output .= "<a href='".wpsc_product_url($product['id'])."' class='wpsc_product_title' ><strong>" . stripslashes($product['name']) . "</strong></a>";

        $output .= "      </td>";
        $variations_procesor = new nzshpcrt_variations;

        $variations_output = $variations_procesor->display_product_variations($product['id'],false, false, true);
        if($variations_output[1] !== null) {
          $product['price'] = $variations_output[1];
				}
				$output .= "      <td width='10px' style='text-align: center;'>";
        if(($product['quantity'] < 1) && ($product['quantity_limited'] == 1)) {
          $output .= "<img style='margin-top:5px;' src='$siteurl/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/no_stock.gif' title='No' alt='No' />";
				} else {
					$output .= "<img style='margin-top:4px;' src='$siteurl/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/yes_stock.gif' title='Yes' alt='Yes' />";
				}
        $output .= "      </td>";
        $output .= "      <td width='10%'>";
        if(($product['special']==1) && ($variations_output[1] === null)) {
          $output .= nzshpcrt_currency_display(($product['price'] - $product['special_price']), $product['notax'],false,$product['id']) . "<br />";
				} else {
					$output .= "<span id='product_price_".$product['id']."'>".nzshpcrt_currency_display($product['price'], $product['notax'])."</span>";
				}
        $output .= "      </td>";

        $output .= "      <td width='20%'>";
				if (get_option('addtocart_or_buynow') == '0'){
					$output .= "<form name='$num'  id='product_".$product['id']."'  method='POST' action='".get_option('product_list_url').$seperator."category=".$_GET['category']."' onsubmit='submitform(this);return false;' >";
				}
				if(get_option('list_view_quantity') == 1) {
					$output .= "<input type='text' name='quantity' value='1' size='3' maxlength='3'>&nbsp;";
				}
				$output .= $variations_output[0];
				$output .= "<input type='hidden' name='item' value='".$product['id']."' />";
				$output .= "<input type='hidden' name='prodid' value='".$product['id']."'>";
				if (get_option('wpsc_selected_theme')=='iShop') {
					if (get_option('addtocart_or_buynow') == '0') {
						if(($product['quantity_limited'] == 1) && ($product['quantity'] < 1)) {
							$output .= "<input disabled='true' type='submit' value='' name='Buy' class='wpsc_buy_button'/>";
						} else {
							$output .= "<input type='submit' name='Buy' value='' class='wpsc_buy_button'/>";
						}
					} else {
						if(!(($product['quantity_limited'] == 1) && ($product['quantity'] < 1))){
							$output .= google_buynow($product['id']);
						}
					}
				} else {
					if (get_option('addtocart_or_buynow') == '0') {
						if(($product['quantity_limited'] == 1) && ($product['quantity'] < 1)) {
							$output .= "<input disabled='true' type='submit' name='Buy' class='wpsc_buy_button'  value='".TXT_WPSC_ADDTOCART."'  />";
						} else {
							$output .= "<input type='submit' name='Buy' class='wpsc_buy_button'  value='".TXT_WPSC_ADDTOCART."'  />";
						}
					} else {
						if(!(($product['quantity_limited'] == 1) && ($product['quantity'] < 1))){
							$output .= google_buynow($product['id']);
						}
					}
				}
        $output .= "</form>";
        $output .= "      </td>\n\r";
        $output .= "    </tr>\n\r";
        
        $output .= "    <tr class='list_view_description'>\n\r";
        $output .= "      <td colspan='5'>\n\r";
        $output .= "        <div id='list_description_".$product['id']."'>\n\r";
        $output .= $product['description'];
        $output .= "        </div>\n\r";
        $output .= "      </td>\n\r";
        $output .= "    </tr>\n\r";
        
        }
      $output .= "</table>";
		} else {
			$output .= "<p>".TXT_WPSC_NOITEMSINTHIS." ".$group_type.".</p>";
		}
    return $output;
    }
  
  
  
    //written by allen
  function  gold_shpcrt_xmlmaker(){
		$keyword = $_POST['value'];
		header("Content-type: text/xml");
		$siteurl = get_option('siteurl');
		global $wpdb;
		$sql = "SELECT DISTINCT `".WPSC_TABLE_PRODUCT_LIST."`.* FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE `".WPSC_TABLE_PRODUCT_LIST."`.`active`='1' AND ".$wpdb->prefix."product_list.name LIKE '$keyword%'";
		$product_list = $wpdb->get_results($sql,ARRAY_A) ;
		echo "<?xml version='1.0'?>\n\r";
		//you can choose any name for the starting tag
		echo "<ajaxresponse>\n\r";
		if ($product_list != null) {
			foreach($product_list as $product)	{
				echo $product['image'];
				echo "<item>\n\r";
				echo "<text>\n\r";
				echo "&lt;a href='#' onClick='window.location=\"".$siteurl."/?page_id=3&amp;product_id=".$product['id']."\"'&gt;\n\r";
				echo "&lt;table cellspacing='2' border='0' class='products'&gt;\n\r";
				echo "&lt;tr&gt;\n\r";
				echo "&lt;td class='product_img' rowspan='2'&gt;\n\r";
				if($product['image']!=""){
					echo "&lt;img src='".WPSC_IMAGE_URL.$product['image']."' width='35' height='35' /&gt;\n\r";
				} else {
					echo "&lt;img src='./wp-content/plugins/".WPSC_DIR_NAME."/no-image-uploaded.gif' width='35' height='35'/&gt;\n\r";
				}
				echo "&lt;/td&gt;\n\r";
				echo "&lt;td width='5px' rowspan='2'&gt;\n\r";
				echo "&lt;/td&gt;\n\r";
				
				echo "&lt;td align='left'&gt;\n\r";
				echo "&lt;strong&gt;".$product['name']."&lt;/strong&gt;\n\r";
				echo "&lt;/td&gt;\n\r";
				echo "&lt;tr&gt;\n\r";
				echo "&lt;td&gt;\n\r";
				if (strlen($product['description'])>34){
					$product['description'] = substr($product['description'],0,33)."...";
				}
				echo $product['description'];
				echo "&lt;/td&gt;\n\r";
				echo "&lt;/tr&gt;\n\r";
				echo "&lt;/table&gt;\n\r";
				echo "&lt;/a&gt;";
				echo "</text>\n\r";
				
				echo "<value>\n\r";
				echo $product['name'];
				echo "</value>\n\r";
				echo "</item>";
			}
		}
		echo "</ajaxresponse>";
		exit();
	}
		//end of written by allen


function gold_shpcrt_add_gateways($nzshpcrt_gateways){
 global $gateway_checkout_form_fields;
  $num = count($nzshpcrt_gateways)+1;
  $gold_gateway_directory = dirname(__FILE__).'/merchants/';
  $gold_nzshpcrt_merchant_list = wpsc_gc_list_dir($gold_gateway_directory);
  foreach((array)$gold_nzshpcrt_merchant_list as $gold_nzshpcrt_merchant) {
    if(!is_dir($gold_gateway_directory.$gold_nzshpcrt_merchant)) {
      include_once($gold_gateway_directory.$gold_nzshpcrt_merchant);
    }
    $num++;
  }
  return $nzshpcrt_gateways;
 }
  
add_filter('wpsc_gateway_modules','gold_shpcrt_add_gateways');


if( defined('WPSC_MINOR_VERSION') && (int)WPSC_MINOR_VERSION < 55){
 global $gateway_checkout_form_fields;
  $num = count($nzshpcrt_gateways)+1;
  $gold_gateway_directory = dirname(__FILE__).'/merchants/';
  $gold_nzshpcrt_merchant_list = wpsc_gc_list_dir($gold_gateway_directory);
  foreach((array)$gold_nzshpcrt_merchant_list as $gold_nzshpcrt_merchant) {
    if(!is_dir($gold_gateway_directory.$gold_nzshpcrt_merchant)&&$gold_nzshpcrt_merchant != 'authorize.merchant.php') {
      include_once($gold_gateway_directory.$gold_nzshpcrt_merchant);
    }
    $num++;
  }

}    
	
	if(count((array)get_option('custom_gateway_options')) == 1) { 
	  // if there is only one active gateway, and it has form fields, append them to the end of the checkout form.
	  $active_gateway = implode('',(array)get_option('custom_gateway_options'));
	  if ( isset($gateway_checkout_form_fields) && (count((array)$gateway_checkout_form_fields) == 1) && ($gateway_checkout_form_fields[$active_gateway] != '')) {
			$gateway_checkout_form_field =  $gateway_checkout_form_fields[$active_gateway];	
		}
	}
  //exit("<pre>".print_r($gateway_checkout_form_field,true)."</pre>");
  
  if(file_exists(dirname(__FILE__).'/mp3_functions/mp3_functions.php')) {
    require_once(dirname(__FILE__).'/mp3_functions/mp3_functions.php');
	}
  
  if(file_exists(dirname(__FILE__).'/dropshop/drag_and_drop_cart.php')) {
    require_once(dirname(__FILE__).'/dropshop/drag_and_drop_cart.php');
	}
  
  if(file_exists(dirname(__FILE__).'/grid_display_functions.php')) {
    require_once(dirname(__FILE__).'/grid_display_functions.php');
	}
  
  if(file_exists(dirname(__FILE__).'/members/members.php')) {
   require_once(dirname(__FILE__).'/members/members.php');
	}

    
   if(file_exists(dirname(__FILE__).'/product_slider/product_slider.php')) {
    require_once(dirname(__FILE__).'/product_slider/product_slider.php');
	}
	
   if(file_exists(dirname(__FILE__).'/api_key_generator/api_key_generator.php')) {
    require_once(dirname(__FILE__).'/api_key_generator/api_key_generator.php');
	}
   
   /* re-added by dev.xiligroup 090701 */
   if(file_exists(dirname(__FILE__).'/touchShop/touchShopCore.php')) {
     require_once(dirname(__FILE__).'/touchShop/touchShopCore.php');
   }


  
  if(isset($_GET['activate']) && $_GET['activate'] == 'true') {
    add_action('init', 'gold_shpcrt_install');
	}
   
	if(get_option('show_search') == 1) {
		add_action('wpsc_top_of_products_page', 'gold_shpcrt_search_form');
	}
  add_action('init', 'wpsc_gold_shpcrt_ajax');
	//exit(get_option('show_live_search'));
//  add_action('init', 'gold_shpcrt_preview_image');
}

function wpsc_gc_list_dir($dirname) {
  /*
  lists the provided directory, was nzshpcrt_listdir
  */
  $dir = @opendir($dirname);
  $num = 0;
  while(($file = @readdir($dir)) !== false) {
    //filter out the dots and any backup files, dont be tempted to correct the "spelling mistake", its to filter out a previous spelling mistake.
    if(($file != "..") && ($file != ".") && !stristr($file, "~") && !stristr($file, "Chekcout") && !( strpos($file, ".") === 0 )) {
      $dirlist[$num] = $file;
      $num++;
    }
  }
  if($dirlist == null) {
  //  $dirlist[0] = "paypal.php";
  //  $dirlist[1] = "testmode.php";
  }
  return $dirlist; 
}

function wpsc_gc_setup_widgets(){
   include_once('widgets/widget_live_search.php');
}

add_action('widgets_init', 'wpsc_gc_setup_widgets');

function wpsc_grid_custom_styles() {
	$items_per_row = get_option( 'grid_number_per_row' );
	
	// roughly calculate the percentage, this will be corrected with JS later
	$percentage = floor( 100 / $items_per_row ) - 7;
	$percentage = apply_filters( 'wpsc_grid_view_column_width', $percentage, $items_per_row ); // themes can override this calculation
	?>
	<!-- Gold Cart Plugin custom styles -->
	<style type="text/css">
		.product_grid_display .product_grid_item {
			width:<?php echo $percentage; ?>%;
		}
		.item_image a {
			display: block;
			height: <?php echo get_option('product_image_height'); ?>px;
			width: <?php echo get_option('product_image_width'); ?>px;
		}
	</style>
	<!-- / Gold Cart Plugin custom styles -->
	<?php
}

if ( get_option( 'product_view' ) == 'grid' ) {
	add_action( 'wp_head', 'wpsc_grid_custom_styles', 9 );
}
?>