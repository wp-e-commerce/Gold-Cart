<?php
function wpsc_activate_gold_module() {
	if(isset($_POST['activate_gold_module']) && $_POST['activate_gold_module'] == 'true' ) {
		if( $_POST['activation_name'] != null )
			update_option( 'activation_name',$_POST['activation_name'] );
		if( isset( $_POST['activation_key'] ) )
			update_option( 'activation_key',$_POST['activation_key'] );
		$target = "http://instinct.co.nz/wp-goldcart-api/api_register.php?name=".$_POST['activation_name']."&key=".$_POST['activation_key']."&url=".get_option( 'siteurl' )."";
		$remote_access_fail = false;
		$useragent = 'WP e-Commerce plugin';
		$activation_name = urlencode( $_POST['activation_name'] );
		$activation_key = urlencode( $_POST['activation_key'] );
		$siteurl = urlencode( get_option( 'siteurl' ) );
		$request = '';
		$http_request  = "GET /wp-goldcart-api/api_register.php?name=$activation_name&key=$activation_key&url=$siteurl HTTP/1.0\r\n";
		$http_request .= "Host: instinct.co.nz\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=".get_option( 'blog_charset' )."\r\n";
		$http_request .= "Content-Length: ".strlen( $request )."\r\n";
		$http_request .= "User-Agent: $useragent\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';
		if( false != ( $fs = @fsockopen( 'instinct.co.nz',80,$errno,$errstr,10 ) ) ) {
			fwrite( $fs,$http_request );
			while ( !feof( $fs ) )
				$response .= fgets( $fs,1160 ); // One TCP-IP packet
			fclose( $fs );
			$response = explode( "\r\n\r\n",$response,2 );
			$returned_value = (int)trim( $response[1] );
			// $returned_value = 1;    
			if( $returned_value == 1 ) {
				if( get_option( 'activation_state' ) != 'true' ) {
					update_option( 'activation_state','true' );
					gold_shpcrt_install();
				}
				?>
<div class="updated" style="min-width:45%; max-width:463px;">
	<p>
		<?php _e( 'Thanks! The Gold Cart upgrade has been activated.', 'wpsc'); ?><br />
		<?php printf( __( 'New options have been added to %s, and your payment gateway list has been extended.', 'wpsc'), sprintf( '<a href="options-general.php?page=wpsc-settings&tab=presentation">%s</a>', __( 'Settings -> Presentation', 'wpsc' ) ) ); ?>
	</p>
</div>
				<?php
			} else {
				update_option( 'activation_state',"false" );
				echo '<div class="error"><p>'.__("Sorry, the API key was incorrect.","wpsc").'</p></div>';
			}
		}
	}
}
add_action( 'wpsc_gold_module_activation','wpsc_activate_gold_module' );

function wpsc_gold_activation_form() { ?>
<div class="postbox">
	<h3 class="hndle"><?php _e('Gold Cart Activation','wpsc');?></h3>
<?php if( get_option( 'activation_state' ) == 'true' ) { ?>
	<p>
		<img align="middle" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/tick.png" alt="" />
		The gold cart is currently activated.
	</p>
<?php	} else { ?>
	<p>
		<img align="middle" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" alt="" />
		The gold cart is currently deactivated.
	</p>
<?php } ?>
	<p>
		<label for="activation_name"><?php _e('Name ','wpsc'); ?>:</label>
		<input type="text" id="activation_name" name="activation_name" size="48" value="<?php echo get_option( 'activation_name' ); ?>" class="text" />
	</p>
	<p>
		<label for="activation_key"><?php _e('API Key ','wpsc'); ?>:</label>
		<input type="text" id="activation_key" name="activation_key" size="48" value="<?php echo get_option( 'activation_key' ); ?>" class="text" />
	</p>
	<p>
		<input type="hidden" value="true" name="activate_gold_module" />
		<input type="submit" class="button-primary" value="<?php _e('Submit ','wpsc'); ?>" name="submit_values" />
		<input type="submit" class="button" value="<?php _e('Reset API Key','wpsc'); ?>" name="reset_values" onclick="document.getElementById('activation_key').value=''" />
	</p>
<?php
	if( get_option( 'activation_state' ) == "true" ) {
		echo '<p><strong>'.__( 'Click <a href="http://getshopped.org/resources/docs/installation/gold-files/#usage" target="_blank">here</a> to learn how to use each Gold Cart feature.','wpsc' ).'</strong></p>';
	} ?>
</div>
<?php
}
add_action( 'wpsc_gold_module_activation_forms','wpsc_gold_activation_form' );
?>