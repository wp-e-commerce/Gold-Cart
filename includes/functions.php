<?php
if( is_admin() ) {

	/* Start of: WordPress Administration */

	// All this does is sit here so that it can be detected by the gold files to turn grid view on.
	function wpsc_gc_product_display_grid( $product_list, $group_type, $group_sql = '', $search_sql = '' ) {

		global $wpdb;

	}

	function wpsc_gc_get_changelog() {

		$output = array();
		$readme_contents = file( WPSC_GOLD_FILE_PATH . '/readme.txt' );
		if( $readme_contents ) {
			$size = count( $readme_contents );
			$key = false;
			for( $i = 0; $i < $size; $i++ ) {
				if( strpos( $readme_contents[$i], '== Changelog ==' ) !== FALSE ) {
					$key = $i;
					unset( $readme_contents[$i], $readme_contents[$i+1], $readme_contents[$i+2] );
					$i = $i+2;
				} else if( !empty( $key ) ) {
					if( $readme_contents[$i] == "\n" ) {
						$i = $size;
						break;
					} else {
						$output[] = $readme_contents[$i];
					}
				} else if( empty( $key ) ) {
					unset( $readme_contents[$i] );
				}
			}
		}
		return $output;

	}

	function wpsc_gc_about_screen_html() {

		$major_version = WPSC_GOLD_VERSION;
		$plugin_status = 'installing';
		$changelog = wpsc_gc_get_changelog();
		$release_notes = 'https://wpecommerce.org/blog/'; ?>
		<div class="wrap about-wrap">

			<h1><?php printf( __( 'Welcome to Gold Cart %s', 'wpsc_gold_cart' ), $major_version ); ?></h1>
			<div class="about-text gc-about-text">
				<p><?php printf( __( '<strong>Thanks for installing</strong>! Gold Cart %s opens up new functionality within your WP eCommerce store. We hope you enjoy it.', 'wpsc_gold_cart' ), $major_version ); ?></p>
				<p><?php printf( __( '<strong>Remember,</strong> to take advantage of automatic plugin updates you need to have an active License and register it under Dashboard -> WPeC Licensing. Read more on our <a href="%s" target="_blank">blog</a>.', 'wpsc_gold_cart' ), $release_notes ); ?></p>
			</div>
			<div class="wp-badge wpsc-badge" style="background:url( '<?php echo wpsc_gc_get_plugin_url(); ?>/images/gold-cart.png' ) left top no-repeat !important;"><?php printf( __( 'Version %s', 'wpsc_gold_cart' ), $major_version ); ?></div>

			<div class="changelog">
				<h3><?php printf( __( 'What\'s New in %s', 'wpsc_gold_cart' ), $major_version ); ?></h3>
		<?php if( $changelog ) { ?>
				<ul class="changelog-list">
			<?php foreach( $changelog as $line ) { ?>
					<li><code><?php echo $line; ?></code></li>
			<?php } ?>
				</ul>
				<p><?php printf( __( 'For more information, see	<a href="%s" target="_blank">the release notes</a>.', 'wpsc_gold_cart' ), $release_notes ); ?></p>
		<?php } ?>
			</div>

			<!-- .changelog -->

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpsc-settings' ), 'options-general.php' ) ) ); ?>"><?php _e( 'Go to Store Settings', 'wpsc_gold_cart' ); ?></a>
			</div>
			<!-- .return-to-dashboard -->

		</div>
		<!-- .about-wrap -->
<?php
	}

	function wpsc_gc_get_right_now_widget() {

		global $wpdb;

		// Products
		$products = 0;
		$post_type = 'wpsc-product';
		$count = wp_count_posts( $post_type );
		if( $count ) {
			if( is_object( $count ) ) {
				unset( $count->inherit );
				foreach( $count as $value )
					$products += $value;
			}
			unset( $count );
			$products = number_format( $products );
		}

		// Variations
		$variations = 0;
		$post_type = 'wpsc-variation';
		$count = wp_count_posts( $post_type );
		if( $count ) {
			if( is_object( $count ) ) {
				foreach( $count as $value )
					$variations += $value;
			}
			unset( $count );
			$variations = number_format( $variations );
		}

		// Categories
		$categories = 0;
		$term_taxonomy = 'wpsc_product_category';
		$categories = number_format( wp_count_terms( $term_taxonomy ) );

		// Tags
		$tags = 0;
		$term_taxonomy = 'product_tag';
		$tags = number_format( wp_count_terms( $term_taxonomy ) );

		// Coupons
		$coupons = 0;
		$count_sql = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "wpsc_coupon_codes`";
		$coupons = number_format( $wpdb->get_var( $count_sql ) );

		// Attributes
		if( function_exists( 'wpsc_cf_check_options_exist' ) ) {
			$attributes = 0;
			$attributes = get_option( 'wpsc_cf_data' );
			if( $attributes )
				$attributes = count( maybe_unserialize( $attributes ) );
		}

		// Sales
		$count_sql = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "wpsc_purchase_logs`";
		$sales_overall = number_format( $wpdb->get_var( $count_sql ) );

		$count_sql = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "wpsc_purchase_logs` WHERE processed IN ('3','4','5')";
		$sales_approved = number_format( $wpdb->get_var( $count_sql ) );

		$count_sql = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "wpsc_purchase_logs` WHERE processed IN ('2')";
		$sales_pending = number_format( $wpdb->get_var( $count_sql ) );

		$count_sql = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "wpsc_purchase_logs` WHERE processed IN ('6')";
		$sales_declined = number_format( $wpdb->get_var( $count_sql ) );

		if( function_exists( 'wpsc_pp_purchlog_statuses' ) ) {
			$count_sql = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "wpsc_purchase_logs` WHERE processed IN ('7')";
			$sales_refunded = number_format( $wpdb->get_var( $count_sql ) );
		} ?>
		<div class="table table_content">
			<p class="sub"><?php _e( 'Catalogue', 'wpsc_st' ); ?></p>
			<table>
				<tr class="first">
					<td class="first b b-posts"><a href="edit.php?post_type=wpsc-product"><?php echo $products; ?></a></td>
					<td class="t posts"><a href="edit.php?post_type=wpsc-product"><?php _e( 'Products', 'wpsc_st' ); ?></a></td>
				</tr>
				<tr>
					<td class="first b b_pages"><a href="edit-tags.php?taxonomy=wpsc-variation&post_type=wpsc-product"><?php echo $variations; ?></a></td>
					<td class="t pages"><a href="edit-tags.php?taxonomy=wpsc-variation&post_type=wpsc-product"><?php _e( 'Variations', 'wpsc_st' ); ?></a></td>
				</tr>
				<tr>
					<td class="first b b_pages"><a href="edit-tags.php?taxonomy=wpsc_product_category&post_type=wpsc-product"><?php echo $categories; ?></a></td>
					<td class="t pages"><a href="edit-tags.php?taxonomy=wpsc_product_category&post_type=wpsc-product"><?php _e( 'Categories', 'wpsc_st' ); ?></a></td>
				</tr>
				<tr>
					<td class="first b b-cats"><a href="edit-tags.php?taxonomy=product_tag&post_type=wpsc-product"><?php echo $tags; ?></a></td>
					<td class="t cats"><a href="edit-tags.php?taxonomy=product_tag&post_type=wpsc-product"><?php _e( 'Tags', 'wpsc_st' ); ?></a></td>
				</tr>
		<?php if( isset( $attributes ) ) { ?>
				<tr>
					<td class="first b b-tags"><a href="edit.php?post_type=wpsc-product&page=wpsc_cf"><?php echo $attributes; ?></a></td>
					<td class="t tags"><a href="edit.php?post_type=wpsc-product&page=wpsc_cf"><?php _e( 'Attributes', 'wpsc_st' ); ?></a></td>
				</tr>
		<?php } ?>
				<tr>
					<td class="first b b-tags"><a href="edit.php?post_type=wpsc-product&page=wpsc-edit-coupons"><?php echo $coupons; ?></a></td>
					<td class="t tags"><a href="edit.php?post_type=wpsc-product&page=wpsc-edit-coupons"><?php _e( 'Coupons', 'wpsc_st' ); ?></a></td>
				</tr>
			</table>
		</div>
		<div class="table table_discussion">
			<p class="sub"><?php _e( 'Sales', 'wpsc_st' ); ?></p>
			<table>

				<tr class="first">
					<td class="b b-comments"><a href="index.php?page=wpsc-purchase-logs"><span class="total-count"><?php echo $sales_overall; ?></span></a></td>
					<td class="last t comments"><a href="index.php?page=wpsc-purchase-logs"><?php _e( 'Sales', 'wpsc_st' ); ?></a></td>
				</tr>

				<tr>
					<td class="b b_approved"><a href="index.php?page=wpsc-purchase-logs&status=3"><span class="approved-count"><?php echo $sales_approved; ?></span></a></td>
					<td class="last t"><a class='approved' href="index.php?page=wpsc-purchase-logs&status=3"><?php _e( 'Approved', 'wpsc_st' ); ?></a></td>
				</tr>

				<tr>
					<td class="b b-waiting"><a href="index.php?page=wpsc-purchase-logs&status=2"><span class="pending-count"><?php echo $sales_pending; ?></span></a></td>
					<td class="last t"><a class='waiting' href="index.php?page=wpsc-purchase-logs&status=2"><?php _e( 'Pending', 'wpsc_st' ); ?></a></td>
				</tr>

				<tr>
					<td class="b b-spam"><a href="index.php?page=wpsc-purchase-logs&status=5"><span class='spam-count'><?php echo $sales_declined; ?></span></a></td>
					<td class="last t"><a class='spam' href="index.php?page=wpsc-purchase-logs&status=6"><?php _e( 'Declined', 'wpsc_st' ); ?></a></td>
				</tr>

		<?php if( isset( $sales_refunded ) ) { ?>
				<tr>
					<td class="b b-comments"><a href="index.php?page=wpsc-purchase-logs&status=6"><span class='spam-count'><?php echo $sales_refunded; ?></span></a></td>
					<td class="last t"><a class='spam' href="index.php?page=wpsc-purchase-logs&status=7"><?php _e( 'Refunded', 'wpsc_st' ); ?></a></td>
				</tr>

		<?php } ?>
			</table>
		</div>
		<!-- .table-content -->

		<div class="versions">
			<span id='wp-version-message'><?php printf( __( 'You are using %s.', 'wpsc_gold_cart' ), '<span class="b">WP eCommerce ' . WPSC_VERSION . '</span>' ); ?></span>
			<br class="clear" />
		</div>
		<!-- .versions -->
		<?php
	}

	/* End of: WordPress Administration */

}

