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
		$activation_status = get_option( 'activation_state', 'false' );
		$premium_upgrades = false;
		if( $activation_status  != 'true' )
			$premium_upgrades = add_query_arg( 'page', 'wpsc-upgrades', 'index.php' );
		$changelog = wpsc_gc_get_changelog();
		$release_notes = 'http://www.getshopped.org/blog/'; ?>
<div class="wrap about-wrap">

	<h1><?php printf( __( 'Welcome to Gold Cart %s', 'wpsc_gc' ), $major_version ); ?></h1>
	<div class="about-text gc-about-text">
<?php if( $activation_status == 'true' ) { ?>
		<p><?php printf( __( '<strong>Thanks for updating!</strong> Gold Cart %s opens up new functionality within your WP e-Commerce store. We hope you enjoy it.', 'wpsc_gc' ), $major_version ); ?></p>
<?php } else { ?>
		<p><?php printf( __( '<strong>Thanks for installing</strong>! Gold Cart %s opens up new functionality within your WP e-Commerce store. We hope you enjoy it.', 'wpsc_gc' ), $major_version ); ?></p>
		<p><?php printf( __( 'Open up <a href="%s">Store Upgrades</a> to activate and begin using Gold Cart.', 'wpsc_gc' ), $premium_upgrades ); ?></p>
<?php } ?>
	</div>
	<div class="wp-badge wpsc-badge" style="background:url( '<?php echo wpsc_gc_get_plugin_url(); ?>/images/wpsc-badge.png' ) left top no-repeat !important;"><?php printf( __( 'Version %s', 'wpsc' ), $major_version ); ?></div>

	<div class="changelog">
		<h3><?php printf( __( 'What\'s New in %s', 'wpsc_gc' ), $major_version ); ?></h3>
<?php if( $changelog ) { ?>
		<ul class="changelog-list">
	<?php foreach( $changelog as $line ) { ?>
			<li><code><?php echo $line; ?></code></li>
	<?php } ?>
		</ul>
		<p><?php printf( __( 'For more information, see	<a href="%s" target="_blank">the release notes</a>.', 'wpsc' ), $release_notes ); ?></p>
<?php } ?>
	</div>

	<div class="changelog">
		<h3><?php _e( 'Under the Hood', 'wpsc_gc' ); ?></h3>

		<div class="feature-section col three-col">
			<div>
				<h4><?php _e( 'Payment Overhaul', 'wpsc_gc' ); ?></h4>
				<p><?php _e( '[...]', 'wpsc_gc' ); ?></p>
			</div>
			<div>
				<h4><?php _e( 'New [...]', 'wpsc_gc' ); ?></h4>
				<p><?php _e( '[...]', 'wpsc_gc' ); ?></p>
			</div>
			<div class="last-feature">
				<h4><?php _e( 'New [...]', 'wpsc_gc' ); ?></h4>
				<p><?php _e( '[...]', 'wpsc_gc' ); ?></p>
			</div>
		</div>
		<!-- .feature-section -->

		<div class="feature-section col three-col">
			<div>
				<h4><?php _e( 'New [...]', 'wpsc_gc' ); ?></h4>
				<p><?php _e( '[...]', 'wpsc_gc' ); ?></p>
			</div>
				<div>
				<h4><?php _e( 'New [...]', 'wpsc_gc' ); ?></h4>
				<p><?php _e( '[...]', 'wpsc_gc' ); ?></p>
			</div>
			<div class="last-feature">
				<h4><?php _e( 'New [...]', 'wpsc_gc' ); ?></h4>
				<p><?php _e( '[...]', 'wpsc_gc' ); ?></p>
			</div>
		</div>
		<!-- .feature-section -->

	</div>
	<!-- .changelog -->

	<div class="return-to-dashboard">
		<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpsc-settings' ), 'options-general.php' ) ) ); ?>"><?php _e( 'Go to Store Settings', 'wpsc_gc' ); ?></a>
	</div>
	<!-- .return-to-dashboard -->

