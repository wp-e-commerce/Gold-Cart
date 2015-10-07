<?php
/**
 * Plugin Name: Gold Cart for WP eCommerce
 * Plugin URI: http://wpecommerce.org
 * Description: Gold Cart extends your WP eCommerce store by enabling additional features and functionality, including views, galleries, store search and payment gateways. See also: <a href="http://wpecommerce.org" target="_blank">WPeCommerce.org</a> | <a href="https://wpecommerce.org/premium-support/" target="_blank">Premium Support</a> | <a href="http://docs.wpecommerce.org/" target="_blank">Documentation</a>
 * Version: 2.9.10
 * Author: WP eCommerce
 * Author URI: https://wpecommerce.org/store/premium-plugins/gold-cart/
 */

/**
 * This is the file that converts the normal shopping cart to the gold shopping cart
 */

// Define Constants
define( 'WPSC_GOLD_MODULE_PRESENT', true );
define( 'WPSC_GOLD_FILE_PATH', dirname( __FILE__ ) );
define( 'WPSC_GOLD_DIR_NAME', basename( WPSC_GOLD_FILE_PATH ) );
define( 'WPSC_GOLD_FILE_URL', get_plugin_url() );
define( 'WPSC_GOLD_VERSION', '2.9.10' );

//Check PHP version
if( ! version_compare( 5.3, PHP_VERSION, '<=' ) ) {
	gc_load_minimum_required_version_notice();
}

function gc_load_minimum_required_version_notice() {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		add_action( 'admin_notices', 'gc_php_version_notice' );
	}
}

function gc_php_version_notice() {
	echo '<div class="error">';
	echo '<p>' . __( 'Your version of PHP is below the minimum version of PHP required by Gold Cart. Please contact your host and request that your version be upgraded to 5.3 or later.', 'wpsc_gold_cart' ) . '</p>';
	echo '</div>';
}
//

/**
 * WP eCommerce Product Licensing validations
 * Will run the plugin update checker if license is registered
 */
if ( is_admin() ) {

	$licenses = get_option( 'wpec_license_active_products', array() );
	if ( ! empty( $licenses ) ) {
		foreach ( $licenses as $license ) {
			if ( in_array( '140', $license ) ) {
				
				// setup the updater
				require 'plugin-update/plugin-update-checker.php';
				$update_checker = PucFactory::buildUpdateChecker(
					'http://updates.wpecommerce.org/?action=get_metadata&slug='.dirname( plugin_basename( __FILE__ )), //Metadata URL.
					__FILE__,
					dirname( plugin_basename( __FILE__ ))
				);
				//Add the license key to query arguments.
				$update_checker->addQueryArgFilter(function($args) use ($license) {
					$args['license_key'] = $license['license'];
					return $args;
				});
			}
		}
	}
}


function _wpsc_action_gc_theme_engine_v2_notices() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'Gold Cart is deactivated because it is not currently compatible with Theme engine V2.', 'wpsc_gold_cart' ); ?></p>
	</div>
	<?php
}

add_action( 'wpsc_pre_init', '_wpsc_gc_init', 10 );
function _wpsc_gc_init() {
	// Don't do anything if theme engine v2 is activated
	if ( function_exists( '_wpsc_te2_register_component' ) ) {
		add_action( 'admin_notices', '_wpsc_action_gc_theme_engine_v2_notices' );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		unset( $_GET['activate'] );
		deactivate_plugins( array( __FILE__ ) );
		return;
	}

	// Add actions
	add_action( 'wp_enqueue_scripts', 'wpsc_gold_cart_scripts' );
	add_action( 'wp_print_styles', 'wpsc_gold_cart_styles' );
	add_action( 'init', 'wpsc_gc_view_mode' );
	add_action( 'init', 'wpsc_gold_cart_load_textdomain' );
	add_filter( 'wpsc_merchants_modules','gold_shpcrt_add_gateways' );
	add_action( 'widgets_init', 'wpsc_gc_setup_widgets' );

	// Check live search for init action
	if ( ! empty( $_REQUEST['wpsc_gc_action'] ) && $_REQUEST['wpsc_gc_action'] == 'live_search_embed' ) {
		add_action( 'init', 'wpsc_gc_live_search_embed', 10 );
	}

	// Show search if option is 1
	if ( get_option('show_search') == 1 ) {
		add_action( 'wpsc_top_of_products_page', 'gold_shpcrt_search_form' );
	}

	// Ajax on init
	add_action( 'init', 'wpsc_gold_shpcrt_ajax' );

}

/**
 * Scribu function to find proper plugin url
 */
function get_plugin_url() {
	// WP < 2.6
	if ( ! function_exists( 'plugins_url' ) ) {
		return get_option( 'siteurl' ) . '/wp-content/plugins/' . plugin_basename( dirname( __FILE__ ) );
	}
	return plugins_url( plugin_basename( dirname( __FILE__ ) ) );
}