// Scribu function to find proper plugin url
function wpsc_gc_get_plugin_url() {

	// WP < 2.6
	if ( function_exists( 'plugins_url' ) )
		return plugins_url( plugin_basename( WPSC_GOLD_FILE_PATH ) );
	else
		return get_option( 'siteurl' ) . '/wp-content/plugins/' . plugin_basename( WPSC_GOLD_FILE_PATH );

}

// List Directory
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

// XML Maker
function wpsc_gc_shpcrt_xmlmaker(){

	global $wpdb;

	$keyword = $_POST['value'];
	header( "Content-type: text/xml" );
	$siteurl = get_option( 'siteurl' );
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

// Add Gold Cart Gateways
function wpsc_gc_shpcrt_add_gateways( $nzshpcrt_gateways ) {

	$num = count( $nzshpcrt_gateways ) + 1;
	$gold_gateway_directory = WPSC_GOLD_FILE_PATH . '/merchants/';
	$gold_nzshpcrt_merchant_list = wpsc_gc_list_dir( $gold_gateway_directory );
	foreach ( (array)$gold_nzshpcrt_merchant_list as $gold_nzshpcrt_merchant ) {
		if ( ! is_dir( $gold_gateway_directory.$gold_nzshpcrt_merchant ) ) {
			include_once( $gold_gateway_directory.$gold_nzshpcrt_merchant );
		}
		
		$num++;
	}
	
	return $nzshpcrt_gateways;
}
?>