</div>
<!-- .about-wrap -->
<?php
	}

	// Registration Form
	function wpsc_gc_get_activation_form() {

		$activation_state = get_option( 'activation_state', 'false' );
		$activation_name = get_option( 'activation_name', '' );
		$activation_key = get_option( 'activation_key', '' ); ?>
<div class="postbox">
	<h3 class="hndle"><?php _e( 'Gold Cart Registration', 'wpsc_gold_cart' );?></h3>
	<div class="inside">
<?php if ( $activation_state == 'true' ) { ?>
		<p>
			<img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/tick.png" alt="" class="icon16" align="middle" />
			<strong><?php _e( 'Gold Cart is currently registered.', 'wpsc_gold_cart' ); ?></strong>
		</p>
<?php	} else { ?>
		<p>
			<img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" alt="" class="icon16" align="middle" />
			<strong><?php _e( 'Gold Cart is currently not registered.', 'wpsc_gold_cart' ); ?></strong>
		</p>
<?php } ?>
		<p>
			<label for="activation_name"><?php _e( 'Name ', 'wpsc_gold_cart' ); ?>:</label>
			<input type="text" id="activation_name" name="activation_name" size="48" value="<?php echo $activation_name; ?>" class="text" />
		</p>
		<p>
			<label for="activation_key"><?php _e( 'API Key ', 'wpsc_gold_cart' ); ?>:</label>
			<input type="text" id="activation_key" name="activation_key" size="48" value="<?php echo $activation_key; ?>" class="text" />
		</p>
		<p>
			<input type="hidden" value="true" name="activate_gold_module" />
			<input type="submit" class="button-primary" value="<?php _e( 'Activate Gold Cart', 'wpsc_gold_cart' ); ?>" name="submit_values" />
			<input type="submit" class="button" value="<?php _e( 'Reset API Key', 'wpsc_gold_cart' ); ?>" name="reset_values" />
		</p>
<?php if( !function_exists( 'curl_init' ) ) : ?>
		<p style="color: red; font-size:8pt; line-height:10pt;">
			<?php _e( 'In order to register your API information your server requires cURL which is not installed on this server. We will attempt to use fsockopen as an alternate method to register Gold Cart. ', 'wpsc' ); ?>
		</p>
<?php endif; ?>
<?php if( !function_exists( 'fsockopen' ) && !function_exists( 'curl_init' )) : ?>
		<p style="color: red; font-size:8pt; line-height:10pt;">
			<?php _e( 'In order to register your API information your server requires cURL or the fscockopen extension which are not installed on this server, you may need to contact your web hosting provider to get them set up. ', 'wpsc' ); ?>
		</p>
<?php endif; ?>	
<?php if( $activation_state == 'true' ) { ?>
		<p><?php printf( __( 'Click <a href="%s" target="_blank">here</a> to learn how to use each Gold Cart feature.', 'wpsc_gold_cart' ), 'http://docs.getshopped.org/category/extending-your-store/premium-plugins/gold-cart/' ); ?></p>
<?php } ?>
	</div>
	<!-- .inside -->
</div>
<?php

	}

	function wpsc_gc_get_premium_support_form() {

		$activation_state = get_option( 'activation_state', 'false' );
		if( $activation_state == 'true' ) {
			$activation_key = get_option( 'activation_key', '' );
			$current_user = wp_get_current_user();
			if( $current_user ) {
				if( !empty( $current_user->first_name ) && !empty( $current_user->last_name ) )
					$current_user->display_name = sprintf( '%s %s', $current_user->first_name, $current_user->last_name );
				$enquiry = false;
			}
			$is_sent = false;
			if( isset( $_GET['sent'] ) )
				$is_sent = $_GET['sent'];
			if( $is_sent ) {
				if( $is_sent == 1 ) {
					$note = 'Thank you for your enquiry, we can confirm that we have received it and we will respond to the provided e-mail address - michael@visser.com.au - shortly. :)' . "\n\n" . 
					'Ticket ID: 12345-12345' . "\n" . 
					'Assigned to: Unassigned (Pending assign)';
				} else {
					$contact_person = $_POST['contact_person'];
					$contact_email = $_POST['contact_email'];
					$enquiry = $_POST['enquiry'];
				}
			} else {
				$action = false;
				if( isset( $_POST['action'] ) )
					$action = $_POST['action'];
				$failed_validation = false;
				if( $action == 'wpsc_gc-support' )
					$failed_validation = true;
				if( isset( $_POST['contact_person'] ) )
					$contact_person = stripslashes( $_POST['contact_person'] );
				if( isset( $_POST['contact_email'] ) )
					$contact_email = stripslashes( $_POST['contact_email'] );
				if( isset( $_POST['enquiry'] ) )
					$enquiry = stripslashes( $_POST['enquiry'] );
			}
		} ?>
<?php if( $activation_state == 'true' ) { ?>
	<?php if( $is_sent ) { ?>
<div class="updated settings-error">
	<p><?php _e( 'Your enquiry has been sent to the GetShopped.org priority support team.', 'wpsc_gold_cart' ); ?></p>
</div>
<!-- .updated -->
<p><?php _e( 'Please find your confirmation note from GetShopped.org included below.', 'wpsc_gold_cart' ); ?></p>
<div class="textarea-wrap">
	<textarea id="note" name="note" rows="10" cols="30" class="mceEditor"><?php echo $note; ?></textarea>
</div>
<!-- .textarea-wrap -->
<div style="margin:0.5em 0 0;">
	<a href="<?php echo add_query_arg( 'sent', null ); ?>" class="button-primary"><?php _e( 'Open a new ticket', 'wpsc_gold_cart' ); ?></a>
</div>
	<?php } else { ?>
		<?php if( $failed_validation ) { ?>
<div style="margin:0.5em 0 0;">
	<strong><?php _e( 'There were missing fields supplied. Please complete required fields and re-send your ticket.', 'wpsc_gold_cart' ); ?></strong>
</div>
		<?php } ?>
<p><?php _e( 'Need help with your WP e-Commerce store? Open a Premium&nbsp;Support ticket with the GetShopped.org team for priority assistance.', 'wpsc_gold_cart' ); ?></p>
<form method="post" action="">
	<div class="input-text-wrap">
		<label for="contact_person"><?php _e( 'Contact Person', 'wpsc_gold_cart' ); ?></label>
		<input type="text" id="contact_person" name="contact_person" placeholder="<?php echo $current_user->display_name; ?>" value="<?php echo $contact_person; ?>" class="prompt" />
	</div>
	<!-- .input-text-wrap -->
	<div class="input-text-wrap">
		<label for="contact_email"><?php _e( 'Contact E-mail', 'wpsc_gold_cart' ); ?></label>
		<input type="text" name="contact_email" placeholder="<?php echo $current_user->user_email; ?>" value="<?php echo $contact_email; ?>" />
	</div>
	<!-- .input-text-wrap -->
	<div class="textarea-wrap">
		<label for="enquiry"><?php _e( 'Enquiry', 'wpsc_gold_cart' ); ?></label>
		<textarea id="enquiry" name="enquiry" rows="5" cols="30" class="mceEditor" placeholder="<?php _e( 'How do I set up flat rate shipping rules?', 'wpsc_gold_cart' ); ?>"><?php echo $enquiry; ?></textarea>
	</div>
	<!-- .textarea-wrap -->
	<p class="submit">
		<span class="publishing-action">
			<input type="submit" value="<?php _e( 'Send', 'wpsc_gold_cart' ); ?>" class="button-primary" />
		</span>
		<input type="reset" value="<?php _e( 'Reset', 'wpsc_gold_cart' ); ?>" class="button" />
		<br class="clear" />
	</p>
	<!-- .submit -->
	<input type="hidden" name="key" value="<?php echo $activation_key; ?>" />
	<input type="hidden" name="action" value="wpsc_gc-support" />
</form>
	<?php } ?>
<?php } else { ?>
<p><strong><?php _e( 'You need to activate your Gold Cart installation.', 'wpsc_gold_cart' ); ?></strong></p>
<p><?php printf( __( 'To open Premium Support tickets with the GetShopped.org priority team activate Gold Cart from the <a href="%s">Store Upgrades screen</a>.', 'wpsc_gold_cart' ), add_query_arg( 'page', 'wpsc-upgrades', 'admin.php' ) ); ?></p>
<?php } ?>
<?php

	}

	function wpsc_gc_validate_support_ticket( $args = array() ) {

		if( !empty( $args['key'] ) && !empty( $args['person'] ) && !empty( $args['email'] ) && !empty( $args['enquiry'] ) )
			return true;

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
	<span id='wp-version-message'><?php printf( __( 'You are using %s.', 'wpsc_gold_cart' ), '<span class="b">WP e-Commerce ' . WPSC_VERSION . '</span>' ); ?></span>
	<br class="clear" />
</div>
<!-- .versions -->
<?php

	}

	/* End of: WordPress Administration */

}