/**
 * Default install gold cart kept to preserve backwards compatibility
 */
function gold_shpcrt_install() {
	global $wpdb, $user_level, $wp_rewrite, $pagenow;
}

// Load Languages
function wpsc_gold_cart_load_textdomain() {
	//load text domain
	load_plugin_textdomain( 'wpsc_gold_cart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * View mode.
 */
function wpsc_gc_view_mode() {
	global $wpsc_gc_view_mode;
	$wpsc_gc_view_mode = wpsc_check_display_type();

	if ( get_option( 'show_search' ) && get_option( 'show_advanced_search' ) ) {
		$meta = wpsc_get_customer_meta( 'display_type' );
		if ( ! empty( $meta ) )
			$wpsc_gc_view_mode = $meta;

		if ( ! empty( $_REQUEST['view_type'] ) && in_array( $_REQUEST['view_type'], array( 'list', 'grid', 'default' ) ) ) {
			$wpsc_gc_view_mode = $_REQUEST['view_type'];
			wpsc_update_customer_meta( 'display_type', $wpsc_gc_view_mode );
		} elseif ( empty( $wpsc_gc_view_mode ) ) {
			$wpsc_gc_view_mode = get_option( 'product_view', 'default' );
		}
	}

	if ( $wpsc_gc_view_mode == 'grid' ) {
		add_action( 'wp_head', 'wpsc_grid_custom_styles', 9 );
	}
}

/**
 * Allows us to properly display the list or grid template when being run in a shortcode.
 * among other contexts.
 * 
 * @param  string $display_type The current display type being passed.
 * @since 2.9.7.8
 * 
 * @return void
 */
function wpsc_gc_grid_view_override( $display_type ) {

    if ( 'grid' === $display_type ) {
        add_action( 'wpsc_top_of_products_page', 'wpsc_grid_custom_styles' );
        wp_enqueue_style( 'wpsc-gold-cart-grid-view' );
    }
}

add_action( 'wpsc_display_products_page', 'wpsc_gc_grid_view_override' );

/**
 * Grid custom styles
 */
function wpsc_grid_custom_styles() {
	$items_per_row = get_option( 'grid_number_per_row' );
	if ( $items_per_row ) {
		// roughly calculate the percentage, this will be corrected with JS later
		$percentage = floor( 100 / $items_per_row ) - 7;
		$percentage = apply_filters( 'wpsc_grid_view_column_width', $percentage, $items_per_row ); // themes can override this calculation
		?>
		<!-- Gold Cart Plugin custom styles 
		<style type="text/css">
			.product_grid_display .product_grid_item {
				width:<?php //echo $percentage; ?>%;
			}
			.product_grid_display .item_image a {
				display: block;
				height: <?php //echo get_option( 'product_image_height' ); ?>px;
				width: <?php //echo get_option( 'product_image_width' ); ?>px;
			}
		</style>
		Gold Cart Plugin custom styles -->
		<?php
	}
}

/**
 * Enqueue Gold Cart Styles
 */
function wpsc_gold_cart_styles() {
    global $wpsc_gc_view_mode;

    wp_enqueue_style( 'wpsc-gold-cart', get_plugin_url() . '/css/gold_cart.css' );
    wp_register_style( 'wpsc-gold-cart-grid-view', get_plugin_url() . '/css/grid_view.css', array( 'wpsc-gold-cart' ) );

    if ( $wpsc_gc_view_mode == 'grid' ) {
        wp_enqueue_style( 'wpsc-gold-cart-grid-view' );
    }
}

/**
 * Include necessary js and css files and dynamic JS
 */
function wpsc_gold_cart_scripts() {
	global $wpsc_gc_view_mode;

	$vars = array();

	if ( ! wp_script_is( 'jquery-query', 'registered' ) ) {
		wp_register_script( 'jquery-query', get_plugin_url() . '/js/jquery.query.js', array( 'jquery' ), '2.1.7' );
	}

	if ( get_option( 'show_gallery' ) && get_option( 'show_thumbnails_thickbox' ) ) {
		$lightbox =
				get_option( 'wpsc_lightbox', 'thickbox' ) == 'thickbox'
			? 'wpsc-thickbox'
			: 'wpsc_colorbox';

		if ( wp_script_is( 'wpsc-thickbox', 'registered' ) )
			$deps = $lightbox;
		else
			wp_enqueue_script( $lightbox );

		$vars['thickboxFix'] = true;
	}

	$deps = array( 'jquery', 'jquery-query' );

	if ( ( get_option( 'show_search' ) == 1 ) && ( get_option( 'show_live_search' ) == 1 ) ) {
		if ( (float)WPSC_VERSION < 3.8 ) {
			$siteurl = get_option( 'siteurl' );
			if ( is_ssl() ) {
				$siteurl = str_replace("http://", "https://", $siteurl);
			}
			$deps[] = 'wpsc-iautocompleter';
			wp_enqueue_script( 'wpsc-iautocompleter', "{$site_url}/wp-content/plugins/" . WPSC_DIR_NAME . '/js/iautocompleter.js', array( 'jquery' ) );
		}
	}

	wp_enqueue_script( 'wpsc-gold-cart', get_plugin_url() . '/js/gold_cart.js', $deps );

	$vars['displayMode'] = $wpsc_gc_view_mode;
	if ( $wpsc_gc_view_mode == 'grid' ){
		$vars['itemsPerRow'] = get_option( 'grid_number_per_row' );
	}

	$product_list_classes = array(
		'grid' 		=> apply_filters( 'wpsc_gc_product_grid_class', 'product_grid_display' ),
		'list' 		=> apply_filters( 'wpsc_gc_product_list_class', 'list_productdisplay' ),
		'default' => apply_filters( 'wpsc_gc_product_default_class', 'wpsc_default_product_list' ),
	);

	$vars['productListClass'] = $product_list_classes[$wpsc_gc_view_mode];

	wp_localize_script( 'wpsc-gold-cart', 'WPSC_GoldCart', $vars );
}

/**
 * Start Search Query
 */
function wpsc_gc_start_search_query() {
	global $wp_query, $wpsc_query;
	$product_page_id = wpsc_get_the_post_id_by_shortcode('[productspage]');
	$post = get_post( $product_page_id );
	$wp_query = new WP_Query( array( 'pagename' => $post->post_name ) );
	add_action( 'pre_get_posts', 'wpsc_gc_live_search_pre_get_posts' );
	wpsc_start_the_query();
	remove_action( 'pre_get_posts', 'wpsc_gc_live_search_pre_get_posts' );
	list( $wp_query, $wpsc_query ) = array( $wpsc_query, $wp_query ); // swap the wpsc_query object
	$GLOBALS['nzshpcrt_activateshpcrt'] = true;
}

/**
* Function to display search bar and execute search
*/
function wpsc_gold_shpcrt_ajax( $id ) {
	global $wpdb;

	if ( isset( $_POST ) && ! empty( $_POST ) )

	if ( isset( $_POST['wpsc_live_search'] ) && ( $_POST['wpsc_live_search']==true ) && ( get_option('show_live_search') == 1 || true == $_POST['wpsc_search_widget']) && !empty($_POST['product_search'] ) ) {
		$keyword = $_POST['product_search'];
		$output =  "<ul>";
		if ( version_compare( WPSC_VERSION, '3.8', '<' ) ) {
			$search_sql = gold_shpcrt_search_sql( $keyword );
			$product_list = $wpdb->get_results( "SELECT DISTINCT `".WPSC_TABLE_PRODUCT_LIST."`.* FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE `".WPSC_TABLE_PRODUCT_LIST."`.`active`='1' $search_sql ORDER BY `".WPSC_TABLE_PRODUCT_LIST."`.`name` ASC",ARRAY_A );
			if ( $product_list != null ) {
				foreach ( $product_list as $product ) {
					 //filter out the HTML, otherwise we get partial tags and everything breaks
					$product['description'] = wp_kses( $product['description'], false );
					// shorten the description;
					if ( strlen($product['description']) > 68 ) {
						$product_description = substr( $product['description'], 0, 68)."...";
					} else {
						$product_description = $product['description'];
					}
					//generate the HTML
					$output .= "<li>\n\r";
						$output .= "<a href='".wpsc_product_url( $product['id'] )."'>\n\r";
						if ( $product['image'] != '' ) {
							$output .= "<img class='live-search-image' src='index.php?productid=".$product['id']."&amp;width=50&amp;height=50'>\n\r";
						} else {
							$output .= "<img class='live-search-image' src='".get_option('siteurl')."/wp-content/plugins/".WPSC_DIR_NAME."/no-image-uploaded.gif' style='height: 50px; width: 50px;'>\n\r";
						}
						$output .= "<div class='live-search-text'>\n\r";
							$output .= "<strong>".$product['name']."</strong>\n\r";
							$output .= "<div class='description'>".stripslashes( $product_description )."</div>\n\r";
						$output .= "</div>\n\r";
						$output .= "<br clear='both' />\n\r";
						$output .= "</a>\n\r";
					$output .= "</li>\n\r";
				}
			}
		} else {
			wpsc_gc_start_search_query();
			echo '<ul>';
			while ( wpsc_have_products() ) {
				wpsc_the_product();
				?>
				<li>
					<a style="clear:both;" href="<?php echo wpsc_the_product_permalink(); ?>">
						<?php if ( wpsc_the_product_thumbnail() ): ?>
							<img class="live-search-image" alt="<?php echo wpsc_the_product_title(); ?>" src="<?php echo wpsc_the_product_thumbnail( 50, 50, 0, 'live-search' ); ?>" />
						<?php else: ?>
							<img class="live-search-image" alt="No Image" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo WPSC_CORE_THEME_URL; ?>wpsc-images/noimage.png" style="width:50px; height:50px;" />
						<?php endif ?>
						<div class="live-search-text">
							<strong><?php echo wpsc_the_product_title(); ?></strong>
							<div class="description">
								<?php echo wpsc_the_product_description(); ?>
							</div>
						</div>
					</a>
				</li>
				<?php
			}
			echo '</ul>';
			exit;
		}
		$output .= "</ul>";
		exit( $output );
	}

	if ( isset( $_POST['affiliate'] ) && $_POST['affiliate'] == true ) {
		if ( ! function_exists('affiliate_text') ) {
			function affiliate_text( $id, $user ) {
				$output = "<a target='_blank' title='".__( 'Your Shopping Cart', 'wpsc_gold_cart')."' href='".get_option( 'siteurl' )."/?action=affiliate&p=$id&user_id=".$user."&height=400&width=600' class='thickbox'><img src='".WPSC_URL."/".$images_dir."/buynow.jpg'></a>";
				return $output;
			}
		}

		$id = $_POST['prodid'];
		$product = $wpdb->get_row( "SELECT * FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE id='$id' LIMIT 1", ARRAY_A );
		$product = $product[0];
		$link = affiliate_text( $id, $_POST['uid'] );
		echo "<textarea class='affiliate_text' onclick='this.select();' >$link</textarea>";
		exit();
	}

	if ( isset( $_POST['log_affiliate'] ) && $_POST['log_affiliate'] == true ) {
		$uid = $_POST['uid'];
		$amount = $_POST['amount'];
		$product = $wpdb->query( "UPDATE {$wpdb->prefix}wpsc_affiliates SET paid=paid+$amount  WHERE user_id='$uid'" );
		echo "uid=".$uid;
		exit();
	}
}

/**
* Function to display additional images in the image gallery
*/
function gold_shpcrt_display_gallery( $product_id, $invisible = false ) {
	global $wpdb;
	$output ='';
	$siteurl = get_option( 'siteurl' );
	/* No GD? No gallery.	 No gallery option? No gallery.	 Range variable set?  Apparently, no gallery. */
	if ( get_option( 'show_gallery' ) == 1 && ! isset( $_GET['range'] ) && function_exists( "getimagesize" ) ) {
		if ( version_compare( WPSC_VERSION, '3.8', '<' ) ) {
			/* get data about the base product image */
			$product = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE `id`='".$product_id."' LIMIT 1",ARRAY_A);
			$image_link = WPSC_IMAGE_URL.$product['image']."";
			$image_file_name = $product['image'];
			$imagepath = WPSC_THUMBNAIL_DIR.$image_file_name;
			$base_image_size = @getimagesize($imagepath);

			/* get data about the extra product images */
			$images = $wpdb->get_results("SELECT * FROM `".WPSC_TABLE_PRODUCT_IMAGES."` WHERE `product_id` = '$product_id' AND `id` NOT IN('$image_file_name')	ORDER BY `image_order` ASC",ARRAY_A);
			$output = "";
			$new_height = get_option( 'wpsc_gallery_image_height' );
			$new_width = get_option( 'wpsc_gallery_image_width' );
			if ( count( $images ) > 0 ) {
				/* display gallery */
				if ( $invisible == true ) {
					foreach($images as $image) {
						$extra_imagepath = WPSC_IMAGE_DIR.$image['image']."";
						$extra_image_size = @getimagesize($extra_imagepath);
						$thickbox_link = WPSC_IMAGE_URL.$image['image']."";
						$image_link = "index.php?image_id=".$image['id']."&amp;width=".$new_width."&amp;height=".$new_height."";
						$output .= "<a href='".$thickbox_link."' class='thickbox hidden_gallery_link'  rel='".str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), $product['name'])."' rev='$image_link'>&nbsp;</a>";
					}
				} else {
					$output .= "<h2 class='prodtitles'>".__( 'Gallery', 'wpsc_gold_cart' )."</h2>";
					$output .= "<div class='wpcart_gallery'>";
					if ( $images != null ) {
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
		} else {
			$output = '';
			$product_name = get_the_title( $product_id );
			$output .= "<div class='wpcart_gallery'>";

			if ( function_exists( 'wpsc_get_product_gallery' ) ) {
				$attachments = wpsc_get_product_gallery( $product_id );
			} else {
				$args = array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'numberposts'    => -1,
					'post_parent'    => $product_id,
				);

				$attachments = get_posts( $args );
			}

			$featured_img = get_post_thumbnail_id( $product_id );
			$thumbnails = array();
			if ( count( $attachments ) > 1 ) {
				foreach ( $attachments as $post ) {
					setup_postdata( $post );
					$link = wp_get_attachment_link( $post->ID, 'gold-thumbnails' );
					$size = is_single() ? 'medium-single-product' : 'product-thumbnails';
					$preview_link = wp_get_attachment_image_src( $post->ID, $size);
					$link = str_replace( 'a href' , 'a rev="' . $preview_link[0] . '" class="thickbox" rel="' . $product_name . '" href' , $link );

					// always display the featured thumbnail first
					if ( $post->ID == $featured_img ) {
						array_unshift( $thumbnails, $link );
					} else {
						$thumbnails[] = $link;
					}
				}
			}
			$output .= implode( "\n", $thumbnails );
			$output .= "</div>";
			wp_reset_postdata();
		}	//closes if > 3.8 condition
	} //closes if gallery setting condition
	return $output;
}

/**
* Generated the sql statement used to search for products
*/
function gold_shpcrt_search_sql( $search_string = '' ) {
	global $wpdb;

	if ( (float)WPSC_VERSION < 3.8 ) {
			$images_dir = 'images';
		} else {
			$images_dir = 'wpsc-core/images';
		}

	$output = "";

	if ( $search_string == '' ) {
		$search_string = $_GET['product_search'];
	}

	if ( $search_string != '' ) {
		$brand_sql = '';
		$category_sql = '';
		$search_string_title = "%".$wpdb->escape(stripslashes($search_string))."%";
		$search_string_description = "%".$wpdb->escape(stripslashes($search_string))."%";
		$category_list = $wpdb->get_col("SELECT `id` FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `name` LIKE '".$search_string_title."'");
		$meta_list = $wpdb->get_col("SELECT DISTINCT `product_id` FROM `".WPSC_TABLE_PRODUCTMETA."` WHERE `meta_value` REGEXP '".$wpdb->escape(stripslashes($search_string))."' AND `custom` IN ('1')");
		if ( $category_list != null ) {
				$category_assoc_list = $wpdb->get_col("SELECT DISTINCT `product_id` FROM `".WPSC_TABLE_ITEM_CATEGORY_ASSOC."` WHERE `category_id` IN ('".implode("', '", $category_list)."')");
				$category_sql = "OR `".WPSC_TABLE_PRODUCT_LIST."`.`id` IN ('".implode("', '", $category_assoc_list)."')";
		}
		// this cannot currently list products that are associated with no categories
		$output = "AND (`".WPSC_TABLE_PRODUCT_LIST."`.`name` LIKE '".$search_string_title."' OR `".WPSC_TABLE_PRODUCT_LIST."`.`description` LIKE '".$search_string_description."' OR `".WPSC_TABLE_PRODUCT_LIST."`.`id` IN ('".implode("','",$meta_list)."') OR `".WPSC_TABLE_PRODUCT_LIST."`.`additional_description` LIKE '".$search_string_description."' $category_sql )";
		//echo $output;
	}
	return $output;
}

/**
 * Function to display search box
 */
function gold_shpcrt_search_form(){
	global $wpsc_gc_view_mode;
	// don't display search form when we're viewing single products
	if ( is_single() ) {
		$post = get_queried_object();
		if ( ! empty( $post->post_type ) && $post->post_type == 'wpsc-product' ){
			return;
		}
	}

	if ( (float) WPSC_VERSION < 3.8 ) {
		$images_dir = 'images';
	} else {
		$images_dir = 'wpsc-core/images';
	}

	$siteurl = get_option( 'siteurl' );
	$output = '';

	if ( get_option( 'permalink_structure' ) != '' ) {
		$seperator ="?";
	} else {
		$seperator ="&amp;";
	}

	wp_parse_str( $_SERVER['QUERY_STRING'], $params );
	$params = array_diff_key( $params, array( 'product_search' => '', 'search' => '' ) );
	$params = array_intersect_key( $params, array( 'page_number' => '', 'page_id' => '' ) );

	$_SERVER['REQUEST_URI'] = esc_url( remove_query_arg( 'view_type' ) );
	$show_advanced_search = get_option( 'show_advanced_search' ) == '1';
	$show_live_search = get_option( 'show_live_search' ) == 1;
	$customer_view = $wpsc_gc_view_mode;
	$order = empty( $_GET['product_order'] ) ? '' : $_GET['product_order'];
	$item_per_page_options = array(
		'10' 	=> esc_html__( '10 per page', 'wpsc_gold_cart' ),
		'20' 	=> esc_html__( '20 per page', 'wpsc_gold_cart' ),
		'50' 	=> esc_html__( '50 per page', 'wpsc_gold_cart' ),
		'all' => esc_html__( 'Show All', 'wpsc_gold_cart' ),
	);
	$selected_item_per_page = empty( $_GET['items_per_page'] ) ? '' : $_GET['items_per_page'];
	$product_search = isset( $_GET['product_search'] ) ? $_GET['product_search'] : '';
	$search_box_classes = array( 'wpsc_product_search' );

	if ( $show_live_search ) {
		$embed_results = (bool) get_option( 'embed_live_search_results', '0' ) && version_compare( WPSC_VERSION, '3.8', '>=' );
		$search_box_classes[] = $embed_results ? 'wpsc_live_search_embed' : 'wpsc_live_search';
	}
	$search_box_classes = implode( ' ', $search_box_classes );
	?>
	<div class='wpsc_product_search' id="wpsc-main-search">
		<form action="<?php echo esc_url( get_option( 'product_list_url' ) ); ?>" method="GET" class="product_search">
			<?php if ( ! empty( $params ) ): ?>
				<?php foreach ( $params as $key => $value ) : ?>
					<input type="hidden" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $key ); ?>" />
				<?php endforeach ?>
			<?php endif ?>
			<div class="wpsc-products-view-mode">
				<input type='hidden' id='view_type' name='view_type' value='<?php echo esc_attr( $customer_view ); ?>'>
				<?php if ( $show_advanced_search ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'view_type', 'default' ) ); ?>" class="default<?php echo $customer_view == 'default' ? ' active' : ''; ?>" title="<?php _e( 'Default View', 'wpsc_gold_cart' ); ?>"><?php _e( 'Default', 'wpsc_gold_cart' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'view_type', 'list' ) ); ?>" class="list<?php echo $customer_view == 'list' ? ' active' : ''; ?>" title="<?php _e( 'List View', 'wpsc_gold_cart' ); ?>"><?php _e( 'List', 'wpsc_gold_cart' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'view_type', 'grid' ) ); ?>" class="grid<?php echo $customer_view == 'grid' ? ' active' : ''; ?>" title="<?php _e( 'Grid View', 'wpsc_gold_cart' ); ?>"><?php _e( 'Grid', 'wpsc_gold_cart' ); ?></a>
				<?php endif ?>
			</div>
			<div class="wpsc-products-sort">
				<span><?php _e( 'Sort:', 'wpsc_gold_cart' ); ?></span>
				<select name="product_order">
					<option value=""><?php _e( 'Select', 'wpsc_gold_cart' ); ?></option>
					<option value="ASC"<?php echo $order == 'ASC' ? ' selected="selected"' : ''; ?>><?php _e( 'Ascending', 'wpsc_gold_cart' ); ?></option>
					<option value="DESC"<?php echo $order == 'DESC' ? ' selected="selected"' : ''; ?>><?php _e( 'Descending', 'wpsc_gold_cart' ); ?></option>
				</select>
			</div>
			<div class="wpsc-products-per-page">
				<span><?php _e( 'Show:', 'wpsc_gold_cart' ); ?></span>
				<select name="items_per_page">
					<option value=""><?php _e( 'Select', 'wpsc_gold_cart' ); ?></option>
					<?php
					foreach ( $item_per_page_options as $value => $title ) {
						$selected = $selected_item_per_page == $value ? ' selected="selected"' : '';
						echo "<option{$selected} value='" . esc_attr( $value ) . "'>{$title}</option>";
					}
					?>
				</select>
			</div>
			<input type="text" id="wpsc_search_autocomplete" name="product_search" value="<?php echo esc_attr( $product_search ); ?>" class="<?php echo esc_attr( $search_box_classes ); ?>" />
		</form>
	<?php if ( empty( $embed_results ) ) : ?>
		<div class="blind_down"></div>
	<?php endif ?>
	</div>
	<?php
}

