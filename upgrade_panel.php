<?php
/**
 * Activate Gold Cart plugin
 */
function wpsc_activate_gold_module() {
	if ( isset( $_POST['activate_gold_module'] ) && $_POST['activate_gold_module'] == 'true' ) {
		if ( $_POST['activation_name'] != null ) {
			update_option( 'activation_name', $_POST['activation_name'] );
		}

		if ( isset( $_POST['activation_key'] ) ){
			update_option( 'activation_key', $_POST['activation_key'] );
		}
		
		//Action to perform
		if (isset($_POST['submit_values'])) {
			//register api
			$action = 'register';
		} 
		if (isset($_POST['reset_values'])) {
			//reset api
			$action = 'reset';
			update_option( 'activation_key', '');
		}

		$useragent = 'WP e-Commerce plugin';
		$activation_name = urlencode( $_POST['activation_name'] );
		$activation_key = urlencode( $_POST['activation_key'] );
		$siteurl = urlencode( get_option( 'siteurl' ) );
		$request = '';
		$http_request  = "GET /wp-goldcart-api/api_register.php?name=$activation_name&key=$activation_key&url=$siteurl&action=$action HTTP/1.0\r\n";
		$http_request .= "Host: getshopped.org\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=".get_option( 'blog_charset' )."\r\n";
		$http_request .= "Content-Length: ".strlen( $request )."\r\n";
		$http_request .= "User-Agent: $useragent\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';

		
		//Added cURL activation option. 31.08.2013
		//Try cURL first
		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => "getshopped.org/wp-goldcart-api/api_register.php?name=$activation_name&key=$activation_key&url=$siteurl&action=$action",
				CURLOPT_USERAGENT => $useragent,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false
			));			
			$returned_value = curl_exec($ch);
			curl_close($ch);
		} else {
			$fs = @fsockopen( 'getshopped.org',80,$errno,$errstr,10 );
			fwrite( $fs,$http_request );
			while ( !feof( $fs ) ){
				$response .= fgets( $fs,1160 ); // One TCP-IP packet
			}
			fclose( $fs );
			$response = explode( "\r\n\r\n",$response,2 );
			$returned_value = (int)trim( $response[1] );
		}
		//$returned_value = 1;
		if ( $returned_value === 'MQ==' ) {
			if( get_option( 'activation_state' ) != 'true' ) {
				update_option( 'activation_state','true' );
				gold_shpcrt_install();
			}
			?>
				<div class="updated" style="min-width:45%; max-width:463px;">
					<p>
						<?php _e( 'Thanks! The Gold Cart upgrade has been activated.', 'wpsc_gold_cart' ); ?><br />
						<?php printf( __( 'New options have been added to %s, and your payment gateway list has been extended.', 'wpsc_gold_cart' ), sprintf( '<a href="options-general.php?page=wpsc-settings&tab=presentation">%s</a>', __( 'Settings -> Presentation', 'wpsc_gold_cart' ) ) ); ?>
					</p>
				</div>
			<?php
		} else if  ( $returned_value === 'Mg==' ){
			//Reset API
			update_option( 'activation_state',"false" );
			echo '<div class="error"><p>Gold Cart upgrade has been deactivated!</p></div>';
		} else {
			echo '<div class="error"><p>'.$returned_value.'</p></div>';
		}
	}
}
add_action( 'wpsc_gold_module_activation','wpsc_activate_gold_module' );

/**
 * Registration Form
 */
function wpsc_gold_activation_form() {
	?>
	<div class="postbox">
		<h3 class="hndle"><?php _e( 'Gold Cart Registration', 'wpsc_gold_cart' );?></h3>
		<?php if ( get_option( 'activation_state' ) == 'true' ) { ?>
		<p>
			<?php _e( 'Gold Cart is currently registered.', 'wpsc_gold_cart' ); ?>
			<img align="middle" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/tick.png" alt="" />
		</p>
		<?php	} else { ?>
		<p>
			<img align="middle" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" alt="" />
			<?php _e( 'Gold Cart is currently not registered.', 'wpsc_gold_cart' ); ?>
		</p>
		<?php } // End Registration state ?>
		<p>
			<label for="activation_name"><?php _e( 'Name ', 'wpsc_gold_cart' ); ?>:</label>
			<input type="text" id="activation_name" name="activation_name" size="48" value="<?php echo get_option( 'activation_name' ); ?>" class="text" />
		</p>
		<p>
			<label for="activation_key"><?php _e( 'API Key ', 'wpsc_gold_cart' ); ?>:</label>
			<input type="text" id="activation_key" name="activation_key" size="48" value="<?php echo get_option( 'activation_key' ); ?>" class="text" />
		</p>
		<p>
			<input type="hidden" value="true" name="activate_gold_module" />
			<input type="submit" class="button-primary" value="<?php _e( 'Submit', 'wpsc_gold_cart' ); ?>" name="submit_values" />
			<input type="submit" class="button" value="<?php _e( 'Reset API Key', 'wpsc_gold_cart' ); ?>" name="reset_values" />
		</p>
		<?php if ( ! function_exists( 'curl_init' ) ) : ?>
			<p style='color: red; font-size:8pt; line-height:10pt;'>
				<?php _e( 'In order to register your API information your server requires cURL which is not installed on this server. We will attempt to use fsockopen as an alternate method to register Gold Cart. ', 'wpsc_gold_cart' ); ?>
			</p>
		<?php endif; ?>
		<?php if ( ! function_exists( 'fsockopen' ) && ! function_exists( 'curl_init' )) : ?>
			<p style='color: red; font-size:8pt; line-height:10pt;'>
				<?php _e( 'In order to register your API information your server requires cURL or the fscockopen extension which are not installed on this server, you may need to contact your web hosting provider to get them set up. ', 'wpsc_gold_cart' ); ?>
			</p>
		<?php endif; ?>	
		<?php
		if ( get_option( 'activation_state' ) == "true" ) {
			echo '<p><strong>'.__( 'Click <a href="http://docs.getshopped.org/category/extending-your-store/premium-plugins/gold-cart/" target="_blank">here</a> to learn how to use each Gold Cart feature.', 'wpsc_gold_cart' ).'</strong></p>';
		} ?>
	</div>
	<?php
}
add_action( 'wpsc_gold_module_activation_forms','wpsc_gold_activation_form' );
?>