// Take over the update check
function wpsc_gc_check_for_plugin_update( $checked_data ) {

	//Comment out these two lines during testing.
	if( empty( $checked_data->checked ) )
		return $checked_data;

	$request_args = array(
		'slug' => WPSC_GOLD_SLUG,
		'version' => $checked_data->checked[WPSC_GOLD_DIR_NAME .'/'. WPSC_GOLD_FILENAME],
	);

	$request_string = wpsc_gc_prepare_request( 'basic_check', $request_args );
	
	// Start checking for an update
	$raw_response = wp_remote_post( WPSC_GOLD_UPDATER, $request_string );
	
	if( !is_wp_error( $raw_response ) && ( $raw_response['response']['code'] == 200 ) )
		$response = unserialize($raw_response['body']);
	if( is_object( $response ) && !empty( $response ) ) // Feed the update data into WP updater
		$checked_data->response[WPSC_GOLD_DIR_NAME .'/'. WPSC_GOLD_FILENAME] = $response;
	
	return $checked_data;

}
add_filter( 'pre_set_site_transient_update_plugins', 'wpsc_gc_check_for_plugin_update' );

// Take over the Plugin info screen
function wpsc_gc_plugin_update_info( $def, $action, $args ) {

	if( !isset( $args->slug ) || ( $args->slug != WPSC_GOLD_SLUG ) )
		return false;
	
	// Get the current version
	$plugin_info = get_site_transient( 'update_plugins' );
	$current_version = $plugin_info->checked[WPSC_GOLD_DIR_NAME .'/'. WPSC_GOLD_FILENAME];
	$args->version = $current_version;
	
	$request_string = wpsc_gc_prepare_request( $action, $args );
	
	$request = wp_remote_post( WPSC_GOLD_UPDATER, $request_string );
	
	if ( is_wp_error( $request ) ) {
		$res = new WP_Error( 'plugins_api_failed', __( 'An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>' ), $request->get_error_message() );
	} else {
		$res = unserialize( $request['body'] );
		if( $res === false )
			$res = new WP_Error( 'plugins_api_failed', __( 'An unknown error occurred' ), $request['body'] );
	}
	return $res;

}
add_filter( 'plugins_api', 'wpsc_gc_plugin_update_info', 10, 3 );