/**
 * Product Display List
 */
function product_display_list( $product_list, $group_type, $group_sql = '', $search_sql = '' ) {

	global $wpdb;
	$siteurl = get_option( 'siteurl' );

	if ( (float)WPSC_VERSION < 3.8 ) {
			$images_dir = 'images';
		} else {
			$images_dir = 'wpsc-core/images';
		}

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

	if ( $product_list != null ) {

		$output .= "<table class='list_productdisplay $category_nice_name'>";
			$i=0;

		foreach ( $product_list as $product ) {

		$num++;
				if ( $i%2 == 1 ) {
					$output .= "<tr class='product_view_{$product['id']}'>";
				} else {
					$output .= "<tr class='product_view_{$product['id']}' style='background-color:#EEEEEE'>";
				}

				$i++;

				$output .= "<td style='width: 9px;'>";

		if ( $product['description'] != null ) {
			$output .= "<a href='#' class='additional_description_link' onclick='return show_additional_description(\"list_description_".$product['id']."\",\"link_icon".$product['id']."\");'>";
			$output .= "<img style='margin-top:3px;' id='link_icon".$product['id']."' src='$siteurl/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/icon_window_expand.gif' title='".$product['name']."' alt='".$product['name']."' />";
			$output .= "</a>";
				}
		$output .= "</td>\n\r";
		$output .= "<td width='55%'>";

		if ( $product['special'] == 1 ) {
			$special = "<strong class='special'>".TXT_WPSC_SPECIAL." - </strong>";
				} else {
					$special = "";
				}

		$output .= "<a href='".wpsc_product_url($product['id'])."' class='wpsc_product_title' ><strong>" . stripslashes($product['name']) . "</strong></a>";

		$output .= "</td>";
		$variations_procesor = new nzshpcrt_variations;

		$variations_output = $variations_procesor->display_product_variations($product['id'],false, false, true);

		if ( $variations_output[1] !== null ) {
			$product['price'] = $variations_output[1];
				}

				$output .= "<td width='10px' style='text-align: center;'>";

		if ( ( $product['quantity'] < 1 ) && ( $product['quantity_limited'] == 1 ) ) {
			$output .= "<img style='margin-top:5px;' src='$siteurl/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/no_stock.gif' title='No' alt='No' />";
				} else {
					$output .= "<img style='margin-top:4px;' src='$siteurl/wp-content/plugins/".WPSC_DIR_NAME."/".$images_dir."/yes_stock.gif' title='Yes' alt='Yes' />";
				}

		$output .= "</td>";
		$output .= "<td width='10%'>";

		if ( ( $product['special'] == 1 ) && ( $variations_output[1] === null ) ) {
			$output .= nzshpcrt_currency_display( ( $product['price'] - $product['special_price'] ), $product['notax'],false,$product['id'] ) . "<br />";
				} else {
					$output .= "<span id='product_price_".$product['id']."'>".nzshpcrt_currency_display( $product['price'], $product['notax'] )."</span>";
				}
		$output .= "</td>";

		$output .= "<td width='20%'>";

				if ( get_option('addtocart_or_buynow') == '0' ) {
					$output .= "<form name='$num'  id='product_".$product['id']."'  method='POST' action='".get_option( 'product_list_url' ).$seperator."category=".$_GET['category']."' onsubmit='submitform(this);return false;' >";
				}

				if ( get_option( 'list_view_quantity' ) == 1 ) {
					$output .= "<input type='text' name='quantity' value='1' size='3' maxlength='3'>&nbsp;";
				}

				$output .= $variations_output[0];
				$output .= "<input type='hidden' name='item' value='".$product['id']."' />";
				$output .= "<input type='hidden' name='prodid' value='".$product['id']."'>";

				if ( get_option('wpsc_selected_theme')=='iShop' ) {
					if ( get_option('addtocart_or_buynow') == '0' ) {
						if ( ( $product['quantity_limited'] == 1 ) && ( $product['quantity'] < 1 ) ) {
							$output .= "<input disabled='true' type='submit' value='' name='Buy' class='wpsc_buy_button'/>";
						} else {
							$output .= "<input type='submit' name='Buy' value='' class='wpsc_buy_button'/>";
						}
					} else {
						if ( ! ( ( $product['quantity_limited'] == 1 ) && ( $product['quantity'] < 1 ) ) ) {
							$output .= google_buynow($product['id']);
						}
					}
				} else {
					if ( get_option( 'addtocart_or_buynow' ) == '0' ) {
						if ( ( $product['quantity_limited'] == 1 ) && ( $product['quantity'] < 1 ) ) {
							$output .= "<input disabled='true' type='submit' name='Buy' class='wpsc_buy_button'  value='".TXT_WPSC_ADDTOCART."'  />";
						} else {
							$output .= "<input type='submit' name='Buy' class='wpsc_buy_button'  value='".TXT_WPSC_ADDTOCART."'  />";
						}
					} else {
						if ( ! ( ( $product['quantity_limited'] == 1 ) && ( $product['quantity'] < 1 ) ) ) {
							$output .= google_buynow($product['id']);
						}
					}
				}

		$output .= "</form>";
		$output .= "</td>\n\r";
		$output .= "</tr>\n\r";

		$output .= "<tr class='list_view_description'>\n\r";
		$output .= "<td colspan='5'>\n\r";
		$output .= "<div id='list_description_".$product['id']."'>\n\r";
		$output .= $product['description'];
		$output .= "</div>\n\r";
		$output .= "</td>\n\r";
		$output .= "</tr>\n\r";

		 }

		$output .= "</table>";

		} else {
			$output .= "<p>".TXT_WPSC_NOITEMSINTHIS." ".$group_type.".</p>";
		}

	return $output;
}

/**
* XML Maker
*
* @author allen
*/
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
	if ( $product_list != null ) {
		foreach ( $product_list as $product ) {
			echo $product['image'];
			echo "<item>\n\r";
			echo "<text>\n\r";
			echo "&lt;a href='#' onClick='window.location=\"".$siteurl."/?page_id=3&amp;product_id=".$product['id']."\"'&gt;\n\r";
			echo "&lt;table cellspacing='2' border='0' class='products'&gt;\n\r";
			echo "&lt;tr&gt;\n\r";
			echo "&lt;td class='product_img' rowspan='2'&gt;\n\r";
			if ( $product['image'] != "" ) {
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
			if ( strlen($product['description'] ) > 34 ) {
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

/**
 * Add Gold Cart Gateways
 */
function gold_shpcrt_add_gateways( $nzshpcrt_gateways ) {
	global $gateway_checkout_form_fields;
	$num = count( $nzshpcrt_gateways ) + 1;
	$gold_gateway_directory = dirname( __FILE__ ).'/merchants/';
	$gold_nzshpcrt_merchant_list = wpsc_gc_list_dir( $gold_gateway_directory );
	foreach ( (array)$gold_nzshpcrt_merchant_list as $gold_nzshpcrt_merchant ) {
	if ( ! is_dir( $gold_gateway_directory.$gold_nzshpcrt_merchant ) ) {
		include_once( $gold_gateway_directory.$gold_nzshpcrt_merchant );
	}
	$num++;
	}
	return $nzshpcrt_gateways;
}

// Require mp3 functions file.
if ( file_exists( dirname( __FILE__ ) . '/mp3_functions/mp3_functions.php' ) ) {
	require_once( dirname( __FILE__ ) . '/mp3_functions/mp3_functions.php' );
}

// Require drag and drop cart file.
if ( file_exists( dirname( __FILE__ ) . '/dropshop/drag_and_drop_cart.php' ) ) {
	require_once( dirname( __FILE__ ) . '/dropshop/drag_and_drop_cart.php' );
}

// Require display grid functions.
if ( file_exists( dirname( __FILE__ ) . '/grid_display_functions.php' ) ) {
	require_once( dirname( __FILE__ ) . '/grid_display_functions.php' );
}

// Require members file.
if ( file_exists( dirname( __FILE__ ) . '/members/members.php' ) ) {
	require_once( dirname( __FILE__ ) . '/members/members.php' );
}

// Require product slider file.
if ( file_exists( dirname( __FILE__ ) . '/product_slider/product_slider.php' ) ) {
	require_once( dirname( __FILE__ ) . '/product_slider/product_slider.php' );
}

// Require api key generator.
if ( file_exists( dirname( __FILE__ ) . '/api_key_generator/api_key_generator.php' ) ) {
	require_once( dirname( __FILE__ ) . '/api_key_generator/api_key_generator.php' );
}

// Require touch shop core file. Re-added by dev.xiligroup 090701
if ( file_exists( dirname( __FILE__ ) . '/touchShop/touchShopCore.php' ) ) {
	require_once( dirname( __FILE__ ) . '/touchShop/touchShopCore.php' );
}

// If activate variable exists and is true, run init
if ( isset( $_GET['activate'] ) && $_GET['activate'] == 'true' ) {
	add_action( 'init', 'gold_shpcrt_install' );
}

/**
 * Live search query modification.
 */
function wpsc_gc_live_search_pre_get_posts( &$q ) {
	if ( ! empty( $q->query_vars['post_type'] ) && $q->query_vars['post_type'] = 'wpsc-product' && ! empty( $q->query_vars['post_status'] ) ){
		$q->query_vars['s'] = $_REQUEST['product_search'];
	}
	return true;
}

/**
 * Live Search emebed
 */
function wpsc_gc_live_search_embed() {
	global $wpsc_gc_view_mode;
	wpsc_gc_start_search_query();
	wpsc_include_products_page_template($wpsc_gc_view_mode);
	exit;
}

/**
 * Register Template Redirect
 */
function wpsc_gc_register_template_redirect() {
	add_action( 'pre_get_posts', 'wpsc_gc_live_search_pre_get_posts' );
}

/**
 * De-register Template Redirect
 */
function wpsc_gc_deregister_template_redirect() {
	remove_action( 'pre_get_posts', 'wpsc_gc_live_search_pre_get_posts' );
}

// Check product search action.
if ( ! empty( $_REQUEST['product_search'] ) ) {
	add_action( 'template_redirect', 'wpsc_gc_register_template_redirect', 7 );
	add_action( 'template_redirect', 'wpsc_gc_deregister_template_redirect', 9 ); // don't want to mess up other queries
}


/**
 * List Directory
 */
function wpsc_gc_list_dir($dirname) {
	// lists the provided directory, was nzshpcrt_listdir
	$dir = @opendir( $dirname );
	$num = 0;
	while ( ( $file = @readdir( $dir ) ) !== false ) {
	//filter out the dots and any backup files, dont be tempted to correct the "spelling mistake", its to filter out a previous spelling mistake.
	if ( ( $file != ".." ) && ( $file != "." ) && ! stristr( $file, "~" ) && ! stristr( $file, "Chekcout" ) && ! ( strpos( $file, "." ) === 0 ) ) {
		$dirlist[$num] = $file;
		$num++;
	}
	}
	if ( $dirlist == null ) {
	//  $dirlist[0] = "paypal.php";
	//  $dirlist[1] = "testmode.php";
	}
	return $dirlist;
}

/**
 * Setup Widgets
 */
function wpsc_gc_setup_widgets(){
	include_once( 'widgets/widget_live_search.php' );
}