function wpsc_gc_prepare_request( $action, $request_args ) {

	global $wp_version;

	$args = array(
		'body' => array(
			'action' => $action, 
			'request' => serialize( $request_args ),
			'api-key' => md5( get_bloginfo( 'url' ) )
		),
		'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
	);
	return $args;

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
			if ( $product['image'] != "" )
				echo "&lt;img src='".WPSC_IMAGE_URL.$product['image']."' width='35' height='35' /&gt;\n\r";
			else
				echo "&lt;img src='./wp-content/plugins/".WPSC_DIR_NAME."/no-image-uploaded.gif' width='35' height='35'/&gt;\n\r";
			echo "&lt;/td&gt;\n\r";
			echo "&lt;td width='5px' rowspan='2'&gt;\n\r";
			echo "&lt;/td&gt;\n\r";

			echo "&lt;td align='left'&gt;\n\r";
			echo "&lt;strong&gt;".$product['name']."&lt;/strong&gt;\n\r";
			echo "&lt;/td&gt;\n\r";
			echo "&lt;tr&gt;\n\r";
			echo "&lt;td&gt;\n\r";
			if ( strlen($product['description'] ) > 34 )
				$product['description'] = substr($product['description'],0,33)."...";
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

	global $gateway_checkout_form_fields;

	$num = count( $nzshpcrt_gateways ) + 1;
	$gold_gateway_directory = WPSC_GOLD_FILE_PATH . '/merchants/';
	$gold_nzshpcrt_merchant_list = wpsc_gc_list_dir( $gold_gateway_directory );
	if( (array)$gold_nzshpcrt_merchant_list ) {
		foreach ( (array)$gold_nzshpcrt_merchant_list as $gold_nzshpcrt_merchant ) {
			if ( ! is_dir( $gold_gateway_directory.$gold_nzshpcrt_merchant ) )
				include_once( $gold_gateway_directory.$gold_nzshpcrt_merchant );
		}
		$num++;
	}
	return $nzshpcrt_gateways;

}
?>